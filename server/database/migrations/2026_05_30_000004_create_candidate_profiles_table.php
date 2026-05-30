<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resume')->nullable();
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('skills')->nullable();
            $table->unsignedInteger('experience_years')->nullable();
            $table->string('location')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};
