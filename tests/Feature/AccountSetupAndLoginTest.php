<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AccountSetupAndLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_owner_can_be_created_with_strong_credentials(): void
    {
        $this->artisan('app:create-owner')
            ->expectsQuestion('Full name', 'Initial Owner')
            ->expectsQuestion('Username', 'initial-owner')
            ->expectsQuestion('Email address', 'initial-owner@example.test')
            ->expectsQuestion('Password', 'SecureOwnerPassword2026!')
            ->expectsQuestion('Confirm password', 'SecureOwnerPassword2026!')
            ->expectsOutput('Initial Owner/Admin account created successfully.')
            ->assertExitCode(0);

        $owner = User::where('username', 'initial-owner')->firstOrFail();
        $this->assertSame('owner', $owner->role->name);
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check('SecureOwnerPassword2026!', $owner->password));
    }

    public function test_initial_owner_setup_cannot_be_repeated(): void
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'existing-owner',
            'email' => 'existing-owner@example.test',
        ]);

        $this->artisan('app:create-owner')
            ->expectsOutput('An Owner/Admin account already exists. Initial setup is closed.')
            ->assertExitCode(1);
        $this->assertSame(1, User::count());
    }

    public function test_role_seeder_creates_roles_but_no_accounts_or_default_passwords(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertSame(2, Role::count());
        $this->assertSame(0, User::count());
    }

    public function test_public_registration_and_owner_setup_urls_are_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Unauthorized',
            'username' => 'unauthorized',
            'email' => 'unauthorized@example.test',
            'password' => 'SecurePassword2026!',
            'password_confirmation' => 'SecurePassword2026!',
            'role' => 'owner',
        ])->assertNotFound();
        $this->get('/setup-owner')->assertNotFound();
        $this->assertSame(0, User::count());
    }

    public function test_login_page_has_accessible_controls_and_no_published_credentials(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('MyBatchoy Inventory')
            ->assertSee('Remember this device')
            ->assertSee('Forgot password?')
            ->assertSee('Show password')
            ->assertSee('Enter your password')
            ->assertDontSee('admin123')
            ->assertDontSee('password123')
            ->assertDontSee('Default username')
            ->assertDontSee('Default password');
    }

    public function test_shared_login_redirects_active_owner_and_staff_by_role(): void
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'login-owner',
            'password' => Hash::make('OwnerPassword2026!'),
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'username' => 'login-staff',
            'password' => Hash::make('StaffPassword2026!'),
            'is_active' => true,
        ]);

        $this->post(route('login'), [
            'username' => $owner->username,
            'password' => 'OwnerPassword2026!',
        ])->assertRedirect(route('owner.dashboard'));

        $this->post(route('logout'));
        $this->post(route('login'), [
            'username' => $staff->username,
            'password' => 'StaffPassword2026!',
        ])->assertRedirect(route('staff.dashboard'));
    }

    public function test_remember_this_device_uses_laravels_remember_token(): void
    {
        $role = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $staff = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'remembered-staff',
            'password' => Hash::make('RememberPassword2026!'),
            'is_active' => true,
            'remember_token' => null,
        ]);

        $this->post(route('login'), [
            'username' => $staff->username,
            'password' => 'RememberPassword2026!',
            'remember' => '1',
        ])->assertRedirect(route('staff.dashboard'));

        $this->assertNotNull($staff->fresh()->remember_token);
    }

    public function test_successful_login_regenerates_session_and_logout_invalidates_it(): void
    {
        $role = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $owner = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'session-owner',
            'password' => Hash::make('SessionPassword2026!'),
            'is_active' => true,
        ]);

        $this->get(route('login'));
        $initialSessionId = session()->getId();

        $this->post(route('login'), [
            'username' => $owner->username,
            'password' => 'SessionPassword2026!',
        ])->assertRedirect(route('owner.dashboard'));

        $authenticatedSessionId = session()->getId();
        $this->assertNotSame($initialSessionId, $authenticatedSessionId);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($authenticatedSessionId, session()->getId());
    }

    public function test_login_rejects_bad_credentials_and_limits_repeated_attempts(): void
    {
        $username = 'rate-limited-user';
        $usernameKey = 'login:username:' . hash('sha256', $username);
        RateLimiter::clear($usernameKey);
        RateLimiter::clear('login:ip:127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'username' => $username,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('username');
        }

        $this->post(route('login'), [
            'username' => $username,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->assertTrue(RateLimiter::tooManyAttempts($usernameKey, 5));
        $this->assertGuest();
    }

    public function test_invalid_and_inactive_accounts_receive_the_same_login_error(): void
    {
        $staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);
        User::factory()->create([
            'role_id' => $staffRole->id,
            'username' => 'disabled-login-user',
            'password' => Hash::make('DisabledPassword2026!'),
            'is_active' => false,
        ]);

        $this->from(route('login'))->post(route('login'), [
            'username' => 'missing-login-user',
            'password' => 'WrongPassword2026!',
        ])->assertRedirect(route('login'));
        $invalidCredentialsPage = $this->get(route('login'))->getContent();

        $this->from(route('login'))->post(route('login'), [
            'username' => 'disabled-login-user',
            'password' => 'DisabledPassword2026!',
        ])->assertRedirect(route('login'));
        $inactiveAccountPage = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('These credentials do not match our records.', $invalidCredentialsPage);
        $this->assertStringContainsString('These credentials do not match our records.', $inactiveAccountPage);
    }

    public function test_database_seeder_seeds_owner_three_staff_and_test_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('username', 'owner')->firstOrFail();
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check('Str0ng!Pass2024', $owner->password));
        $this->assertSame('owner', $owner->role->name);

        $staffCredentials = [
            'staff_maria' => 'Batchoy!Staff1',
            'staff_juan' => 'Batchoy!Staff2',
            'staff_elena' => 'Batchoy!Staff3',
        ];

        foreach ($staffCredentials as $username => $plainPassword) {
            $staff = User::where('username', $username)->firstOrFail();
            $this->assertTrue($staff->is_active);
            $this->assertSame('staff', $staff->role->name);
            $this->assertTrue(Hash::check($plainPassword, $staff->password));
        }

        $this->assertSame(3, User::whereHas('role', fn ($q) => $q->where('name', 'staff'))->count());
        $this->assertGreaterThanOrEqual(10, InventoryItem::count());
        $this->assertGreaterThanOrEqual(3, Supplier::count());
        $this->assertGreaterThanOrEqual(5, Purchase::count());
        $this->assertGreaterThanOrEqual(10, InventoryTransaction::count());
        $this->assertNotNull(BusinessSetting::find(1));
    }
}
