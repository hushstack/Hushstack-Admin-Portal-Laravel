<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\Admin\UserRequestController;
use App\Http\Controllers\Api\Admin\UserActivityController;
use App\Http\Controllers\Api\MemberRequestController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\ProductController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth.register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth.login');

    Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp'])->middleware('throttle:auth.otp.verify');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:auth.otp.resend');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth.password.forgot');

    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);

    Route::get('microsoft/redirect', [\App\Http\Controllers\Api\MicrosoftAuthController::class, 'redirect']);
    Route::get('microsoft/callback', [\App\Http\Controllers\Api\MicrosoftAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/delete-account', [AccountController::class, 'requestDelete']);
    });
});

Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'send'])->middleware('throttle:public.contact');
Route::post('/member/request', [MemberRequestController::class, 'store'])->middleware('throttle:public.member-request');

Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('header',        [ProfileController::class, 'header']);
    Route::get('personal-info', [ProfileController::class, 'personalInfo']);
    Route::get('address',       [ProfileController::class, 'address']);
    Route::post('header',        [ProfileController::class, 'updateHeader']);
    Route::post('personal-info',[ProfileController::class, 'updatePersonalInfo']);
//    Route::patch('header',      [ProfileController::class, 'updateHeader']);
    Route::post('address',      [ProfileController::class, 'updateAddress']);
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
});

Route::middleware(['auth:sanctum', 'catalog_editor'])->prefix('admin')->group(function () {
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
