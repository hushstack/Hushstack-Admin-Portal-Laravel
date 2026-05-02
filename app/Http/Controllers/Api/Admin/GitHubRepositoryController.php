<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Permission;
use App\Exceptions\GitHub\GitHubApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GitHub\IndexGitHubCommitRequest;
use App\Http\Requests\Admin\GitHub\StoreGitHubRepositoryRequest;
use App\Services\GitHub\GitHubRepositoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class GitHubRepositoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly GitHubRepositoryService $github) {}

    public function repositories(Request $request)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_VIEW)) {
            return $response;
        }

        try {
            $repositories = $this->github->repositories([
                'page' => $request->integer('page', 1),
                'per_page' => $request->integer('per_page', 20),
            ]);
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }

        return $this->successResponse($repositories, 'GitHub repositories loaded.');
    }

    public function repository(Request $request, int $repositoryId)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_VIEW)) {
            return $response;
        }

        try {
            return $this->successResponse(
                $this->github->repositoryById($repositoryId),
                'GitHub repository loaded.'
            );
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }
    }

    public function storeRepository(StoreGitHubRepositoryRequest $request)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_CREATE)) {
            return $response;
        }

        try {
            $repository = $this->github->validateRepository($request->validated('full_name'));
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }

        return $this->successResponse(
            $repository,
            'GitHub token can access this repository.'
        );
    }

    public function branches(Request $request, int $repositoryId)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_VIEW)) {
            return $response;
        }

        try {
            [$owner, $repoName] = $this->github->ownerAndRepoFromId($repositoryId);

            return $this->successResponse(
                $this->github->branches($owner, $repoName, [
                    'page' => $request->integer('page', 1),
                    'per_page' => $request->integer('per_page', 30),
                ]),
                'GitHub branches loaded.'
            );
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }
    }

    public function branch(Request $request, int $repositoryId)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_VIEW)) {
            return $response;
        }

        $branchName = $request->query('branch_name');

        if (! is_string($branchName) || trim($branchName) === '') {
            return $this->validationErrorResponse(['branch_name' => ['The branch_name query parameter is required.']]);
        }

        try {
            [$owner, $repoName] = $this->github->ownerAndRepoFromId($repositoryId);

            return $this->successResponse(
                $this->github->branch($owner, $repoName, trim($branchName)),
                'GitHub branch loaded.'
            );
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }
    }

    public function commits(IndexGitHubCommitRequest $request)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_VIEW)) {
            return $response;
        }

        $filters = $request->validated();

        if (isset($filters['repo_id'])) {
            try {
                [$owner, $name] = $this->github->ownerAndRepoFromId((int) $filters['repo_id']);
            } catch (GitHubApiException $exception) {
                return $this->githubErrorResponse($exception);
            }
        } else {
            $repo = $filters['repo'] ?? null;

            if (! is_string($repo)) {
                return $this->validationErrorResponse(['repo_id' => ['The repo_id query parameter is required.']]);
            }

            [$owner, $name] = $this->github->ownerAndRepo($repo);
        }

        try {
            return $this->successResponse(
                $this->github->commits($owner, $name, $filters['branch_name'] ?? null, $filters),
                'GitHub commits loaded.'
            );
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }
    }

    public function branchCommits(IndexGitHubCommitRequest $request, int $repositoryId)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_VIEW)) {
            return $response;
        }

        $filters = $request->validated();
        $branchName = $request->query('branch_name');

        if (! is_string($branchName) || trim($branchName) === '') {
            return $this->validationErrorResponse(['branch_name' => ['The branch_name query parameter is required.']]);
        }

        try {
            [$owner, $repoName] = $this->github->ownerAndRepoFromId($repositoryId);

            return $this->successResponse(
                $this->github->commits($owner, $repoName, trim($branchName), $filters),
                'GitHub branch commits loaded.'
            );
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }
    }

    public function show(Request $request, int $repositoryId, string $sha)
    {
        if ($response = $this->requirePermission($request, Permission::GITHUB_REPOSITORIES_VIEW)) {
            return $response;
        }

        if (! preg_match('/^[a-f0-9]{7,64}$/i', $sha)) {
            return $this->validationErrorResponse(['sha' => ['The commit SHA format is invalid.']]);
        }

        try {
            [$owner, $repoName] = $this->github->ownerAndRepoFromId($repositoryId);

            return $this->successResponse(
                $this->github->commit($owner, $repoName, $sha),
                'GitHub commit loaded.'
            );
        } catch (GitHubApiException $exception) {
            return $this->githubErrorResponse($exception);
        }
    }

    private function requirePermission(Request $request, Permission $permission)
    {
        if (! $request->user()?->hasPermission($permission)) {
            return $this->forbiddenResponse('Forbidden. You do not have the required permission.');
        }

        return null;
    }

    private function githubErrorResponse(GitHubApiException $exception)
    {
        return $this->errorResponse(
            $exception->getMessage(),
            $exception->statusCode(),
            ['rate_limit' => $exception->rateLimit()],
            'GITHUB_API_ERROR'
        );
    }
}
