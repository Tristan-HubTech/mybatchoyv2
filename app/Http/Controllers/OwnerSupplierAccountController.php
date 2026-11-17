<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ActiveSessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class OwnerSupplierAccountController extends Controller
{
    public function index()
    {
        $supplierRoleId = Role::where('name', 'supplier')->value('id');
        $accounts = $supplierRoleId
            ? User::with(['role', 'supplier'])->where('role_id', $supplierRoleId)->orderBy('name')->paginate(15)
            : User::whereRaw('1 = 0')->paginate(15);

        return view('owner.supplier-accounts', [
            'accounts' => $accounts,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
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
            $supplierRole = Role::firstOrCreate(['name' => 'supplier'], ['label' => 'Supplier']);

            $user = User::create([
                'role_id' => $supplierRole->id,
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
                'details' => "Supplier account {$user->username} created and linked to supplier ID {$user->supplier_id}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $user;
        });

        return redirect()->route('owner.supplier-accounts.index')
            ->with('success', "Supplier account {$account->username} created.");
    }

    public function updateStatus(Request $request, User $supplierAccount, ActiveSessionManager $sessions)
    {
        if (!$supplierAccount->isSupplier()) {
            abort(403, 'Target user is not a supplier account.');
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);
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
                    'details' => "Supplier account {$supplierAccount->username} " . ($isActive ? 'activated' : 'deactivated') . '.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        }

        return redirect()->route('owner.supplier-accounts.index')
            ->with('success', 'Supplier account status updated.');
    }

    public function resetPassword(Request $request, User $supplierAccount, ActiveSessionManager $sessions)
    {
        if (!$supplierAccount->isSupplier()) {
            abort(403, 'Target user is not a supplier account.');
        }

        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        DB::transaction(function () use ($supplierAccount, $validated, $request, $sessions): void {
            $supplierAccount->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_resets')->where('email', $supplierAccount->email)->delete();
            $sessions->invalidateAllForUser($supplierAccount);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'supplier_account_password_reset',
                'model_type' => User::class,
                'model_id' => $supplierAccount->id,
                'details' => "Supplier account {$supplierAccount->username} password reset by Owner.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('owner.supplier-accounts.index')
            ->with('success', "Password reset for supplier account {$supplierAccount->username}.");
    }
}

