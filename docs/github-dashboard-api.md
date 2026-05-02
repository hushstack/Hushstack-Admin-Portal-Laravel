# GitHub Dashboard API

Internal admin API for the GitHub dashboard. Data is read directly from GitHub with `GITHUB_TOKEN`; GitHub repo/branch/commit data is not stored in local tables.

## Setup

Add these values to `.env`:

```env
GITHUB_TOKEN=github_pat_xxx
GITHUB_OWNER=hushstack
```

Then run:

```bash
php artisan config:clear
php artisan migrate
```

The token must have access to private repositories. For fine-grained GitHub tokens, select the target repositories and grant read access for repository metadata and contents.

## Auth

All endpoints require:

```text
Authorization: Bearer {sanctum_token}
Accept: application/json
```

Required permission:

```text
github_repositories.view
```

The repository validation endpoint also requires:

```text
github_repositories.create
```

## Dashboard Flow

```text
GitHub Action tab
  -> GET /api/admin/github/repositories
  -> choose repository id
  -> GET /api/admin/github/repositories/{repositoryId}
  -> GET /api/admin/github/repositories/{repositoryId}/branches
  -> choose branch name
  -> GET /api/admin/github/repositories/{repositoryId}/branch?branch_name=develop
  -> GET /api/admin/github/repositories/{repositoryId}/branch/commits?branch_name=develop
  -> choose commit sha
  -> GET /api/admin/github/repositories/{repositoryId}/commits/{sha}
```

## Endpoints

### List Repositories

```http
GET /api/admin/github/repositories?page=1&per_page=20
```

Response `data.data[]` item:

```json
{
  "id": 123456789,
  "owner": "hushstack",
  "name": "Hushstack-Admin-Portal-Laravel",
  "full_name": "hushstack/Hushstack-Admin-Portal-Laravel",
  "description": null,
  "html_url": "https://github.com/hushstack/Hushstack-Admin-Portal-Laravel",
  "default_branch": "develop",
  "is_private": true,
  "visibility": "private",
  "pushed_at": "29 Apr 2026 at 04:10 pm",
  "updated_at": "29 Apr 2026 at 04:10 pm"
}
```

### Repository Detail

```http
GET /api/admin/github/repositories/{repositoryId}
```

Response includes contributor list:

```json
{
  "id": 123456789,
  "owner": "hushstack",
  "name": "Hushstack-Admin-Portal-Laravel",
  "full_name": "hushstack/Hushstack-Admin-Portal-Laravel",
  "default_branch": "develop",
  "language": "PHP",
  "forks_count": 0,
  "open_issues_count": 0,
  "stargazers_count": 0,
  "contributor": [
    {
      "name": "Somonor Hong",
      "email": null,
      "profile": {
        "username": "cachewraith",
        "avatar_url": "https://avatars.githubusercontent.com/u/150505791?v=4",
        "url": "https://github.com/cachewraith"
      }
    }
  ]
}
```

Note: contributor `email` is `null` when the GitHub user does not expose a public profile email. GitHub does not expose private emails.

### List Branches

```http
GET /api/admin/github/repositories/{repositoryId}/branches?page=1&per_page=30
```

Response `data.data[]` item:

```json
{
  "name": "develop",
  "latest_commit_sha": "abc123",
  "is_protected": false,
  "commit_url": "https://github.com/hushstack/Hushstack-Admin-Portal-Laravel/commit/abc123"
}
```

### Branch Detail

```http
GET /api/admin/github/repositories/{repositoryId}/branch?branch_name=develop
```

Use `branch_name` as a query parameter because branch names can contain slashes.

### Branch Commits

```http
GET /api/admin/github/repositories/{repositoryId}/branch/commits?branch_name=develop&page=1&per_page=20
```

Optional filters:

```text
search=author-email-or-branch-name
author_username=cachewraith
date_from=2026-01-01
date_to=2026-04-29
```

Response `data.data[]` item:

```json
{
  "repository": {
    "full_name": "hushstack/Hushstack-Admin-Portal-Laravel",
    "html_url": "https://github.com/hushstack/Hushstack-Admin-Portal-Laravel"
  },
  "email": "author@example.com",
  "username": "cachewraith",
  "profile": {
    "avatar_url": "https://avatars.githubusercontent.com/u/150505791?v=4",
    "url": "https://github.com/cachewraith"
  },
  "branch_name": "develop",
  "commit_sha": "abc123",
  "html_url": "https://github.com/hushstack/Hushstack-Admin-Portal-Laravel/commit/abc123",
  "date": "29 Apr 2026 at 04:10 pm"
}
```

### Commit Detail

```http
GET /api/admin/github/repositories/{repositoryId}/commits/{sha}
```

Detail adds:

```json
{
  "commit_message": "Fix GitHub dashboard API",
  "commit_description": "Longer commit body when available.",
  "author_name": "Somonor Hong",
  "committer_name": "Somonor Hong",
  "committed_at": "29 Apr 2026 at 04:10 pm"
}
```

### Global Commits

Use this when a page already has a repository id but does not use the branch-specific route:

```http
GET /api/admin/github/commits?repo_id={repositoryId}&branch_name=develop&page=1&per_page=20
```

## Repository Validation

This does not store a repository. It only checks that `GITHUB_TOKEN` can access it.

```http
POST /api/admin/github/repositories
```

Body:

```json
{
  "full_name": "hushstack/Hushstack-Admin-Portal-Laravel"
}
```

You may also send only the repo name when `GITHUB_OWNER=hushstack` is configured:

```json
{
  "full_name": "Hushstack-Admin-Portal-Laravel"
}
```

## Pagination Shape

List responses use:

```json
{
  "data": {
    "data": [],
    "page": 1,
    "per_page": 20,
    "page_total": 20,
    "last_page": 5
  }
}
```

`last_page` can be `null` when GitHub does not send a last-page link.
