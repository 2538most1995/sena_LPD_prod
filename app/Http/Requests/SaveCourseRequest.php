<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('courses', 'name')->ignore($courseId)],
            'category' => ['required', 'string', 'max:100'],
            'hours' => ['required', 'integer', 'min:1', 'max:1000'],
            'owner' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'word_attachment' => ['nullable', 'file', 'mimes:doc,docx', 'max:10240'],
            'pdf_attachment' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'ชื่อหลักสูตร',
            'category' => 'กลุ่มหลักสูตร',
            'hours' => 'จำนวนชั่วโมง',
            'owner' => 'หน่วยงานเจ้าของ',
            'description' => 'รายละเอียดหลักสูตร',
            'word_attachment' => 'ไฟล์ Word',
            'pdf_attachment' => 'ไฟล์ PDF',
        ];
    }
}
