<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'Security Test Owner',
            'username' => 'testowner',
            'email' => 'testowner@mybatchoy.com',
            'password' => Hash::make('OldPassword123'),
            'is_active' => true,
        ]);

        RateLimiter::clear('password-reset-request:127.0.0.1');
        RateLimiter::clear('password-reset-verify:127.0.0.1');
    }

    public function test_forgot_password_and_reset_pages_render_successfully(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertOk();
        $response->assertSee('Reset Password');
        $response->assertSee('6-digit verification code');

        $resetResponse = $this->get(route('password.reset'));
        $resetResponse->assertOk();
        $resetResponse->assertSee('Enter Verification Code');
    }

    public function test_user_can_request_reset_code_with_username_or_email(): void
    {
        $response = $this->post(route('password.email'), [
            'identifier' => 'testowner',
        ]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionHas('status');

        // Verify DB record exists and is hashed
        $record = DB::table('password_resets')->where('email', $this->user->email)->first();
        $this->assertNotNull($record);
        $this->assertNotEquals(6, strlen($record->token)); // Must be bcrypt hash, not plaintext

        // Verify audit log was recorded
        $audit = AuditLog::where('user_id', $this->user->id)
            ->where('action', 'password_reset_code_requested')
            ->first();
        $this->assertNotNull($audit);
    }

    public function test_nonexistent_identifier_returns_generic_message_without_enumeration(): void
    {
        $response = $this->post(route('password.email'), [
            'identifier' => 'nonexistent_person_123',
        ]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('password_resets', ['email' => 'nonexistent_person_123']);
    }

    public function test_invalid_or_expired_code_is_rejected(): void
    {
        // Store an expired code
        DB::table('password_resets')->updateOrInsert(
            ['email' => $this->user->email],
            [
                'token' => Hash::make('123456'),
                'created_at' => Carbon::now()->subMinutes(20),
            ]
        );

        $response = $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => '123456',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_invalid_code_fails_verification(): void
    {
        DB::table('password_resets')->updateOrInsert(
            ['email' => $this->user->email],
            [
                'token' => Hash::make('888888'),
                'created_at' => Carbon::now(),
            ]
        );

        $response = $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => '111111',
            'password' => 'NewSecurePassword123',
            'password_confirmation' => 'NewSecurePassword123',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_weak_password_is_rejected(): void
    {
        DB::table('password_resets')->updateOrInsert(
            ['email' => $this->user->email],
            [
                'token' => Hash::make('654321'),
                'created_at' => Carbon::now(),
            ]
        );

        // Password with only letters and less than 8 chars
        $response = $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => '654321',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_valid_code_successfully_resets_password_and_audits_event(): void
    {
        $code = '456789';
        DB::table('password_resets')->updateOrInsert(
            ['email' => $this->user->email],
            [
                'token' => Hash::make($code),
                'created_at' => Carbon::now(),
            ]
        );

        $response = $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => $code,
            'password' => 'BrandNewPassword123',
            'password_confirmation' => 'BrandNewPassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Token record must be deleted
        $this->assertDatabaseMissing('password_resets', ['email' => $this->user->email]);

        // Audit log created
        $audit = AuditLog::where('user_id', $this->user->id)
            ->where('action', 'password_reset_completed')
            ->first();
        $this->assertNotNull($audit);

        // Can log in with new password
        $this->user->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123', $this->user->password));

        $loginResponse = $this->post(route('login'), [
            'username' => 'testowner',
            'password' => 'BrandNewPassword123',
        ]);

        $loginResponse->assertRedirect(route('owner.dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }
}
