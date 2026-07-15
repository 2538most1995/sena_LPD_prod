<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $table = 'scores';

    public $incrementing = false;

    public const CREATED_AT = null;

    protected $primaryKey = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'knowledge_score' => 'decimal:2',
            'skill_score' => 'decimal:2',
            'attribute_score' => 'decimal:2',
            'updated_at' => 'datetime',
        ];
    }
}
