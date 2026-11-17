<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\InventoryCategory;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserActiveSession;
use App\Services\ActiveSessionManager;
use App\Services\OwnerMfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SettingsApiController extends Controller
{
    public function index(Request $request, ActiveSessionManager $sessions): JsonResponse
    {
        $settings = BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => config('app.name', 'MyBatchoy'),
                'session_timeout_minutes' => config('session.lifetime', 20),
            ]
        );

        $sessions->pruneExpiredSessions();
        $activeSessions = UserActiveSession::with('user.role')
            ->whereHas('user.role', fn ($q) => $q->where('name', '!=', 'system_admin'))
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn (UserActiveSession $record) => [
                'user_id' => $record->user_id,
                'username' => $record->user->username,
                'name' => $record->user->name,
                'role' => $record->user->role?->name,
                'device_info' => $record->device_info,
                'logged_in_at' => $record->logged_in_at?->toIso8601String(),
                'last_activity_at' => $record->last_activity_at?->toIso8601String(),
                'status' => $record->isExpired() ? 'Expired' : 'Active',
            ]);

        return response()->json([
            'settings' => [
                'id' => $settings->id,
                'business_name' => $settings->business_name,
                'phone' => $settings->phone,
                'email' => $settings->email,
                'address' => $settings->address,
                'logo_path' => $settings->logo_path,
                'logo_url' => $settings->resolveLogoUrl(),
                'session_timeout_minutes' => (int) $settings->session_timeout_minutes,
                'default_minimum_stock_level' => (float) $settings->default_minimum_stock_level,
                'default_reorder_level' => (float) $settings->default_reorder_level,
                'single_active_session' => true,
            ],
            'categories' => InventoryCategory::withCount('items')->orderBy('name')->get()->map(fn (InventoryCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'is_active' => (bool) $c->is_active,
                'items_count' => (int) $c->items_count,
            ]),
            'units' => Unit::withCount('items')->orderBy('name')->get()->map(fn (Unit $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'short_name' => $u->short_name,
                'description' => $u->description,
                'is_active' => (bool) $u->is_active,
                'items_count' => (int) $u->items_count,
            ]),
            'active_sessions' => $activeSessions,
            'system_info' => [
                'application_version' => app()->version(),
                'environment' => config('app.env'),
                'database_driver' => DB::connection()->getDriverName(),
                'database_status' => DB::select('SELECT 1') ? 'Connected' : 'Unavailable',
                'single_active_session_status' => 'Enabled',
                'owner_mfa_status' => $request->user()?->mfa_enabled
                    ? 'Enabled (Authenticator App + Email Verification)'
                    : 'Email Verification Active (Authenticator Optional)',
            ],
        ]);
    }

    public function sendMfaChallenge(Request $request, OwnerMfaService $mfa): JsonResponse
    {
        $mfa->issueEmailChallenge($request->user(), $request, 'security_verification');

        return response()->json([
            'message' => 'A 6-digit MFA verification code has been sent to your Owner email address.',
        ]);
    }

    public function revokeActiveSession(Request $request, User $user, ActiveSessionManager $sessions, OwnerMfaService $mfa): JsonResponse
    {
        abort_if($user->isSystemAdmin(), 403, 'Owners cannot view or revoke System Admin sessions.');

        $validated = $request->validate([
            'current_password' => 'required|string',
            'mfa_code' => 'required|digits:6',
        ]);

        $mfa->verifyPasswordAndMfa(
            $request->user(),
            (string) $validated['current_password'],
            (string) $validated['mfa_code'],
            'security_verification'
        );

        $sessions->revokeByOwner($user, $request->user(), $request);

        return response()->json([
            'message' => "Active login session revoked for {$user->username}.",
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'session_timeout_minutes' => 'required|integer|min:5|max:240',
            'default_minimum_stock_level' => 'required|numeric|min:0|max:999999999.999',
            'default_reorder_level' => 'required|numeric|min:0|max:999999999.999',
        ]);

        $settings = BusinessSetting::firstOrNew(['id' => 1]);

        $settings = DB::transaction(function () use ($validated, $settings, $request): BusinessSetting {
            $settings->fill($validated);
            $settings->save();

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'business_settings_updated',
                'model_type' => BusinessSetting::class,
                'model_id' => $settings->id,
                'details' => 'Business and application settings updated.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $settings;
        });

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => [
                'id' => $settings->id,
                'business_name' => $settings->business_name,
                'phone' => $settings->phone,
                'email' => $settings->email,
                'address' => $settings->address,
                'session_timeout_minutes' => (int) $settings->session_timeout_minutes,
                'default_minimum_stock_level' => (float) $settings->default_minimum_stock_level,
                'default_reorder_level' => (float) $settings->default_reorder_level,
            ],
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);

        $category = DB::transaction(function () use ($validated, $request): InventoryCategory {
            $category = InventoryCategory::create($validated);
            $this->recordChange($request, 'inventory_category_created', InventoryCategory::class, $category->id);
            return $category;
        });

        return response()->json([
            'message' => 'Inventory category created.',
            'category' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, InventoryCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('inventory_categories', 'name')->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $category, $request): void {
            $category->update($validated);
            $this->recordChange($request, 'inventory_category_updated', InventoryCategory::class, $category->id);
        });

        return response()->json([
            'message' => 'Inventory category updated.',
            'category' => $category->fresh(),
        ]);
    }

    public function updateCategoryStatus(Request $request, InventoryCategory $category): JsonResponse
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);

        DB::transaction(function () use ($validated, $category, $request): void {
            $category->update(['is_active' => (bool) $validated['is_active']]);
            $this->recordChange($request, 'inventory_category_status_updated', InventoryCategory::class, $category->id);
        });

        return response()->json([
            'message' => 'Inventory category status updated.',
            'category' => $category->fresh(),
        ]);
    }

    public function storeUnit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:units,name',
            'short_name' => 'required|string|max:255|unique:units,short_name',
            'description' => 'nullable|string|max:1000',
        ]);

        $unit = DB::transaction(function () use ($validated, $request): Unit {
            $unit = Unit::create($validated);
            $this->recordChange($request, 'inventory_unit_created', Unit::class, $unit->id);
            return $unit;
        });

        return response()->json([
            'message' => 'Measurement unit created.',
            'unit' => $unit,
        ], 201);
    }

    public function updateUnit(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($unit->id)],
            'short_name' => ['required', 'string', 'max:255', Rule::unique('units', 'short_name')->ignore($unit->id)],
            'description' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $unit, $request): void {
            $unit->update($validated);
            $this->recordChange($request, 'inventory_unit_updated', Unit::class, $unit->id);
        });

        return response()->json([
            'message' => 'Measurement unit updated.',
            'unit' => $unit->fresh(),
        ]);
    }

    public function updateUnitStatus(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);

        DB::transaction(function () use ($validated, $unit, $request): void {
            $unit->update(['is_active' => (bool) $validated['is_active']]);
            $this->recordChange($request, 'inventory_unit_status_updated', Unit::class, $unit->id);
        });

        return response()->json([
            'message' => 'Measurement unit status updated.',
            'unit' => $unit->fresh(),
        ]);
    }

    public function backupInfo(): JsonResponse
    {
        $driver = DB::connection()->getDriverName();
        $dbName = DB::connection()->getDatabaseName();

        if ($driver === 'sqlite') {
            $rawTables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            $tables = array_map(function ($t) {
                $count = DB::table($t->name)->count();
                return [
                    'name' => $t->name,
                    'rows' => $count,
                    'size' => $count * 128,
                ];
            }, $rawTables);
        } else {
            $raw = DB::select("
                SELECT table_name as `name`, table_rows as `rows`, (data_length + index_length) as `size`
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY table_name ASC
            ", [$dbName]);
            $tables = array_map(fn ($t) => [
                'name' => $t->name,
                'rows' => (int) $t->rows,
                'size' => (int) $t->size,
            ], $raw);
        }

        $totalRows = array_sum(array_column($tables, 'rows'));
        $totalBytes = array_sum(array_column($tables, 'size'));

        $backupHistory = AuditLog::with('user')
            ->where('action', 'database_backup_downloaded')
            ->whereHas('user.role', fn ($q) => $q->where('name', '!=', 'system_admin'))
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'user_name' => $log->user?->name ?? 'Owner',
                'details' => $log->details,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => strtoupper($driver),
                'tables_count' => count($tables),
                'total_rows' => $totalRows,
                'total_size_kb' => round($totalBytes / 1024, 2),
                'environment' => config('app.env'),
                'server_time' => now()->toDateTimeString(),
                'database_status' => DB::select('SELECT 1') ? 'Connected' : 'Unavailable',
            ],
            'tables' => array_values($tables),
            'backup_history' => $backupHistory,
        ]);
    }

    private function recordChange(Request $request, string $action, string $modelType, int $modelId): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'details' => 'Inventory configuration updated.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
