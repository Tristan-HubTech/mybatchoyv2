<?php

namespace App\Services;

use App\Models\TechnicalAuditLog;
use Illuminate\Http\Request;

class TechnicalAuditLogger
{
    public static function record(
        ?Request $request,
        ?int $userId,
        string $category,
        string $action,
        string $details,
        ?string $targetType = null,
        ?int $targetId = null
    ): void {
        if (!ActiveSessionManager::hasTable('technical_audit_logs')) {
            return;
        }

        TechnicalAuditLog::create([
            'user_id' => $userId,
            'category' => $category,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => self::scrubSecrets($details),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? mb_substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }

    public static function scrubSecrets(string $text): string
    {
        $patterns = [
            '/(password|password_confirmation|current_password|new_password|mfa_secret|token|bearer|secret|app_key|aws_secret_access_key|mail_password|db_password|redis_password|api_key|otp|mfa_code|verification_code|reset_code|recovery_code)\s*[:=]\s*\S+/i' => '$1=[REDACTED]',
            '/"(password|password_confirmation|current_password|new_password|mfa_secret|token|secret|otp|mfa_code|recovery_code|verification_code|reset_code)"\s*:\s*"[^"]*"/i' => '"$1":"[REDACTED]"',
            '/Bearer\s+[A-Za-z0-9\-_\.|]+/i' => 'Bearer [REDACTED]',
            '/\b((?:mysql|pgsql|redis|rediss|smtp|smtps):\/\/)[^:\s\/@]+:[^@\s]+@/i' => '$1[REDACTED]@',
            '/((?:verification|reset|security|mfa)\s+code(?:\s+is|\s*[:=]))\s*\d{6}\b/i' => '$1 [REDACTED]',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $text);
    }
}

