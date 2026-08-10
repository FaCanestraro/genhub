<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(string $area, string $action, string $description, array $opts = []): AuditLog
    {
        $request = request();
        $user = $request?->user();

        $subject = $opts['subject'] ?? null;

        return AuditLog::create([
            'company_id' => $opts['company_id'] ?? $request?->company()?->id,
            'causer_id' => $opts['causer_id'] ?? $user?->id,
            'area' => $area,
            'action' => $action,
            'status' => $opts['status'] ?? 'success',
            'description' => $description,
            'input' => $opts['input'] ?? null,
            'output' => $opts['output'] ?? null,
            'ai_model' => $opts['ai_model'] ?? null,
            'duration_ms' => $opts['duration_ms'] ?? null,
            'subject_type' => $subject instanceof Model ? $subject->getMorphClass() : null,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
