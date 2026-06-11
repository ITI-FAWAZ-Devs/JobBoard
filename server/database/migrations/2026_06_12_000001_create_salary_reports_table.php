<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location')->default('Remote');
            $table->string('level');
            $table->string('category');
            $table->integer('median_salary');
            $table->integer('min_salary');
            $table->integer('max_salary');
            $table->integer('report_count')->default(1);
            $table->string('currency')->default('EGP');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_reports');
    }
};
