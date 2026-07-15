<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(private readonly Request $request) {}

    public function record(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null): void
    {
        AuditLog::query()->create([
            'user_id' => $this->request->user()?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 1000),
        ]);
    }
}
