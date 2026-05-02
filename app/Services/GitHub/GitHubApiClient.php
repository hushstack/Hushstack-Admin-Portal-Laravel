<?php

namespace App\Services\GitHub;

use App\Exceptions\GitHub\GitHubApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubApiClient
{
    public function repositories(int $page, int $perPage): array
    {
        return $this->getWithPagination('/user/repos', [
            'visibility' => 'all',
            'affiliation' => 'owner,collaborator,organization_member',
            'sort' => 'updated',
            'direction' => 'desc',
            'page' => $page,
            'per_page' => min(max($perPage, 1), 100),
        ]);
    }

    public function repository(string $owner, string $repo): array
    {
        return $this->get("/repos/{$owner}/{$repo}");
    }

    public function repositoryById(int $repositoryId): array
    {
        return $this->get("/repositories/{$repositoryId}");
    }

    public function contributors(string $owner, string $repo, int $perPage = 30): array
    {
        return $this->get("/repos/{$owner}/{$repo}/contributors", [
            'per_page' => min(max($perPage, 1), 100),
            'page' => 1,
        ]);
    }

    public function user(string $username): array
    {
        return $this->get('/users/'.rawurlencode($username));
    }

    public function branches(string $owner, string $repo, int $page = 1, int $perPage = 30): array
    {
        return $this->getWithPagination("/repos/{$owner}/{$repo}/branches", [
            'page' => $page,
            'per_page' => min(max($perPage, 1), 100),
        ]);
    }

    public function branch(string $owner, string $repo, string $branch): array
    {
        return $this->get("/repos/{$owner}/{$repo}/branches/".rawurlencode($branch));
    }

    public function commits(
        string $owner,
        string $repo,
        ?string $branch,
        int $page,
        int $perPage,
        array $filters = []
    ): array
    {
        $query = array_filter([
            'sha' => $branch,
            'per_page' => min(max($perPage, 1), 100),
            'page' => $page,
            'author' => $filters['author_username'] ?? null,
            'since' => $filters['date_from'] ?? null,
            'until' => $filters['date_to'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return $this->getWithPagination("/repos/{$owner}/{$repo}/commits", $query);
    }

    public function commit(string $owner, string $repo, string $sha): array
    {
        return $this->get("/repos/{$owner}/{$repo}/commits/{$sha}");
    }

    public function events(string $owner, string $repo): array
    {
        return $this->get("/repos/{$owner}/{$repo}/events", [
            'per_page' => 30,
            'page' => 1,
        ]);
    }

    private function get(string $path, array $query = []): array
    {
        return $this->request($path, $query)->json() ?? [];
    }

    private function getWithPagination(string $path, array $query = []): array
    {
        $response = $this->request($path, $query);

        return [
            'data' => $response->json() ?? [],
            'pagination' => $this->paginationFromLinkHeader($response->header('Link'), (int) ($query['page'] ?? 1), (int) ($query['per_page'] ?? 30)),
        ];
    }

    private function request(string $path, array $query = []): Response
    {
        $token = config('services.github.token');

        $request = Http::baseUrl('https://api.github.com')
            ->accept('application/vnd.github+json')
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->timeout(15)
            ->retry(2, 250);

        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $request->get($path, $query);

        if ($response->failed()) {
            $this->throwGitHubException($response);
        }

        return $response;
    }

    private function throwGitHubException(Response $response): void
    {
        $payload = $response->json() ?? [];
        $message = $payload['message'] ?? 'GitHub request failed.';
        $status = match ($response->status()) {
            401 => 401,
            403 => 403,
            404 => 404,
            422 => 422,
            default => $response->serverError() ? 502 : 400,
        };

        throw new GitHubApiException($message, $status, [
            'limit' => $response->header('X-RateLimit-Limit'),
            'remaining' => $response->header('X-RateLimit-Remaining'),
            'reset' => $response->header('X-RateLimit-Reset'),
        ]);
    }

    private function paginationFromLinkHeader(?string $linkHeader, int $page, int $perPage): array
    {
        $lastPage = null;

        if ($linkHeader) {
            foreach (explode(',', $linkHeader) as $link) {
                if (! str_contains($link, 'rel="last"')) {
                    continue;
                }

                if (preg_match('/[?&]page=(\d+)/', $link, $matches)) {
                    $lastPage = (int) $matches[1];
                }
            }
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }
}
