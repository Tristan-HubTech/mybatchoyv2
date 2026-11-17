<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TechnicalAuditLog;
use App\Services\TechnicalAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /**
     * Technical-only metadata tables safe for System Admin SQL exports.
     */
    private const TECHNICAL_EXPORT_TABLES = [
        'migrations',
        'roles',
        'technical_settings',
        'technical_audit_logs',
    ];

    /**
     * Ephemeral credential, token, reset, and session tables excluded from SQL data exports.
     */
    private const EPHEMERAL_SECRET_TABLES = [
        'personal_access_tokens',
        'password_resets',
        'password_reset_tokens',
        'owner_mfa_challenges',
        'user_active_sessions',
        'sessions',
        'cache',
        'cache_locks',
    ];

    public function index()
    {
        $driver = DB::connection()->getDriverName();
        $dbName = DB::connection()->getDatabaseName();
        $isSysAdmin = Auth::user()?->isSystemAdmin() ?? false;

        if ($driver === 'sqlite') {
            $rawTables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = array_map(function ($t) {
                $count = DB::table($t->name)->count();
                return (object) [
                    'name' => $t->name,
                    'rows' => $count,
                    'size' => $count * 128,
                ];
            }, $rawTables);
        } else {
            $tables = DB::select("
                SELECT table_name as `name`, table_rows as `rows`, (data_length + index_length) as `size`
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY table_name ASC
            ", [$dbName]);
        }

        $totalRows = array_sum(array_column($tables, 'rows'));
        $totalBytes = array_sum(array_column($tables, 'size'));

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => strtoupper($driver),
            'tables_count' => count($tables),
            'total_rows' => $totalRows,
            'total_size_kb' => round($totalBytes / 1024, 2),
            'environment' => config('app.env'),
            'server_time' => now()->toDateTimeString(),
            'database_status' => DB::select('SELECT 1') ? 'Connected' : 'Unavailable',
        ];

        $backupHistory = $isSysAdmin
            ? TechnicalAuditLog::with('user')
                ->where('category', 'backup')
                ->where('action', 'database_backup_downloaded')
                ->latest()
                ->limit(10)
                ->get()
            : AuditLog::with('user')
                ->where('action', 'database_backup_downloaded')
                ->whereHas('user.role', fn ($q) => $q->where('name', '!=', 'system_admin'))
                ->latest()
                ->limit(10)
                ->get();

        return view('backup.index', compact('tables', 'systemInfo', 'backupHistory'));
    }

    public function downloadBackup(Request $request): StreamedResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
        ]);

        $dbName = DB::connection()->getDatabaseName();
        $driver = DB::connection()->getDriverName();
        $user = $request->user();
        $userId = $user?->id;
        $isSysAdmin = $user?->isSystemAdmin() ?? false;
        $scopePrefix = $isSysAdmin ? 'technical' : 'business';
        $fileName = "mybatchoy_{$scopePrefix}_backup_" . now()->format('Y-m-d_His') . '.sql';
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        if ($isSysAdmin) {
            TechnicalAuditLogger::record(
                $request,
                $userId,
                'backup',
                'database_backup_downloaded',
                'Technical infrastructure SQL backup exported (credentials, tokens, sessions, and business rows excluded).'
            );
        } else {
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'database_backup_downloaded',
                'details' => 'Business database backup SQL export generated and downloaded.',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }

        return response()->streamDownload(function () use ($dbName, $driver, $isSysAdmin) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "-- MyBatchoy Database Backup\n");
            fwrite($handle, '-- Scope: ' . ($isSysAdmin
                ? 'Technical Schema & Metadata Only (Credentials, Tokens, Sessions, and Business Data Excluded)'
                : 'Business Operations Backup (Ephemeral Session & Token Tables Excluded)') . "\n");
            fwrite($handle, '-- Generated: ' . now()->toIso8601String() . "\n");
            fwrite($handle, "-- Driver: {$driver}\n\n");
            if ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys=OFF;\n\n");
            } else {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
            }

            $quoteIdentifier = static fn (string $identifier): string => '`' . str_replace('`', '``', $identifier) . '`';

            $escapeValue = function ($value) use ($driver): string {
                if ($value === null) {
                    return 'NULL';
                }

                if ($driver === 'sqlite') {
                    return "'" . str_replace("'", "''", (string) $value) . "'";
                }

                $quoted = DB::connection()->getPdo()->quote((string) $value);
                if ($quoted === false) {
                    throw new \RuntimeException('A database value could not be safely quoted for export.');
                }

                return $quoted;
            };

            if ($driver === 'sqlite') {
                $rawTables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($rawTables as $t) {
                    $table = $t->name;
                    $quotedTable = $quoteIdentifier($table);
                    fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
                    fwrite($handle, $t->sql . ";\n\n");

                    if (!$this->shouldExportTableRows($table, $isSysAdmin)) {
                        continue;
                    }

                    $this->dumpTableRows($handle, $table, $quotedTable, $escapeValue, $quoteIdentifier);
                }
            } else {
                $tables = DB::select('SHOW TABLES');
                $keyName = 'Tables_in_' . $dbName;

                foreach ($tables as $t) {
                    $table = $t->$keyName ?? array_values((array) $t)[0];
                    $quotedTable = $quoteIdentifier($table);

                    $createTable = DB::select("SHOW CREATE TABLE {$quotedTable}");
                    $createSql = $createTable[0]->{'Create Table'} ?? '';
                    fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
                    fwrite($handle, $createSql . ";\n\n");

                    if (!$this->shouldExportTableRows($table, $isSysAdmin)) {
                        continue;
                    }

                    $this->dumpTableRows($handle, $table, $quotedTable, $escapeValue, $quoteIdentifier);
                }
            }

            if ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys=ON;\n");
            } else {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function shouldExportTableRows(string $table, bool $isSysAdmin): bool
    {
        if ($isSysAdmin) {
            return in_array($table, self::TECHNICAL_EXPORT_TABLES, true);
        }

        return !in_array($table, self::EPHEMERAL_SECRET_TABLES, true);
    }

    /**
     * Stream batched SQL INSERT statements for an allowed table.
     */
    private function dumpTableRows($handle, string $table, string $quotedTable, callable $escapeValue, callable $quoteIdentifier): void
    {
        $wroteRows = false;
        $buffer = [];

        $flushBatch = static function (array $rows) use ($handle, $quotedTable, $escapeValue, $quoteIdentifier): void {
            $columns = array_map($quoteIdentifier, array_keys($rows[0]));
            $colList = implode(', ', $columns);
            $values = [];

            foreach ($rows as $row) {
                $escaped = array_map($escapeValue, $row);
                $values[] = '(' . implode(', ', $escaped) . ')';
            }

            fwrite($handle, "INSERT INTO {$quotedTable} ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n");
        };

        foreach (DB::table($table)->cursor() as $row) {
            $wroteRows = true;
            $buffer[] = (array) $row;

            if (count($buffer) >= 100) {
                $flushBatch($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            $flushBatch($buffer);
        }

        if ($wroteRows) {
            fwrite($handle, "\n");
        }
    }
}
