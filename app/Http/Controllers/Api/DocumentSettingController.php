<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentSetting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentSettingController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->role === 'subdistrict_admin', 403);

        return response()->json(['data' => $this->resolvedSettings($user)]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->role === 'subdistrict_admin', 403);
        $data = $request->validate($this->rules());
        $before = $user->documentSetting?->toArray() ?? [];
        $setting = DocumentSetting::query()->updateOrCreate(
            ['user_id' => $user->id],
            $data,
        );

        $this->audit->record('document_settings.updated', $setting, $before, $setting->fresh()->toArray());

        return response()->json([
            'message' => 'บันทึกข้อมูลสำหรับเอกสาร PDF เรียบร้อย',
            'data' => $this->resolvedSettings($user->fresh()),
        ]);
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        $required = ['required', 'string', 'max:255'];
        $person = ['nullable', 'string', 'max:200'];
        $position = ['nullable', 'string', 'max:180'];

        return [
            'learning_center_name' => $required,
            'district_office_name' => $required,
            'district_office_short_name' => $required,
            'province_office_name' => $required,
            'document_no_prefix' => ['required', 'string', 'max:80'],
            'office_address' => $required,
            'phone' => ['required', 'string', 'max:30'],
            'fax' => ['nullable', 'string', 'max:30'],
            'owner_unit' => $required,
            'registrar_name' => $person,
            'registrar_position' => $position,
            'responsible_name' => $person,
            'responsible_position' => $position,
            'director_name' => $person,
            'director_position' => $position,
            'finance_officer_name' => $person,
            'finance_officer_position' => $position,
            'payer_name' => $person,
            'payer_position' => $position,
            'certifier_name' => $person,
            'certifier_position' => $position,
            'supervisor_name' => $person,
            'supervisor_position' => $position,
            'follow_up_name' => $person,
            'follow_up_position' => $position,
        ];
    }

    /** @return array<string, mixed> */
    private function resolvedSettings(User $user): array
    {
        $user->loadMissing(['parent', 'documentSetting']);
        $setting = $user->documentSetting?->toArray() ?? [];
        $address = trim(implode(' ', array_filter([
            $user->address_line,
            $user->subdistrict ? 'ต.'.$user->subdistrict : null,
            $user->district ? 'อ.'.$user->district : null,
            $user->province ? 'จ.'.$user->province : null,
            $user->postal_code,
        ])));
        $districtName = $user->parent?->school_name ?: 'ศูนย์ส่งเสริมการเรียนรู้ระดับอำเภอเสนา';
        $responsibleName = $user->teacher_name ?: $user->display_name;

        return array_merge([
            'learning_center_name' => $user->school_name ?: 'ศกร.ระดับตำบล',
            'district_office_name' => $districtName,
            'district_office_short_name' => 'สกร.ระดับอำเภอเสนา',
            'province_office_name' => 'สำนักงานส่งเสริมการเรียนรู้ประจำจังหวัดพระนครศรีอยุธยา',
            'document_no_prefix' => 'ศธ 07093.05',
            'office_address' => $address ?: 'อำเภอเสนา จังหวัดพระนครศรีอยุธยา 13110',
            'phone' => $user->phone ?: '035-201-671',
            'fax' => $user->phone ?: '035-201-671',
            'owner_unit' => 'งานการเรียนรู้เพื่อการพัฒนาตนเอง',
            'registrar_name' => $responsibleName,
            'registrar_position' => $user->position ?: 'ครูศูนย์การเรียนรู้',
            'responsible_name' => $responsibleName,
            'responsible_position' => $user->position ?: 'ครูศูนย์การเรียนรู้',
            'director_name' => $user->parent?->teacher_name ?: '',
            'director_position' => $user->parent?->position ?: 'ผู้อำนวยการ'.$districtName,
            'finance_officer_name' => '',
            'finance_officer_position' => 'เจ้าหน้าที่การเงิน',
            'payer_name' => '',
            'payer_position' => 'ผู้จ่ายเงิน',
            'certifier_name' => '',
            'certifier_position' => 'ผู้รับรอง',
            'supervisor_name' => '',
            'supervisor_position' => 'ผู้นิเทศ',
            'follow_up_name' => $responsibleName,
            'follow_up_position' => $user->position ?: 'ครูศูนย์การเรียนรู้',
        ], array_filter($setting, static fn (mixed $value, string $key): bool => $key !== 'id'
            && $key !== 'user_id'
            && $key !== 'created_at'
            && $key !== 'updated_at'
            && $value !== null, ARRAY_FILTER_USE_BOTH));
    }
}
