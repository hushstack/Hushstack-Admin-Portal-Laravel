<?php

namespace App\Providers;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Contracts\Repositories\RolePermissionRepositoryInterface;
use App\Contracts\Services\BranchServiceInterface;
use App\Contracts\Services\CollectionServiceInterface;
use App\Contracts\Services\CommitServiceInterface;
use App\Contracts\Services\PermissionServiceInterface;
use App\Contracts\Services\RolePermissionServiceInterface;
use App\Repositories\PermissionRepository;
use App\Repositories\RolePermissionRepository;
use App\Services\PermissionService;
use App\Services\RolePermissionService;
use App\Services\VersionControl\BranchService;
use App\Services\VersionControl\CollectionService;
use App\Services\VersionControl\CommitService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * OOAD: Dependency Inversion Principle - bind abstractions to concretions
     * Clean Architecture: Interface-based programming enables testability
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );

        $this->app->bind(
            RolePermissionRepositoryInterface::class,
            RolePermissionRepository::class
        );

        // Service bindings
        $this->app->bind(
            PermissionServiceInterface::class,
            PermissionService::class
        );

        $this->app->bind(
            RolePermissionServiceInterface::class,
            RolePermissionService::class
        );

        // Version Control Service bindings
        $this->app->bind(
            CollectionServiceInterface::class,
            CollectionService::class
        );

        $this->app->bind(
            BranchServiceInterface::class,
            BranchService::class
        );

        $this->app->bind(
            CommitServiceInterface::class,
            CommitService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
