<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lecturer = $this->route('lecturer');
        $ownerId = $lecturer?->created_by ?? $this->user()?->id;

        return [
            'prefix' => ['required', Rule::in(['นาย', 'นาง', 'นางสาว'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'id_card' => [
                'required',
                'string',
                'max:20',
                Rule::unique('lecturers', 'id_card')
                    ->where(fn ($query) => $query->where('created_by', $ownerId))
                    ->ignore($lecturer?->id),
            ],
            'birthday' => ['nullable', 'date', 'before:today'],
            'education' => ['nullable', 'string', 'max:100'],
            'career' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'registered_at' => ['nullable', 'date'],
            'expertise' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'กรุณากรอก :attribute',
            'in' => ':attribute ต้องเลือกจากรายการที่กำหนด',
            'string' => ':attribute ต้องเป็นข้อความ',
            'max' => ':attribute มีความยาวเกินกำหนด',
            'date' => ':attribute ต้องเป็นวันที่ที่ถูกต้อง',
            'before' => ':attribute ต้องเป็นวันที่ก่อนวันนี้',
            'id_card.unique' => 'เลขประจำตัวประชาชนนี้มีอยู่ในทะเบียนวิทยากรของตำบลนี้แล้ว',
        ];
    }

    public function attributes(): array
    {
        return [
            'prefix' => 'คำนำหน้า',
            'first_name' => 'ชื่อ',
            'last_name' => 'นามสกุล',
            'id_card' => 'เลขประจำตัวประชาชน',
            'birthday' => 'วันเกิด',
            'education' => 'การศึกษา',
            'career' => 'อาชีพ',
            'address' => 'ที่อยู่',
            'phone' => 'โทรศัพท์',
            'registered_at' => 'วันที่ขึ้นทะเบียน',
            'expertise' => 'ความเชี่ยวชาญ',
        ];
    }
}
