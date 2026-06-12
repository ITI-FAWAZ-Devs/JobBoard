<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_listing_id',
        'candidate_profile_id',
        'status',
        'cover_letter',
        'resume_path',
    ];

    public function getResumeUrlAttribute(): ?string
    {
        return $this->resume_path ? asset('storage/'.$this->resume_path) : null;
    }

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function candidateProfile()
    {
        return $this->belongsTo(CandidateProfile::class);
    }
}
