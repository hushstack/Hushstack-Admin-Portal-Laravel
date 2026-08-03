<?php

use App\Enums\Permission;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\Admin\AlertController as AdminAlertController;
use App\Http\Controllers\Api\Admin\GitHubRepositoryController;
use App\Http\Controllers\Api\Admin\LogController;
use App\Http\Controllers\Api\Admin\MemberController;
use App\Http\Controllers\Api\Admin\MessengerUserController;
use App\Http\Controllers\Api\Admin\PositionController;
use App\Http\Controllers\Api\Admin\ProjectController;
use App\Http\Controllers\Api\Admin\UserActivityController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\Admin\UserRequestController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CliAuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\MemberRequestController;
use App\Http\Controllers\Api\NoteFlow\AiController;
use App\Http\Controllers\Api\NoteFlow\BillingController;
use App\Http\Controllers\Api\NoteFlow\DashboardController;
use App\Http\Controllers\Api\NoteFlow\FolderController;
use App\Http\Controllers\Api\NoteFlow\NoteController;
use App\Http\Controllers\Api\NoteFlow\NotificationController;
use App\Http\Controllers\Api\NoteFlow\SettingsController;
use App\Http\Controllers\Api\NoteFlow\UploadController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\VersionControl\BranchController;
use App\Http\Controllers\Api\VersionControl\CollectionController;
use App\Http\Controllers\Api\VersionControl\CommitController;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp'])->middleware('throttle:5,1');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');

    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);

    Route::get('microsoft/redirect', [\App\Http\Controllers\Api\MicrosoftAuthController::class, 'redirect']);
    Route::get('microsoft/callback', [\App\Http\Controllers\Api\MicrosoftAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn (\Illuminate\Http\Request $request) => response()->json([
            'success' => true,
            'user' => (new UserResource($request->user()->load('role')))->toArray($request),
        ]));
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/delete-account', [AccountController::class, 'requestDelete']);
    });
});

Route::prefix('cli/auth')->group(function () {
    Route::post('/start', [CliAuthController::class, 'start'])->middleware('throttle:cli-auth-start');
    Route::post('/exchange', [CliAuthController::class, 'exchange'])->middleware('throttle:cli-auth-exchange');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [CliAuthController::class, 'me']);
        Route::post('/logout', [CliAuthController::class, 'logout']);
    });
});

Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'send']);
Route::post('/member/request', [MemberRequestController::class, 'store']);
Route::post('/alerts', [AlertController::class, 'store'])
    ->middleware(['cachewraith.agent', 'throttle:cachewraith-alerts']);

Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('header', [ProfileController::class, 'header']);
    Route::get('personal-info', [ProfileController::class, 'personalInfo']);
    Route::get('address', [ProfileController::class, 'address']);
    Route::post('header', [ProfileController::class, 'updateHeader']);
    Route::post('personal-info', [ProfileController::class, 'updatePersonalInfo']);
    //    Route::patch('header',      [ProfileController::class, 'updateHeader']);
    Route::post('address', [ProfileController::class, 'updateAddress']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('roles', RoleController::class)->except(['show']);
    Route::post('users/{user}/role', [UserRoleController::class, 'assign']);
    Route::get('users', [UserAdminController::class, 'index']);
    Route::get('users/search', [UserAdminController::class, 'search']);
    Route::get('users/{user}', [UserAdminController::class, 'show']);
    Route::delete('users/{user}', [UserAdminController::class, 'destroy']);
    Route::get('user-requests', [UserRequestController::class, 'index']);
    Route::get('user-activities', [UserActivityController::class, 'index']);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('members', MemberController::class);

    // Project Routes with Rate Limiting
    // POST for create and update to support file uploads (multipart/form-data)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('projects', [ProjectController::class, 'index']);
        Route::post('projects', [ProjectController::class, 'store']);
        Route::get('projects/{project}', [ProjectController::class, 'show']);
        Route::post('projects/{project}', [ProjectController::class, 'update']);
        Route::delete('projects/{project}', [ProjectController::class, 'destroy']);
        Route::post('projects/{id}/restore', [ProjectController::class, 'restore']);
        Route::delete('projects/{id}/force', [ProjectController::class, 'forceDelete']);
    });

    // Laravel Log Viewer Routes - Admin Only
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('logs', [LogController::class, 'index']);
        Route::get('logs/files', [LogController::class, 'files']);
    });

    Route::middleware('throttle:60,1')->prefix('github')->group(function () {
        Route::middleware('permission:'.Permission::GITHUB_REPOSITORIES_VIEW->value)->group(function () {
            Route::get('repositories', [GitHubRepositoryController::class, 'repositories']);
            Route::get('repositories/{repositoryId}/branches', [GitHubRepositoryController::class, 'branches'])->whereNumber('repositoryId');
            Route::get('repositories/{repositoryId}/branch', [GitHubRepositoryController::class, 'branch'])->whereNumber('repositoryId');
            Route::get('repositories/{repositoryId}/branch/commits', [GitHubRepositoryController::class, 'branchCommits'])->whereNumber('repositoryId');
            Route::get('repositories/{repositoryId}/commits/{sha}', [GitHubRepositoryController::class, 'show'])->whereNumber('repositoryId');
            Route::get('repositories/{repositoryId}', [GitHubRepositoryController::class, 'repository'])->whereNumber('repositoryId');
            Route::get('commits', [GitHubRepositoryController::class, 'commits']);
        });

        Route::middleware('permission:'.Permission::GITHUB_REPOSITORIES_CREATE->value)
            ->post('repositories', [GitHubRepositoryController::class, 'storeRepository']);
    });

    // Messenger internal service proxy
    Route::middleware('throttle:60,1')->prefix('messenger')->group(function () {
        Route::get('users', [MessengerUserController::class, 'index']);
    });
});

