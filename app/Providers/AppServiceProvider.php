<?php

namespace App\Providers;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Contracts\Repositories\RolePermissionRepositoryInterface;
use App\Contracts\Services\PermissionServiceInterface;
use App\Contracts\Services\RolePermissionServiceInterface;
use App\Repositories\PermissionRepository;
use App\Repositories\RolePermissionRepository;
use App\Services\PermissionService;
use App\Services\RolePermissionService;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
