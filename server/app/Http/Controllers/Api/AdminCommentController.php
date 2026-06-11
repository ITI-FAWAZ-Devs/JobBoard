<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Comment::with(['user', 'jobListing']);

        $flagged = $request->boolean('flagged', false) || $request->boolean('reported', false);

        if ($flagged) {
            $query->where('is_reported', true);
        }

        $comments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'message' => 'Comments retrieved successfully.',
            'data' => $comments,
        ]);
    }

    public function hide(Comment $comment): JsonResponse
    {
        $comment->is_hidden = ! $comment->is_hidden;
        $comment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment visibility toggled.',
            'data' => $comment->fresh()->load(['user', 'jobListing']),
        ]);
    }

    public function unflag(Comment $comment): JsonResponse
    {
        $comment->is_reported = false;
        $comment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Flag dismissed.',
            'data' => $comment->fresh()->load(['user', 'jobListing']),
        ]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment deleted.',
            'data' => null,
        ]);
    }
}