Route::middleware(['auth:sanctum', 'active_catalog_user'])->prefix('admin')->group(function () {
    Route::apiResource('departments', DepartmentController::class)->except(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
    Route::apiResource('brands', BrandController::class)->except(['index', 'show']);
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('brands', BrandController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
});

// Public Projects Endpoint - No auth required, rate limited
// OWASP A01:2021 - Broken Access Control: Only published projects visible
Route::middleware('throttle:60,1')->get('projects-public', [ProjectController::class, 'publicIndex']);

/**
 * Permission Management Routes
 *
 * Security: Permission-based access control using hardcoded Permission enum
 * Performance: Rate limited to prevent abuse
 * OWASP: Compliant with A01 (Access Control), A03 (Injection), A07 (Logging)
 *
 * Note: Only Super Admin can manage permissions, but we check specific permission
 * for future flexibility (e.g., delegated permission management)
 */
Route::middleware(['auth:sanctum', 'super_admin'])->prefix('admin')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Route::middleware('permission:'.Permission::ALERTS_VIEW->value)->group(function () {
            Route::get('alerts', [AdminAlertController::class, 'index']);
            Route::get('alerts/{alert}', [AdminAlertController::class, 'show']);
        });

        Route::middleware('permission:'.Permission::ALERTS_ACKNOWLEDGE->value)
            ->post('alerts/{alert}/acknowledge', [AdminAlertController::class, 'acknowledge']);

        Route::middleware('permission:'.Permission::ALERTS_RESOLVE->value)
            ->post('alerts/{alert}/resolve', [AdminAlertController::class, 'resolve']);

        Route::middleware('permission:'.Permission::ALERTS_REOPEN->value)
            ->post('alerts/{alert}/reopen', [AdminAlertController::class, 'reopen']);

        Route::middleware('permission:'.Permission::ALERTS_DELETE->value)
            ->delete('alerts/{alert}', [AdminAlertController::class, 'destroy']);
    });

    // Permission Management with rate limiting
    // Requires 'permissions.view' to list/show, 'permissions.assign' to modify
    Route::middleware('throttle:60,1')->group(function () {

        // List/Show permissions - requires permissions.view
        Route::middleware('permission:'.Permission::PERMISSIONS_VIEW->value)->group(function () {
            Route::get('permissions', [PermissionController::class, 'index']);
            Route::get('permissions/{permission}', [PermissionController::class, 'show']);
            Route::get('permissions/{permission}/roles', [RolePermissionController::class, 'rolesWithPermission']);
        });

        // Role-Permission Assignment - requires permissions.assign
        Route::middleware('permission:'.Permission::PERMISSIONS_ASSIGN->value)->group(function () {
            Route::get('roles/{role}/permissions', [RolePermissionController::class, 'index']);
            Route::post('roles/{role}/permissions', [RolePermissionController::class, 'assign']);
        });

        // Revoke permissions - requires permissions.revoke
        Route::middleware('permission:'.Permission::PERMISSIONS_REVOKE->value)->group(function () {
            // Revoke specific permissions by ID in body
            Route::delete('roles/{role}/permissions', [RolePermissionController::class, 'revoke']);
            // Revoke all permissions from role
            Route::delete('roles/{role}/permissions/all', [RolePermissionController::class, 'revokeAll']);
        });
    });
});

/**
 * Version Control Routes (Collections, Branches, Commits)
 *
 * Security: Permission-based access control using hardcoded Permission enum
 * Performance: Rate limited to prevent abuse
 * OWASP: Compliant with A01 (Access Control), A03 (Injection), A05 (Config)
 *
 * Endpoints:
 * 1. GET    /api/collections           - List all collections
 * 2. GET    /api/branches              - List all branches (filter: stage)
 * 3. GET    /api/commits               - List all commits (filter: branch_id, collection_id)
 * 4. GET    /api/collections/{id}/branches - List branches by collection (filter: stage)
 * 5. GET    /api/branches/{id}/commits - List commits by branch
 * 6. GET    /api/collections/count     - Count collections, branches by stage, total commits
 * 7. GET    /api/branches/count        - Count branches by stage and total commits by collection
 */
