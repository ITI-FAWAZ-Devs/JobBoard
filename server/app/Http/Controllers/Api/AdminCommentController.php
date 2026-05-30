<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminCommentController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Comment::with(['user', 'jobListing']);
        
        if ($request->has('reported') && $request->reported == 'true') {
            $query->where('is_reported', true);
        }

        $comments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'message' => 'Comments retrieved successfully',
            'data' => $comments
        ]);
    }

    public function hide(\App\Models\Comment $comment)
    {
        $comment->is_hidden = !$comment->is_hidden;
        $comment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment visibility toggled successfully',
            'data' => $comment
        ]);
    }

    public function destroy(\App\Models\Comment $comment)
    {
        $comment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comment deleted successfully',
            'data' => null
        ]);
    }
}
