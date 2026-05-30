<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

class JobListing extends Model
{
    use Searchable, SoftDeletes;

    protected $fillable = [
        'employer_profile_id',
        'category_id',
        'title',
        'description',
        'requirements',
        'benefits',
        'location',
        'salary_min',
        'salary_max',
        'work_type',
        'deadline',
        'status',
        'views_count',
        'rejection_reason',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'approved_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    #[SearchUsingFullText(['title', 'description', 'requirements', 'benefits', 'location'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'benefits' => $this->benefits,
            'location' => $this->location,
            'work_type' => $this->work_type,
            'category_id' => $this->category_id,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'status' => $this->status,
            'created_at' => $this->created_at?->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'approved';
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForEmployer($query, int $employerProfileId)
    {
        return $query->where('employer_profile_id', $employerProfileId);
    }
}
