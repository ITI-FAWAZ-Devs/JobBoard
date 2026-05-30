<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $education = $request->user()->education()->latest()->get();

        return response()->json(['data' => $education]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'school' => ['required', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:255'],
        ]);

        $education = $request->user()->education()->create($validated);

        return response()->json(['data' => $education], 201);
    }

    public function show(Request $request, Education $education): JsonResponse
    {
        if ($education->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $education]);
    }

    public function update(Request $request, Education $education): JsonResponse
    {
        if ($education->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'school' => ['sometimes', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:255'],
        ]);

        $education->update($validated);

        return response()->json(['data' => $education]);
    }

    public function destroy(Request $request, Education $education): JsonResponse
    {
        if ($education->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $education->delete();

        return response()->json(['message' => 'Education deleted successfully.']);
    }
}
