<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'registered_at' => 'date',
            'annual_income' => 'decimal:2',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(LearningProject::class, 'project_students', 'student_id', 'project_id')
            ->withPivot('joined_at');
    }
}
