<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningProject;
use App\Services\Pdf\ThaiPdfFactory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mpdf\HTMLParserMode;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ThaiPdfFactory $pdfFactory) {}

    public function open(Request $request): Response|BinaryFileResponse
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'type' => ['required', Rule::in(['pt', 'blank', 'open', 'time', 'finance', 'survey', 'result', 'photo'])],
            'doc' => ['nullable', 'integer', 'between:0,30'],
        ]);

        if (! empty($data['project_id'])) {
            $projectModel = LearningProject::query()->findOrFail($data['project_id']);
            $this->authorize('view', $projectModel);
        }

        $this->loadPdfTemplates();

        $projectId = (int) ($data['project_id'] ?? 0);
        $project = $projectId ? pdf_project($projectId) : pdf_blank_project();
        $students = $projectId ? pdf_students($projectId) : [];
        $documentOwnerId = (int) ($project['created_by'] ?? $request->user()->getAuthIdentifier());
        pdf_define_org_context(pdf_document_profile($documentOwnerId ?: (int) $request->user()->getAuthIdentifier()));
        $key = $this->reportKey($data['type'], (int) ($data['doc'] ?? 0));

        $titles = $this->reportTitles();
        $isCertificate = $key === 'pt_14';
        $mpdf = $this->pdfFactory->make($isCertificate);

        $mpdf->SetTitle($titles[$key] ?? 'ชุดเอกสาร พต.');
        $mpdf->SetAuthor('Sena LPD - สกร.ระดับอำเภอเสนา');
        $mpdf->SetCreator('Sena LPD');
        $mpdf->WriteHTML('body{font-family:thsarabunnew135zws;color:#000}', HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML(pdf_native_css(), HTMLParserMode::HEADER_CSS);
        $html = $this->pdfFactory->prepareHtml(pdf_native_template($key, $project, $students));

        try {
            $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
            $content = $mpdf->Output('', Destination::STRING_RETURN);
        } catch (MpdfException $exception) {
            report($exception);
            abort(500, 'ไม่สามารถสร้างเอกสาร PDF ได้ กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง');
        }

        $filename = 'Sena-LPD-'.str_replace('_', '-', $key).'.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function loadPdfTemplates(): void
    {
        require_once resource_path('pdf/pdf_native_templates.php');
    }

    private function reportKey(string $type, int $document): string
    {
        $routes = [
            'open' => ['open_group', 'pt_6', 'invite_lecturer', 'pt_8'],
            'time' => ['pt_9_1', 'time_blank', 'time_blank_two', 'lecturer_time_blank'],
            'finance' => ['pt_18', 'pt_19'],
            'survey' => ['pt_12'],
            'result' => ['pt_13', 'pt_20', 'pt_14'],
            'photo' => ['photo_material', 'photo_activity'],
        ];
        $ptRoutes = [
            'pt_1', 'pt_2', 'pt_3', 'pt_4', 'pt_5', 'pt_6', 'pt_7', 'pt_8', 'pt_9_1', 'pt_9_2',
            'pt_10_2', 'pt_11', 'pt_12', 'pt_13', 'pt_14', 'pt_15', 'pt_16', 'pt_17', 'pt_18', 'pt_19',
            'pt_20', 'pt_21', 'pt_22',
        ];

        return match ($type) {
            'pt' => $ptRoutes[$document] ?? 'pt_1',
            'blank' => 'blank_pack',
            default => $routes[$type][$document] ?? 'pt_13',
        };
    }

    /** @return array<string, string> */
    private function reportTitles(): array
    {
        return [
            'pt_1' => 'พต.1 แบบสำรวจความต้องการ', 'pt_2' => 'พต.2 ใบสมัครผู้เรียน',
            'pt_3' => 'พต.3 หลักสูตรฝึกอบรม', 'pt_4' => 'พต.4 หนังสือเชิญวิทยากร',
            'pt_5' => 'พต.5 แบบเขียนโครงการฝึกอบรม', 'pt_6' => 'พต.6 บันทึกขออนุมัติโครงการ',
            'pt_7' => 'พต.7 บันทึกขออนุมัติจัดฝึกอบรม', 'pt_8' => 'พต.8 ทะเบียนรายชื่อผู้เรียน',
            'pt_9_1' => 'พต.9(1) บัญชีลงเวลาผู้เรียน', 'pt_9_2' => 'พต.9 บัญชีลงเวลาวิทยากร',
            'pt_10_2' => 'พต.10 แบบประเมินผลพร้อมคำอธิบาย', 'pt_11' => 'พต.11 บันทึกการนิเทศ',
            'pt_12' => 'พต.12 แบบประเมินความพึงพอใจ', 'pt_13' => 'พต.13 แบบรายงานผลการฝึกอบรม',
            'pt_14' => 'พต.14 ใบรับรองผ่านการฝึกอบรม', 'pt_15' => 'พต.15 หนังสือส่งหลักฐานเบิกจ่าย',
            'pt_16' => 'พต.16 บันทึกขอเบิกจ่าย', 'pt_17' => 'พต.17 สรุปงบหน้าเบิกเงิน',
            'pt_18' => 'พต.18 ใบสำคัญรับเงิน', 'pt_19' => 'พต.19 ใบสำคัญรับเงินและแบบ KTB',
            'pt_20' => 'พต.20 แบบติดตามผู้ผ่านการอบรม', 'pt_21' => 'พต.21 ประกาศแหล่งเรียนรู้',
            'pt_22' => 'พต.22 ข้อตกลงความร่วมมือ', 'time_blank' => 'ใบลงเวลาผู้เรียนฉบับเปล่า',
            'time_blank_two' => 'ใบลงเวลาผู้เรียน 2 วัน', 'lecturer_time_blank' => 'ใบลงเวลาวิทยากรฉบับเปล่า',
            'photo_material' => 'รายงานภาพวัสดุ', 'photo_activity' => 'รายงานภาพกิจกรรม',
            'open_group' => 'บันทึกขออนุมัติจัดฝึกอบรม (เปิดกลุ่ม)',
            'invite_lecturer' => 'หนังสือเชิญวิทยากรและแบบตอบรับ',
        ];
    }
}
