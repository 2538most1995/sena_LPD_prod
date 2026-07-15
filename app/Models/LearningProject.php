<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningProject extends Model
{
    protected $table = 'projects';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'lecturer_cost' => 'decimal:2',
            'material_cost' => 'decimal:2',
            'board_cost' => 'decimal:2',
            'food_cost' => 'decimal:2',
            'snack_cost' => 'decimal:2',
            'place_cost' => 'decimal:2',
            'transport_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'project_students', 'project_id', 'student_id')
            ->withPivot('joined_at');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ActivityPhoto::class, 'project_id');
    }
}
