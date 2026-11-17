<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\ActiveSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SupplierAccountApiController extends Controller
{
    public function index(): JsonResponse
    {
        $roleId = Role::where('name', 'supplier')->value('id');
        $accounts = $roleId
            ? User::with(['role', 'supplier'])->where('role_id', $roleId)->orderBy('name')->get()
            : collect();

        return response()->json([
            'supplier_accounts' => $accounts->map(fn (User $u) => AuthApiController::formatUser($u))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'role' => 'prohibited',
            'role_id' => 'prohibited',
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', 1)],
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => 'nullable|string|max:32',
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
            'is_active' => 'required|boolean',
        ]);

        $account = DB::transaction(function () use ($validated, $request): User {
            $role = Role::firstOrCreate(['name' => 'supplier'], ['label' => 'Supplier']);

            $user = User::create([
                'role_id' => $role->id,
                'supplier_id' => (int) $validated['supplier_id'],
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => mb_strtolower($validated['email']),
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) $validated['is_active'],
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'supplier_account_created',
                'model_type' => User::class,
                'model_id' => $user->id,
                'details' => "Supplier account {$user->username} created via API for supplier ID {$user->supplier_id}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $user->load(['role', 'supplier']);
        });

        return response()->json([
            'message' => 'Supplier account created.',
            'supplier_account' => AuthApiController::formatUser($account),
        ], 201);
    }

    public function updateStatus(Request $request, User $supplierAccount, ActiveSessionManager $sessions): JsonResponse
    {
        if (!$supplierAccount->isSupplier()) {
            return response()->json(['message' => 'Target user is not a supplier account.'], 404);
        }

        $validated = $request->validate(['is_active' => 'required|boolean']);
        $isActive = (bool) $validated['is_active'];

        if ($supplierAccount->is_active !== $isActive) {
            DB::transaction(function () use ($supplierAccount, $isActive, $request, $sessions): void {
                $attrs = ['is_active' => $isActive];
                if (!$isActive) {
                    $attrs['remember_token'] = Str::random(60);
                    DB::table('password_resets')->where('email', $supplierAccount->email)->delete();
                    $sessions->invalidateAllForUser($supplierAccount);
                }

                $supplierAccount->forceFill($attrs)->save();

                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => $isActive ? 'supplier_account_activated' : 'supplier_account_deactivated',
                    'model_type' => User::class,
                    'model_id' => $supplierAccount->id,
                    'details' => "Supplier account {$supplierAccount->username} " . ($isActive ? 'activated' : 'deactivated') . ' via API.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        }

        return response()->json([
            'message' => 'Supplier account status updated.',
            'supplier_account' => AuthApiController::formatUser($supplierAccount->fresh(['role', 'supplier'])),
        ]);
    }
}

