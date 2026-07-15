<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate([
            'school_id' => 'DEV-SENA-LPD',
        ], [
            'password_hash' => password_hash('change-me-before-use', PASSWORD_DEFAULT),
            'display_name' => 'ผู้ดูแลระบบสำหรับพัฒนา',
            'school_name' => 'Sena LPD Development',
            'role' => 'super_admin',
            'status' => 'inactive',
        ]);
    }
}
