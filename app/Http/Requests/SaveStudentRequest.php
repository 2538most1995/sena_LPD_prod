<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'prefix' => ['required', Rule::in(['นาย', 'นาง', 'นางสาว'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['ชาย', 'หญิง', 'ไม่ระบุ'])],
            'id_card' => ['required', 'string', 'max:20', Rule::unique('students', 'id_card')->ignore($studentId)],
            'birthday' => ['nullable', 'date', 'before:today'],
            'education' => ['nullable', 'string', 'max:100'],
            'career' => ['nullable', 'string', 'max:100'],
            'target_group' => ['nullable', 'string', 'max:100'],
            'annual_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'registered_at' => ['nullable', 'date'],
        ];
    }
}
