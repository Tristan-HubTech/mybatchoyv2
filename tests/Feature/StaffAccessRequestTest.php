<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\OwnerMfaChallenge;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccessRequestTest extends TestCase
{
    use RefreshDatabase;

    private Role $ownerRole;
    private Role $staffRole;
    private User $owner;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $this->staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);

        $this->owner = User::factory()->create([
            'role_id' => $this->ownerRole->id,
            'username' => 'owner-admin',
            'email' => 'owner@example.com',
            'password' => Hash::make('OwnerPassword2026!'),
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $this->staffRole->id,
            'username' => 'active-staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('StaffPassword2026!'),
            'is_active' => true,
        ]);
    }

    public function test_login_page_removes_public_staff_registration_and_keeps_required_controls(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('Eustace Batchoy')
            ->assertSee('MyBatchoy')
            ->assertSee('eustace-mascot.jpg', false)
            ->assertSee('Username or email')
            ->assertSee('Password')
            ->assertSee('Show password')
            ->assertSee('Remember this device')
            ->assertSee('Forgot password?')
            ->assertSee('Sign In')
            ->assertSee('Authorized personnel only.')
            ->assertDontSee('New team member?')
            ->assertDontSee('Staff requests require Owner/Admin approval.')
            ->assertDontSee('Request Staff Access')
            ->assertDontSee('request-access')
            ->assertDontSee('Register')
            ->assertDontSee('Sign Up');
    }

    public function test_public_registration_routes_and_endpoints_are_disabled(): void
    {
        $userCountBefore = User::count();

        $this->get('/request-access')->assertNotFound();
        $this->post('/request-access', [
            'name' => 'Public Visitor',
            'username' => 'public-visitor',
            'email' => 'visitor@example.com',
            'password' => 'VisitorPassword2026!',
            'password_confirmation' => 'VisitorPassword2026!',
        ])->assertNotFound();

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Public Register',
            'email' => 'register@example.com',
            'password' => 'VisitorPassword2026!',
        ])->assertNotFound();

        $this->get('/signup')->assertNotFound();
        $this->post('/signup', [])->assertNotFound();
        $this->postJson('/api/v1/register', [])->assertNotFound();
        $this->postJson('/api/v1/request-access', [])->assertNotFound();

        $this->assertSame($userCountBefore, User::count());
    }

    public function test_existing_inactive_staff_accounts_are_preserved_and_owner_can_approve_or_reject(): void
    {
        $pendingOne = User::factory()->create([
            'role_id' => $this->staffRole->id,
            'username' => 'legacy-pending-one',
            'email' => 'legacy1@example.com',
            'password' => Hash::make('LegacyStaff2026!'),
            'is_active' => false,
            'last_login_at' => null,
        ]);

        AuditLog::create([
            'user_id' => null,
            'action' => 'staff_access_requested',
            'model_type' => User::class,
            'model_id' => $pendingOne->id,
            'details' => 'Legacy self-requested staff account.',
        ]);

        $pendingTwo = User::factory()->create([
            'role_id' => $this->staffRole->id,
            'username' => 'legacy-pending-two',
            'email' => 'legacy2@example.com',
            'password' => Hash::make('LegacyStaff2026!'),
            'is_active' => false,
            'last_login_at' => null,
        ]);

        AuditLog::create([
            'user_id' => null,
            'action' => 'staff_access_requested',
            'model_type' => User::class,
            'model_id' => $pendingTwo->id,
            'details' => 'Legacy self-requested staff account.',
        ]);

        $this->post(route('login'), [
            'username' => $pendingOne->username,
            'password' => 'LegacyStaff2026!',
        ])->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->actingAs($this->owner)
            ->patch(route('owner.staff.approve', $pendingOne))
            ->assertRedirect(route('owner.staff.index'));
        $this->assertTrue($pendingOne->fresh()->is_active);

        $this->actingAs($this->owner)
            ->delete(route('owner.staff.reject', $pendingTwo))
            ->assertRedirect(route('owner.staff.index'));
        $this->assertDatabaseMissing('users', ['id' => $pendingTwo->id]);
        $this->assertDatabaseHas('users', ['id' => $this->staff->id, 'is_active' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff_request_approved', 'model_id' => $pendingOne->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff_request_rejected', 'model_id' => $pendingTwo->id]);
    }

    public function test_staff_cannot_create_accounts_or_access_settings_staff_access_controls(): void
    {
        $userCountBefore = User::count();

        $this->actingAs($this->staff)
            ->post(route('owner.settings.staff-access.verify'), [
                'current_password' => 'StaffPassword2026!',
            ])->assertForbidden();

        $this->actingAs($this->staff)
            ->post(route('owner.settings.staff-access.create'), [
                'name' => 'Unauthorized',
                'username' => 'unauth-staff',
                'email' => 'unauth@example.com',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '1',
            ])->assertForbidden();

        $this->actingAs($this->staff)
            ->post(route('owner.staff.store'), [
                'name' => 'Unauthorized Direct',
                'username' => 'unauth-direct',
                'email' => 'unauth-direct@example.com',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '1',
            ])->assertForbidden();

        $this->assertSame($userCountBefore, User::count());
    }

    public function test_settings_staff_access_management_requires_owner_password_reverification(): void
    {
        $this->actingAs($this->owner)
            ->get(route('owner.settings.index'))
            ->assertOk()
            ->assertSee('Staff Access Management')
            ->assertSee('Verification Required')
            ->assertDontSee('id="settings-staff-username"', false);

        // Creating directly without re-verification must fail.
        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.create'), [
                'name' => 'Bypass Attempt',
                'username' => 'bypass-attempt',
                'email' => 'bypass@example.com',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '1',
            ])->assertSessionHasErrors('staff_access');
        $this->assertNull(User::where('username', 'bypass-attempt')->first());

        // Wrong owner password must be rejected.
        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.verify'), [
                'current_password' => 'WrongPassword123!',
            ])->assertSessionHasErrors('current_password');

        // Correct owner password unlocks creation controls in Settings.
        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.verify'), [
                'current_password' => 'OwnerPassword2026!',
            ])->assertRedirect(route('owner.settings.index') . '#staff-access-management');

        $this->actingAs($this->owner)
            ->get(route('owner.settings.index'))
            ->assertOk()
            ->assertSee('id="settings-staff-username"', false)
            ->assertSee('Role assignment is restricted to Staff');

        // Owner/Admin role assignment attempt must be rejected.
        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.create'), [
                'role' => 'owner',
                'role_id' => $this->ownerRole->id,
                'name' => 'Privilege Escalation',
                'username' => 'priv-esc',
                'email' => 'priv@example.com',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '1',
            ])->assertSessionHasErrors(['role', 'role_id']);
        $this->assertNull(User::where('username', 'priv-esc')->first());

        // Valid staff creation via Settings succeeds and restricts role to staff.
        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.create'), [
                'name' => 'Kitchen Helper',
                'username' => 'kitchen-helper',
                'email' => 'kitchen@example.com',
                'phone' => '09171234567',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '1',
            ])->assertRedirect(route('owner.settings.index') . '#staff-access-management');

        $created = User::where('username', 'kitchen-helper')->firstOrFail();
        $this->assertSame('staff', $created->role->name);
        $this->assertTrue(Hash::check('StaffPassword2026!', $created->password));
        $this->assertTrue($created->is_active);
        $this->assertTrue(AuditLog::where('action', 'staff_created')->where('model_id', $created->id)->exists());
    }

    public function test_settings_staff_access_management_requires_mfa_when_configured(): void
    {
        $this->owner->forceFill([
            'mfa_enabled' => true,
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
        ])->save();

        // Missing or wrong MFA code must fail when MFA is enabled.
        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.verify'), [
                'current_password' => 'OwnerPassword2026!',
                'mfa_code' => '000000',
            ])->assertSessionHasErrors('mfa_code');

        OwnerMfaChallenge::create([
            'user_id' => $this->owner->id,
            'code_hash' => Hash::make('654321'),
            'purpose' => 'staff_access_management',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.verify'), [
                'current_password' => 'OwnerPassword2026!',
                'mfa_code' => '654321',
            ])->assertRedirect(route('owner.settings.index') . '#staff-access-management');

        $this->actingAs($this->owner)
            ->post(route('owner.settings.staff-access.create'), [
                'name' => 'Verified MFA Staff',
                'username' => 'mfa-staff',
                'email' => 'mfa-staff@example.com',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '1',
            ])->assertRedirect(route('owner.settings.index') . '#staff-access-management');

        $this->assertDatabaseHas('users', [
            'username' => 'mfa-staff',
            'role_id' => $this->staffRole->id,
            'is_active' => 1,
        ]);
    }
}
