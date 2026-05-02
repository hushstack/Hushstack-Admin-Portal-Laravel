<?php

namespace App\Services\GitHub;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GitHubRepositoryService
{
    public function __construct(private readonly GitHubApiClient $client) {}

    public function repositories(array $filters): array
    {
        $response = $this->client->repositories(
            $filters['page'] ?? 1,
            $filters['per_page'] ?? 20
        );

        return $this->paginated(
            collect($response['data'])->map(fn (array $repo) => $this->repositoryData($repo))->all(),
            $response['pagination']
        );
    }

    public function repository(string $owner, string $repo): array
    {
        return $this->repositoryData($this->client->repository($owner, $repo), true);
    }

    public function repositoryById(int $repositoryId): array
    {
        $repository = $this->client->repositoryById($repositoryId);

        return $this->repositoryData($repository, true);
    }

    public function ownerAndRepoFromId(int $repositoryId): array
    {
        $repository = $this->client->repositoryById($repositoryId);

        return [
            (string) Arr::get($repository, 'owner.login'),
            (string) ($repository['name'] ?? ''),
        ];
    }

    public function validateRepository(string $fullName): array
    {
        [$owner, $repo] = $this->ownerAndRepo($fullName);

        return $this->repository($owner, $repo);
    }

    public function branches(string $owner, string $repo, array $filters): array
    {
        $response = $this->client->branches(
            $owner,
            $repo,
            $filters['page'] ?? 1,
            $filters['per_page'] ?? 30
        );

        return $this->paginated(
            collect($response['data'])->map(fn (array $branch) => $this->branchData($owner, $repo, $branch))->all(),
            $response['pagination']
        );
    }

    public function branch(string $owner, string $repo, string $branchName): array
    {
        return $this->branchData($owner, $repo, $this->client->branch($owner, $repo, $branchName), true);
    }

    public function commits(string $owner, string $repo, ?string $branchName, array $filters): array
    {
        $response = $this->client->commits(
            $owner,
            $repo,
            $branchName,
            $filters['page'] ?? 1,
            $filters['per_page'] ?? 20,
            $filters
        );

        $commits = collect($response['data'])
            ->map(fn (array $commit) => $this->commitData($owner, $repo, $commit, $branchName))
            ->filter(fn (array $commit) => $this->matchesSearch($commit, $filters['search'] ?? null))
            ->values()
            ->all();

        return $this->paginated($commits, $response['pagination']);
    }

    public function commit(string $owner, string $repo, string $sha): array
    {
        return $this->commitData($owner, $repo, $this->client->commit($owner, $repo, $sha), null, true);
    }

    private function repositoryData(array $repo, bool $detail = false): array
    {
        $data = [
            'id' => $repo['id'] ?? null,
            'owner' => Arr::get($repo, 'owner.login'),
            'name' => $repo['name'] ?? null,
            'full_name' => $repo['full_name'] ?? null,
            'description' => $repo['description'] ?? null,
            'html_url' => $repo['html_url'] ?? null,
            'default_branch' => $repo['default_branch'] ?? null,
            'is_private' => $repo['private'] ?? null,
            'visibility' => $repo['visibility'] ?? null,
            'pushed_at' => $this->formatDate($repo['pushed_at'] ?? null),
            'updated_at' => $this->formatDate($repo['updated_at'] ?? null),
        ];

        if ($detail) {
            $data += [
                'language' => $repo['language'] ?? null,
                'forks_count' => $repo['forks_count'] ?? null,
                'open_issues_count' => $repo['open_issues_count'] ?? null,
                'stargazers_count' => $repo['stargazers_count'] ?? null,
                'contributor' => $this->contributors(
                    (string) Arr::get($repo, 'owner.login'),
                    (string) ($repo['name'] ?? '')
                ),
            ];
        }

        return $data;
    }

    private function contributors(string $owner, string $repo): array
    {
        if ($owner === '' || $repo === '') {
            return [];
        }

        return collect($this->client->contributors($owner, $repo, 30))
            ->map(function (array $contributor) {
                $username = $contributor['login'] ?? null;
                $profile = is_string($username) && $username !== ''
                    ? $this->client->user($username)
                    : [];

                return [
                    'name' => $profile['name'] ?? $username,
                    'email' => $profile['email'] ?? null,
                    'profile' => [
                        'username' => $username,
                        'avatar_url' => $contributor['avatar_url'] ?? ($profile['avatar_url'] ?? null),
                        'url' => $contributor['html_url'] ?? ($profile['html_url'] ?? null),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private function branchData(string $owner, string $repo, array $branch, bool $detail = false): array
    {
        $name = $branch['name'] ?? null;
        $sha = Arr::get($branch, 'commit.sha');

        $data = [
            'name' => $name,
            'latest_commit_sha' => $sha,
            'is_protected' => $branch['protected'] ?? false,
            'commit_url' => $sha ? "https://github.com/{$owner}/{$repo}/commit/{$sha}" : null,
        ];

        if ($detail) {
            $data['html_url'] = $name ? "https://github.com/{$owner}/{$repo}/tree/{$name}" : null;
        }

        return $data;
    }

    private function commitData(
        string $owner,
        string $repo,
        array $payload,
        ?string $branchName = null,
        bool $detail = false
    ): array {
        $messageParts = $this->splitMessage((string) Arr::get($payload, 'commit.message', ''));
        $username = Arr::get($payload, 'author.login');

        $data = [
            'repository' => [
                'full_name' => "{$owner}/{$repo}",
                'html_url' => "https://github.com/{$owner}/{$repo}",
            ],
            'email' => Arr::get($payload, 'commit.author.email'),
            'username' => $username,
            'profile' => [
                'avatar_url' => Arr::get($payload, 'author.avatar_url'),
                'url' => Arr::get($payload, 'author.html_url'),
            ],
            'branch_name' => $branchName,
            'commit_sha' => $payload['sha'] ?? null,
            'html_url' => $payload['html_url'] ?? null,
            'date' => $this->formatDate(Arr::get($payload, 'commit.author.date')),
        ];

        if ($detail) {
            $data['commit_message'] = $messageParts['message'];
            $data['commit_description'] = $messageParts['description'];
            $data['author_name'] = Arr::get($payload, 'commit.author.name');
            $data['committer_name'] = Arr::get($payload, 'commit.committer.name');
            $data['committed_at'] = $this->formatDate(Arr::get($payload, 'commit.committer.date'));
        }

        return $data;
    }

    private function matchesSearch(array $commit, ?string $search): bool
    {
        if (! $search) {
            return true;
        }

        $needle = Str::lower($search);

        return str_contains(Str::lower((string) ($commit['email'] ?? '')), $needle)
            || str_contains(Str::lower((string) ($commit['branch_name'] ?? '')), $needle);
    }

    private function splitMessage(string $rawMessage): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($rawMessage));
        [$message, $description] = array_pad(explode("\n", $normalized, 2), 2, null);

        return [
            'message' => trim($message ?? ''),
            'description' => $description ? trim($description) : null,
        ];
    }

    private function formatDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('d M Y \a\t h:i a');
    }

    private function paginated(array $data, array $pagination): array
    {
        return [
            'data' => $data,
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'page_total' => count($data),
            'last_page' => $pagination['last_page'],
        ];
    }

    public function ownerAndRepo(string $value): array
    {
        if (str_contains($value, '/')) {
            return explode('/', $value, 2);
        }

        return [(string) config('services.github.owner', 'hushstack'), $value];
    }
}
