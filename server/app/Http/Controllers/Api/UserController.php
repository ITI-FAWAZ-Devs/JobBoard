<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection(
            User::with(['employerProfile', 'candidateProfile'])->latest()->paginate(20)
        );
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load(['employerProfile', 'candidateProfile']));
    }

    public function update(Request $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'employee_count' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'perks' => ['sometimes', 'nullable', 'array'],
            'perks.*' => ['string', 'max:255'],
            'cover_photo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'linkedin_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'skills' => ['sometimes', 'nullable', 'array'],
            'skills.*' => ['string', 'max:255'],
            'experience_years' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'bio' => ['sometimes', 'nullable', 'string'],
            'resume' => ['sometimes', 'nullable', 'file', 'mimes:pdf,docx,doc', 'max:5120'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update(collect($validated)->only([
            'name',
            'email',
            'password',
            'avatar',
        ])->all());

        if ($request->hasFile('cover_photo')) {
            $profile = $user->employerProfile;
            if ($profile && $profile->cover_photo) {
                Storage::disk('public')->delete($profile->cover_photo);
            }

            $validated['cover_photo'] = $request->file('cover_photo')->store('cover-photos', 'public');
        }

        if ($user->isEmployer()) {
            $employerProfileData = collect($validated)->only([
                'company_name',
                'website',
                'industry',
                'employee_count',
                'phone',
                'location',
                'description',
                'perks',
                'cover_photo',
            ])->filter(fn ($v) => ! is_null($v))->all();

            $employerProfileData['company_name'] ??= $user->employerProfile?->company_name ?? '';

            $user->employerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $employerProfileData
            );
        }

        if ($request->hasFile('resume')) {
            if ($user->candidateProfile?->resume) {
                Storage::disk('public')->delete($user->candidateProfile->resume);
            }

            $validated['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        if ($user->isCandidate()) {
            $user->candidateProfile()->updateOrCreate(
                ['user_id' => $user->id],
                collect($validated)->only([
                    'phone',
                    'linkedin_url',
                    'skills',
                    'experience_years',
                    'location',
                    'bio',
                    'resume',
                ])->all()
            );
        }

        return new UserResource($user->fresh(['employerProfile', 'candidateProfile', 'offices', 'galleryPhotos']));
    }

    public function updateSelf(Request $request): UserResource
    {
        return $this->update($request, $request->user());
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
