# Feature Plan: GitHub Repository Data API (2026-04-29)

## Overview

Plan an internal Laravel API for reading GitHub repository data such as repository metadata, branches, commits, tags, contributors, pull requests, issues, releases, and languages.

The Laravel backend should act as a controlled integration layer between the admin portal and GitHub. The frontend should call our API, not GitHub directly, so tokens, rate limits, pagination, caching, and data normalization stay server-side.

## Goal

Provide admin-facing endpoints that can fetch and expose GitHub repository information in a predictable format.

Primary use cases:
- View repository metadata.
- List branches.
- List commits.
- List commits per branch.
- List tags, contributors, releases, pull requests, issues, and languages.
- Support public repositories and private repositories when a valid GitHub token is configured.

## Recommended Architecture

Use a layered Laravel structure:

```text
Routes
  -> GitHubRepositoryController
      -> GitHubRepositoryService
          -> GitHubApiClient
              -> GitHub REST API
```

Suggested responsibilities:

- Controller: HTTP validation, request/response shape, authorization.
- Service: business flow, aggregation, deduplication, caching decisions.
- API client: authenticated GitHub requests, pagination, error mapping, rate-limit handling.
- Jobs: background sync for large repositories.
- Database/cache: optional local storage for expensive or frequently viewed data.

## Suggested Internal API Endpoints

```text
GET /api/github/repos/{owner}/{repo}
GET /api/github/repos/{owner}/{repo}/branches
GET /api/github/repos/{owner}/{repo}/commits
GET /api/github/repos/{owner}/{repo}/commits-by-branch
GET /api/github/repos/{owner}/{repo}/tags
GET /api/github/repos/{owner}/{repo}/contributors
GET /api/github/repos/{owner}/{repo}/pull-requests
GET /api/github/repos/{owner}/{repo}/issues
GET /api/github/repos/{owner}/{repo}/releases
GET /api/github/repos/{owner}/{repo}/languages
POST /api/github/repos/{owner}/{repo}/sync
GET /api/github/repos/{owner}/{repo}/sync-status
```

For small repositories, live reads can be acceptable. For large repositories, prefer `POST /sync` to fetch data in the background, then serve stored results from the database.

## GitHub REST API Endpoints

Base URL:

```text
https://api.github.com
```

Useful endpoints:

```text
GET /repos/{owner}/{repo}
GET /repos/{owner}/{repo}/branches
GET /repos/{owner}/{repo}/commits
GET /repos/{owner}/{repo}/tags
GET /repos/{owner}/{repo}/contributors
GET /repos/{owner}/{repo}/pulls
GET /repos/{owner}/{repo}/issues
GET /repos/{owner}/{repo}/releases
GET /repos/{owner}/{repo}/languages
```

Recommended headers:

```text
Accept: application/vnd.github+json
Authorization: Bearer {GITHUB_TOKEN}
X-GitHub-Api-Version: 2022-11-28
```

The token is optional for public repositories, but strongly recommended for production.

## Authentication Plan

Recommended options:

1. Fine-grained Personal Access Token
   - Best for internal admin tooling.
   - Easier to configure.
   - Can be limited to selected repositories.

2. GitHub App
   - Better if the platform will connect many GitHub users or organizations.
   - Better long-term permission model.
   - More setup work.

Store tokens securely:

```text
GITHUB_TOKEN=...
```

Never expose GitHub tokens to the frontend.

## Pagination Plan

GitHub list endpoints are paginated. The integration should:

- Request `per_page=100` when supported.
- Read the response `Link` header.
- Continue fetching while a `rel="next"` link exists.
- Return paginated internal responses to the frontend unless a full sync is explicitly requested.

Avoid loading very large repositories into memory in one request. For full history sync, fetch page by page inside a queued job.

## Commits Across All Branches

There is an important detail for commits: `GET /repos/{owner}/{repo}/commits` usually follows the default branch unless a `sha` query parameter is provided.

To collect commits from all branches:

1. Fetch all branches:

```text
GET /repos/{owner}/{repo}/branches
```

2. For each branch, fetch commits using the branch name:

```text
GET /repos/{owner}/{repo}/commits?sha={branch_name}
```

