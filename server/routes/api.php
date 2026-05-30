<?php

use App\Http\Controllers\Api\AdminJobController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployerCandidateController;
use App\Http\Controllers\Api\EmployerJobController;
use App\Http\Controllers\Api\EmployerPaymentController;
use App\Http\Controllers\Api\AdminCommentController;
use App\Http\Controllers\Api\EmployerAnalyticsController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{jobListing}', [JobController::class, 'show']);

    Route::post('/payments/stripe/webhook', [PaymentWebhookController::class, 'stripe']);
    Route::post('/payments/paypal/webhook', [PaymentWebhookController::class, 'paypal']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::middleware('role:employer')->prefix('employer')->group(function (): void {
            Route::get('/analytics', [EmployerAnalyticsController::class, 'index']);
            Route::get('/jobs', [EmployerJobController::class, 'index']);
            Route::post('/jobs', [EmployerJobController::class, 'store']);
            Route::get('/jobs/{jobListing}', [EmployerJobController::class, 'show']);
            Route::put('/jobs/{jobListing}', [EmployerJobController::class, 'update']);
            Route::delete('/jobs/{jobListing}', [EmployerJobController::class, 'destroy']);

            Route::get('/candidates', [EmployerCandidateController::class, 'index']);
            Route::get('/candidates/{candidate}/contact', [EmployerCandidateController::class, 'contact']);

            Route::post('/payments/stripe/intent', [EmployerPaymentController::class, 'createStripeIntent']);
            Route::post('/payments/paypal/order', [EmployerPaymentController::class, 'createPayPalOrder']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function (): void {
            Route::get('/jobs/pending', [AdminJobController::class, 'pending']);
            Route::patch('/jobs/{jobListing}/approve', [AdminJobController::class, 'approve']);
            Route::patch('/jobs/{jobListing}/reject', [AdminJobController::class, 'reject']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::patch('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
            Route::patch('/users/{user}/ban', [AdminUserController::class, 'ban']);
            Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate']);

            Route::get('/comments', [AdminCommentController::class, 'index']);
            Route::patch('/comments/{comment}/hide', [AdminCommentController::class, 'hide']);
            Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy']);
        });
    });
});
