<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class StudentImportController extends Controller
{
    private const HEADERS = [
        'คำนำหน้า*' => 'prefix',
        'ชื่อ*' => 'first_name',
        'นามสกุล*' => 'last_name',
        'เพศ*' => 'gender',
        'เลขประจำตัวประชาชน*' => 'id_card',
        'วันเกิด' => 'birthday',
        'การศึกษา' => 'education',
        'อาชีพ' => 'career',
        'กลุ่มเป้าหมาย' => 'target_group',
        'รายได้ต่อปี' => 'annual_income',
        'ที่อยู่' => 'address',
        'โทรศัพท์' => 'phone',
        'วันที่ขึ้นทะเบียน' => 'registered_at',
    ];

    public function __construct(private readonly AuditService $audit) {}

    public function template(Request $request): BinaryFileResponse
    {
        $this->authorize('create', Student::class);
        $path = storage_path('app/templates/student_import_template.xlsx');
        abort_unless(is_file($path), 404, 'ไม่พบไฟล์เทมเพลต');

        return response()->download($path, 'เทมเพลตนำเข้าผู้เรียน.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Student::class);
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'กรุณาเลือกไฟล์ Excel',
            'file.mimes' => 'รองรับเฉพาะไฟล์ .xlsx หรือ .xls',
            'file.max' => 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB',
        ]);

        try {
            $reader = IOFactory::createReaderForFile($request->file('file')->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($request->file('file')->getRealPath());
        } catch (Throwable) {
            return response()->json(['message' => 'ไม่สามารถเปิดไฟล์ Excel ได้ กรุณาใช้เทมเพลตของระบบ'], 422);
        }

        $sheet = $spreadsheet->getSheetByName('นำเข้าผู้เรียน') ?? $spreadsheet->getSheet(0);
        $headerColumns = [];
        for ($column = 1; $column <= count(self::HEADERS); $column++) {
            $header = trim((string) $sheet->getCell([$column, 1])->getFormattedValue());
            if (isset(self::HEADERS[$header])) {
                $headerColumns[self::HEADERS[$header]] = $column;
            }
        }

        $missingHeaders = array_diff(array_values(self::HEADERS), array_keys($headerColumns));
        if ($missingHeaders !== []) {
            return response()->json(['message' => 'หัวคอลัมน์ไม่ตรงกับเทมเพลต กรุณาดาวน์โหลดเทมเพลตใหม่จากระบบ'], 422);
        }

        $rows = [];
        $errors = [];
        $seenIdCards = [];
        $highestRow = $sheet->getHighestDataRow();
        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $data = [];
            foreach ($headerColumns as $field => $column) {
                $cell = $sheet->getCell([$column, $rowNumber]);
                $data[$field] = in_array($field, ['birthday', 'registered_at'], true)
                    ? $this->dateValue($cell)
                    : $this->textValue($cell, $field);
            }

            if (collect($data)->every(fn ($value): bool => $value === null || $value === '')) {
                continue;
            }
            if (count($rows) >= 1000) {
                $errors[] = 'ไฟล์มีข้อมูลเกิน 1,000 รายการ';
                break;
            }

            $data['annual_income'] = $data['annual_income'] === '' ? 0 : str_replace(',', '', (string) $data['annual_income']);
            $validator = Validator::make($data, [
                'prefix' => ['required', Rule::in(['นาย', 'นาง', 'นางสาว'])],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'gender' => ['required', Rule::in(['ชาย', 'หญิง', 'ไม่ระบุ'])],
                'id_card' => ['required', 'string', 'max:20', Rule::unique('students', 'id_card')],
                'birthday' => ['nullable', 'date_format:Y-m-d', 'before:today'],
                'education' => ['nullable', 'string', 'max:100'],
                'career' => ['nullable', 'string', 'max:100'],
                'target_group' => ['nullable', 'string', 'max:100'],
                'annual_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
                'address' => ['nullable', 'string', 'max:2000'],
                'phone' => ['nullable', 'string', 'max:30'],
                'registered_at' => ['nullable', 'date_format:Y-m-d'],
            ], [
                'required' => 'กรุณากรอก :attribute',
                'in' => ':attribute ต้องเลือกจากรายการที่กำหนดในเทมเพลต',
                'string' => ':attribute ต้องเป็นข้อความ',
                'max' => ':attribute มีความยาวหรือค่ามากเกินกำหนด',
                'id_card.unique' => 'เลขประจำตัวประชาชนนี้มีอยู่ในระบบแล้ว',
                'birthday.date_format' => 'วันเกิดไม่ถูกต้อง ตัวอย่าง 21/08/2538 หรือ 1995-08-21',
                'birthday.before' => 'วันเกิดต้องเป็นวันที่ก่อนวันนี้',
                'registered_at.date_format' => 'วันที่ขึ้นทะเบียนไม่ถูกต้อง ตัวอย่าง 16/07/2569 หรือ 2026-07-16',
                'annual_income.numeric' => 'รายได้ต่อปีต้องเป็นตัวเลข',
                'annual_income.min' => 'รายได้ต่อปีต้องไม่น้อยกว่า 0',
                'annual_income.max' => 'รายได้ต่อปีมีค่ามากเกินกำหนด',
            ], [
                'prefix' => 'คำนำหน้า', 'first_name' => 'ชื่อ', 'last_name' => 'นามสกุล',
                'gender' => 'เพศ', 'id_card' => 'เลขประจำตัวประชาชน', 'birthday' => 'วันเกิด',
                'education' => 'การศึกษา', 'career' => 'อาชีพ', 'target_group' => 'กลุ่มเป้าหมาย',
                'annual_income' => 'รายได้ต่อปี', 'address' => 'ที่อยู่', 'phone' => 'โทรศัพท์',
                'registered_at' => 'วันที่ขึ้นทะเบียน',
            ]);

            if (isset($seenIdCards[$data['id_card']])) {
                $validator->errors()->add('id_card', 'เลขประจำตัวประชาชนซ้ำกับแถว '.$seenIdCards[$data['id_card']]);
            }
            if ($validator->fails()) {
                $errors[] = 'แถว '.$rowNumber.': '.implode(', ', $validator->errors()->all());

                continue;
            }

            $seenIdCards[$data['id_card']] = $rowNumber;
            $rows[] = $data;
        }

        if ($errors !== []) {
            $message = "พบข้อมูลที่ต้องแก้ไข:\n• ".implode("\n• ", array_slice($errors, 0, 12));
            if (count($errors) > 12) {
                $message .= "\n• และข้อผิดพลาดอีก ".(count($errors) - 12).' รายการ';
            }

            return response()->json(['message' => $message], 422);
        }
        if ($rows === []) {
            return response()->json(['message' => 'ไม่พบข้อมูลผู้เรียนในไฟล์'], 422);
        }

        DB::transaction(function () use ($request, $rows): void {
            foreach ($rows as $data) {
                Student::query()->create([...$data, 'created_by' => $request->user()->id]);
            }
            $this->audit->record('student.imported', null, null, [
                'count' => count($rows),
                'file_name' => mb_substr($request->file('file')->getClientOriginalName(), 0, 255),
            ]);
        });

        return response()->json([
            'message' => 'นำเข้าผู้เรียนสำเร็จ '.count($rows).' รายการ',
            'data' => ['imported' => count($rows)],
        ]);
    }

    private function textValue(Cell $cell, string $field): string
    {
        $value = $cell->getValue();
        if (in_array($field, ['id_card', 'phone'], true) && is_numeric($value)) {
            return sprintf('%.0f', (float) $value);
        }

        return trim((string) $cell->getFormattedValue());
    }

    private function dateValue(Cell $cell): ?string
    {
        $value = $cell->getValue();
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value >= 5000 && (float) $value <= 100000) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $text = strtr(trim((string) $cell->getFormattedValue()), [
            '๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4',
            '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9',
        ]);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $matches)) {
            return $this->normalizedDate((int) $matches[1], (int) $matches[2], (int) $matches[3]) ?? $text;
        }
        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $text, $matches)) {
            return $this->normalizedDate((int) $matches[3], (int) $matches[2], (int) $matches[1]) ?? $text;
        }

        return $text;
    }

    private function normalizedDate(int $year, int $month, int $day): ?string
    {
        if ($year >= 2400) {
            $year -= 543;
        }
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::create($year, $month, $day)->format('Y-m-d');
    }
}
