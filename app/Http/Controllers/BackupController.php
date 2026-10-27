<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index()
    {
        $dbName = config('database.connections.mysql.database', 'mybatchoy');
        $driver = DB::connection()->getDriverName();

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
            'database' => $driver === 'sqlite' ? 'SQLite (:memory:)' : $dbName,
            'tables_count' => count($tables),
            'total_rows' => $totalRows,
            'total_size_kb' => round($totalBytes / 1024, 2),
            'environment' => config('app.env'),
            'server_time' => now()->toDateTimeString(),
        ];

        return view('backup.index', compact('tables', 'systemInfo'));
    }

    public function downloadBackup(Request $request): StreamedResponse
    {
        $dbName = config('database.connections.mysql.database', 'mybatchoy');
        $driver = DB::connection()->getDriverName();
        $fileName = 'mybatchoy_db_backup_' . now()->format('Y-m-d_His') . '.sql';

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'database_backup_downloaded',
            'details' => 'Downloaded full SQL database backup archive.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->streamDownload(function () use ($dbName, $driver) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "-- MyBatchoy Database Backup\n");
            fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n");
            fwrite($handle, "-- Driver: {$driver}\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            if ($driver === 'sqlite') {
                $rawTables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($rawTables as $t) {
                    $table = $t->name;
                    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                    fwrite($handle, $t->sql . ";\n\n");

                    $rows = DB::table($table)->get();
                    if ($rows->isNotEmpty()) {
                        foreach ($rows->chunk(50) as $chunk) {
                            $values = [];
                            foreach ($chunk as $row) {
                                $escaped = array_map(function ($val) {
                                    if (is_null($val)) {
                                        return 'NULL';
                                    }
                                    return "'" . addslashes((string) $val) . "'";
                                }, (array) $row);
                                $values[] = '(' . implode(', ', $escaped) . ')';
                            }
                            $columns = array_keys((array) $chunk[0]);
                            $colList = '`' . implode('`, `', $columns) . '`';
                            fwrite($handle, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n");
                        }
                        fwrite($handle, "\n");
                    }
                }
            } else {
                $tables = DB::select('SHOW TABLES');
                $keyName = 'Tables_in_' . $dbName;

                foreach ($tables as $t) {
                    $table = $t->$keyName ?? array_values((array) $t)[0];

                    $createTable = DB::select("SHOW CREATE TABLE `{$table}`");
                    $createSql = $createTable[0]->{'Create Table'} ?? '';
                    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                    fwrite($handle, $createSql . ";\n\n");

                    $rows = DB::table($table)->get();
                    if ($rows->isNotEmpty()) {
                        foreach ($rows->chunk(50) as $chunk) {
                            $values = [];
                            foreach ($chunk as $row) {
                                $escaped = array_map(function ($val) {
                                    if (is_null($val)) {
                                        return 'NULL';
                                    }
                                    return "'" . addslashes((string) $val) . "'";
                                }, (array) $row);
                                $values[] = '(' . implode(', ', $escaped) . ')';
                            }
                            $columns = array_keys((array) $chunk[0]);
                            $colList = '`' . implode('`, `', $columns) . '`';
                            fwrite($handle, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n");
                        }
                        fwrite($handle, "\n");
                    }
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
