<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SystemAdminApiController extends Controller
{
    public function health(): JsonResponse
    {
        $start = microtime(true);
        $dbConnected = (bool) DB::select('SELECT 1');
        $latencyMs = round((microtime(true) - $start) * 1000, 2);

        return response()->json([
            'database_status' => $dbConnected ? 'Connected' : 'Unavailable',
            'database_driver' => strtoupper(DB::connection()->getDriverName()),
            'db_latency_ms' => $latencyMs,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'technical_settings' => TechnicalSetting::current(),
        ]);
    }

    public function security(ActiveSessionManager $sessions): JsonResponse
    {
        $sessions->pruneExpiredSessions();

        return response()->json([
            'technical_settings' => TechnicalSetting::current(),
            'active_sessions' => UserActiveSession::with('user.role')
                ->whereHas('user')
                ->orderByDesc('last_activity_at')
                ->get()
                ->map(fn (UserActiveSession $s) => [
                    'username' => $s->user->username,
                    'role' => $s->user->role?->name,
                    'channel' => $s->channel,
                    'device_info' => $s->device_info,
                    'last_activity_at' => $s->last_activity_at?->toIso8601String(),
                    'status' => $s->isExpired() ? 'Expired' : 'Active',
                ])->values(),
            'technical_logs' => TechnicalAuditLog::with('user:id,name,username')
                ->latest()
                ->take(40)
                ->get(),
        ]);
    }

    public function updateConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_notice' => 'nullable|string|max:255',
            'log_retention_days' => 'nullable|integer|min:7|max:365',
            'security_lockout_attempts' => 'nullable|integer|min:3|max:15',
            'security_lockout_minutes' => 'nullable|integer|min:5|max:120',
            'api_rate_limit_per_minute' => 'nullable|integer|min:20|max:300',
            'backup_retention_days' => 'nullable|integer|min:7|max:365',
            'owner_can_edit_technical' => 'nullable|boolean',
        ]);

        unset($validated['current_password']);
        $settings = TechnicalSetting::current();
        $validated['updated_by'] = $request->user()->id;
        $settings->update($validated);

        TechnicalAuditLogger::record(
            $request,
            $request->user()->id,
            'technical',
            'technical_config_updated_api',
            'Technical configuration updated via API.',
            TechnicalSetting::class,
            $settings->id
        );

        return response()->json([
            'message' => 'Technical configuration updated.',
            'technical_settings' => $settings->fresh(),
        ]);
    }

    public function accounts(): JsonResponse
    {
        $roleId = Role::where('name', 'system_admin')->value('id');
        $admins = $roleId
            ? User::with('role')->where('role_id', $roleId)->orderBy('name')->get()
            : collect();

        return response()->json([
            'accounts' => $admins->map(fn (User $u) => AuthApiController::formatUser($u))->values(),
        ]);
    }

    public function storeAccount(Request $request): JsonResponse
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

        $admin = DB::transaction(function () use ($validated, $request): User {
            $role = Role::firstOrCreate(['name' => 'system_admin'], ['label' => 'System Admin (Developer)']);
            $user = User::create([
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
                'system_admin_created_api',
                "System Admin account {$user->username} created via API.",
                User::class,
                $user->id
            );

            return $user->load('role');
        });

        return response()->json([
            'message' => 'System Admin account created.',
            'account' => AuthApiController::formatUser($admin),
        ], 201);
    }

    public function accessRequests(Request $request): JsonResponse
    {
        PermissionService::expireElapsedGrants();

        $requests = BusinessAccessRequest::with('reviewer:id,name,username')
            ->where('system_admin_id', $request->user()->id)
            ->latest()
            ->take(50)
            ->get();

        return response()->json(['access_requests' => $requests]);
    }

    public function storeAccessRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', Rule::in(array_keys(BusinessAccessRequest::SCOPES))],
            'reason' => 'required|string|min:10|max:1000',
            'duration_minutes' => 'required|integer|min:15|max:240',
        ]);

        $req = DB::transaction(function () use ($validated, $request): BusinessAccessRequest {
            $grant = BusinessAccessRequest::create([
                'system_admin_id' => $request->user()->id,
                'scope' => $validated['scope'],
                'reason' => trim($validated['reason']),
                'duration_minutes' => (int) $validated['duration_minutes'],
                'status' => 'pending',
            ]);

            $detail = "System Admin {$request->user()->username} requested [{$grant->scope}] access for {$grant->duration_minutes} mins via API.";
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'exceptional_access_requested',
                'model_type' => BusinessAccessRequest::class,
                'model_id' => $grant->id,
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
                $grant->id
            );

            return $grant;
        });

        return response()->json([
            'message' => 'Business access request submitted for Owner approval.',
            'access_request' => $req,
        ], 201);
    }

    public function viewGrantedBusinessData(Request $request, BusinessAccessRequest $accessRequest): JsonResponse
    {
        if ((int) $accessRequest->system_admin_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'You can only use your own approved exceptional grants.'], 403);
        }

        PermissionService::expireElapsedGrants();
        $accessRequest->refresh();

        if (!$accessRequest->isCurrentlyActive()) {
            TechnicalAuditLogger::record(
                $request,
                $request->user()->id,
                'access_request',
                'exceptional_access_denied',
                "Denied API access to [{$accessRequest->scope}] via grant #{$accessRequest->id} (status={$accessRequest->effectiveStatus()}).",
                BusinessAccessRequest::class,
                $accessRequest->id
            );

            return response()->json(['message' => 'This exceptional access grant is inactive, revoked, or expired.'], 403);
        }

        $accessRequest->update(['last_accessed_at' => now()]);
        $detail = "System Admin {$request->user()->username} viewed [{$accessRequest->scope}] records via API grant #{$accessRequest->id}.";

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
            'inventory' => InventoryItem::with(['category:id,name', 'unit:id,name,short_name'])->orderBy('name')->take(50)->get(),
            'transactions' => InventoryTransaction::with(['inventoryItem:id,name,sku', 'user:id,name,username'])->latest('transaction_date')->take(50)->get(),
            'purchases' => Purchase::with(['supplier:id,name', 'creator:id,name,username'])->latest('purchase_date')->take(50)->get(),
            'suppliers' => Supplier::orderBy('name')->take(50)->get(['id', 'name', 'contact_person', 'phone', 'email', 'is_active']),
            'reports' => [
                'active_items' => InventoryItem::where('is_active', true)->count(),
                'purchases_total' => (float) Purchase::realized()->sum('total_amount'),
                'purchases_count' => Purchase::realized()->count(),
                'transactions_count' => InventoryTransaction::count(),
            ],
            'audit_logs' => AuditLog::with('user:id,name,username')->latest()->take(50)->get(),
            default => [],
        };

        return response()->json([
            'grant' => $accessRequest,
            'scope' => $accessRequest->scope,
            'records' => $records,
        ]);
    }
}

