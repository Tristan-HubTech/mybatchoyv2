<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_search_audit_logs_and_staff_cannot_view_them(): void
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $staffRole = Role::create(['name' => 'staff', 'label' => 'Staff']);
        $owner = User::factory()->create(['role_id' => $ownerRole->id, 'username' => 'owner-user', 'is_active' => true]);
        $staff = User::factory()->create(['role_id' => $staffRole->id, 'username' => 'staff-user', 'is_active' => true]);

        AuditLog::create([
            'user_id' => $owner->id,
            'action' => 'staff_created',
            'model_type' => User::class,
            'model_id' => 7,
            'details' => 'Staff account created.',
        ]);
        AuditLog::create([
            'user_id' => $owner->id,
            'action' => 'purchase_recorded',
            'model_type' => 'App\\Models\\Purchase',
            'model_id' => 9,
            'details' => 'Purchase saved.',
        ]);

        $this->actingAs($owner)
            ->get(route('owner.audit.index', ['search' => 'staff_created']))
            ->assertOk()
            ->assertSee('staff created')
            ->assertSee('User #7')
            ->assertDontSee('purchase recorded');

        $this->actingAs($staff)->get(route('owner.audit.index'))->assertForbidden();
    }

    public function test_owner_can_filter_audit_logs_by_date_range(): void
    {
        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner / Admin']);
        $owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-date-filter',
            'is_active' => true,
        ]);

        $inside = AuditLog::create([
            'user_id' => $owner->id,
            'action' => 'inside_date_range',
            'details' => 'Included event.',
        ]);
        $outside = AuditLog::create([
            'user_id' => $owner->id,
            'action' => 'outside_date_range',
            'details' => 'Excluded event.',
        ]);
        DB::table('audit_logs')->where('id', $inside->id)->update(['created_at' => '2026-10-01 09:00:00']);
        DB::table('audit_logs')->where('id', $outside->id)->update(['created_at' => '2026-09-30 09:00:00']);

        $this->actingAs($owner)
            ->get(route('owner.audit.index', ['from' => '2026-10-01', 'to' => '2026-10-01']))
            ->assertOk()
            ->assertSee('inside date range')
            ->assertDontSee('outside date range');
    }
}