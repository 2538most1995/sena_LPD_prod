<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_template_can_be_downloaded(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/api/v1/students/import-template')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_students_can_be_imported_from_excel_and_are_owned_by_the_importer(): void
    {
        $user = $this->user();
        $file = $this->excelFile([
            ['นาย', 'สมชาย', 'ใจดี', 'ชาย', '1000000000001', '1990-04-12', 'มัธยมศึกษา', 'ค้าขาย', 'ประชาชนทั่วไป', 150000, 'อำเภอเสนา', '0810000001', '2026-07-16'],
            ['นางสาว', 'สมหญิง', 'เรียนรู้', 'หญิง', '1000000000002', '1995-08-21', 'ปริญญาตรี', 'รับจ้าง', 'ประชาชนทั่วไป', 180000, 'อำเภอเสนา', '0810000002', '2026-07-16'],
        ]);

        $this->actingAs($user)->post('/api/v1/students/import', ['file' => $file], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.imported', 2);

        $this->assertDatabaseCount('students', 2);
        $this->assertDatabaseHas('students', [
            'created_by' => $user->id,
            'id_card' => '1000000000001',
            'first_name' => 'สมชาย',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.imported']);
    }

    public function test_invalid_excel_rows_do_not_create_partial_student_data(): void
    {
        $user = $this->user();
        $file = $this->excelFile([
            ['นาย', 'ข้อมูลดี', 'ทดสอบ', 'ชาย', '2000000000001', null, null, null, null, 0, null, null, null],
            ['ผิด', '', 'ทดสอบ', 'ไม่ถูกต้อง', '2000000000002', null, null, null, null, 0, null, null, null],
        ]);

        $this->actingAs($user)->post('/api/v1/students/import', ['file' => $file], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'แถว 3'));

        $this->assertDatabaseCount('students', 0);
    }

    private function excelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('นำเข้าผู้เรียน');
        $sheet->fromArray([[
            'คำนำหน้า*', 'ชื่อ*', 'นามสกุล*', 'เพศ*', 'เลขประจำตัวประชาชน*',
            'วันเกิด', 'การศึกษา', 'อาชีพ', 'กลุ่มเป้าหมาย', 'รายได้ต่อปี',
            'ที่อยู่', 'โทรศัพท์', 'วันที่ขึ้นทะเบียน',
        ], ...$rows]);
        $path = tempnam(sys_get_temp_dir(), 'student-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'students.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function user(): User
    {
        $district = User::query()->create([
            'school_id' => 'DISTRICT', 'password_hash' => Hash::make('password-123'),
            'display_name' => 'District', 'school_name' => 'อำเภอทดสอบ', 'teacher_name' => 'District',
            'role' => 'district_admin', 'status' => 'active',
        ]);

        return User::query()->create([
            'parent_id' => $district->id,
            'school_id' => 'SUBDISTRICT', 'password_hash' => Hash::make('password-123'),
            'display_name' => 'Subdistrict', 'school_name' => 'ตำบลทดสอบ', 'teacher_name' => 'Subdistrict',
            'role' => 'subdistrict_admin', 'status' => 'active',
        ]);
    }
}
