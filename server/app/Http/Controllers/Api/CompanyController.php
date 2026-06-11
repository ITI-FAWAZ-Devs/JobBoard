<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EmployerProfile::withCount(['jobListings' => function ($q) {
            $q->where('status', 'approved');
        }]);

        // Search query
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('company_name', 'like', "%{$q}%")
                    ->orWhere('industry', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // Filter by industry
        if ($request->filled('industry')) {
            $industries = is_array($request->input('industry')) 
                ? $request->input('industry') 
                : explode(',', $request->input('industry'));
            $query->whereIn('industry', $industries);
        }

        // Filter by employee count / size
        if ($request->filled('employee_count')) {
            $sizes = is_array($request->input('employee_count')) 
                ? $request->input('employee_count') 
                : explode(',', $request->input('employee_count'));
            $query->whereIn('employee_count', $sizes);
        }

        // Filter by location
        if ($request->filled('location')) {
            $locations = is_array($request->input('location')) 
                ? $request->input('location') 
                : explode(',', $request->input('location'));
            $query->where(function ($sub) use ($locations) {
                foreach ($locations as $loc) {
                    if (strtolower($loc) === 'remote') {
                        $sub->orWhere('location', 'like', '%remote%');
                    } else {
                        $sub->orWhere('location', 'like', "%{$loc}%");
                    }
                }
            });
        }

        // Sorting
        $sort = $request->input('sort', 'relevance');
        if ($sort === 'alphabetical') {
            $query->orderBy('company_name', 'asc');
        } elseif ($sort === 'most_open_jobs') {
            $query->orderBy('job_listings_count', 'desc');
        } else {
            // relevance / default
            $query->latest();
        }

        $companies = $query->paginate(12);

        return response()->json([
            'status' => 'success',
            'message' => 'Companies retrieved successfully.',
            'data' => [
                'data' => $companies->map(fn ($profile) => [
                    'id' => $profile->id,
                    'company_name' => $profile->company_name,
                    'logo_url' => $profile->logo ? asset('storage/' . $profile->logo) : null,
                    'website' => $profile->website,
                    'industry' => $profile->industry,
                    'employee_count' => $profile->employee_count,
                    'location' => $profile->location,
                    'description' => $profile->description,
                    'open_jobs_count' => $profile->job_listings_count,
                ]),
                'meta' => [
                    'current_page' => $companies->currentPage(),
                    'last_page' => $companies->lastPage(),
                    'per_page' => $companies->perPage(),
                    'total' => $companies->total(),
                ],
            ],
        ]);
    }

    public function filters(): JsonResponse
    {
        $industries = EmployerProfile::select('industry')
            ->selectRaw('count(*) as count')
            ->whereNotNull('industry')
            ->where('industry', '!=', '')
            ->groupBy('industry')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->industry,
                'count' => (int) $item->count,
            ]);

        $locations = EmployerProfile::select('location')
            ->selectRaw('count(*) as count')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->location,
                'value' => $item->location,
                'count' => (int) $item->count,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'industries' => $industries,
                'locations' => $locations,
            ],
        ]);
    }
}

