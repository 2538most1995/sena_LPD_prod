<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourseApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_subdistrict_course_is_sent_to_its_district_and_can_be_approved(): void
    {
        $district = $this->user('DISTRICT01', 'district_admin');
        $subdistrict = $this->user('SUBDISTRICT01', 'subdistrict_admin', $district->id);

        $response = $this->actingAs($subdistrict)->postJson('/api/v1/courses', [
            'name' => 'หลักสูตรทดสอบกระบวนการอนุมัติ',
            'category' => 'อาชีพเฉพาะทาง',
            'hours' => 9,
            'owner' => $subdistrict->school_name,
            'description' => 'รายละเอียดหลักสูตรสำหรับทดสอบระบบ',
        ]);

        $response->assertCreated()->assertJsonPath('data.approval_status', 'pending');
        $course = Course::query()->firstOrFail();
        $this->assertDatabaseHas('notifications', ['user_id' => $district->id, 'is_read' => false]);

        $this->actingAs($district)->getJson('/api/v1/approvals')
            ->assertOk()
            ->assertJsonPath('data.pending.courses.0.id', $course->id);

        $this->actingAs($district)->postJson("/api/v1/courses/{$course->id}/review", [
            'status' => 'approved',
            'note' => 'ตรวจสอบแล้ว',
        ])->assertOk();

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'approval_status' => 'approved', 'reviewed_by' => $district->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $subdistrict->id, 'title' => 'หลักสูตรได้รับการอนุมัติ']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'course.reviewed', 'subject_id' => $course->id]);
    }

    public function test_other_district_cannot_review_course(): void
    {
        $district = $this->user('DISTRICT01', 'district_admin');
        $otherDistrict = $this->user('DISTRICT02', 'district_admin');
        $subdistrict = $this->user('SUBDISTRICT01', 'subdistrict_admin', $district->id);
        $course = Course::query()->create([
            'created_by' => $subdistrict->id,
            'name' => 'หลักสูตรเฉพาะสังกัด',
            'category' => 'ดิจิทัล',
            'hours' => 10,
            'owner' => $subdistrict->school_name,
            'description' => 'ข้อมูลทดสอบ',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($otherDistrict)->postJson("/api/v1/courses/{$course->id}/review", [
            'status' => 'approved',
        ])->assertForbidden();
    }

    private function user(string $schoolId, string $role, ?int $parentId = null): User
    {
        return User::query()->create([
            'parent_id' => $parentId,
            'school_id' => $schoolId,
            'password_hash' => Hash::make('password-123'),
            'display_name' => $schoolId,
            'school_name' => 'สถานศึกษา '.$schoolId,
            'teacher_name' => 'ผู้ดูแล '.$schoolId,
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