3. Deduplicate commits by SHA because the same commit can exist on multiple branches.

Suggested normalized shape:

```json
{
  "sha": "commit_sha",
  "message": "Commit message",
  "author_name": "Author Name",
  "author_email": "author@example.com",
  "authored_at": "2026-04-29T00:00:00Z",
  "committer_name": "Committer Name",
  "committed_at": "2026-04-29T00:00:00Z",
  "branches": ["main", "develop"],
  "html_url": "https://github.com/owner/repo/commit/commit_sha"
}
```

## Sync Strategy

Recommended flow for large or repeated usage:

1. Admin requests repository sync.
2. Backend creates a sync record with status `queued`.
3. Laravel queue job fetches repository data from GitHub.
4. Job stores normalized data in local database tables or cache.
5. Frontend polls sync status.
6. Frontend reads stored data through normal API endpoints.

Suggested statuses:

```text
queued
running
completed
failed
partially_completed
```

Suggested data to store:

- Repository metadata.
- Branches.
- Commits.
- Commit-branch mapping.
- Tags.
- Contributors.
- Pull requests.
- Issues.
- Releases.
- Languages.
- Last synced timestamp.
- Last sync error, if any.

## Rate Limit Handling

The API client should inspect GitHub response headers such as:

```text
X-RateLimit-Limit
X-RateLimit-Remaining
X-RateLimit-Reset
```

If rate limit is low or exhausted:

- Stop or pause background sync.
- Mark sync as `partially_completed` or `failed`.
- Return a clear API error to the frontend.
- Retry later based on `X-RateLimit-Reset`.

## Caching Plan

Suggested cache durations:

- Repository metadata: 5-15 minutes.
- Branches/tags/languages: 5-15 minutes.
- Contributors/releases: 15-60 minutes.
- Commit lists: prefer database sync for large repositories.

Cache keys should include owner, repo, endpoint, branch, page, and token/account context if private repositories are supported.

## Error Handling

Map GitHub errors into consistent internal errors:

| GitHub Response | Internal Meaning |
| --- | --- |
| `401` | Invalid or missing GitHub token |
| `403` | Forbidden, permission issue, or rate limit |
| `404` | Repository not found or token cannot access it |
| `422` | Invalid request parameters |
| `429`/rate headers | Rate limit pressure |
| `5xx` | GitHub unavailable or temporary failure |

## Security Notes

- Require admin authorization for these endpoints.
- Validate `owner` and `repo` path parameters.
- Do not log tokens.
- Do not return raw token-related GitHub errors to the frontend.
- Encrypt any user-provided GitHub tokens stored in the database.
- Use read-only GitHub permissions unless write operations are later required.

## Implementation Phases

### Phase 1: Live Read API

- Add GitHub API client.
- Add repository metadata endpoint.
- Add branches endpoint.
- Add commits endpoint for default branch.
- Add pagination helper.
- Add basic error handling.

### Phase 2: Expanded Repository Data

- Add tags, contributors, pull requests, issues, releases, and languages.
- Add commit fetching by branch.
- Add commit deduplication by SHA.

### Phase 3: Background Sync

- Add sync endpoint.
- Add queue job.
- Add sync status endpoint.
- Store normalized repository data locally.
- Add retry/rate-limit handling.

### Phase 4: Production Hardening

- Add caching.
- Add tests for pagination and GitHub error mapping.
- Add monitoring/logging around sync failures.
- Add GitHub token management if multiple accounts are supported.

## Official References

- GitHub REST API: https://docs.github.com/rest
- Authentication: https://docs.github.com/rest/authentication/authenticating-to-the-rest-api
- Pagination: https://docs.github.com/rest/guides/using-pagination-in-the-rest-api
- Repositories: https://docs.github.com/rest/repos/repos
- Branches: https://docs.github.com/rest/branches
- Commits: https://docs.github.com/rest/commits/commits
- Pull requests: https://docs.github.com/rest/pulls/pulls
- Issues: https://docs.github.com/rest/issues
- Rate limits: https://docs.github.com/rest/using-the-rest-api/rate-limits-for-the-rest-api
