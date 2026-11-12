<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index()
    {
        $driver = DB::connection()->getDriverName();
        $dbName = DB::connection()->getDatabaseName();

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

        $backupHistory = AuditLog::with('user')
            ->where('action', 'database_backup_downloaded')
            ->latest()
            ->limit(10)
            ->get();

        return view('backup.index', compact('tables', 'systemInfo', 'backupHistory'));
    }

    public function downloadBackup(Request $request): StreamedResponse
    {
        $dbName = DB::connection()->getDatabaseName();
        $driver = DB::connection()->getDriverName();
        $fileName = 'mybatchoy_db_backup_' . now()->format('Y-m-d_His') . '.sql';
        $userId = Auth::id();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        return response()->streamDownload(function () use ($dbName, $driver, $userId, $ipAddress, $userAgent) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "-- MyBatchoy Database Backup\n");
            fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n");
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

                    $rows = DB::table($table)->get();
                    if ($rows->isNotEmpty()) {
                        foreach ($rows->chunk(50) as $chunk) {
                            $values = [];
                            foreach ($chunk as $row) {
                                $escaped = array_map($escapeValue, (array) $row);
                                $values[] = '(' . implode(', ', $escaped) . ')';
                            }
                            $columns = array_map($quoteIdentifier, array_keys((array) $chunk[0]));
                            $colList = implode(', ', $columns);
                            fwrite($handle, "INSERT INTO {$quotedTable} ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n");
                        }
                        fwrite($handle, "\n");
                    }
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

                    $rows = DB::table($table)->get();
                    if ($rows->isNotEmpty()) {
                        foreach ($rows->chunk(50) as $chunk) {
                            $values = [];
                            foreach ($chunk as $row) {
                                $escaped = array_map($escapeValue, (array) $row);
                                $values[] = '(' . implode(', ', $escaped) . ')';
                            }
                            $columns = array_map($quoteIdentifier, array_keys((array) $chunk[0]));
                            $colList = implode(', ', $columns);
                            fwrite($handle, "INSERT INTO {$quotedTable} ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n");
                        }
                        fwrite($handle, "\n");
                    }
                }
            }

            if ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys=ON;\n");
            } else {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            }
            fclose($handle);

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'database_backup_downloaded',
                'details' => 'Database backup export generated and streamed.',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
