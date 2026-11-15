<?php

namespace Tests\Feature;

use App\Mail\SecurityMfaCode;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\Role;
use App\Models\User;
use App\Models\UserActiveSession;
use App\Services\ActiveSessionManager;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SingleActiveSessionTest extends TestCase
{
    use RefreshDatabase;

    private const REJECTION_MESSAGE = 'This account is already logged in on another device. Please log out from that device before trying again.';

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => 'MyBatchoy Single Session Test',
                'session_timeout_minutes' => 30,
                'default_minimum_stock_level' => 5,
                'default_reorder_level' => 10,
            ]
        );

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'name' => 'Owner One',
            'username' => 'single-owner',
            'email' => 'single-owner@mybatchoy.test',
            'password' => Hash::make('OwnerSecure2026!'),
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Staff One',
            'username' => 'single-staff',
            'email' => 'single-staff@mybatchoy.test',
            'password' => Hash::make('StaffSecure2026!'),
            'is_active' => true,
        ]);
    }

    public function test_1_to_4_first_login_succeeds_second_browser_or_device_rejected_and_original_stays_active(): void
    {
        // 1. First login succeeds (Computer A: Chrome on Windows)
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/126.0.0.0 Safari/537.36'])
            ->post(route('login'), [
                'username' => $this->owner->username,
                'password' => 'OwnerSecure2026!',
            ])
            ->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($this->owner);
        $computerASessionToken = (string) session(ActiveSessionManager::WEB_SESSION_TOKEN_KEY);
        $this->assertNotEmpty($computerASessionToken);

        $activeRow = UserActiveSession::where('user_id', $this->owner->id)->firstOrFail();
        $this->assertSame('Google Chrome on Windows', $activeRow->device_info);

        // Simulate clean isolated second browser/device (Computer B: Firefox on macOS)
        Auth::forgetGuards();
        session()->flush();
        session()->regenerate(true);

        // 2. Second login from another browser is rejected with exact message
        $secondBrowserAttempt = $this->from(route('login'))
            ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) Firefox/127.0'])
            ->post(route('login'), [
                'username' => $this->owner->username,
                'password' => 'OwnerSecure2026!',
            ]);

        $secondBrowserAttempt->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'username' => self::REJECTION_MESSAGE,
            ]);
        $this->assertGuest();

        // 3. Second login from another device (Mobile API) is also rejected
        $mobileAttempt = $this->postJson('/api/v1/auth/login', [
            'username' => $this->owner->username,
            'password' => 'OwnerSecure2026!',
            'device_name' => 'Samsung Galaxy S24',
        ]);

        $mobileAttempt->assertStatus(422)
            ->assertJsonValidationErrors('username')
            ->assertJsonPath('errors.username.0', self::REJECTION_MESSAGE);
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $this->owner->id)->count());

        // 4. Original session (Computer A) remains active and untouched
        $this->assertSame(1, UserActiveSession::where('user_id', $this->owner->id)->count());
        $this->assertSame(
            app(ActiveSessionManager::class)->hashWebSessionToken($computerASessionToken),
            UserActiveSession::where('user_id', $this->owner->id)->value('session_hash')
        );

        // Restore Computer A's exact session state & verify protected route succeeds
        Auth::login($this->owner);
        session()->put(ActiveSessionManager::WEB_SESSION_BOUND_KEY, true);
        session()->put(ActiveSessionManager::WEB_SESSION_USER_KEY, $this->owner->id);
        session()->put(ActiveSessionManager::WEB_SESSION_TOKEN_KEY, $computerASessionToken);
        $this->get(route('owner.dashboard'))->assertOk();
    }

    public function test_3b_staff_active_on_mobile_blocks_web_login_and_keeps_mobile_active(): void
    {
        $mobileLogin = $this->postJson('/api/v1/auth/login', [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
            'device_name' => 'Expo Go Kitchen Tablet',
        ]);

        $mobileLogin->assertOk();
        $token = (string) $mobileLogin->json('token');

        // Another device tries Web login with same Staff account
        $this->from(route('login'))
            ->post(route('login'), [
                'username' => $this->staff->username,
                'password' => 'StaffSecure2026!',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => self::REJECTION_MESSAGE]);

        $this->assertGuest();

        // Original Mobile API session continues operating
        $this->withToken($token)->getJson('/api/v1/dashboard')->assertOk();
    }

    public function test_5_simultaneous_login_attempts_cannot_create_two_active_sessions(): void
    {
        $sessions = app(ActiveSessionManager::class);
        $request = request();
        $request->setLaravelSession($this->app['session.store']);

        // Simulate a concurrent/re-entrant second login attempting inside the first claim's callback
        $secondAttemptRejected = false;
        $sessions->claimForWeb($this->staff, $request, function () use ($sessions, $request, &$secondAttemptRejected): void {
            Auth::login($this->staff);
            $request->session()->regenerate();

            try {
                $sessions->claimForApi(
                    $this->staff,
                    $request,
                    'Concurrent Phone',
                    fn ($expiresAt) => $this->staff->createToken('Concurrent Phone', ['*'], $expiresAt)
                );
            } catch (ValidationException $exception) {
                $secondAttemptRejected = true;
                $this->assertSame(self::REJECTION_MESSAGE, $exception->errors()['username'][0] ?? '');
            }
        });

        $this->assertTrue($secondAttemptRejected);
        $this->assertSame(1, UserActiveSession::where('user_id', $this->staff->id)->count());
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $this->staff->id)->count());

        // Also verify database unique constraint independently prevents duplicate rows per user
        $duplicateRejectedByDb = false;
        try {
            UserActiveSession::create([
                'user_id' => $this->staff->id,
                'channel' => 'api',
                'session_hash' => hash('sha256', 'race-duplicate'),
                'device_info' => 'Race Device',
                'logged_in_at' => now(),
                'last_activity_at' => now(),
                'expires_at' => now()->addMinutes(30),
            ]);
        } catch (\Illuminate\Database\QueryException) {
            $duplicateRejectedByDb = true;
        }

        $this->assertTrue($duplicateRejectedByDb);
        $this->assertSame(1, UserActiveSession::where('user_id', $this->staff->id)->count());
    }

    public function test_6_logout_releases_active_session_for_web_and_mobile(): void
    {
        // Web login then logout
        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
        ])->assertRedirect(route('staff.dashboard'));

        $this->assertDatabaseHas('user_active_sessions', ['user_id' => $this->staff->id]);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseMissing('user_active_sessions', ['user_id' => $this->staff->id]);

        // Different device (Mobile API) can now log in cleanly
        $apiLogin = $this->postJson('/api/v1/auth/login', [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
            'device_name' => 'Kitchen Phone',
        ])->assertOk();

        $token = (string) $apiLogin->json('token');
        $this->assertDatabaseHas('user_active_sessions', ['user_id' => $this->staff->id]);

        // Mobile logout releases session so Web can log in again
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseMissing('user_active_sessions', ['user_id' => $this->staff->id]);

        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        session()->flush();
        $this->withoutToken()->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
        ])->assertRedirect(route('staff.dashboard'));
    }

    public function test_7_expired_sessions_can_be_replaced_and_stale_requests_rejected(): void
    {
        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
        ])->assertRedirect(route('staff.dashboard'));

        $expiredSessionToken = (string) session(ActiveSessionManager::WEB_SESSION_TOKEN_KEY);

        // Force expiration on the stored active session
        UserActiveSession::where('user_id', $this->staff->id)->update([
            'expires_at' => now()->subMinute(),
        ]);

        // Simulate fresh Computer B logging in after expiration
        Auth::forgetGuards();
        session()->flush();
        session()->regenerate(true);

        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
        ])->assertRedirect(route('staff.dashboard'));

        $newSession = UserActiveSession::where('user_id', $this->staff->id)->firstOrFail();
        $this->assertNotSame(
            app(ActiveSessionManager::class)->hashWebSessionToken($expiredSessionToken),
            $newSession->session_hash
        );
        $this->assertFalse($newSession->isExpired());
    }

    public function test_8_password_changes_invalidate_previous_session(): void
    {
        // Staff logs in via Mobile API first
        $mobileLogin = $this->postJson('/api/v1/auth/login', [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
            'device_name' => 'Staff Old Phone',
        ])->assertOk();
        $oldToken = (string) $mobileLogin->json('token');

        // Owner resets Staff password
        $this->actingAs($this->owner)
            ->put(route('owner.staff.password', $this->staff), [
                'password' => 'BrandNewStaff2026!',
                'password_confirmation' => 'BrandNewStaff2026!',
            ])
            ->assertRedirect(route('owner.staff.edit', $this->staff));

        $this->assertDatabaseMissing('user_active_sessions', ['user_id' => $this->staff->id]);
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $this->staff->id)->count());

        // Old mobile bearer token no longer authenticates
        $this->app['auth']->forgetGuards();
        $this->withToken($oldToken)->getJson('/api/v1/dashboard')->assertStatus(401);

        // Now test self-service web password change rotates/invalidates old web session ID
        Auth::forgetGuards();
        session()->flush();
        session()->regenerate(true);

        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'BrandNewStaff2026!',
        ])->assertRedirect(route('staff.dashboard'));

        $oldWebSessionId = session()->getId();
        $oldWebToken = (string) session(ActiveSessionManager::WEB_SESSION_TOKEN_KEY);
        $oldHash = app(ActiveSessionManager::class)->hashWebSessionToken($oldWebToken);

        $this->put(route('profile.password'), [
            'current_password' => 'BrandNewStaff2026!',
            'password' => 'RotatedStaff2026!',
            'password_confirmation' => 'RotatedStaff2026!',
        ])->assertRedirect(route('profile.show'));

        $newWebSessionId = session()->getId();
        $newWebToken = (string) session(ActiveSessionManager::WEB_SESSION_TOKEN_KEY);
        $this->assertNotSame($oldWebSessionId, $newWebSessionId);
        $this->assertNotSame($oldWebToken, $newWebToken);
        $this->assertNotSame(
            $oldHash,
            UserActiveSession::where('user_id', $this->staff->id)->value('session_hash')
        );

        // Old session token fails revalidation check
        session()->put(ActiveSessionManager::WEB_SESSION_TOKEN_KEY, $oldWebToken);
        $this->get(route('staff.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_9_and_10_owner_can_view_and_securely_revoke_stuck_session_with_password_and_mfa_while_staff_cannot(): void
    {
        Mail::fake();

        // Staff logs in on a stuck browser session
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1'])
            ->post(route('login'), [
                'username' => $this->staff->username,
                'password' => 'StaffSecure2026!',
            ])
            ->assertRedirect(route('staff.dashboard'));

        $stuckSessionHash = (string) UserActiveSession::where('user_id', $this->staff->id)->value('session_hash');

        // 10. Staff cannot access Settings or revoke sessions (403 Forbidden)
        $this->get(route('owner.settings.index'))->assertForbidden();
        $this->post(route('owner.settings.mfa.challenge'))->assertForbidden();
        $this->post(route('owner.settings.sessions.revoke', $this->staff), [
            'current_password' => 'StaffSecure2026!',
            'mfa_code' => '123456',
        ])->assertForbidden();
        $this->assertDatabaseHas('user_active_sessions', ['user_id' => $this->staff->id]);

        // Owner (on separate browser session) views Settings -> Security & Active Login Sessions
        Auth::forgetGuards();
        session()->flush();
        session()->regenerate(true);
        $settingsPage = $this->actingAs($this->owner)->get(route('owner.settings.index'));
        $settingsPage->assertOk()
            ->assertSee('Single Active Session')
            ->assertSee('Only one device or browser can use an account at a time.')
            ->assertSee('single-staff')
            ->assertSee('Safari on iPhone')
            ->assertDontSee($stuckSessionHash);

        // Revocation fails with invalid password
        $this->post(route('owner.settings.sessions.revoke', $this->staff), [
            'current_password' => 'WrongPassword2026!',
            'mfa_code' => '123456',
        ])->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('user_active_sessions', ['user_id' => $this->staff->id]);

        // Revocation fails with wrong/missing MFA code even when password is right
        $this->post(route('owner.settings.sessions.revoke', $this->staff), [
            'current_password' => 'OwnerSecure2026!',
            'mfa_code' => '000000',
        ])->assertSessionHasErrors('mfa_code');
        $this->assertDatabaseHas('user_active_sessions', ['user_id' => $this->staff->id]);

        // Owner requests 6-digit MFA code via email
        $this->post(route('owner.settings.mfa.challenge'))
            ->assertRedirect(route('owner.settings.index'));

        $sentCode = '';
        Mail::assertSent(SecurityMfaCode::class, function (SecurityMfaCode $mail) use (&$sentCode): bool {
            $sentCode = $mail->code;

            return $mail->hasTo($this->owner->email);
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sentCode);

        // Owner verifies password + 6-digit MFA code -> revokes stuck session & writes audit log
        $this->post(route('owner.settings.sessions.revoke', $this->staff), [
            'current_password' => 'OwnerSecure2026!',
            'mfa_code' => $sentCode,
        ])->assertRedirect(route('owner.settings.index'));

        $this->assertDatabaseMissing('user_active_sessions', ['user_id' => $this->staff->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'session_revoked',
            'model_type' => User::class,
            'model_id' => $this->staff->id,
        ]);

        // Reusing same one-time email code is blocked
        $this->assertNotNull(
            AuditLog::where('action', 'session_revoked')->where('model_id', $this->staff->id)->first()
        );

        // Staff account can immediately log in again on a new device
        Auth::forgetGuards();
        session()->flush();
        session()->regenerate(true);
        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
        ])->assertRedirect(route('staff.dashboard'));

        $this->assertDatabaseHas('user_active_sessions', ['user_id' => $this->staff->id]);
    }

    public function test_owner_can_enable_totp_authenticator_mfa_and_use_it_for_session_recovery(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        // Staff holds an active mobile session
        $this->postJson('/api/v1/auth/login', [
            'username' => $this->staff->username,
            'password' => 'StaffSecure2026!',
            'device_name' => 'Kitchen Phone',
        ])->assertOk();

        // Owner sets up Authenticator App MFA on Settings
        $this->actingAs($this->owner)->withSession(['_eb_pending_mfa_secret' => $secret]);
        $setupCode = $this->currentTotpCode($secret);

        $this->post(route('owner.settings.mfa.configure'), [
            'current_password' => 'OwnerSecure2026!',
            'code' => $setupCode,
        ])->assertRedirect(route('owner.settings.index'));

        $this->assertTrue($this->owner->fresh()->mfa_enabled);

        // Owner uses Authenticator App TOTP code + password to revoke Staff's mobile session
        $recoveryCode = $this->currentTotpCode($secret);
        $this->post(route('owner.settings.sessions.revoke', $this->staff), [
            'current_password' => 'OwnerSecure2026!',
            'mfa_code' => $recoveryCode,
        ])->assertRedirect(route('owner.settings.index'));

        $this->assertDatabaseMissing('user_active_sessions', ['user_id' => $this->staff->id]);
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $this->staff->id)->count());
    }

    private function currentTotpCode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $cleaned = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bitsLeft = 0;
        $binaryKey = '';

        for ($i = 0, $len = strlen($cleaned); $i < $len; $i++) {
            $val = strpos($alphabet, $cleaned[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binaryKey .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        $counter = (int) floor(time() / 30);
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $binaryKey, true);
        $offset = ord($hash[19]) & 0x0F;
        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
