<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\InventoryCategory;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserActiveSession;
use App\Services\ActiveSessionManager;
use App\Services\OwnerMfaService;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SettingsController extends Controller
{
    public function index(Request $request, ActiveSessionManager $sessions, TotpService $totp)
    {
        $settings = BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => config('app.name'),
                'session_timeout_minutes' => config('session.lifetime', 20),
            ]
        );

        $sessions->pruneExpiredSessions();
        $activeSessions = UserActiveSession::with('user.role')
            ->whereHas('user')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(function (UserActiveSession $record) use ($request): array {
                return [
                    'user_id' => $record->user_id,
                    'username' => $record->user->username,
                    'name' => $record->user->name,
                    'role_label' => $record->user->role?->label ?? ucfirst((string) ($record->user->role?->name ?? 'User')),
                    'device_info' => $record->device_info,
                    'logged_in_at' => $record->logged_in_at,
                    'last_activity_at' => $record->last_activity_at,
                    'status' => $record->isExpired() ? 'Expired' : 'Active',
                    'is_current_user' => (int) $record->user_id === (int) $request->user()?->id,
                ];
            });

        $owner = $request->user();
        $pendingMfaSecret = null;
        $pendingMfaUri = null;
        if ($owner && !$owner->mfa_enabled) {
            $pendingMfaSecret = (string) $request->session()->get('_eb_pending_mfa_secret', '');
            if ($pendingMfaSecret === '') {
                $pendingMfaSecret = $totp->generateSecret();
                $request->session()->put('_eb_pending_mfa_secret', $pendingMfaSecret);
            }
            $pendingMfaUri = $totp->provisioningUri($owner, $pendingMfaSecret);
        }

        $staffRoleId = Role::where('name', 'staff')->value('id');
        $staffAccounts = $staffRoleId
            ? User::where('role_id', $staffRoleId)->orderBy('name')->get(['id', 'name', 'username', 'email', 'is_active'])
            : collect();
        $staffAccessVerified = $this->hasVerifiedStaffAccessSession($request);

        return view('settings.index', [
            'settings' => $settings,
            'categories' => InventoryCategory::withCount('items')->orderBy('name')->get(),
            'units' => Unit::withCount('items')->orderBy('name')->get(),
            'activeSessions' => $activeSessions,
            'pendingMfaSecret' => $pendingMfaSecret,
            'pendingMfaUri' => $pendingMfaUri,
            'staffAccounts' => $staffAccounts,
            'staffAccessVerified' => $staffAccessVerified,
            'systemInfo' => [
                'application_version' => app()->version(),
                'environment' => config('app.env'),
                'database_driver' => DB::connection()->getDriverName(),
                'database_status' => DB::select('SELECT 1') ? 'Connected' : 'Unavailable',
                'session_driver' => config('session.driver'),
            ],
        ]);
    }

    public function sendMfaChallenge(Request $request, OwnerMfaService $mfa)
    {
        $mfa->issueEmailChallenge($request->user(), $request, 'security_verification');

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'A 6-digit MFA verification code has been sent to your Owner email address.');
    }

    public function configureAuthenticator(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'code' => 'required|digits:6',
        ]);

        $owner = $request->user();
        if (!Hash::check((string) $validated['current_password'], $owner->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['The Owner/Admin password entered is incorrect.'],
            ]);
        }

        $secret = (string) $request->session()->get('_eb_pending_mfa_secret', '');
        if ($secret === '' || !$totp->verifyCode($secret, (string) $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Invalid 6-digit Authenticator verification code. Please check your app clock and try again.'],
            ]);
        }

        DB::transaction(function () use ($owner, $secret, $request): void {
            $owner->forceFill([
                'mfa_enabled' => true,
                'mfa_secret' => $secret,
            ])->save();

            AuditLog::create([
                'user_id' => $owner->id,
                'action' => 'owner_mfa_enabled',
                'model_type' => User::class,
                'model_id' => $owner->id,
                'details' => 'Owner/Admin enabled Authenticator App MFA.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        $request->session()->forget('_eb_pending_mfa_secret');

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Authenticator App MFA is now enabled for your Owner account.');
    }

    public function disableAuthenticator(Request $request, OwnerMfaService $mfa)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'mfa_code' => 'required|digits:6',
        ]);

        $owner = $request->user();
        $mfa->verifyPasswordAndMfa(
            $owner,
            (string) $validated['current_password'],
            (string) $validated['mfa_code'],
            'security_verification'
        );

        DB::transaction(function () use ($owner, $request): void {
            $owner->forceFill([
                'mfa_enabled' => false,
                'mfa_secret' => null,
            ])->save();

            AuditLog::create([
                'user_id' => $owner->id,
                'action' => 'owner_mfa_disabled',
                'model_type' => User::class,
                'model_id' => $owner->id,
                'details' => 'Owner/Admin disabled Authenticator App MFA.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Authenticator App MFA disabled. Email MFA verification remains required for sensitive recovery actions.');
    }

    public function revokeActiveSession(Request $request, User $user, ActiveSessionManager $sessions, OwnerMfaService $mfa)
    {
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

        return redirect()
            ->route('owner.settings.index')
            ->with('success', "Active login session for {$user->username} has been revoked. The account can now sign in again.");
    }

    public function verifyStaffAccessManagement(Request $request, OwnerMfaService $mfa)
    {
        $owner = $request->user();
        $rules = [
            'current_password' => 'required|string',
        ];
        if ($owner?->mfa_enabled && !empty($owner->mfa_secret)) {
            $rules['mfa_code'] = 'required|string|size:6';
        }

        $validated = $request->validate($rules);

        $mfa->verifySensitiveAccess(
            $owner,
            (string) $validated['current_password'],
            $validated['mfa_code'] ?? null,
            'staff_access_management'
        );

        $request->session()->put('_eb_staff_access_verified_at', now()->timestamp);
        $request->session()->put('_eb_staff_access_mfa_state', (bool) ($owner?->mfa_enabled && !empty($owner->mfa_secret)));

        AuditLog::create([
            'user_id' => $owner->id,
            'action' => 'staff_access_controls_unlocked',
            'model_type' => User::class,
            'model_id' => $owner->id,
            'details' => 'Owner/Admin re-verified credentials to access Staff Access Management controls in Settings.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->to(route('owner.settings.index') . '#staff-access-management')
            ->with('success', 'Staff Access Management controls unlocked for 10 minutes.');
    }

    public function lockStaffAccessManagement(Request $request)
    {
        $request->session()->forget(['_eb_staff_access_verified_at', '_eb_staff_access_mfa_state']);

        return redirect()
            ->to(route('owner.settings.index') . '#staff-access-management')
            ->with('success', 'Staff Access Management controls locked.');
    }

    public function storeStaffAccount(Request $request)
    {
        if (!$this->hasVerifiedStaffAccessSession($request)) {
            throw ValidationException::withMessages([
                'staff_access' => ['Re-verify your Owner/Admin password (and MFA code when enabled) before creating staff accounts.'],
            ]);
        }

        $validated = $request->validate([
            'role' => 'prohibited',
            'role_id' => 'prohibited',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:32',
            'password' => ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols(), 'confirmed'],
            'is_active' => 'required|boolean',
        ]);

        $staff = DB::transaction(function () use ($validated, $request): User {
            $staffRole = Role::where('name', 'staff')->firstOrFail();

            $staff = User::create([
                'role_id' => $staffRole->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => (bool) $validated['is_active'],
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'staff_created',
                'model_type' => User::class,
                'model_id' => $staff->id,
                'details' => 'Staff account created from Settings Staff Access Management.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $staff;
        });

        $request->session()->put('_eb_staff_access_verified_at', now()->timestamp);

        return redirect()
            ->to(route('owner.settings.index') . '#staff-access-management')
            ->with('success', "Staff account created for {$staff->username}.");
    }

    private function hasVerifiedStaffAccessSession(Request $request): bool
    {
        $verifiedAt = (int) $request->session()->get('_eb_staff_access_verified_at', 0);
        if ($verifiedAt <= 0 || (now()->timestamp - $verifiedAt) > 600) {
            return false;
        }

        $owner = $request->user();
        $mfaCurrentlyEnabled = (bool) ($owner?->mfa_enabled && !empty($owner->mfa_secret));
        $verifiedMfaState = (bool) $request->session()->get('_eb_staff_access_mfa_state', false);

        return $verifiedMfaState === $mfaCurrentlyEnabled;
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'logo' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:2048',
            'session_timeout_minutes' => 'required|integer|min:5|max:240',
            'default_minimum_stock_level' => 'required|numeric|min:0|max:999999999.999',
            'default_reorder_level' => 'required|numeric|min:0|max:999999999.999',
        ]);

        $settings = BusinessSetting::firstOrNew(['id' => 1]);
        $previousLogo = $settings->logo_path;
        $newLogo = null;

        if ($request->hasFile('logo')) {
            $newLogo = $request->file('logo')->storePublicly('business-logos', 'public');
            if ($newLogo === false) {
                throw new RuntimeException('The business logo could not be stored.');
            }
        }

        unset($validated['logo']);
        if ($newLogo !== null) {
            $validated['logo_path'] = $newLogo;
        }

        try {
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
        } catch (Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete($newLogo);
            }

            throw $exception;
        }

        if ($newLogo !== null && $previousLogo !== null) {
            Storage::disk('public')->delete($previousLogo);
        }

        return redirect()->route('owner.settings.index')->with('success', 'Settings saved.');
    }
}
