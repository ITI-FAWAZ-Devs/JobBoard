<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'location',
        'level',
        'category',
        'median_salary',
        'min_salary',
        'max_salary',
        'report_count',
        'currency',
    ];
}
