<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $perPage = $request->integer('per_page', 20);

        $users = User::with(['employerProfile', 'candidateProfile'])
            ->latest()
            ->paginate($perPage);

        $payload = UserResource::collection($users)->response()->getData(true);

        return response()->json([
            'status' => 'success',
            'message' => 'Users fetched successfully.',
            'data' => $payload,
        ]);
    }

    public function suspend(User $user): JsonResponse
    {
        $this->authorize('suspend', $user);

        if ($user->banned_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is already banned.',
                'data' => null,
            ], 422);
        }

        if ($user->suspended_at && ! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is already suspended.',
                'data' => null,
            ], 422);
        }

        $user->update([
            'is_active' => false,
            'suspended_at' => now(),
            'banned_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User suspended successfully.',
            'data' => $this->buildUserPayload($user),
        ]);
    }

    public function ban(User $user): JsonResponse
    {
        $this->authorize('ban', $user);

        if ($user->banned_at && ! $user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is already banned.',
                'data' => null,
            ], 422);
        }

        $user->update([
            'is_active' => false,
            'banned_at' => now(),
            'suspended_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User banned successfully.',
            'data' => $this->buildUserPayload($user),
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorize('activate', $user);

        if ($user->is_active && ! $user->banned_at && ! $user->suspended_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'User is already active.',
                'data' => null,
            ], 422);
        }

        $user->update([
            'is_active' => true,
            'banned_at' => null,
            'suspended_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User activated successfully.',
            'data' => $this->buildUserPayload($user),
        ]);
    }

    private function buildUserPayload(User $user): array
    {
        return (new UserResource($user->fresh(['employerProfile', 'candidateProfile'])))->resolve();
    }
}
