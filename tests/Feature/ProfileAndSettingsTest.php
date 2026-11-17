<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\InventoryCategory;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $oldRememberToken = Str::random(60);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'owner-user',
            'password' => Hash::make('CurrentPassword2026!'),
            'remember_token' => $oldRememberToken,
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword2026!',
            'password_confirmation' => 'NewPassword2026!',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('profile.password'), [
            'current_password' => 'CurrentPassword2026!',
            'password' => 'CurrentPassword2026!',
            'password_confirmation' => 'CurrentPassword2026!',
        ])->assertSessionHasErrors('password');

        $this->put(route('profile.password'), [
            'current_password' => 'CurrentPassword2026!',
            'password' => 'NewPassword2026!',
            'password_confirmation' => 'DifferentPassword2026!',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'CurrentPassword2026!',
            'password' => 'NewPassword2026!',
            'password_confirmation' => 'NewPassword2026!',
        ])->assertRedirect(route('profile.show'));

        $this->assertTrue(Hash::check('NewPassword2026!', $user->fresh()->password));
        $this->assertNotSame($oldRememberToken, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('password_resets', ['email' => $user->email]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'profile_password_changed']);
        $this->get(route('profile.show'))->assertOk();
    }

    public function test_staff_can_change_their_own_password(): void
    {
        $role = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $staff = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'staff-password-owner',
            'password' => Hash::make('CurrentStaff2026!'),
            'is_active' => true,
        ]);

        $this->actingAs($staff)->put(route('profile.password'), [
            'current_password' => 'CurrentStaff2026!',
            'password' => 'ChangedStaff2026!',
            'password_confirmation' => 'ChangedStaff2026!',
        ])->assertRedirect(route('profile.show'));

        $this->assertTrue(Hash::check('ChangedStaff2026!', $staff->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff->id,
            'action' => 'profile_password_changed',
        ]);
    }

    public function test_stale_authenticated_session_is_invalidated_after_password_change(): void
    {
        $role = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'stale-session-user',
            'password' => Hash::make('old-password-123'),
            'is_active' => true,
        ]);

        Auth::login($user);
        session(['password_hash_web' => $user->getAuthPassword()]);
        User::whereKey($user->id)->update(['password' => Hash::make('new-password-123')]);
        Auth::forgetGuards();

        $this->get(route('profile.show'))->assertRedirect(route('login'));
        $this->assertGuest();
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
            'session_timeout_minutes' => 20,
            'default_minimum_stock_level' => 0,
            'default_reorder_level' => 0,
        ])->assertRedirect(route('owner.settings.index'));

        $this->assertSame('MyBatchoy Kitchen', BusinessSetting::firstOrFail()->business_name);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $owner->id, 'action' => 'business_settings_updated']);
        $this->actingAs($staff)->get(route('owner.settings.index'))->assertForbidden();
    }

    public function test_settings_are_persisted_and_apply_session_timeout_and_inventory_defaults(): void
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-preferences',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->put(route('owner.settings.update'), [
            'business_name' => 'MyBatchoy Settings Test',
            'phone' => '09170000002',
            'email' => 'settings@example.com',
            'address' => 'Test address',
            'session_timeout_minutes' => 35,
            'default_minimum_stock_level' => '2.500',
            'default_reorder_level' => '5.000',
        ])->assertRedirect(route('owner.settings.index'));

        $this->get(route('owner.settings.index'))->assertOk()
            ->assertSee('MyBatchoy Settings Test')
            ->assertSee('35');

        $this->assertSame(35, config('session.lifetime'));
        $this->assertDatabaseHas('business_settings', [
            'id' => 1,
            'business_name' => 'MyBatchoy Settings Test',
            'session_timeout_minutes' => 35,
            'default_minimum_stock_level' => '2.500',
            'default_reorder_level' => '5.000',
        ]);

        $this->get(route('owner.inventory.create'))
            ->assertOk()
            ->assertSee('value="2.500"', false)
            ->assertSee('value="5.000"', false);

        $this->put(route('owner.settings.update'), [
            'business_name' => 'MyBatchoy Settings Test',
            'session_timeout_minutes' => 241,
            'default_minimum_stock_level' => 0,
            'default_reorder_level' => 0,
        ])->assertSessionHasErrors('session_timeout_minutes');
    }

    public function test_owner_can_upload_business_logo_to_public_storage(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-logo',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->put(route('owner.settings.update'), [
            'business_name' => 'Logo Test',
            'session_timeout_minutes' => 20,
            'default_minimum_stock_level' => 0,
            'default_reorder_level' => 0,
            'logo' => \Illuminate\Http\UploadedFile::fake()->createWithContent(
                'batchoy.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect(route('owner.settings.index'));

        $path = BusinessSetting::findOrFail(1)->logo_path;
        $this->assertNotNull($path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($path);
    }

    public function test_staff_cannot_change_owner_settings_or_inventory_configuration(): void
    {
        $staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'username' => 'staff-settings-block',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->post(route('owner.settings.categories.store'), ['name' => 'Unauthorized'])
            ->assertForbidden();
        $this->assertDatabaseMissing('inventory_categories', ['name' => 'Unauthorized']);
    }

    public function test_owner_can_manage_categories_and_units_without_deleting_history(): void
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-inventory-settings',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('owner.settings.categories.store'), ['name' => 'Test Category'])
            ->assertRedirect(route('owner.settings.index'));
        $category = InventoryCategory::where('name', 'Test Category')->firstOrFail();

        $this->put(route('owner.settings.categories.update', $category), [
            'name' => 'Updated Test Category',
            'description' => 'Preserved category',
        ])->assertRedirect(route('owner.settings.index'));
        $this->patch(route('owner.settings.categories.status', $category), [
            'is_active' => 0,
        ])->assertRedirect(route('owner.settings.index'));

        $this->post(route('owner.settings.units.store'), [
            'name' => 'Test Pack',
            'short_name' => 'pkt',
        ])->assertRedirect(route('owner.settings.index'));
        $unit = Unit::where('short_name', 'pkt')->firstOrFail();

        $this->put(route('owner.settings.units.update', $unit), [
            'name' => 'Test Packet',
            'short_name' => 'packet',
            'description' => 'Updated unit',
        ])->assertRedirect(route('owner.settings.index'));
        $this->patch(route('owner.settings.units.status', $unit), [
            'is_active' => 0,
        ])->assertRedirect(route('owner.settings.index'));

        $this->assertSame('Updated Test Category', $category->fresh()->name);
        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame('Test Packet', $unit->fresh()->name);
        $this->assertFalse($unit->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'inventory_category_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'inventory_unit_created']);
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

    public function test_logo_and_apk_support_configured_object_storage_disk(): void
    {
        \Illuminate\Support\Facades\Storage::fake('s3');
        config([
            'filesystems.media_disk' => 's3',
            'filesystems.apk_disk' => 's3',
        ]);

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-s3-media',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->put(route('owner.settings.update'), [
            'business_name' => 'S3 Brand',
            'session_timeout_minutes' => 20,
            'default_minimum_stock_level' => 0,
            'default_reorder_level' => 0,
            'logo' => \Illuminate\Http\UploadedFile::fake()->createWithContent(
                'batchoy-s3.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect(route('owner.settings.index'));

        $logoPath = BusinessSetting::findOrFail(1)->logo_path;
        $this->assertNotNull($logoPath);
        \Illuminate\Support\Facades\Storage::disk('s3')->assertExists($logoPath);
        $this->get(route('media.logo'))->assertOk();

        $apkFile = \Illuminate\Http\UploadedFile::fake()->create('mybatchoy.apk', 64, 'application/vnd.android.package-archive');
        $this->post(route('owner.settings.apk.upload'), ['apk_file' => $apkFile])
            ->assertRedirect(route('owner.settings.index') . '#mobile-app-download');

        \Illuminate\Support\Facades\Storage::disk('s3')->assertExists('downloads/mybatchoy.apk');
        $this->get(route('app.download'))->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="mybatchoy.apk"');
    }

    public function test_serverless_readonly_blocks_local_logo_and_apk_uploads_and_allows_mobile_apk_url_redirect(): void
    {
        config([
            'app.serverless_readonly' => true,
            'filesystems.media_disk' => 'public',
            'filesystems.apk_disk' => 'local',
        ]);

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-vercel-guard',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->put(route('owner.settings.update'), [
            'business_name' => 'Serverless Brand',
            'session_timeout_minutes' => 20,
            'default_minimum_stock_level' => 0,
            'default_reorder_level' => 0,
            'logo' => \Illuminate\Http\UploadedFile::fake()->createWithContent(
                'batchoy.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=')
            ),
        ])->assertSessionHasErrors('logo');

        $apkFile = \Illuminate\Http\UploadedFile::fake()->create('mybatchoy.apk', 64, 'application/vnd.android.package-archive');
        $this->post(route('owner.settings.apk.upload'), ['apk_file' => $apkFile])
            ->assertSessionHasErrors('apk_file');

        config(['app.mobile_apk_url' => 'https://cdn.example.com/releases/mybatchoy.apk']);
        $this->get(route('app.download'))
            ->assertRedirect('https://cdn.example.com/releases/mybatchoy.apk');

        foreach ([
            'http://insecure.example.com/mybatchoy.apk',
            'javascript:alert(1)',
            'ftp://files.example.com/mybatchoy.apk',
            'not-a-valid-url',
        ] as $unsafeUrl) {
            config(['app.mobile_apk_url' => $unsafeUrl]);
            $this->assertNull(\App\Http\Controllers\AppDownloadController::validatedExternalApkUrl());
            $this->assertSame(route('app.download'), \App\Http\Controllers\AppDownloadController::directDownloadUrl());

            $res = $this->get(route('app.download'));
            $res->assertStatus(422);
            $this->assertNull($res->headers->get('Location'));
        }
    }
}