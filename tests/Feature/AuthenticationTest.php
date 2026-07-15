<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_log_in_with_school_id(): void
    {
        $user = User::query()->create([
            'school_id' => 'TEST001',
            'password_hash' => Hash::make('correct-password'),
            'display_name' => 'ผู้ดูแลทดสอบ',
            'role' => 'district_admin',
            'status' => 'active',
        ]);

        $response = $this->postJson('/next-api/auth/login', [
            'school_id' => 'TEST001',
            'password' => 'correct-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'district_admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::query()->create([
            'school_id' => 'DISABLED001',
            'password_hash' => Hash::make('correct-password'),
            'display_name' => 'บัญชีปิดใช้งาน',
            'role' => 'subdistrict_admin',
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/next-api/auth/login', [
            'school_id' => 'DISABLED001',
            'password' => 'correct-password',
        ]);

        $this->assertSame(422, $response->getStatusCode());

        $this->assertGuest();
    }

    public function test_protected_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/courses')->assertUnauthorized();
    }
}
