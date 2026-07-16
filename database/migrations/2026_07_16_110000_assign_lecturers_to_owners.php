<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lecturers', 'created_by')) {
            Schema::table('lecturers', function (Blueprint $table): void {
                $table->unsignedInteger('created_by')->nullable()->after('id')->index();
            });
        }

        DB::table('lecturers')
            ->whereNull('created_by')
            ->orderBy('id')
            ->chunkById(500, function ($lecturers): void {
                foreach ($lecturers as $lecturer) {
                    $ownerId = DB::table('projects')
                        ->join('users', 'users.id', '=', 'projects.created_by')
                        ->where('projects.lecturer_id', $lecturer->id)
                        ->orderByRaw("CASE WHEN users.role = 'subdistrict_admin' THEN 0 ELSE 1 END")
                        ->orderBy('projects.id')
                        ->value('projects.created_by');

                    if ($ownerId) {
                        DB::table('lecturers')->where('id', $lecturer->id)->update(['created_by' => $ownerId]);
                    }
                }
            }, 'id');

        $districtId = DB::table('users')
            ->where('role', 'district_admin')
            ->where('status', 'active')
            ->whereIn('school_id', array_filter([
                config('sena.district_school_id'),
                config('sena.legacy_district_school_id'),
            ]))
            ->value('id');

        if ($districtId) {
            DB::table('lecturers')->whereNull('created_by')->update(['created_by' => $districtId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lecturers', 'created_by')) {
            Schema::table('lecturers', function (Blueprint $table): void {
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
