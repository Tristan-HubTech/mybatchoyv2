<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-admin',
            'email' => 'owner@example.com',
            'is_active' => true,
        ]);
        $this->staffUser = User::factory()->create([
            'role_id' => $staffRole->id,
            'username' => 'staff-member',
            'email' => 'staff@example.com',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_create_staff_with_hashed_password_and_audit_event(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.staff.store'), [
            'name' => 'New Staff',
            'username' => 'new-staff',
            'email' => 'new-staff@example.com',
            'phone' => '09170000003',
            'password' => 'StaffPassword2026!',
            'password_confirmation' => 'StaffPassword2026!',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('owner.staff.index'));
        $created = User::where('username', 'new-staff')->firstOrFail();
        $this->assertSame('staff', $created->role->name);
        $this->assertTrue(Hash::check('StaffPassword2026!', $created->password));
        $this->assertTrue($created->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'staff_created',
            'model_id' => $created->id,
        ]);
    }

    public function test_owner_can_create_an_inactive_staff_account_that_cannot_log_in(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.staff.store'), [
                'name' => 'Inactive New Staff',
                'username' => 'inactive-new-staff',
                'email' => 'inactive-new-staff@example.com',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '0',
            ])->assertRedirect(route('owner.staff.index'));

        $created = User::where('username', 'inactive-new-staff')->firstOrFail();
        $this->assertFalse($created->is_active);

        auth()->logout();
        $this->post(route('login'), [
            'username' => $created->username,
            'password' => 'StaffPassword2026!',
        ])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_staff_cannot_access_staff_management(): void
    {
        $this->actingAs($this->staffUser)
            ->get(route('owner.staff.index'))
            ->assertForbidden();

        $this->post(route('owner.staff.store'), [
            'name' => 'Unauthorized',
            'username' => 'unauthorized',
            'email' => 'unauthorized@example.com',
            'password' => 'StaffPassword2026!',
            'password_confirmation' => 'StaffPassword2026!',
            'is_active' => '1',
        ])->assertForbidden();
    }

    public function test_staff_creation_rejects_duplicate_username_and_email(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.staff.store'), [
                'name' => 'Duplicate Username',
                'username' => $this->staffUser->username,
                'email' => 'new-email@example.com',
                'password' => 'StaffPassword2026!',
                'password_confirmation' => 'StaffPassword2026!',
                'is_active' => '1',
            ])->assertSessionHasErrors('username');

        $this->post(route('owner.staff.store'), [
            'name' => 'Duplicate Email',
            'username' => 'new-unique-username',
            'email' => $this->staffUser->email,
            'password' => 'StaffPassword2026!',
            'password_confirmation' => 'StaffPassword2026!',
            'is_active' => '1',
        ])->assertSessionHasErrors('email');
    }

    public function test_owner_sidebar_links_to_staff_management_and_profile_without_placeholders(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.staff.index'));

        $response->assertOk()
            ->assertSee('href="' . route('owner.staff.index') . '"', false)
            ->assertSee('href="' . route('owner.settings.index') . '"', false)
            ->assertSee('href="' . route('profile.show') . '"', false)
            ->assertDontSee('href="#"', false);
    }

    public function test_owner_can_deactivate_staff_and_action_is_audited(): void
    {
        $this->actingAs($this->owner)
            ->patch(route('owner.staff.status', $this->staffUser), ['is_active' => '0'])
            ->assertRedirect(route('owner.staff.index'));

        $this->assertDatabaseHas('users', ['id' => $this->staffUser->id, 'is_active' => 0]);
        $this->assertTrue(AuditLog::where('action', 'staff_deactivated')->where('model_id', $this->staffUser->id)->exists());
    }

    public function test_owner_can_edit_reactivate_and_reset_staff_password(): void
    {
        $this->actingAs($this->owner)->put(route('owner.staff.update', $this->staffUser), [
            'name' => 'Updated Staff',
            'username' => 'staff-member',
            'email' => 'staff@example.com',
            'phone' => '09170000004',
        ])->assertRedirect(route('owner.staff.index'));

        $this->patch(route('owner.staff.status', $this->staffUser), ['is_active' => '0'])
            ->assertRedirect(route('owner.staff.index'));
        $this->patch(route('owner.staff.status', $this->staffUser), ['is_active' => '1'])
            ->assertRedirect(route('owner.staff.index'));

        $this->put(route('owner.staff.password', $this->staffUser), [
            'password' => 'ResetStaffPassword2026!',
            'password_confirmation' => 'ResetStaffPassword2026!',
        ])->assertRedirect(route('owner.staff.edit', $this->staffUser));

        $this->assertTrue(Hash::check('ResetStaffPassword2026!', $this->staffUser->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff_updated', 'model_id' => $this->staffUser->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff_activated', 'model_id' => $this->staffUser->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff_password_reset', 'model_id' => $this->staffUser->id]);
    }

    public function test_owner_sidebar_destinations_resolve(): void
    {
        $this->actingAs($this->owner);

        $this->followingRedirects()->get(route('dashboard'))->assertOk();
        foreach ([
            'owner.inventory.index',
            'owner.transactions.index',
            'owner.suppliers.index',
            'owner.purchases.index',
            'owner.reports.index',
            'owner.staff.index',
            'owner.audit.index',
            'owner.settings.index',
            'profile.show',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}