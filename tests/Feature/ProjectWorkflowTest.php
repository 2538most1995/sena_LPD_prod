<?php

namespace Tests\Feature;

use App\Models\ActivityPhoto;
use App\Models\Course;
use App\Models\LearningProject;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_approval_participants_and_scores_work_end_to_end(): void
    {
        $district = $this->user('DISTRICT01', 'district_admin');
        $subdistrict = $this->user('SUBDISTRICT01', 'subdistrict_admin', $district->id);
        $course = Course::query()->create([
            'created_by' => $district->id,
            'name' => 'หลักสูตร 12 ชั่วโมง',
            'category' => 'อาชีพ',
            'hours' => 12,
            'owner' => $district->school_name,
            'description' => 'ข้อมูลทดสอบ',
            'approval_status' => 'approved',
        ]);
        $lecturer = Lecturer::query()->create([
            'created_by' => $subdistrict->id,
            'prefix' => 'นางสาว', 'first_name' => 'วิทยา', 'last_name' => 'ทดสอบ',
            'id_card' => '1111111111111', 'expertise' => 'งานอาชีพ',
        ]);
        $student = Student::query()->create([
            'created_by' => $subdistrict->id,
            'prefix' => 'นาย', 'first_name' => 'ผู้เรียน', 'last_name' => 'ทดสอบ',
            'gender' => 'ชาย', 'id_card' => '2222222222222',
        ]);

        $response = $this->actingAs($subdistrict)->postJson('/api/v1/projects', $this->projectPayload($course->id, $lecturer->id));
        $response->assertCreated()
            ->assertJsonPath('data.approval_status', 'pending')
            ->assertJsonPath('data.format_type', 'หลักสูตร 10 ชั่วโมงขึ้นไป');
        $project = LearningProject::query()->firstOrFail();

        $this->actingAs($district)->postJson("/api/v1/projects/{$project->id}/review", [
            'status' => 'approved',
        ])->assertOk();

        $this->actingAs($subdistrict)->putJson("/api/v1/projects/{$project->id}/participants", [
            'student_ids' => [$student->id],
        ])->assertOk();
        $this->actingAs($subdistrict)->putJson("/api/v1/projects/{$project->id}/scores", [
            'scores' => [[
                'student_id' => $student->id,
                'knowledge' => 18,
                'skill' => 35,
                'attribute' => 38,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('project_students', ['project_id' => $project->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('scores', ['project_id' => $project->id, 'student_id' => $student->id, 'knowledge_score' => 18]);
        $this->actingAs($subdistrict)->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.students.0.scores.attribute', 38)
            ->assertJsonPath('data.students.0.score_recorded', true);

        $this->actingAs($subdistrict)->post("/api/v1/projects/{$project->id}/photos", [
            'photo_type' => 'activity',
            'photos' => [UploadedFile::fake()->image('activity.jpg', 1200, 900)],
            'captions' => ['ภาพทดสอบการจัดกิจกรรม'],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.0.caption', 'ภาพทดสอบการจัดกิจกรรม');

        $photo = ActivityPhoto::query()->firstOrFail();
        $this->actingAs($subdistrict)->getJson("/api/v1/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.photos.0.id', $photo->id)
            ->assertJsonPath('data.photos.0.caption', 'ภาพทดสอบการจัดกิจกรรม');
        $this->actingAs($subdistrict)->get("/api/v1/projects/{$project->id}/photos/{$photo->id}/file")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($subdistrict)->deleteJson("/api/v1/projects/{$project->id}/photos/{$photo->id}")
            ->assertOk();
        $this->assertDatabaseMissing('activity_photos', ['id' => $photo->id]);
    }

    private function projectPayload(int $courseId, int $lecturerId): array
    {
        return [
            'course_id' => $courseId,
            'lecturer_id' => $lecturerId,
            'title' => 'โครงการทดสอบเต็มระบบ',
            'objective' => 'ทดสอบกระบวนการจัดตั้งกลุ่ม',
            'format_type' => 'หลักสูตร 10 ชั่วโมงขึ้นไป',
            'place' => 'ศูนย์การเรียนรู้',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'start_time' => '09:00',
            'end_time' => '16:00',
            'fiscal_year' => 2569,
            'lecturer_cost' => 1200,
            'material_cost' => 800,
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
