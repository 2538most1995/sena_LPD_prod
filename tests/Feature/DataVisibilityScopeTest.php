<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningProject;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DataVisibilityScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_courses_are_shared_with_subdistricts_in_the_same_district(): void
    {
        $district = $this->user('DISTRICT-ONE', 'district_admin');
        $owner = $this->user('SUBDISTRICT-OWNER', 'subdistrict_admin', $district->id);
        $consumer = $this->user('SUBDISTRICT-CONSUMER', 'subdistrict_admin', $district->id);
        $otherDistrict = $this->user('DISTRICT-TWO', 'district_admin');
        $outsider = $this->user('SUBDISTRICT-OUTSIDE', 'subdistrict_admin', $otherDistrict->id);

        $approved = $this->course($owner, 'หลักสูตรใช้ร่วมกัน', 'approved');
        $pending = $this->course($owner, 'หลักสูตรยังรออนุมัติ', 'pending');
        $outside = $this->course($outsider, 'หลักสูตรต่างอำเภอ', 'approved');

        $courseIds = collect($this->actingAs($consumer)->getJson('/api/v1/courses?per_page=100')
            ->assertOk()
            ->json('data'))->pluck('id');
        $this->assertSame([$approved->id], $courseIds->all());

        $referenceCourseIds = collect($this->actingAs($consumer)->getJson('/api/v1/references')
            ->assertOk()
            ->json('data.courses'))->pluck('id');
        $this->assertTrue($referenceCourseIds->contains($approved->id));
        $this->assertFalse($referenceCourseIds->contains($pending->id));
        $this->assertFalse($referenceCourseIds->contains($outside->id));

        $this->actingAs($consumer)->putJson("/api/v1/courses/{$approved->id}", [
            'name' => 'พยายามแก้ไขหลักสูตรร่วม',
            'category' => $approved->category,
            'hours' => $approved->hours,
            'owner' => $approved->owner,
            'description' => $approved->description,
        ])->assertForbidden();
    }

    public function test_students_are_isolated_by_subdistrict_and_visible_to_the_district(): void
    {
        $district = $this->user('DISTRICT-ONE', 'district_admin');
        $firstSubdistrict = $this->user('SUBDISTRICT-ONE', 'subdistrict_admin', $district->id);
        $secondSubdistrict = $this->user('SUBDISTRICT-TWO', 'subdistrict_admin', $district->id);

        $firstStudent = $this->actingAs($firstSubdistrict)->postJson('/api/v1/students', $this->studentPayload('1111111111111', 'หนึ่ง'))
            ->assertCreated()
            ->assertJsonPath('data.created_by', $firstSubdistrict->id)
            ->json('data');
        $secondStudent = $this->actingAs($secondSubdistrict)->postJson('/api/v1/students', $this->studentPayload('2222222222222', 'สอง'))
            ->assertCreated()
            ->assertJsonPath('data.created_by', $secondSubdistrict->id)
            ->json('data');

        $this->actingAs($firstSubdistrict)->getJson('/api/v1/students?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $firstStudent['id']);

        $this->actingAs($firstSubdistrict)->getJson('/api/v1/references')
            ->assertOk()
            ->assertJsonCount(1, 'data.students')
            ->assertJsonPath('data.students.0.id', $firstStudent['id']);

        $this->actingAs($district)->getJson('/api/v1/students?per_page=100')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $firstStudent['id']])
            ->assertJsonFragment(['id' => $secondStudent['id']]);

        $this->actingAs($firstSubdistrict)->putJson("/api/v1/students/{$secondStudent['id']}", $this->studentPayload('2222222222222', 'แก้ไขข้ามตำบล'))
            ->assertForbidden();
    }

    public function test_project_cannot_attach_a_student_owned_by_another_subdistrict(): void
    {
        $district = $this->user('DISTRICT-ONE', 'district_admin');
        $projectOwner = $this->user('SUBDISTRICT-ONE', 'subdistrict_admin', $district->id);
        $otherSubdistrict = $this->user('SUBDISTRICT-TWO', 'subdistrict_admin', $district->id);
        $otherStudent = Student::query()->create([
            'created_by' => $otherSubdistrict->id,
            ...$this->studentPayload('3333333333333', 'ต่างตำบล'),
        ]);
        $project = LearningProject::query()->create([
            'created_by' => $projectOwner->id,
            'course_id' => 1,
            'lecturer_id' => 1,
            'title' => 'กลุ่มของตำบลหนึ่ง',
            'place' => 'ศกร.ตำบลหนึ่ง',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'fiscal_year' => 2569,
            'approval_status' => 'approved',
        ]);

        $this->actingAs($projectOwner)->putJson("/api/v1/projects/{$project->id}/participants", [
            'student_ids' => [$otherStudent->id],
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'พบผู้เรียนที่ไม่ได้อยู่ในหน่วยงานเจ้าของกลุ่ม');
    }

    public function test_lecturers_are_isolated_by_subdistrict_and_visible_to_the_district(): void
    {
        $district = $this->user('DISTRICT-ONE', 'district_admin');
        $firstSubdistrict = $this->user('SUBDISTRICT-ONE', 'subdistrict_admin', $district->id);
        $secondSubdistrict = $this->user('SUBDISTRICT-TWO', 'subdistrict_admin', $district->id);
        $firstLecturer = Lecturer::query()->create([
            'created_by' => $firstSubdistrict->id,
            'prefix' => 'นาย', 'first_name' => 'วิทยากรหนึ่ง', 'last_name' => 'ทดสอบ',
            'id_card' => '4111111111111', 'expertise' => 'งานอาชีพ',
        ]);
        $secondLecturer = Lecturer::query()->create([
            'created_by' => $secondSubdistrict->id,
            'prefix' => 'นาง', 'first_name' => 'วิทยากรสอง', 'last_name' => 'ทดสอบ',
            'id_card' => '4222222222222', 'expertise' => 'งานดิจิทัล',
        ]);

        $this->actingAs($firstSubdistrict)->getJson('/api/v1/lecturers?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $firstLecturer->id);

        $this->actingAs($firstSubdistrict)->getJson('/api/v1/references')
            ->assertOk()
            ->assertJsonCount(1, 'data.lecturers')
            ->assertJsonPath('data.lecturers.0.id', $firstLecturer->id);

        $this->actingAs($district)->getJson('/api/v1/lecturers?per_page=100')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $firstLecturer->id])
            ->assertJsonFragment(['id' => $secondLecturer->id]);

        $this->actingAs($firstSubdistrict)->putJson("/api/v1/lecturers/{$secondLecturer->id}", [
            'prefix' => 'นาง', 'first_name' => 'แก้ไขข้ามตำบล', 'last_name' => 'ทดสอบ',
            'id_card' => '4222222222222', 'expertise' => 'งานดิจิทัล',
        ])->assertForbidden();
    }

    public function test_same_lecturer_id_card_can_be_registered_by_different_subdistricts_only(): void
    {
        $district = $this->user('DISTRICT-ONE', 'district_admin');
        $firstSubdistrict = $this->user('SUBDISTRICT-ONE', 'subdistrict_admin', $district->id);
        $secondSubdistrict = $this->user('SUBDISTRICT-TWO', 'subdistrict_admin', $district->id);
        $payload = [
            'prefix' => 'นาย',
            'first_name' => 'วิทยากรร่วม',
            'last_name' => 'สองตำบล',
            'id_card' => '4999999999999',
            'expertise' => 'การใช้เทคโนโลยี',
        ];

        $this->actingAs($firstSubdistrict)->postJson('/api/v1/lecturers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.created_by', $firstSubdistrict->id);

        $this->actingAs($secondSubdistrict)->postJson('/api/v1/lecturers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.created_by', $secondSubdistrict->id);

        $this->actingAs($firstSubdistrict)->postJson('/api/v1/lecturers', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'เลขประจำตัวประชาชนนี้มีอยู่ในทะเบียนวิทยากรของตำบลนี้แล้ว');

        $this->assertDatabaseCount('lecturers', 2);
    }

    private function course(User $owner, string $name, string $status): Course
    {
        return Course::query()->create([
            'created_by' => $owner->id,
            'name' => $name,
            'category' => 'อาชีพ',
            'hours' => 5,
            'owner' => $owner->school_name,
            'description' => 'รายละเอียดหลักสูตร',
            'approval_status' => $status,
        ]);
    }

    private function studentPayload(string $idCard, string $firstName): array
    {
        return [
            'prefix' => 'นาย',
            'first_name' => $firstName,
            'last_name' => 'ทดสอบ',
            'gender' => 'ชาย',
            'id_card' => $idCard,
        ];
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
