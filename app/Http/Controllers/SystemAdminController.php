<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessAccessRequest;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\TechnicalAuditLog;
use App\Models\TechnicalSetting;
use App\Models\User;
use App\Models\UserActiveSession;
use App\Services\ActiveSessionManager;
use App\Services\PermissionService;
use App\Services\TechnicalAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class SystemAdminController extends Controller
{
    public function dashboard(ActiveSessionManager $sessions)
    {
        PermissionService::expireElapsedGrants();
        $sessions->pruneExpiredSessions();

        $driver = DB::connection()->getDriverName();
        $dbConnected = (bool) DB::select('SELECT 1');

        $techSettings = TechnicalSetting::current();
        $activeSessionsCount = UserActiveSession::count();
        $recentSecurityEvents = TechnicalAuditLog::with('user')
            ->whereIn('category', ['security', 'account_admin', 'backup', 'technical', 'access_request'])
            ->latest()
            ->take(10)
            ->get();

        $failedLoginCount24h = AuditLog::whereIn('action', ['login_failed', 'login_rate_limited'])
            ->where('created_at', '>=', now()->subDay())
            ->count()
            + TechnicalAuditLog::where('category', 'security')
                ->whereIn('action', ['login_failed', 'login_rate_limited'])
                ->where('created_at', '>=', now()->subDay())
                ->count();

        $myGrants = BusinessAccessRequest::where('system_admin_id', auth()->id())
            ->latest()
            ->take(6)
            ->get();

        $roleCounts = Role::withCount('users')->get()->pluck('users_count', 'name');

        return view('sysadmin.dashboard', [
            'techSettings' => $techSettings,
            'systemInfo' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => strtoupper($driver),
                'database_status' => $dbConnected ? 'Connected' : 'Unavailable',
                'environment' => config('app.env'),
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver'),
            ],
            'activeSessionsCount' => $activeSessionsCount,
            'failedLoginCount24h' => $failedLoginCount24h,
            'recentSecurityEvents' => $recentSecurityEvents,
            'myGrants' => $myGrants,
            'roleCounts' => $roleCounts,
        ]);
    }

    public function health()
    {
        $start = microtime(true);
        $dbConnected = (bool) DB::select('SELECT 1');
        $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);

        $extensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'openssl', 'mbstring', 'json'];
        $extStatus = [];
        foreach ($extensions as $ext) {
            $extStatus[$ext] = extension_loaded($ext);
        }

        $logPath = storage_path('logs/laravel.log');
        $logTail = [];
        if (is_file($logPath) && is_readable($logPath)) {
            $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $recentLines = array_slice($lines, -30);
            foreach ($recentLines as $line) {
                $logTail[] = TechnicalAuditLogger::scrubSecrets(mb_substr($line, 0, 280));
            }
        }

        $logChannel = (string) config('logging.default', 'stack');
        $cacheDriver = (string) config('cache.default', 'file');
        $storageWritable = $logChannel === 'stderr' ? true : File::isWritable(storage_path('logs'));
        $cacheWritable = in_array($cacheDriver, ['database', 'redis', 'memcached', 'dynamodb'], true)
            ? true
            : File::isWritable(storage_path('framework/cache'));

        return view('sysadmin.health', [
            'dbConnected' => $dbConnected,
            'dbLatencyMs' => $dbLatencyMs,
            'extStatus' => $extStatus,
            'storageWritable' => $storageWritable,
            'cacheWritable' => $cacheWritable,
            'logChannel' => $logChannel,
            'cacheDriver' => $cacheDriver,
            'logTail' => array_reverse($logTail),
            'techSettings' => TechnicalSetting::current(),
        ]);
    }

    public function security(Request $request, ActiveSessionManager $sessions)
    {
        $sessions->pruneExpiredSessions();

        $validated = $request->validate([
            'category' => 'nullable|in:security,technical,backup,account_admin,access_request',
            'search' => 'nullable|string|max:100',
        ]);

        $logsQuery = TechnicalAuditLog::with('user')->latest();
        if (!empty($validated['category'])) {
            $logsQuery->where('category', $validated['category']);
        }
        if (!empty($validated['search'])) {
            $search = trim((string) $validated['search']);
            $logsQuery->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%");
            });
        }

        // Only safe metadata exposed; never tokens or hashes.
        $currentAdminId = $request->user()->id;
        $activeSessions = UserActiveSession::with('user.role')
            ->whereHas('user')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(function (UserActiveSession $record) use ($currentAdminId): array {
                return [
                    'user_id' => $record->user_id,
                    'username' => $record->user->username,
                    'role_name' => $record->user->role?->name ?? 'user',
                    'role_label' => $record->user->role?->label ?? ucfirst((string) ($record->user->role?->name ?? 'User')),
                    'is_current_user' => (int) $record->user_id === (int) $currentAdminId,
                    'channel' => $record->channel,
                    'device_info' => $record->device_info,
                    'logged_in_at' => $record->logged_in_at,
                    'last_activity_at' => $record->last_activity_at,
                    'status' => $record->isExpired() ? 'Expired' : 'Active',
                ];
            });

        return view('sysadmin.security', [
            'techSettings' => TechnicalSetting::current(),
            'technicalLogs' => $logsQuery->paginate(20)->withQueryString(),
            'activeSessions' => $activeSessions,
        ]);
    }

    public function revokeAdminSession(Request $request, User $user, ActiveSessionManager $sessions)
    {
        abort_if(!$user->isSystemAdmin(), 403, 'Technical session revocation is strictly limited to System Admin accounts.');

        if ((int) $user->id === (int) $request->user()->id) {
            return redirect()->route('sysadmin.security')->with('error', 'Use standard Log Out to end your own active session.');
        }

        $request->validate([
            'current_password' => 'required|current_password',
        ]);

        $sessions->revokeBySystemAdmin($user, $request->user(), $request);

        return redirect()
            ->route('sysadmin.security')
            ->with('success', "Active session revoked for System Admin {$user->username}.");
    }

    public function updateSecurityConfig(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'security_lockout_attempts' => 'required|integer|min:3|max:15',
            'security_lockout_minutes' => 'required|integer|min:5|max:120',
            'api_rate_limit_per_minute' => 'required|integer|min:20|max:300',
        ]);

        $settings = TechnicalSetting::current();
        $settings->update([
            'security_lockout_attempts' => $validated['security_lockout_attempts'],
            'security_lockout_minutes' => $validated['security_lockout_minutes'],
            'api_rate_limit_per_minute' => $validated['api_rate_limit_per_minute'],
            'updated_by' => $request->user()->id,
        ]);

        TechnicalAuditLogger::record(
            $request,
            $request->user()->id,
            'security',
            'technical_security_updated',
            "Technical security settings updated (lockout={$validated['security_lockout_attempts']} attempts/{$validated['security_lockout_minutes']}m, API rate={$validated['api_rate_limit_per_minute']}/min).",
            TechnicalSetting::class,
            $settings->id
        );

        return redirect()->route('sysadmin.security')->with('success', 'Technical security settings updated.');
    }

    public function config()
    {
        return view('sysadmin.config', [
            'techSettings' => TechnicalSetting::current(),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_notice' => 'nullable|string|max:255',
            'log_retention_days' => 'required|integer|min:7|max:365',
            'backup_retention_days' => 'required|integer|min:7|max:365',
            'owner_can_edit_technical' => 'nullable|boolean',
        ]);

        $settings = TechnicalSetting::current();
        $settings->update([
            'maintenance_mode' => (bool) ($validated['maintenance_mode'] ?? false),
            'maintenance_notice' => $validated['maintenance_notice'] ?? null,
            'log_retention_days' => $validated['log_retention_days'],
            'backup_retention_days' => $validated['backup_retention_days'],
            'owner_can_edit_technical' => (bool) ($validated['owner_can_edit_technical'] ?? false),
            'updated_by' => $request->user()->id,
        ]);

        TechnicalAuditLogger::record(
            $request,
            $request->user()->id,
            'technical',
            'technical_config_updated',
            'Technical application configuration updated (maintenance=' . ($settings->maintenance_mode ? 'on' : 'off') . ', owner_technical=' . ($settings->owner_can_edit_technical ? 'yes' : 'no') . ').',
            TechnicalSetting::class,
            $settings->id
        );

        if ($request->user()->isOwner()) {
            return redirect()->route('owner.settings.index')->with('success', 'Technical configuration updated.');
        }

        return redirect()->route('sysadmin.config')->with('success', 'Technical configuration updated.');
    }

    public function accounts()
    {
        $sysAdminRoleId = Role::where('name', 'system_admin')->value('id');
        $sysAdmins = $sysAdminRoleId
            ? User::with('role')->where('role_id', $sysAdminRoleId)->orderBy('name')->get(['id', 'role_id', 'name', 'username', 'email', 'is_active', 'last_login_at'])
            : collect();

        $permissionGroups = PermissionService::GROUPS;

        return view('sysadmin.accounts', [
            'sysAdmins' => $sysAdmins,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'role' => 'prohibited',
            'role_id' => 'prohibited',
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        $newAdmin = DB::transaction(function () use ($validated, $request): User {
            $role = Role::firstOrCreate(
                ['name' => 'system_admin'],
                ['label' => 'System Admin (Developer)']
            );

            $admin = User::create([
                'role_id' => $role->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => mb_strtolower($validated['email']),
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            TechnicalAuditLogger::record(
                $request,
                $request->user()->id,
                'account_admin',
                'system_admin_created',
                "System Admin account {$admin->username} created through protected technical management.",
                User::class,
                $admin->id
            );

            return $admin;
        });

        return redirect()->route('sysadmin.accounts.index')->with('success', "System Admin account {$newAdmin->username} created.");
    }

    public function updateAccountStatus(Request $request, User $account, ActiveSessionManager $sessions)
    {
        if (!$account->isSystemAdmin()) {
            abort(403, 'System Admin cannot modify business owner, staff, or supplier accounts.');
        }

        if ((int) $account->id === (int) $request->user()->id) {
            throw ValidationException::withMessages([
                'account' => ['You cannot deactivate your own active System Admin account.'],
            ]);
        }

        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'is_active' => 'required|boolean',
        ]);

        $isActive = (bool) $validated['is_active'];

        DB::transaction(function () use ($account, $isActive, $request, $sessions): void {
            $attrs = ['is_active' => $isActive];
            if (!$isActive) {
                $attrs['remember_token'] = Str::random(60);
                DB::table('password_resets')->where('email', $account->email)->delete();
                $sessions->invalidateAllForUser($account);
            }
            $account->forceFill($attrs)->save();

            TechnicalAuditLogger::record(
                $request,
                $request->user()->id,
                'account_admin',
                $isActive ? 'system_admin_activated' : 'system_admin_deactivated',
                "System Admin account {$account->username} " . ($isActive ? 'activated' : 'deactivated') . '.',
                User::class,
                $account->id
            );
        });

        return redirect()->route('sysadmin.accounts.index')->with('success', 'System Admin status updated.');
    }

    public function accessRequests()
    {
        PermissionService::expireElapsedGrants();

        $requests = BusinessAccessRequest::with('reviewer')
            ->where('system_admin_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('sysadmin.access-requests', [
            'requests' => $requests,
            'scopes' => BusinessAccessRequest::SCOPES,
        ]);
    }

    public function storeAccessRequest(Request $request)
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', Rule::in(array_keys(BusinessAccessRequest::SCOPES))],
            'reason' => 'required|string|min:10|max:1000',
            'duration_minutes' => 'required|integer|min:15|max:240',
        ]);

        $grantRequest = DB::transaction(function () use ($validated, $request): BusinessAccessRequest {
            $req = BusinessAccessRequest::create([
                'system_admin_id' => $request->user()->id,
                'scope' => $validated['scope'],
                'reason' => trim($validated['reason']),
                'duration_minutes' => (int) $validated['duration_minutes'],
                'status' => 'pending',
            ]);

            $detail = "System Admin {$request->user()->username} requested exceptional access to [{$req->scope}] for {$req->duration_minutes} mins.";
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'exceptional_access_requested',
                'model_type' => BusinessAccessRequest::class,
                'model_id' => $req->id,
                'details' => $detail,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            TechnicalAuditLogger::record(
                $request,
                $request->user()->id,
                'access_request',
                'exceptional_access_requested',
                $detail,
                BusinessAccessRequest::class,
                $req->id
            );

            return $req;
        });

        return redirect()->route('sysadmin.access.index')
            ->with('success', 'Exceptional business data access request submitted for Owner review.');
    }

    public function viewGrantedBusinessData(Request $request, BusinessAccessRequest $accessRequest)
    {
        if ((int) $accessRequest->system_admin_id !== (int) $request->user()->id) {
            abort(403, 'You can only use your own approved exceptional access grants.');
        }

        PermissionService::expireElapsedGrants();
        $accessRequest->refresh();

        if (!$accessRequest->isCurrentlyActive()) {
            TechnicalAuditLogger::record(
                $request,
                $request->user()->id,
                'access_request',
                'exceptional_access_denied',
                "Denied access to [{$accessRequest->scope}] using request #{$accessRequest->id} (status={$accessRequest->effectiveStatus()}).",
                BusinessAccessRequest::class,
                $accessRequest->id
            );

            abort(403, 'This business access authorization is inactive, revoked, or expired.');
        }

        $accessRequest->update(['last_accessed_at' => now()]);

        $detail = "System Admin {$request->user()->username} accessed scoped read-only [{$accessRequest->scope}] data under Owner grant #{$accessRequest->id}.";
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'exceptional_access_viewed',
            'model_type' => BusinessAccessRequest::class,
            'model_id' => $accessRequest->id,
            'details' => $detail,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        TechnicalAuditLogger::record(
            $request,
            $request->user()->id,
            'access_request',
            'exceptional_access_viewed',
            $detail,
            BusinessAccessRequest::class,
            $accessRequest->id
        );

        $records = match ($accessRequest->scope) {
            'inventory' => InventoryItem::with(['category', 'unit'])->orderBy('name')->take(50)->get(),
            'transactions' => InventoryTransaction::with(['inventoryItem.unit', 'user:id,name,username'])->latest('transaction_date')->take(50)->get(),
            'purchases' => Purchase::with(['supplier:id,name', 'creator:id,name,username'])->latest('purchase_date')->take(50)->get(),
            'suppliers' => Supplier::orderBy('name')->take(50)->get(),
            'reports' => [
                'active_items' => InventoryItem::where('is_active', true)->count(),
                'low_or_out_items' => InventoryItem::where('is_active', true)->whereColumn('current_quantity', '<=', 'minimum_stock_level')->count(),
                'purchases_total' => (float) Purchase::realized()->sum('total_amount'),
                'purchases_count' => Purchase::realized()->count(),
                'transactions_count' => InventoryTransaction::count(),
            ],
            'audit_logs' => AuditLog::with('user:id,name,username')->latest()->take(50)->get(),
            default => collect(),
        };

        return view('sysadmin.access-grant-view', [
            'accessRequest' => $accessRequest,
            'records' => $records,
        ]);
    }
}
