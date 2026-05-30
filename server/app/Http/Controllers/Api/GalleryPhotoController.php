<?php

namespace App\Http\Controllers\Api;

use App\Models\GalleryPhoto;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryPhotoController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->galleryPhotos;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $validated['photo'] = $request->file('photo')->store('gallery-photos', 'public');

        $galleryPhoto = $request->user()->galleryPhotos()->create($validated);

        return response()->json($galleryPhoto, 201);
    }

    public function destroy(Request $request, GalleryPhoto $galleryPhoto): JsonResponse
    {
        if ($galleryPhoto->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($galleryPhoto->photo) {
            Storage::disk('public')->delete($galleryPhoto->photo);
        }

        $galleryPhoto->delete();

        return response()->json(['message' => 'Photo deleted.']);
    }
}
