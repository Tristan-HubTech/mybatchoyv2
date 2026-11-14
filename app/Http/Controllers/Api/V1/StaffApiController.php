<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $staffRoleId = Role::where('name', 'staff')->value('id');
        $query = User::with('role')->where('role_id', $staffRoleId);

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (($validated['status'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($validated['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        }

        $staff = $query->orderBy('name')->get();

        return response()->json([
            'staff' => $staff->map(fn (User $u) => AuthApiController::formatUser($u))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:32',
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
            'is_active' => 'required|boolean',
        ]);

        $staff = DB::transaction(function () use ($validated, $request): User {
            $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

            $staff = User::create([
                'role_id' => $staffRole->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) $validated['is_active'],
            ]);

            $this->recordAccountChange($request, $staff, 'staff_created', 'Staff account created.');

            return $staff->load('role');
        });

        return response()->json([
            'message' => 'Staff account created.',
            'staff' => AuthApiController::formatUser($staff),
        ], 201);
    }

    public function update(Request $request, int $staff): JsonResponse
    {
        $staffMember = $this->findStaff($staff);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($staffMember->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staffMember->id)],
            'phone' => 'nullable|string|max:32',
        ]);

        $staffMember->update($validated);
        $this->recordAccountChange($request, $staffMember, 'staff_updated', 'Staff account information updated.');

        return response()->json([
            'message' => 'Staff account updated.',
            'staff' => AuthApiController::formatUser($staffMember->fresh('role')),
        ]);
    }

    public function updateStatus(Request $request, int $staff): JsonResponse
    {
        $staffMember = $this->findStaff($staff);
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $isActive = (bool) $validated['is_active'];

        if ($staffMember->is_active !== $isActive) {
            DB::transaction(function () use ($staffMember, $isActive, $request): void {
                $attributes = ['is_active' => $isActive];
                if (!$isActive) {
                    $attributes['remember_token'] = Str::random(60);
                    DB::table('password_resets')->where('email', $staffMember->email)->delete();
                    $staffMember->tokens()->delete();
                }

                $staffMember->forceFill($attributes)->save();
                $this->recordAccountChange(
                    $request,
                    $staffMember,
                    $isActive ? 'staff_activated' : 'staff_deactivated',
                    $isActive ? 'Staff account activated.' : 'Staff account deactivated.'
                );
            });
        }

        return response()->json([
            'message' => 'Staff account status updated.',
            'staff' => AuthApiController::formatUser($staffMember->fresh('role')),
        ]);
    }

    public function resetPassword(Request $request, int $staff): JsonResponse
    {
        $staffMember = $this->findStaff($staff);
        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        DB::transaction(function () use ($staffMember, $validated, $request): void {
            $staffMember->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_resets')->where('email', $staffMember->email)->delete();
            $staffMember->tokens()->delete();
            $this->recordAccountChange($request, $staffMember, 'staff_password_reset', 'Staff password reset by an owner.');
        });

        return response()->json([
            'message' => 'Staff password reset.',
            'staff' => AuthApiController::formatUser($staffMember->fresh('role')),
        ]);
    }

    private function findStaff(int $id): User
    {
        return User::whereKey($id)
            ->whereHas('role', fn ($query) => $query->where('name', 'staff'))
            ->firstOrFail();
    }

    private function recordAccountChange(Request $request, User $staff, string $action, string $details): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'model_type' => User::class,
            'model_id' => $staff->id,
            'details' => $details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}

