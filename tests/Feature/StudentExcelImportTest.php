<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
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

    public function test_student_template_has_instructions_blank_input_and_separate_examples(): void
    {
        $workbook = IOFactory::load(storage_path('app/templates/student_import_template.xlsx'));

        $this->assertSame(['เริ่มที่นี่', 'นำเข้าผู้เรียน', 'ตัวอย่างข้อมูล'], $workbook->getSheetNames());
        $this->assertSame('เริ่มที่นี่', $workbook->getActiveSheet()->getTitle());
        $this->assertSame('คำนำหน้า*', $workbook->getSheetByName('นำเข้าผู้เรียน')->getCell('A1')->getValue());
        $this->assertNull($workbook->getSheetByName('นำเข้าผู้เรียน')->getCell('A2')->getValue());
        $this->assertSame('สมชาย', $workbook->getSheetByName('ตัวอย่างข้อมูล')->getCell('B2')->getValue());
    }

    public function test_students_can_be_imported_from_excel_and_are_owned_by_the_importer(): void
    {
        $user = $this->user();
        $file = $this->excelFile([
            ['นาย', 'สมชาย', 'ใจดี', 'ชาย', '1000000000001', ExcelDate::PHPToExcel(new \DateTimeImmutable('1990-04-12')), 'มัธยมศึกษา', 'ค้าขาย', 'ประชาชนทั่วไป', 150000, 'อำเภอเสนา', '0810000001', ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-07-16'))],
            ['นางสาว', 'สมหญิง', 'เรียนรู้', 'หญิง', '1000000000002', '21/08/2538', 'ปริญญาตรี', 'รับจ้าง', 'ประชาชนทั่วไป', 180000, 'อำเภอเสนา', '0810000002', '16/07/2569'],
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
        $firstStudent = Student::query()->where('id_card', '1000000000001')->firstOrFail();
        $secondStudent = Student::query()->where('id_card', '1000000000002')->firstOrFail();
        $this->assertSame('1990-04-12', $firstStudent->birthday->toDateString());
        $this->assertSame('2026-07-16', $firstStudent->registered_at->toDateString());
        $this->assertSame('0810000001', $firstStudent->phone);
        $this->assertSame('1995-08-21', $secondStudent->birthday->toDateString());
        $this->assertSame('2026-07-16', $secondStudent->registered_at->toDateString());
        $this->assertSame('0810000002', $secondStudent->phone);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.imported']);
    }

    public function test_invalid_excel_rows_do_not_create_partial_student_data(): void
    {
        $user = $this->user();
        $file = $this->excelFile([
            ['นาย', 'ข้อมูลดี', 'ทดสอบ', 'ชาย', '2000000000001', null, null, null, null, 0, null, null, null],
            ['ผิด', '', 'ทดสอบ', 'ไม่ถูกต้อง', '2000000000002', '31/02/2569', null, null, null, 0, null, null, '17/13/2569'],
        ]);

        $response = $this->actingAs($user)->post('/api/v1/students/import', ['file' => $file], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'แถว 3')
                && str_contains($message, 'วันเกิดไม่ถูกต้อง')
                && str_contains($message, 'วันที่ขึ้นทะเบียนไม่ถูกต้อง'));

        $this->assertStringNotContainsString('validation.', $response->json('message'));
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
