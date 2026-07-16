<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $activeDistricts = DB::table('users')
            ->where('role', 'district_admin')
            ->where('status', 'active')
            ->select('id', 'school_name')
            ->get();

        foreach ($activeDistricts as $district) {
            $aliasIds = DB::table('users')
                ->where('role', 'district_admin')
                ->where('school_name', $district->school_name)
                ->where('id', '!=', $district->id)
                ->pluck('id');

            if ($aliasIds->isEmpty()) {
                continue;
            }

            DB::table('users')
                ->where('role', 'subdistrict_admin')
                ->whereIn('parent_id', $aliasIds)
                ->update(['parent_id' => $district->id]);
        }
    }

    public function down(): void
    {
        // This migration repairs ownership links and intentionally keeps them intact.
    }
};
