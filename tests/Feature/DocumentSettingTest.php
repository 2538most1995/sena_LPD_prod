<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_subdistrict_admin_receives_defaults_from_its_account_and_parent(): void
    {
        $district = User::factory()->create([
            'role' => 'district_admin',
            'school_name' => 'ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา',
            'teacher_name' => 'นายผู้อำนวยการ ทดสอบ',
            'position' => 'ผู้อำนวยการ สกร.ระดับอำเภอเสนา',
        ]);
        $user = User::factory()->create([
            'parent_id' => $district->id,
            'role' => 'subdistrict_admin',
            'school_name' => 'ศกร.ระดับตำบลบ้านแพน',
            'teacher_name' => 'นางครู ผู้รับผิดชอบ',
            'position' => 'ครู กศน.ตำบล',
            'phone' => '035-000-001',
        ]);

        $this->actingAs($user)->getJson('/api/v1/document-settings')
            ->assertOk()
            ->assertJsonPath('data.learning_center_name', 'ศกร.ระดับตำบลบ้านแพน')
            ->assertJsonPath('data.district_office_name', 'ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา')
            ->assertJsonPath('data.registrar_name', 'นางครู ผู้รับผิดชอบ')
            ->assertJsonPath('data.director_name', 'นายผู้อำนวยการ ทดสอบ');
    }

    public function test_subdistrict_admin_can_save_document_settings(): void
    {
        $user = User::factory()->create(['role' => 'subdistrict_admin']);
        $payload = [
            'learning_center_name' => 'ศกร.ระดับตำบลสามกอ',
            'district_office_name' => 'ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา',
            'district_office_short_name' => 'สกร.ระดับอำเภอเสนา',
            'province_office_name' => 'สำนักงานส่งเสริมการเรียนรู้ประจำจังหวัดพระนครศรีอยุธยา',
            'document_no_prefix' => 'ศธ 07093.05',
            'office_address' => 'ตำบลสามกอ อำเภอเสนา จังหวัดพระนครศรีอยุธยา 13110',
            'phone' => '035-000-002',
            'fax' => '035-000-003',
            'owner_unit' => 'งานการเรียนรู้เพื่อการพัฒนาตนเอง',
            'registrar_name' => 'นางสาวผู้รับ สมัคร',
            'registrar_position' => 'ครูศูนย์การเรียนรู้',
            'responsible_name' => 'นายผู้รับผิด ชอบ',
            'responsible_position' => 'ครูศูนย์การเรียนรู้',
            'director_name' => 'นายผู้บริหาร ระบบ',
            'director_position' => 'ผู้อำนวยการ สกร.ระดับอำเภอเสนา',
        ];

        $this->actingAs($user)->postJson('/api/v1/document-settings', $payload)
            ->assertOk()
            ->assertJsonPath('data.registrar_name', 'นางสาวผู้รับ สมัคร');

        $this->assertDatabaseHas('document_settings', [
            'user_id' => $user->id,
            'learning_center_name' => 'ศกร.ระดับตำบลสามกอ',
            'registrar_name' => 'นางสาวผู้รับ สมัคร',
        ]);
    }

    public function test_document_settings_menu_api_is_limited_to_subdistrict_admin(): void
    {
        $district = User::factory()->create(['role' => 'district_admin']);

        $this->actingAs($district)->getJson('/api/v1/document-settings')->assertForbidden();
    }
}