Route::middleware(['auth:sanctum'])->group(function () {

    // Collections
    Route::middleware('permission:'.Permission::COLLECTIONS_VIEW->value)->group(function () {
        Route::get('collections', [CollectionController::class, 'index']);
        Route::get('collections/count', [CollectionController::class, 'count']);
        Route::get('collections/{id}', [CollectionController::class, 'show']);
    });

    Route::middleware('permission:'.Permission::COLLECTIONS_CREATE->value)
        ->post('collections', [CollectionController::class, 'store']);

    Route::middleware('permission:'.Permission::COLLECTIONS_EDIT->value)
        ->put('collections/{id}', [CollectionController::class, 'update']);

    Route::middleware('permission:'.Permission::COLLECTIONS_DELETE->value)
        ->delete('collections/{id}', [CollectionController::class, 'destroy']);

    // Branches
    Route::middleware('permission:'.Permission::BRANCHES_VIEW->value)->group(function () {
        Route::get('branches', [BranchController::class, 'index']);
        Route::get('branches/count', [BranchController::class, 'count']);
        Route::get('branches/{id}', [BranchController::class, 'show']);
        Route::get('collections/{id}/branches', [BranchController::class, 'byCollection']);
    });

    Route::middleware('permission:'.Permission::BRANCHES_CREATE->value)
        ->post('branches', [BranchController::class, 'store']);

    Route::middleware('permission:'.Permission::BRANCHES_EDIT->value)
        ->put('branches/{id}', [BranchController::class, 'update']);

    Route::middleware('permission:'.Permission::BRANCHES_DELETE->value)
        ->delete('branches/{id}', [BranchController::class, 'destroy']);

    // Commits
    Route::middleware('permission:'.Permission::COMMITS_VIEW->value)->group(function () {
        Route::get('commits', [CommitController::class, 'index']);
        Route::get('commits/count', [CommitController::class, 'count']);
        Route::get('commits/{id}', [CommitController::class, 'show']);
        Route::get('branches/{id}/commits', [CommitController::class, 'byBranch']);
    });

    Route::middleware('permission:'.Permission::COMMITS_CREATE->value)
        ->post('commits', [CommitController::class, 'store']);

    Route::middleware('permission:'.Permission::COMMITS_EDIT->value)
        ->put('commits/{id}', [CommitController::class, 'update']);

    Route::middleware('permission:'.Permission::COMMITS_DELETE->value)
        ->delete('commits/{id}', [CommitController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('dashboard', DashboardController::class);

    Route::get('folders', [FolderController::class, 'index']);
    Route::post('folders', [FolderController::class, 'store']);

    Route::get('notes', [NoteController::class, 'index']);
    Route::post('notes', [NoteController::class, 'store']);
    Route::get('notes/{id}', [NoteController::class, 'show'])->whereNumber('id');
    Route::patch('notes/{id}', [NoteController::class, 'update'])->whereNumber('id');
    Route::delete('notes/{id}', [NoteController::class, 'destroy'])->whereNumber('id');
    Route::post('notes/{id}/duplicate', [NoteController::class, 'duplicate'])->whereNumber('id');
    Route::patch('notes/{id}/favorite', [NoteController::class, 'favorite'])->whereNumber('id');
    Route::post('notes/{id}/share', [NoteController::class, 'share'])->whereNumber('id');
    Route::get('notes/{id}/versions', [NoteController::class, 'versions'])->whereNumber('id');

    Route::get('ai/tools', [AiController::class, 'tools']);
    Route::post('ai/generate', [AiController::class, 'generate'])->middleware('throttle:20,1');
    Route::get('ai/generations', [AiController::class, 'generations']);
    Route::get('ai/settings', [AiController::class, 'settings']);
    Route::patch('ai/settings', [AiController::class, 'updateSettings']);

    Route::post('uploads', [UploadController::class, 'store'])->middleware('throttle:20,1');
    Route::get('uploads', [UploadController::class, 'index']);
    Route::get('uploads/{id}', [UploadController::class, 'show'])->whereNumber('id');
    Route::delete('uploads/{id}', [UploadController::class, 'destroy'])->whereNumber('id');
    Route::post('uploads/{id}/reprocess', [UploadController::class, 'reprocess'])->whereNumber('id')->middleware('throttle:20,1');

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications', [NotificationController::class, 'store']);
    Route::patch('notifications/{id}', [NotificationController::class, 'update'])->whereNumber('id');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
    Route::get('notifications/history', [NotificationController::class, 'history']);

    Route::get('settings', [SettingsController::class, 'show']);
    Route::patch('settings/profile', [SettingsController::class, 'updateProfile']);
    Route::patch('settings/appearance', [SettingsController::class, 'updateAppearance']);
    Route::patch('settings/notifications', [SettingsController::class, 'updateNotifications']);
    Route::patch('settings/security/password', [SettingsController::class, 'changePassword']);
    Route::get('settings/security/sessions', [SettingsController::class, 'sessions']);
    Route::delete('settings/security/sessions/{id}', [SettingsController::class, 'revokeSession'])->whereNumber('id');
    Route::post('settings/security/2fa/enable', [SettingsController::class, 'enableTwoFactor']);

    Route::get('billing/subscription', [BillingController::class, 'subscription']);
    Route::get('billing/invoices', [BillingController::class, 'invoices']);
});
