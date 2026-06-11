<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(JobListing $jobListing): JsonResponse
    {
        $comments = Comment::where('job_listing_id', $jobListing->id)
            ->where('is_hidden', false)
            ->with(['user:id,name,avatar'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Comments retrieved successfully.',
            'data' => $comments->map(fn (Comment $c) => [
                'id' => $c->id,
                'user_id' => $c->user_id,
                'job_listing_id' => $c->job_listing_id,
                'content' => $c->content,
                'is_hidden' => $c->is_hidden,
                'is_reported' => $c->is_reported,
                'created_at' => $c->created_at?->format('Y-m-d H:i:s'),
                'user' => $c->user ? [
                    'id' => $c->user->id,
                    'name' => $c->user->name,
                    'avatar_url' => $c->user->avatar_url,
                ] : null,
            ]),
        ]);
    }

    public function store(Request $request, JobListing $jobListing): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'job_listing_id' => $jobListing->id,
            'content' => $validated['content'],
            'is_hidden' => false,
            'is_reported' => false,
        ]);

        $comment->load('user:id,name,avatar');

        return response()->json([
            'status' => 'success',
            'message' => 'Comment posted successfully.',
            'data' => [
                'id' => $comment->id,
                'user_id' => $comment->user_id,
                'job_listing_id' => $comment->job_listing_id,
                'content' => $comment->content,
                'is_hidden' => $comment->is_hidden,
                'is_reported' => $comment->is_reported,
                'created_at' => $comment->created_at?->format('Y-m-d H:i:s'),
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar_url' => $comment->user->avatar_url,
                ] : null,
            ],
        ], 201);
    }

    public function report(Request $request, Comment $comment): JsonResponse
    {
        $comment->update(['is_reported' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment reported successfully.',
            'data' => null,
        ]);
    }
}
