<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $primaryId = DB::table('users')
            ->where('school_id', config('sena.district_school_id'))
            ->value('id');
        $legacyId = DB::table('users')
            ->where('school_id', config('sena.legacy_district_school_id'))
            ->value('id');

        if (! $primaryId || ! $legacyId || (int) $primaryId === (int) $legacyId) {
            return;
        }

        DB::transaction(function () use ($primaryId, $legacyId): void {
            DB::table('users')
                ->where('role', 'subdistrict_admin')
                ->where('parent_id', $legacyId)
                ->update(['parent_id' => $primaryId]);

            DB::table('users')->where('id', $primaryId)->update(['parent_id' => null]);
            DB::table('users')->where('id', $legacyId)->update([
                'parent_id' => null,
                'status' => 'inactive',
            ]);

            DB::table('courses')->where('created_by', $legacyId)->update(['created_by' => $primaryId]);
            DB::table('projects')->where('created_by', $legacyId)->update(['created_by' => $primaryId]);
            DB::table('notifications')->where('user_id', $legacyId)->update(['user_id' => $primaryId]);
        });
    }

    public function down(): void
    {
        $primaryId = DB::table('users')
            ->where('school_id', config('sena.district_school_id'))
            ->value('id');
        $legacyId = DB::table('users')
            ->where('school_id', config('sena.legacy_district_school_id'))
            ->value('id');

        if (! $primaryId || ! $legacyId) {
            return;
        }

        DB::transaction(function () use ($primaryId, $legacyId): void {
            DB::table('users')
                ->where('role', 'subdistrict_admin')
                ->where('parent_id', $primaryId)
                ->update(['parent_id' => $legacyId]);
            DB::table('users')->where('id', $legacyId)->update(['status' => 'active']);
            DB::table('courses')->where('created_by', $primaryId)->update(['created_by' => $legacyId]);
            DB::table('projects')->where('created_by', $primaryId)->update(['created_by' => $legacyId]);
        });
    }
};
