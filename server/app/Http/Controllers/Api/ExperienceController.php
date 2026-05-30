<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExperienceController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $experiences = $request->user()->experiences()->latest()->get();

        return JsonResource::collection($experiences);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'current' => ['nullable', 'boolean'],
        ]);

        $experience = $request->user()->experiences()->create($validated);

        return response()->json(['data' => $experience], 201);
    }

    public function show(Request $request, Experience $experience): JsonResponse
    {
        if ($experience->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $experience]);
    }

    public function update(Request $request, Experience $experience): JsonResponse
    {
        if ($experience->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'company' => ['sometimes', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'current' => ['nullable', 'boolean'],
        ]);

        $experience->update($validated);

        return response()->json(['data' => $experience]);
    }

    public function destroy(Request $request, Experience $experience): JsonResponse
    {
        if ($experience->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $experience->delete();

        return response()->json(['message' => 'Experience deleted successfully.']);
    }
}
