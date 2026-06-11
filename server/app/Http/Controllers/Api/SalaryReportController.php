<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalaryReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SalaryReport::query();

        // Search query
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('location', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }

        // Filter by location
        if ($request->filled('location')) {
            $loc = strtolower($request->input('location'));
            if ($loc !== 'all locations' && $loc !== 'all') {
                if ($loc === 'egypt') {
                    $query->where(function ($sub) {
                        $sub->where('location', 'like', '%egypt%')
                            ->orWhere('location', 'like', '%cairo%')
                            ->orWhere('location', 'like', '%alexandria%');
                    });
                } elseif ($loc === 'remote') {
                    $query->where('location', 'like', '%remote%');
                } elseif ($loc === 'usa') {
                    $query->where(function ($sub) {
                        $sub->where('location', 'like', '%usa%')
                            ->orWhere('location', 'like', '%san francisco%')
                            ->orWhere('location', 'like', '%new york%');
                    });
                } elseif ($loc === 'uk') {
                    $query->where(function ($sub) {
                        $sub->where('location', 'like', '%uk%')
                            ->orWhere('location', 'like', '%london%');
                    });
                } else {
                    $query->where('location', 'like', "%{$loc}%");
                }
            }
        }

        // Filter by category
        if ($request->filled('category')) {
            $categories = is_array($request->input('category'))
                ? $request->input('category')
                : explode(',', $request->input('category'));
            $query->whereIn('category', $categories);
        }

        // Filter by experience level
        if ($request->filled('level')) {
            $levels = is_array($request->input('level'))
                ? $request->input('level')
                : explode(',', $request->input('level'));
            $query->whereIn('level', $levels);
        }

        $reports = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Salary reports retrieved successfully.',
            'data' => $reports->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'location' => $r->location,
                'level' => $r->level,
                'category' => $r->category,
                'medianSalary' => (int) $r->median_salary,
                'minSalary' => (int) $r->min_salary,
                'maxSalary' => (int) $r->max_salary,
                'reportCount' => (int) $r->report_count,
                'currency' => $r->currency,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'integer', 'min:1'],
        ]);

        $baseSalary = $validated['salary'];
        $deviance = (int) round($baseSalary * 0.25);
        $minVal = $baseSalary - $deviance;
        $maxVal = $baseSalary + $deviance;

        $report = SalaryReport::create([
            'title' => $validated['title'],
            'location' => $validated['location'],
            'level' => $validated['level'],
            'category' => $validated['category'],
            'median_salary' => $baseSalary,
            'min_salary' => $minVal > 0 ? $minVal : (int) round($baseSalary * 0.7),
            'max_salary' => $maxVal,
            'report_count' => 1,
            'currency' => 'EGP',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Salary report created successfully.',
            'data' => [
                'id' => $report->id,
                'title' => $report->title,
                'location' => $report->location,
                'level' => $report->level,
                'category' => $report->category,
                'medianSalary' => (int) $report->median_salary,
                'minSalary' => (int) $report->min_salary,
                'maxSalary' => (int) $report->max_salary,
                'reportCount' => (int) $report->report_count,
                'currency' => $report->currency,
            ],
        ], 201);
    }

    public function topCompanies(): JsonResponse
    {
        $top = \App\Models\EmployerProfile::has('jobListings')
            ->withCount(['jobListings' => function ($q) {
                $q->where('status', 'approved');
            }])
            ->get()
            ->map(function ($profile) {
                $maxSalary = \App\Models\JobListing::where('employer_profile_id', $profile->id)
                    ->where('status', 'approved')
                    ->max('salary_max');
                
                return [
                    'name' => $profile->company_name,
                    'initial' => strtoupper(substr($profile->company_name, 0, 1)),
                    'count' => $profile->job_listings_count,
                    'maxSalary' => $maxSalary ? (round($maxSalary / 1000) . 'k EGP') : 'N/A',
                    'raw_max_salary' => $maxSalary ? (float)$maxSalary : 0,
                ];
            })
            ->sortByDesc('raw_max_salary')
            ->take(3)
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => $top,
        ]);
    }
}

