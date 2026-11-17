<?php

namespace App\Console\Commands;

use App\Models\OwnerMfaChallenge;
use App\Models\TechnicalAuditLog;
use App\Models\TechnicalSetting;
use App\Models\UserActiveSession;
use App\Services\ActiveSessionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneTechnicalRetention extends Command
{
    protected $signature = 'app:prune-technical-retention';

    protected $description = 'Prune aged technical audit logs and expired ephemeral security tokens without touching business history.';

    public function handle(): int
    {
        if (!ActiveSessionManager::hasTable('technical_settings')) {
            $this->warn('technical_settings table does not exist yet.');

            return self::SUCCESS;
        }

        $settings = TechnicalSetting::current();
        $logRetentionDays = max(7, min(365, (int) $settings->log_retention_days));
        $backupRetentionDays = max(7, min(365, (int) $settings->backup_retention_days));

        $logCutoff = now()->subDays($logRetentionDays);
        $prunedTechLogs = TechnicalAuditLog::where('created_at', '<', $logCutoff)->delete();

        $prunedSessions = ActiveSessionManager::hasTable('user_active_sessions')
            ? UserActiveSession::where('expires_at', '<=', now())->delete()
            : 0;

        $prunedMfa = ActiveSessionManager::hasTable('owner_mfa_challenges')
            ? OwnerMfaChallenge::where(function ($query) {
                $query->where('expires_at', '<=', now())
                    ->orWhere('created_at', '<', now()->subDay());
            })->delete()
            : 0;

        $prunedResets = ActiveSessionManager::hasTable('password_resets')
            ? DB::table('password_resets')->where('created_at', '<', now()->subDay())->delete()
            : 0;

        $prunedBackupFiles = 0;
        $backupDir = storage_path('app/backups');
        if (is_dir($backupDir)) {
            $backupCutoffTs = now()->subDays($backupRetentionDays)->getTimestamp();
            foreach (glob($backupDir . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $filePath) {
                if (is_file($filePath) && (@filemtime($filePath) ?: PHP_INT_MAX) < $backupCutoffTs && @unlink($filePath)) {
                    $prunedBackupFiles++;
                }
            }
        }

        $this->info(
            "Pruned technical_audit_logs={$prunedTechLogs}, expired_sessions={$prunedSessions}, "
            . "mfa_challenges={$prunedMfa}, password_resets={$prunedResets}, local_backups={$prunedBackupFiles}."
        );

        return self::SUCCESS;
    }
}

