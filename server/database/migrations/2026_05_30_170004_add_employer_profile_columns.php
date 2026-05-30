<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table): void {
            $table->string('cover_photo')->nullable()->after('logo');
            $table->string('industry')->nullable()->after('website');
            $table->string('employee_count')->nullable()->after('industry');
            $table->json('perks')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table): void {
            $table->dropColumn(['cover_photo', 'industry', 'employee_count', 'perks']);
        });
    }
};
