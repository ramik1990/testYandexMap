<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SnapshotController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'me']);
    Route::apiResource('organizations', OrganizationController::class)->except('update');
    Route::put('organizations/{organization}', [OrganizationController::class, 'update']);
    Route::get('organizations/{organization}/reviews', [ReviewController::class, 'index']);
    Route::get('organizations/{organization}/snapshots', [SnapshotController::class, 'index']);
});
