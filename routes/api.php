<?php

use App\Http\Controllers\Api\ActionController;
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
use App\Http\Controllers\Api\ProxyDownloadController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/images', [ProductController::class, 'uploadImage']);

    Route::apiResource('templates', TemplateController::class)->except('show');
    Route::post('templates/{template}/preview', [TemplateController::class, 'uploadPreview']);

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

    Route::get('proxy-download', [ProxyDownloadController::class, 'download']);
});
