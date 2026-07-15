<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSetting extends Model
{
    protected $fillable = [
        'user_id',
        'learning_center_name',
        'district_office_name',
        'district_office_short_name',
        'province_office_name',
        'document_no_prefix',
        'office_address',
        'phone',
        'fax',
        'owner_unit',
        'registrar_name',
        'registrar_position',
        'responsible_name',
        'responsible_position',
        'director_name',
        'director_position',
        'finance_officer_name',
        'finance_officer_position',
        'payer_name',
        'payer_position',
        'certifier_name',
        'certifier_position',
        'supervisor_name',
        'supervisor_position',
        'follow_up_name',
        'follow_up_position',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
