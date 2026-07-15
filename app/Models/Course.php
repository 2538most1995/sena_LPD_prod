<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'courses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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

    public function projects(): HasMany
    {
        return $this->hasMany(LearningProject::class, 'course_id');
    }

    public function attachments(): array
    {
        return array_values(array_filter([
            $this->word_attachment_path ? [
                'type' => 'word',
                'name' => $this->word_attachment_name,
                'path' => $this->word_attachment_path,
            ] : null,
            $this->pdf_attachment_path ? [
                'type' => 'pdf',
                'name' => $this->pdf_attachment_name,
                'path' => $this->pdf_attachment_path,
            ] : null,
        ]));
    }
}
