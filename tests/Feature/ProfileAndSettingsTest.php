<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_updates_only_the_authenticated_users_permitted_fields(): void
    {
        $role = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $user = User::factory()->create(['role_id' => $role->id, 'username' => 'staff-user', 'is_active' => true]);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '09171112222',
            'role_id' => 1,
            'is_active' => 0,
        ])->assertRedirect(route('profile.show'));

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue($user->is_active);
    }

    public function test_password_change_requires_current_password_and_is_audited(): void
    {
        $role = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'owner-user',
            'password' => Hash::make('current-password-123'),
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'next-password-123',
            'password_confirmation' => 'next-password-123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'current-password-123',
            'password' => 'next-password-123',
            'password_confirmation' => 'next-password-123',
        ])->assertRedirect(route('profile.show'));

        $this->assertTrue(Hash::check('next-password-123', $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'profile_password_changed']);
    }

    public function test_owner_can_save_business_settings_and_staff_is_forbidden(): void
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $owner = User::factory()->create(['role_id' => $ownerRole->id, 'username' => 'owner-user', 'is_active' => true]);
        $staff = User::factory()->create(['role_id' => $staffRole->id, 'username' => 'staff-user', 'is_active' => true]);

        $this->actingAs($owner)->put(route('owner.settings.update'), [
            'business_name' => 'MyBatchoy Kitchen',
            'phone' => '09170000001',
            'email' => 'business@example.com',
            'address' => 'Local test address',
        ])->assertRedirect(route('owner.settings.index'));

        $this->assertSame('MyBatchoy Kitchen', BusinessSetting::firstOrFail()->business_name);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $owner->id, 'action' => 'business_settings_updated']);
        $this->actingAs($staff)->get(route('owner.settings.index'))->assertForbidden();
    }

    public function test_inactive_staff_cannot_log_in(): void
    {
        $role = Role::create(['name' => 'staff', 'label' => 'Staff']);
        User::factory()->create([
            'role_id' => $role->id,
            'username' => 'inactive-staff',
            'password' => Hash::make('inactive-password-123'),
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'username' => 'inactive-staff',
            'password' => 'inactive-password-123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_deactivated_staff_session_is_logged_out_on_the_next_request(): void
    {
        $role = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'active-session-user',
            'is_active' => true,
        ]);

        Auth::login($user);
        User::whereKey($user->id)->update(['is_active' => false]);
        Auth::forgetGuards();

        $this->get(route('profile.show'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_staff_sidebar_contains_only_staff_destinations(): void
    {
        $role = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'staff-navigation-user',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('staff.dashboard'));

        $response->assertOk()
            ->assertSee('href="' . route('staff.inventory.index') . '"', false)
            ->assertSee('href="' . route('staff.transactions.index') . '"', false)
            ->assertSee('href="' . route('profile.show') . '"', false)
            ->assertDontSee('Staff Management')
            ->assertDontSee('Audit Logs')
            ->assertDontSee('href="#"', false);

        foreach (['staff.inventory.index', 'staff.transactions.index', 'profile.show'] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }
}