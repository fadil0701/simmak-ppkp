<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentCategoryController;
use App\Http\Controllers\Api\V1\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard/executive', [DashboardController::class, 'executive']);

        Route::get('/dms/categories', [DocumentCategoryController::class, 'index']);
        Route::get('/dms/documents/stats', [DocumentController::class, 'stats']);
        Route::get('/dms/documents', [DocumentController::class, 'index']);
        Route::post('/dms/documents', [DocumentController::class, 'store']);
        Route::get('/dms/documents/{id}', [DocumentController::class, 'show']);
        Route::post('/dms/documents/{id}/publish', [DocumentController::class, 'publish']);
    });
});
