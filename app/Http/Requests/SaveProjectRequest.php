<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'lecturer_id' => ['required', 'integer', 'exists:lecturers,id'],
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:10000'],
            'format_type' => ['required', Rule::in(['หลักสูตร 3-9 ชั่วโมง', 'หลักสูตร 10 ชั่วโมงขึ้นไป'])],
            'attribute_type' => ['nullable', 'string', 'max:120'],
            'activity_type' => ['nullable', 'string', 'max:120'],
            'place' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'fiscal_year' => ['required', 'integer', 'between:2500,2700'],
            'lecturer_cost' => ['nullable', 'numeric', 'min:0'],
            'material_cost' => ['nullable', 'numeric', 'min:0'],
            'board_cost' => ['nullable', 'numeric', 'min:0'],
            'food_cost' => ['nullable', 'numeric', 'min:0'],
            'snack_cost' => ['nullable', 'numeric', 'min:0'],
            'place_cost' => ['nullable', 'numeric', 'min:0'],
            'transport_cost' => ['nullable', 'numeric', 'min:0'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
