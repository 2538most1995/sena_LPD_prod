<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LegacyStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_and_replace_private_photo(): void
    {
        $user = User::factory()->create([
            'display_name' => 'ผู้ดูแลเดิม',
            'teacher_name' => 'นายทดสอบ ระบบ',
        ]);
        $storage = app(LegacyStorage::class);

        $firstResponse = $this->actingAs($user)->post('/api/v1/profile', $this->payload([
            'display_name' => 'ผู้ดูแลสถานศึกษา',
            'password' => 'new-password-123',
            'photo' => UploadedFile::fake()->image('profile-one.jpg', 640, 640),
        ]), ['Accept' => 'application/json']);

        $firstResponse->assertOk()
            ->assertJsonPath('message', 'บันทึกโปรไฟล์เรียบร้อย')
            ->assertJsonPath('data.display_name', 'ผู้ดูแลสถานศึกษา')
            ->assertJsonStructure(['data' => ['photo_path', 'photo_url', 'updated_at']]);

        $firstPath = $user->fresh()->photo_path;
        $this->assertNotNull($storage->absolute($firstPath));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password_hash));

        $this->actingAs($user->fresh())->get('/api/v1/profile/photo')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $secondResponse = $this->actingAs($user->fresh())->post('/api/v1/profile', $this->payload([
            'display_name' => 'ผู้ดูแลฉบับแก้ไข',
            'photo' => UploadedFile::fake()->image('profile-two.png', 720, 720),
        ]), ['Accept' => 'application/json']);

        $secondResponse->assertOk()->assertJsonPath('data.display_name', 'ผู้ดูแลฉบับแก้ไข');
        $secondPath = $user->fresh()->photo_path;
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertNull($storage->absolute($firstPath));
        $this->assertNotNull($storage->absolute($secondPath));

        $storage->delete($secondPath);
    }

    public function test_invalid_photo_does_not_replace_existing_photo(): void
    {
        $user = User::factory()->create();
        $storage = app(LegacyStorage::class);
        $existing = $storage->store(UploadedFile::fake()->image('existing.jpg'), 'profiles');
        $user->update(['photo_path' => $existing]);

        $this->actingAs($user)->post('/api/v1/profile', $this->payload([
            'photo' => UploadedFile::fake()->create('profile.pdf', 20, 'application/pdf'),
        ]), ['Accept' => 'application/json'])->assertUnprocessable();

        $this->assertSame($existing, $user->fresh()->photo_path);
        $this->assertNotNull($storage->absolute($existing));
        $storage->delete($existing);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'display_name' => 'ผู้ดูแลสถานศึกษา',
            'school_name' => 'ศกร.ระดับตำบลสามกอ',
            'teacher_name' => 'นายทดสอบ ระบบ',
            'position' => 'ครู ศกร.ระดับตำบล',
            'address_line' => 'ตำบลสามกอ อำเภอเสนา',
            'subdistrict' => 'สามกอ',
            'district' => 'เสนา',
            'province' => 'พระนครศรีอยุธยา',
            'postal_code' => '13110',
            'phone' => '035-000-001',
            'latitude' => '14.3260000',
            'longitude' => '100.4040000',
        ], $overrides);
    }
}
