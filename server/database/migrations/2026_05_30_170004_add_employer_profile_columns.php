<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table): void {
            if (!Schema::hasColumn('employer_profiles', 'cover_photo')) {
                $table->string('cover_photo')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('employer_profiles', 'industry')) {
                $table->string('industry')->nullable()->after('website');
            }
            if (!Schema::hasColumn('employer_profiles', 'employee_count')) {
                $table->string('employee_count')->nullable()->after('industry');
            }
            if (!Schema::hasColumn('employer_profiles', 'perks')) {
                $table->json('perks')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employer_profiles', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('employer_profiles', 'cover_photo')) {
                $columns[] = 'cover_photo';
            }
            if (Schema::hasColumn('employer_profiles', 'industry')) {
                $columns[] = 'industry';
            }
            if (Schema::hasColumn('employer_profiles', 'employee_count')) {
                $columns[] = 'employee_count';
            }
            if (Schema::hasColumn('employer_profiles', 'perks')) {
                $columns[] = 'perks';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
