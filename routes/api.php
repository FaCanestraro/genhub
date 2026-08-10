<?php

use App\Http\Controllers\Api\ActionController;
use App\Http\Controllers\Api\Admin\AdminClientController;
use App\Http\Controllers\Api\Admin\AdminTemplateController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AiCredentialController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\GenerationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\LeadActivityController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\ProxyDownloadController;
use App\Http\Middleware\EnsureClientAccess;
use App\Http\Middleware\ResolveCurrentCompany;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::get('companies', [AuthController::class, 'companies']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {

    // Client-only routes — blocked for platform-admin-only accounts with no client company,
    // and scoped to whichever company the X-Company-Id header resolves to.
    Route::middleware([EnsureClientAccess::class, ResolveCurrentCompany::class])->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::post('products/{product}/images', [ProductController::class, 'uploadImage']);

        Route::get('templates', [TemplateController::class, 'index']);

        Route::apiResource('campaigns', CampaignController::class);
        Route::get('actions', [ActionController::class, 'allActions']);
        Route::apiResource('campaigns.actions', ActionController::class)->shallow();

        Route::post('actions/{action}/generate', [GenerationController::class, 'generate']);
        Route::post('generate', [GenerationController::class, 'generateStandalone']);
        Route::get('generate/history', [GenerationController::class, 'standaloneHistory']);
        Route::put('generate/session/{sessionId}', [GenerationController::class, 'renameSession']);
        Route::delete('generate/session/{sessionId}', [GenerationController::class, 'destroySession']);
        Route::get('generations', [GenerationController::class, 'index']);
        Route::get('generations/{generation}', [GenerationController::class, 'show']);
        Route::delete('generations/{generation}', [GenerationController::class, 'destroy']);
        Route::patch('generations/{generation}/detach', [GenerationController::class, 'detach']);

        Route::delete('assets/{asset}', [AssetController::class, 'destroy']);

        Route::get('leads/pipeline', [LeadController::class, 'pipeline']);
        Route::apiResource('leads', LeadController::class);
        Route::get('leads/{lead}/activities', [LeadActivityController::class, 'index']);
        Route::post('leads/{lead}/activities', [LeadActivityController::class, 'store']);
        Route::delete('leads/{lead}/activities/{activity}', [LeadActivityController::class, 'destroy']);

        Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle']);
        Route::apiResource('tasks', TaskController::class)->except('show');

        Route::get('settings', [SettingController::class, 'show']);
        Route::put('settings', [SettingController::class, 'update']);
        Route::post('settings/logo', [SettingController::class, 'uploadLogo']);

        Route::get('menus', [MenuController::class, 'index']);
        Route::apiResource('roles', RoleController::class)->except('show');
        Route::apiResource('team-members', TeamMemberController::class)->except('show');

        Route::get('audit-logs', [AuditLogController::class, 'index']);

        Route::get('ai-providers', [AiCredentialController::class, 'index']);
        Route::post('ai-providers', [AiCredentialController::class, 'store']);
        Route::patch('ai-providers/{ai_provider}', [AiCredentialController::class, 'update']);
        Route::delete('ai-providers/{ai_provider}', [AiCredentialController::class, 'destroy']);

        Route::get('proxy-download', [ProxyDownloadController::class, 'download']);
    });

    // Admin-only routes — gated per-controller via EnsurePlatformAdmin, independent of is_client.
    Route::prefix('admin')->group(function () {
        Route::get('clients', [AdminClientController::class, 'index']);
        Route::patch('clients/{client}', [AdminClientController::class, 'update']);

        Route::apiResource('templates', AdminTemplateController::class);
        Route::post('templates/{template}/preview', [AdminTemplateController::class, 'uploadPreview']);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);
        Route::post('users/grant', [AdminUserController::class, 'grant']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
    });
});
