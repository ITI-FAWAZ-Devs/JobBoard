<?php

namespace App\Http\Controllers\Api;

use App\Models\Office;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->offices;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'is_headquarters' => ['boolean'],
        ]);

        $office = $request->user()->offices()->create($validated);

        return response()->json($office, 201);
    }

    public function update(Request $request, Office $office): JsonResponse
    {
        if ($office->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
            'is_headquarters' => ['boolean'],
        ]);

        $office->update($validated);

        return response()->json($office);
    }

    public function destroy(Request $request, Office $office): JsonResponse
    {
        if ($office->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $office->delete();

        return response()->json(['message' => 'Office deleted.']);
    }
}
