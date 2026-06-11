<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\JobListing;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $employersCount = User::where('role', 'employer')->count();
        $candidatesCount = User::where('role', 'candidate')->count();

        $totalJobs = JobListing::count();
        $pendingJobsCount = JobListing::where('status', 'pending')->count();
        $approvedJobsCount = JobListing::where('status', 'approved')->count();
        $rejectedJobsCount = JobListing::where('status', 'rejected')->count();

        $flaggedCommentsCount = Comment::where('is_reported', true)->count();

        $recentPendingJobs = JobListing::where('status', 'pending')
            ->with('employerProfile')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'company_name' => $job->employerProfile?->company_name,
                'location' => $job->location,
                'created_at' => $job->created_at?->diffForHumans(),
            ]);

        $recentFlaggedComments = Comment::where('is_reported', true)
            ->with(['user', 'jobListing'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($comment) => [
                'id' => $comment->id,
                'user_name' => $comment->user?->name,
                'job_title' => $comment->jobListing?->title,
                'content' => $comment->content,
                'created_at' => $comment->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'employers' => $employersCount,
                    'candidates' => $candidatesCount,
                ],
                'jobs' => [
                    'total' => $totalJobs,
                    'pending' => $pendingJobsCount,
                    'approved' => $approvedJobsCount,
                    'rejected' => $rejectedJobsCount,
                ],
                'flagged_comments_count' => $flaggedCommentsCount,
                'recent_pending_jobs' => $recentPendingJobs,
                'recent_flagged_comments' => $recentFlaggedComments,
            ],
        ]);
    }
}
