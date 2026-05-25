<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resume',
        'phone',
        'linkedin_url',
        'skills',
        'experience_years',
        'location',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getResumeUrlAttribute(): ?string
    {
        return $this->resume ? asset('storage/'.$this->resume) : null;
    }
}
