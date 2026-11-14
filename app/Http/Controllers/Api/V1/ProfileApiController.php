<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => AuthApiController::formatUser($request->user()->load('role')),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:32',
        ]);

        $user->update($validated);
        $this->recordProfileEvent($request, $user, 'profile_updated', 'Profile information updated.');

        return response()->json([
            'message' => 'Profile updated.',
            'user' => AuthApiController::formatUser($user->fresh('role')),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentHash = $user->getAuthPassword();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($currentHash): void {
                    if (!Hash::check((string) $value, $currentHash)) {
                        $fail('The provided password does not match your current password.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail) use ($currentHash): void {
                    if (Hash::check((string) $value, $currentHash)) {
                        $fail('Your new password must be different from your current password.');
                    }
                },
            ],
        ]);

        $currentTokenId = $user->currentAccessToken()?->id;

        DB::transaction(function () use ($user, $validated, $request, $currentTokenId): void {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_resets')->where('email', $user->email)->delete();

            if ($currentTokenId) {
                $user->tokens()->where('id', '!=', $currentTokenId)->delete();
            } else {
                $user->tokens()->delete();
            }

            $this->recordProfileEvent($request, $user, 'profile_password_changed', 'Account password changed by the account owner.');
        });

        return response()->json([
            'message' => 'Password changed.',
        ]);
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

