<?php

use App\Http\Controllers\Api\AdminCommentController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminJobController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidateDashboardController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\EducationController;
use App\Http\Controllers\Api\EmployerAnalyticsController;
use App\Http\Controllers\Api\EmployerApplicationController;
use App\Http\Controllers\Api\EmployerCandidateController;
use App\Http\Controllers\Api\EmployerJobController;
use App\Http\Controllers\Api\EmployerPaymentController;
use App\Http\Controllers\Api\ExperienceController;
use App\Http\Controllers\Api\GalleryPhotoController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\SavedJobController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\UserController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/statistics', [JobController::class, 'statistics']);
    Route::get('/jobs/{jobListing}', [JobController::class, 'show']);
    Route::get('/jobs/{jobListing}/comments', [CommentController::class, 'index']);
    Route::get('/companies/filters', [CompanyController::class, 'filters']);
    Route::get('/companies', [CompanyController::class, 'index']);

    Route::get('/salaries/top-companies', [\App\Http\Controllers\Api\SalaryReportController::class, 'topCompanies']);
    Route::get('/salaries', [\App\Http\Controllers\Api\SalaryReportController::class, 'index']);
    Route::post('/salaries', [\App\Http\Controllers\Api\SalaryReportController::class, 'store']);


    Route::get('/categories', function () {
        return response()->json(['data' => Category::orderBy('name')->get(['id', 'name'])]);
    });

    Route::post('/payments/stripe/webhook', [PaymentWebhookController::class, 'stripe']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::match(['post', 'patch'], '/auth/me', [UserController::class, 'updateSelf']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::apiResource('experiences', ExperienceController::class)->except(['show']);
        Route::apiResource('education', EducationController::class)->except(['show']);
        Route::apiResource('offices', OfficeController::class)->except(['show', 'edit', 'create']);
        Route::apiResource('gallery-photos', GalleryPhotoController::class)->except(['show', 'edit', 'create', 'update']);

        // Applications (candidate)
        Route::post('/jobs/{jobListing}/apply', [ApplicationController::class, 'store']);
        Route::get('/my-applications', [ApplicationController::class, 'index']);
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);

        // Candidate dashboard & saved jobs
        Route::get('/candidate/dashboard', [CandidateDashboardController::class, 'index'])->middleware('role:candidate');
        Route::get('/saved-jobs', [SavedJobController::class, 'index'])->middleware('role:candidate');
        Route::post('/jobs/{jobListing}/save', [SavedJobController::class, 'store'])->middleware('role:candidate');
        Route::delete('/jobs/{jobListing}/save', [SavedJobController::class, 'destroy'])->middleware('role:candidate');

        // Comments (authenticated)
        Route::post('/jobs/{jobListing}/comments', [CommentController::class, 'store']);
        Route::patch('/comments/{comment}/report', [CommentController::class, 'report']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

        // Payments (employer only)
        Route::middleware('role:employer')->group(function (): void {
            Route::get('/applications/{application}/checkout', [EmployerPaymentController::class, 'getCheckout']);
            Route::post('/payments/stripe/session', [EmployerPaymentController::class, 'createCheckoutSession']);
            Route::get('/applications/{application}/payment/status', [EmployerPaymentController::class, 'verifySessionStatus']);
            Route::get('/applications/{application}/contact', [EmployerPaymentController::class, 'getContact']);
        });

        Route::middleware('role:employer')->prefix('employer')->group(function (): void {
            Route::get('/analytics', [EmployerAnalyticsController::class, 'index']);
            Route::get('/jobs', [EmployerJobController::class, 'index']);
            Route::post('/jobs', [EmployerJobController::class, 'store']);
            Route::get('/jobs/{jobListing}', [EmployerJobController::class, 'show']);
            Route::put('/jobs/{jobListing}', [EmployerJobController::class, 'update']);
            Route::delete('/jobs/{jobListing}', [EmployerJobController::class, 'destroy']);

            Route::get('/candidates', [EmployerCandidateController::class, 'index']);
            Route::get('/candidates/{candidate}/contact', [EmployerCandidateController::class, 'contact']);

            // Employer application inbox
            Route::get('/applications', [EmployerApplicationController::class, 'index']);
            Route::patch('/applications/{application}/accept', [EmployerApplicationController::class, 'accept']);
            Route::patch('/applications/{application}/reject', [EmployerApplicationController::class, 'reject']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function (): void {
            Route::get('/dashboard', [AdminDashboardController::class, 'index']);
            Route::get('/jobs', [AdminJobController::class, 'index']);
            Route::get('/jobs/pending', [AdminJobController::class, 'pending']);
            Route::patch('/jobs/{jobListing}/approve', [AdminJobController::class, 'approve']);
            Route::patch('/jobs/{jobListing}/reject', [AdminJobController::class, 'reject']);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::patch('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
            Route::patch('/users/{user}/ban', [AdminUserController::class, 'ban']);
            Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate']);
            Route::patch('/users/{user}/restore', [AdminUserController::class, 'restore']);

            Route::get('/comments', [AdminCommentController::class, 'index']);
            Route::patch('/comments/{comment}/hide', [AdminCommentController::class, 'hide']);
            Route::patch('/comments/{comment}/unflag', [AdminCommentController::class, 'unflag']);
            Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy']);
        });
    });
});

