<?php

use App\Http\Controllers\Api\AdminJobController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployerJobController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{jobListing}', [JobController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::middleware('role:employer')->prefix('employer')->group(function (): void {
        Route::get('/jobs', [EmployerJobController::class, 'index']);
        Route::post('/jobs', [EmployerJobController::class, 'store']);
        Route::get('/jobs/{jobListing}', [EmployerJobController::class, 'show']);
        Route::put('/jobs/{jobListing}', [EmployerJobController::class, 'update']);
        Route::delete('/jobs/{jobListing}', [EmployerJobController::class, 'destroy']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function (): void {
        Route::get('/jobs/pending', [AdminJobController::class, 'pending']);
        Route::patch('/jobs/{jobListing}/approve', [AdminJobController::class, 'approve']);
        Route::patch('/jobs/{jobListing}/reject', [AdminJobController::class, 'reject']);
    });
});
