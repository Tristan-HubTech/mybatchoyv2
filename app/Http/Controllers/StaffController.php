<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $staffRoleId = Role::where('name', 'staff')->value('id');
        $staff = User::with('role')->where('role_id', $staffRoleId);

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $staff->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (($validated['status'] ?? null) === 'active') {
            $staff->where('is_active', true);
        } elseif (($validated['status'] ?? null) === 'inactive') {
            $staff->where('is_active', false);
        }

        return view('staff.index', [
            'staff' => $staff->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('staff.form', ['staffMember' => null]);
    }

    public function store(Request $request)
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
            $staff = User::create([
                'role_id' => Role::where('name', 'staff')->firstOrFail()->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) $validated['is_active'],
            ]);

            $this->recordAccountChange($request, $staff, 'staff_created', 'Staff account created.');

            return $staff;
        });

        return redirect()->route('owner.staff.index')->with('success', 'Staff account created.');
    }

    public function edit(int $staff)
    {
        return view('staff.form', ['staffMember' => $this->findStaff($staff)]);
    }

    public function update(Request $request, int $staff)
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

        return redirect()->route('owner.staff.index')->with('success', 'Staff account updated.');
    }

    public function updateStatus(Request $request, int $staff)
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

        return redirect()->route('owner.staff.index')->with('success', 'Staff account status updated.');
    }

    public function resetPassword(Request $request, int $staff)
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
            $this->recordAccountChange($request, $staffMember, 'staff_password_reset', 'Staff password reset by an owner.');
        });

        return redirect()->route('owner.staff.edit', $staffMember)->with('success', 'Staff password reset.');
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
