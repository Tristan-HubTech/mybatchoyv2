<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCode;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
            'password' => Hash::make('OldPassword123!'),
            'is_active' => true,
        ]);

        Mail::fake();
        config(['mail.default' => 'smtp']);
        RateLimiter::clear('password-reset-request:ip:127.0.0.1');
        RateLimiter::clear('password-reset-verify:ip:127.0.0.1');
    }

    public function test_forgot_password_and_reset_pages_render_without_showing_codes(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Email address')
            ->assertDontSee('Username or Email');

        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee('Enter Verification Code')
            ->assertDontSee('security_code_demo');
    }

    public function test_user_can_request_a_hashed_reset_code_by_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect(route('password.reset'));
        $response->assertSessionHas('status');

        $record = DB::table('password_resets')->where('email', $this->user->email)->first();
        $this->assertNotNull($record);
        $this->assertFalse(Hash::check('000000', $record->token));
        $this->assertGreaterThan(6, strlen($record->token));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'password_reset_code_requested',
        ]);

        Mail::assertSent(PasswordResetCode::class, fn (PasswordResetCode $mail): bool => $mail->hasTo($this->user->email)
            && preg_match('/^\d{6}$/', $mail->code) === 1);
    }

    public function test_unknown_email_gets_the_same_generic_response_without_sending_mail(): void
    {
        $validResponse = $this->post(route('password.email'), ['email' => $this->user->email]);
        $unknownResponse = $this->post(route('password.email'), ['email' => 'unknown@example.test']);

        $this->assertSame(
            $validResponse->getSession()->get('status'),
            $unknownResponse->getSession()->get('status')
        );
        $this->assertDatabaseMissing('password_resets', ['email' => 'unknown@example.test']);
        Mail::assertSent(PasswordResetCode::class, 1);
    }

    public function test_reset_request_is_rate_limited_per_account(): void
    {
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post(route('password.email'), ['email' => $this->user->email])
                ->assertRedirect(route('password.reset'))
                ->assertSessionHas('status');
        }

        Mail::assertSent(PasswordResetCode::class, 3);
    }

    public function test_reset_code_expires_after_ten_minutes(): void
    {
        DB::table('password_resets')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()->subMinutes(11),
        ]);

        $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => '123456',
            'password' => 'NewPassword2026!',
            'password_confirmation' => 'NewPassword2026!',
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('password_resets', ['email' => $this->user->email]);
    }

    public function test_invalid_code_is_rejected(): void
    {
        DB::table('password_resets')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('888888'),
            'created_at' => Carbon::now(),
        ]);

        $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => '111111',
            'password' => 'NewPassword2026!',
            'password_confirmation' => 'NewPassword2026!',
        ])->assertSessionHasErrors('code');
    }

    public function test_weak_reset_password_is_rejected(): void
    {
        DB::table('password_resets')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('654321'),
            'created_at' => Carbon::now(),
        ]);

        $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => '654321',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');
    }

    public function test_valid_reset_code_is_emailed_and_single_use(): void
    {
        $response = $this->post(route('password.email'), ['email' => $this->user->email]);
        $response->assertRedirect(route('password.reset'));

        $sentCode = null;
        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use (&$sentCode): bool {
            $sentCode = $mail->code;

            return $mail->hasTo($this->user->email);
        });
        $this->assertNotNull($sentCode);

        $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => $sentCode,
            'password' => 'BrandNewPassword2026!',
            'password_confirmation' => 'BrandNewPassword2026!',
        ])->assertRedirect(route('login'))->assertSessionHas('success');

        $this->assertDatabaseMissing('password_resets', ['email' => $this->user->email]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'password_reset_completed',
        ]);
        $this->assertTrue(Hash::check('BrandNewPassword2026!', $this->user->fresh()->password));

        $this->post(route('password.update'), [
            'email' => $this->user->email,
            'code' => $sentCode,
            'password' => 'AnotherPassword2026!',
            'password_confirmation' => 'AnotherPassword2026!',
        ])->assertSessionHasErrors('code');
    }
}
