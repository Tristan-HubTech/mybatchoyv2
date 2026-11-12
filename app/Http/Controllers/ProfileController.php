<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', ['user' => $request->user()->load('role')]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:32',
        ]);

        $user->update($validated);
        $this->recordProfileEvent($request, $user, 'profile_updated', 'Profile information updated.');

        return redirect()->route('profile.show')->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $currentHash = $user->getAuthPassword();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail) use ($currentHash): void {
                    if (Hash::check($value, $currentHash)) {
                        $fail('Your new password must be different from your current password.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($user, $validated, $request): void {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_resets')->where('email', $user->email)->delete();
            $this->recordProfileEvent($request, $user, 'profile_password_changed', 'Account password changed by the account owner.');
        });

        $request->session()->put('password_hash_' . Auth::getDefaultDriver(), $user->getAuthPassword());

        return redirect()->route('profile.show')->with('success', 'Password changed.');
    }

    private function recordProfileEvent(Request $request, User $user, string $action, string $details): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => User::class,
            'model_id' => $user->id,
            'details' => $details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}