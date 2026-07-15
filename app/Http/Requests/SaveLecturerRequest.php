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
        $lecturerId = $this->route('lecturer')?->id;

        return [
            'prefix' => ['required', Rule::in(['นาย', 'นาง', 'นางสาว'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'id_card' => ['required', 'string', 'max:20', Rule::unique('lecturers', 'id_card')->ignore($lecturerId)],
            'birthday' => ['nullable', 'date', 'before:today'],
            'education' => ['nullable', 'string', 'max:100'],
            'career' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'registered_at' => ['nullable', 'date'],
            'expertise' => ['required', 'string', 'max:255'],
        ];
    }
}
