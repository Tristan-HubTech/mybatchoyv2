<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessAccessRequest;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\StockAdjustmentRequest;
use App\Models\Supplier;
use App\Models\TechnicalAuditLog;
use App\Models\TechnicalSetting;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserActiveSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Role $sysAdminRole;
    private Role $ownerRole;
    private Role $staffRole;
    private Role $supplierRole;

    private User $sysAdmin;
    private User $owner;
    private User $staff;
    private User $supplierUserA;
    private User $supplierUserB;

    private Supplier $supplierA;
    private Supplier $supplierB;
    private InventoryItem $item;
    private Purchase $poA;
    private Purchase $poB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->sysAdminRole = Role::where('name', 'system_admin')->firstOrFail();
        $this->ownerRole = Role::where('name', 'owner')->firstOrFail();
        $this->staffRole = Role::where('name', 'staff')->firstOrFail();
        $this->supplierRole = Role::where('name', 'supplier')->firstOrFail();

        $passwordHash = Hash::make('StrongPass#1234');

        $this->sysAdmin = User::factory()->create([
            'role_id' => $this->sysAdminRole->id,
            'name' => 'Dev Technical Admin',
            'username' => 'sysadmin_dev',
            'email' => 'sysadmin@mybatchoy.local',
            'password' => $passwordHash,
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role_id' => $this->ownerRole->id,
            'name' => 'Business Owner',
            'username' => 'owner_main',
            'email' => 'owner@mybatchoy.local',
            'password' => $passwordHash,
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $this->staffRole->id,
            'name' => 'Kitchen Staff',
            'username' => 'staff_kitchen',
            'email' => 'staff@mybatchoy.local',
            'password' => $passwordHash,
            'is_active' => true,
        ]);

        $this->supplierA = Supplier::create([
            'name' => 'Iloilo Pork Supply',
            'contact_person' => 'Juan',
            'phone' => '09170000001',
            'email' => 'pork@example.com',
            'is_active' => true,
        ]);

        $this->supplierB = Supplier::create([
            'name' => 'Molo Noodle Traders',
            'contact_person' => 'Maria',
            'phone' => '09170000002',
            'email' => 'noodles@example.com',
            'is_active' => true,
        ]);

        $this->supplierUserA = User::factory()->create([
            'role_id' => $this->supplierRole->id,
            'supplier_id' => $this->supplierA->id,
            'name' => 'Supplier A Portal User',
            'username' => 'supplier_a',
            'email' => 'supa@mybatchoy.local',
            'password' => $passwordHash,
            'is_active' => true,
        ]);

        $this->supplierUserB = User::factory()->create([
            'role_id' => $this->supplierRole->id,
            'supplier_id' => $this->supplierB->id,
            'name' => 'Supplier B Portal User',
            'username' => 'supplier_b',
            'email' => 'supb@mybatchoy.local',
            'password' => $passwordHash,
            'is_active' => true,
        ]);

        $category = InventoryCategory::create(['name' => 'Soup Base']);
        $unit = Unit::create(['name' => 'Kilograms', 'short_name' => 'kg']);

        $this->item = InventoryItem::create([
            'name' => 'Pork Bones',
            'sku' => 'BONE-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 25,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        $this->poA = Purchase::create([
            'purchase_number' => 'PO-SUP-A-001',
            'supplier_id' => $this->supplierA->id,
            'created_by' => $this->owner->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 1500,
            'supplier_status' => 'pending',
            'delivery_status' => 'pending',
            'received_in_inventory' => false,
        ]);

        PurchaseItem::create([
            'purchase_id' => $this->poA->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_cost' => 150,
            'subtotal' => 1500,
        ]);

        $this->poB = Purchase::create([
            'purchase_number' => 'PO-SUP-B-001',
            'supplier_id' => $this->supplierB->id,
            'created_by' => $this->owner->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 800,
            'supplier_status' => 'pending',
            'delivery_status' => 'pending',
            'received_in_inventory' => false,
        ]);
    }

    private function actingAsWeb(User $user): self
    {
        app('auth')->shouldUse('web');

        return $this->actingAs($user, 'web');
    }

    public function test_each_role_redirects_to_role_specific_dashboard_on_login(): void
    {
        $cases = [
            [$this->sysAdmin, '/admin/dashboard'],
            [$this->owner, '/owner/dashboard'],
            [$this->staff, '/staff/dashboard'],
            [$this->supplierUserA, '/supplier/dashboard'],
        ];

        foreach ($cases as [$user, $expectedPath]) {
            $response = $this->post('/login', [
                'username' => $user->username,
                'password' => 'StrongPass#1234',
            ]);

            $response->assertRedirect($expectedPath);
            $this->post('/logout');
        }
    }

    public function test_system_admin_can_use_technical_modules_but_cannot_access_business_routes_or_apis(): void
    {
        $this->actingAs($this->sysAdmin)->get(route('sysadmin.dashboard'))->assertOk();
        $this->actingAs($this->sysAdmin)->get(route('sysadmin.health'))->assertOk();
        $this->actingAs($this->sysAdmin)->get(route('sysadmin.security'))->assertOk();
        $this->actingAs($this->sysAdmin)->get(route('sysadmin.config'))->assertOk();
        $this->actingAs($this->sysAdmin)->get(route('sysadmin.accounts.index'))->assertOk();
        $this->actingAs($this->sysAdmin)->get(route('sysadmin.access.index'))->assertOk();

        // Confidential business web routes are strictly blocked
        foreach ([
            route('owner.dashboard'),
            route('owner.inventory.index'),
            route('owner.purchases.index'),
            route('owner.suppliers.index'),
            route('owner.reports.index'),
            route('owner.staff.index'),
            route('owner.audit.index'),
            route('owner.settings.index'),
            route('staff.dashboard'),
            route('supplier.dashboard'),
        ] as $forbiddenUrl) {
            $this->actingAs($this->sysAdmin)->get($forbiddenUrl)->assertForbidden();
        }

        // Business API endpoints reject System Admin
        Sanctum::actingAs($this->sysAdmin);
        foreach ([
            '/api/v1/inventory',
            '/api/v1/transactions',
            '/api/v1/suppliers',
            '/api/v1/purchases',
            '/api/v1/reports',
            '/api/v1/staff',
            '/api/v1/audit-logs',
            '/api/v1/settings',
        ] as $forbiddenApi) {
            $this->getJson($forbiddenApi)->assertForbidden();
        }

        // System Admin cannot approve business transactions
        $adj = StockAdjustmentRequest::create([
            'inventory_item_id' => $this->item->id,
            'requested_by' => $this->staff->id,
            'request_type' => 'adjustment',
            'previous_quantity' => 25,
            'counted_quantity' => 20,
            'reason' => 'Shelf recount test',
            'status' => 'pending',
        ]);

        $this->actingAsWeb($this->sysAdmin)
            ->post(route('owner.adjustments.approve', $adj))
            ->assertForbidden();

        Sanctum::actingAs($this->sysAdmin);
        $this->postJson("/api/v1/adjustment-requests/{$adj->id}/approve")
            ->assertForbidden();
    }

    public function test_system_admin_backup_strips_sensitive_token_and_session_tables(): void
    {
        $this->sysAdmin->forceFill([
            'mfa_enabled' => true,
            'mfa_secret' => 'SUPERSECRETMFAKEY1234',
        ])->save();
        $this->sysAdmin->createToken('secret-mobile-device');
        UserActiveSession::create([
            'user_id' => $this->sysAdmin->id,
            'channel' => 'web',
            'session_hash' => hash('sha256', 'secret-session-value'),
            'device_info' => 'Test Admin Browser',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // Unverified GET or wrong/missing password must be rejected
        $this->actingAsWeb($this->sysAdmin)->get(route('sysadmin.backup.download'))->assertStatus(405);
        $this->actingAsWeb($this->sysAdmin)
            ->post(route('sysadmin.backup.download'), ['current_password' => 'WrongPassword!'])
            ->assertSessionHasErrors('current_password');

        $response = $this->actingAsWeb($this->sysAdmin)->post(route('sysadmin.backup.download'), [
            'current_password' => 'StrongPass#1234',
        ]);
        $response->assertOk();
        $sql = $response->streamedContent();

        $this->assertStringContainsString('DROP TABLE IF EXISTS `users`;', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `personal_access_tokens`;', $sql);
        $this->assertStringNotContainsString('INSERT INTO `users`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `personal_access_tokens`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `user_active_sessions`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `inventory_items`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `purchases`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `audit_logs`', $sql);
        $this->assertStringNotContainsString('SUPERSECRETMFAKEY1234', $sql);

        // API technical backup download also enforces password and strips confidential/credential rows
        Sanctum::actingAs($this->sysAdmin);
        $this->postJson('/api/v1/sysadmin/backup/download', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $apiSql = $this->post('/api/v1/sysadmin/backup/download', [
            'current_password' => 'StrongPass#1234',
        ], ['Accept' => 'application/json'])->assertOk()->streamedContent();
        $this->assertStringNotContainsString('INSERT INTO `users`', $apiSql);
        $this->assertStringNotContainsString('INSERT INTO `purchases`', $apiSql);

        // Audit separation: System Admin logs ONLY to technical_audit_logs, never business audit_logs
        $this->assertDatabaseHas('technical_audit_logs', [
            'user_id' => $this->sysAdmin->id,
            'category' => 'backup',
            'action' => 'database_backup_downloaded',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'user_id' => $this->sysAdmin->id,
            'action' => 'database_backup_downloaded',
        ]);
    }

    public function test_system_admin_protected_account_creation_and_role_escalation_prevention(): void
    {
        // Rejects attempts to pass role or wrong password
        $this->actingAs($this->sysAdmin)->post(route('sysadmin.accounts.store'), [
            'name' => 'Fake Owner',
            'username' => 'fake_owner',
            'email' => 'fake@mybatchoy.local',
            'password' => 'StrongPass#1234',
            'password_confirmation' => 'StrongPass#1234',
            'current_password' => 'StrongPass#1234',
            'role' => 'owner',
        ])->assertSessionHasErrors(['role']);

        // Deactivating business Owner is forbidden for System Admin
        $this->actingAs($this->sysAdmin)->patch(route('sysadmin.accounts.status', $this->owner), [
            'current_password' => 'StrongPass#1234',
            'is_active' => 0,
        ])->assertForbidden();

        // Authorized System Admin account creation succeeds via UI and CLI command
        $this->actingAs($this->sysAdmin)->post(route('sysadmin.accounts.store'), [
            'name' => 'Second Dev Admin',
            'username' => 'sysadmin_two',
            'email' => 'sysadmin2@mybatchoy.local',
            'password' => 'StrongPass#9876',
            'password_confirmation' => 'StrongPass#9876',
            'current_password' => 'StrongPass#1234',
        ])->assertRedirect(route('sysadmin.accounts.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'sysadmin_two',
            'role_id' => $this->sysAdminRole->id,
        ]);
    }

    public function test_system_admin_cli_setup_works_when_no_admin_exists_and_blocks_repeat(): void
    {
        User::where('role_id', $this->sysAdminRole->id)->delete();

        $this->artisan('app:create-system-admin')
            ->expectsQuestion('Full name', 'CLI Admin')
            ->expectsQuestion('Username', 'sysadmin_cli')
            ->expectsQuestion('Email address', 'cliadmin@mybatchoy.local')
            ->expectsQuestion('Password', 'CliAdminPass#1234')
            ->expectsQuestion('Confirm password', 'CliAdminPass#1234')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'username' => 'sysadmin_cli',
            'role_id' => $this->sysAdminRole->id,
        ]);

        $this->artisan('app:create-system-admin')->assertExitCode(1);
    }

    public function test_exceptional_business_access_workflow_and_automatic_expiry(): void
    {
        // 1. System Admin submits request
        $this->actingAs($this->sysAdmin)->post(route('sysadmin.access.store'), [
            'scope' => 'purchases',
            'duration_minutes' => 30,
            'reason' => 'Investigating PO sync timeout anomaly for debugging.',
        ])->assertRedirect(route('sysadmin.access.index'));

        $grant = BusinessAccessRequest::firstOrFail();
        $this->assertSame('pending', $grant->status);

        // Cannot access while pending
        $this->actingAs($this->sysAdmin)
            ->get(route('sysadmin.access.view', $grant))
            ->assertForbidden();

        // 2. Owner reviews & approves (requires Owner current_password)
        $this->actingAs($this->owner)->post(route('owner.access.approve', $grant), [
            'current_password' => 'WrongPassword!',
        ])->assertSessionHasErrors(['current_password']);

        $this->actingAs($this->owner)->post(route('owner.access.approve', $grant), [
            'current_password' => 'StrongPass#1234',
            'review_notes' => 'Approved for 30 minute purchase debugging',
        ])->assertRedirect(route('owner.access.index'));

        $grant->refresh();
        $this->assertSame('approved', $grant->status);

        // 3. System Admin opens scoped read-only view
        $viewResponse = $this->actingAs($this->sysAdmin)->get(route('sysadmin.access.view', $grant));
        $viewResponse->assertOk();
        $viewResponse->assertSee('PO-SUP-A-001');

        // Verify both audit logs recorded request, approval, and view
        $this->assertDatabaseHas('audit_logs', ['action' => 'exceptional_access_requested']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'exceptional_access_approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'exceptional_access_viewed']);
        $this->assertDatabaseHas('technical_audit_logs', ['action' => 'exceptional_access_viewed']);

        // 4. Advance time past expiry -> automatically expires and blocks further inspection
        $this->travel(35)->minutes();
        $this->actingAs($this->sysAdmin)
            ->get(route('sysadmin.access.view', $grant))
            ->assertForbidden();

        $grant->refresh();
        $this->assertSame('expired', $grant->status);
    }

    public function test_owner_technical_config_requires_explicit_authorization_and_staff_cannot_approve_own_adjustments(): void
    {
        // Owner blocked from /technical-config by default
        $this->actingAs($this->owner)->put(route('technical.config.update'), [
            'current_password' => 'StrongPass#1234',
            'log_retention_days' => 90,
            'backup_retention_days' => 30,
        ])->assertForbidden();

        // Once System Admin explicitly enables owner_can_edit_technical, Owner can update it
        TechnicalSetting::current()->update(['owner_can_edit_technical' => true]);
        $this->actingAs($this->owner)->put(route('technical.config.update'), [
            'current_password' => 'StrongPass#1234',
            'log_retention_days' => 90,
            'backup_retention_days' => 45,
            'owner_can_edit_technical' => true,
        ])->assertRedirect(route('owner.settings.index'));

        // Staff submits count adjustment request -> stays pending, inventory unchanged
        $this->actingAs($this->staff)->post(route('staff.adjustments.store'), [
            'inventory_item_id' => $this->item->id,
            'request_type' => 'stock_count',
            'counted_quantity' => 18,
            'reason' => 'Evening freezer physical count discrepancy',
        ])->assertRedirect(route('staff.adjustments.index'));

        $adjRequest = StockAdjustmentRequest::firstOrFail();
        $this->assertSame('pending', $adjRequest->status);
        $this->assertEquals(25.0, (float) $this->item->fresh()->current_quantity);

        // Staff CANNOT approve own request (Web or API)
        $this->actingAs($this->staff)
            ->post(route('owner.adjustments.approve', $adjRequest))
            ->assertForbidden();

        Sanctum::actingAs($this->staff);
        $this->postJson("/api/v1/adjustment-requests/{$adjRequest->id}/approve")
            ->assertForbidden();

        // Owner approves -> stock updates cleanly
        $this->actingAsWeb($this->owner)->post(route('owner.adjustments.approve', $adjRequest), [
            'review_notes' => 'Verified shelf count',
        ])->assertRedirect(route('owner.adjustments.index'));

        $this->assertSame('approved', $adjRequest->fresh()->status);
        $this->assertEquals(18.0, (float) $this->item->fresh()->current_quantity);
    }

    public function test_supplier_portal_isolation_fulfillment_workflow_and_account_provisioning(): void
    {
        // Owner provisions a new Supplier account bound to Supplier A
        $this->actingAs($this->owner)->post(route('owner.supplier-accounts.store'), [
            'current_password' => 'StrongPass#1234',
            'supplier_id' => $this->supplierA->id,
            'name' => 'Iloilo Pork Dispatcher',
            'username' => 'sup_a_dispatch',
            'email' => 'dispatch@example.com',
            'password' => 'StrongPass#4321',
            'password_confirmation' => 'StrongPass#4321',
            'is_active' => 1,
        ])->assertRedirect(route('owner.supplier-accounts.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'sup_a_dispatch',
            'supplier_id' => $this->supplierA->id,
        ]);

        // Supplier A sees ONLY PO-SUP-A-001, NOT PO-SUP-B-001
        $ordersPage = $this->actingAs($this->supplierUserA)->get(route('supplier.orders.index'));
        $ordersPage->assertOk();
        $ordersPage->assertSee('PO-SUP-A-001');
        $ordersPage->assertDontSee('PO-SUP-B-001');

        // Cross-tenant Supplier B PO access is strictly 403 on Web & API
        $this->actingAs($this->supplierUserA)
            ->post(route('supplier.orders.confirm', $this->poB))
            ->assertForbidden();

        Sanctum::actingAs($this->supplierUserA);
        $this->postJson("/api/v1/supplier/orders/{$this->poB->id}/confirm")->assertForbidden();

        // Supplier blocked from internal inventory, reports, staff, audit logs, sysadmin
        foreach ([
            route('owner.inventory.index'),
            route('staff.inventory.index'),
            route('owner.reports.index'),
            route('owner.audit.index'),
            route('sysadmin.dashboard'),
        ] as $forbiddenUrl) {
            $this->actingAsWeb($this->supplierUserA)->get($forbiddenUrl)->assertForbidden();
        }
        Sanctum::actingAs($this->supplierUserA);
        $this->getJson('/api/v1/inventory')->assertForbidden();
        $this->getJson('/api/v1/purchases')->assertForbidden();

        // Supplier A confirms PO-SUP-A-001 and updates delivery + invoice
        $this->actingAsWeb($this->supplierUserA)
            ->post(route('supplier.orders.confirm', $this->poA))
            ->assertRedirect(route('supplier.orders.index'));

        $this->actingAsWeb($this->supplierUserA)
            ->patch(route('supplier.orders.delivery', $this->poA), [
                'delivery_status' => 'delivered',
                'invoice_number' => 'INV-2026-881',
                'delivery_notes' => 'Delivered at loading bay',
            ])->assertRedirect(route('supplier.orders.index'));

        // Stock did NOT auto-increment by Supplier action alone (still 25 kg)
        $this->assertEquals(25.0, (float) $this->item->fresh()->current_quantity);

        // Supplier updates own contact details
        $this->actingAsWeb($this->supplierUserA)
            ->put(route('supplier.profile.update'), [
                'contact_person' => 'Juan Dela Cruz',
                'phone' => '09179999999',
            ])->assertRedirect(route('supplier.profile'));
        $this->assertSame('Juan Dela Cruz', $this->supplierA->fresh()->contact_person);

        // Owner marks confirmed delivery received -> adds +10 kg into inventory (25 -> 35 kg)
        $this->actingAsWeb($this->owner)
            ->post(route('owner.purchases.receive', $this->poA))
            ->assertRedirect(route('owner.purchases.index'));

        $this->assertTrue((bool) $this->poA->fresh()->received_in_inventory);
        $this->assertEquals(35.0, (float) $this->item->fresh()->current_quantity);
    }

    public function test_new_role_views_preserve_design_consistency_and_staff_boundary(): void
    {
        foreach ([
            route('sysadmin.dashboard'),
            route('sysadmin.health'),
            route('sysadmin.security'),
            route('sysadmin.config'),
            route('sysadmin.accounts.index'),
            route('sysadmin.access.index'),
        ] as $url) {
            $res = $this->actingAsWeb($this->sysAdmin)->get($url)->assertOk();
            $res->assertSee('--bg: #111111;', false);
            $res->assertSee('eb-page-header', false);
            $res->assertDontSee('#6A1B9A', false);
        }

        foreach ([
            route('owner.adjustments.index'),
            route('owner.supplier-accounts.index'),
            route('owner.access.index'),
        ] as $url) {
            $res = $this->actingAsWeb($this->owner)->get($url)->assertOk();
            $res->assertSee('eb-page-header', false);
        }

        $staffAdj = $this->actingAsWeb($this->staff)->get(route('staff.adjustments.index'))->assertOk();
        $staffAdj->assertSee('eb-page-header', false);
        foreach (['PURCHASING', 'ANALYTICS', 'ADMINISTRATION', 'Staff Management', 'Audit Logs', 'Settings'] as $forbiddenText) {
            $staffAdj->assertDontSee($forbiddenText, false);
        }

        foreach ([
            route('supplier.dashboard'),
            route('supplier.orders.index'),
            route('supplier.profile'),
        ] as $url) {
            $res = $this->actingAsWeb($this->supplierUserA)->get($url)->assertOk();
            $res->assertSee('eb-page-header', false);
        }
    }

    public function test_secrets_and_passwords_are_scrubbed_from_technical_audit_logs(): void
    {
        TechnicalAuditLog::create([
            'user_id' => $this->sysAdmin->id,
            'category' => 'security',
            'action' => 'test_log_event',
            'details' => 'Attempted connection password=TopSecretValue token=abcd1234secret',
        ]);

        // Ensure TechnicalAuditLogger::record scrubs values cleanly
        $req = request();
        \App\Services\TechnicalAuditLogger::record(
            $req,
            $this->sysAdmin->id,
            'security',
            'scrub_check',
            'Header authorization=Bearer SuperSecretJwtToken999 password=MyPlainPassword!'
        );

        $entry = TechnicalAuditLog::where('action', 'scrub_check')->firstOrFail();
        $this->assertStringNotContainsString('SuperSecretJwtToken999', $entry->details);
        $this->assertStringNotContainsString('MyPlainPassword!', $entry->details);
        $this->assertStringContainsString('[REDACTED]', $entry->details);
    }

    public function test_historical_direct_purchase_defaults_and_api_permission_groups_per_role(): void
    {
        // Legacy/direct purchase payload without fulfillment_mode defaults to direct_received and credits stock
        $this->actingAsWeb($this->owner)->post(route('owner.purchases.store'), [
            'supplier_id' => $this->supplierA->id,
            'purchase_date' => now()->toDateString(),
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'quantity' => 5,
                    'unit_cost' => 120,
                ],
            ],
        ])->assertRedirect(route('owner.purchases.index'));

        $latestPurchase = Purchase::latest('id')->firstOrFail();
        $this->assertSame('direct_received', $latestPurchase->fulfillment_mode);
        $this->assertSame('confirmed', $latestPurchase->supplier_status);
        $this->assertSame('delivered', $latestPurchase->delivery_status);
        $this->assertTrue((bool) $latestPurchase->received_in_inventory);
        $this->assertEquals(30.0, (float) $this->item->fresh()->current_quantity);

        // /api/v1/auth/me exposes separated technical vs business permission groups per role
        Sanctum::actingAs($this->sysAdmin);
        $saMe = $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.role', 'system_admin')
            ->json('user.permission_groups');
        $this->assertContains('technical_administration', $saMe);
        $this->assertContains('security_management', $saMe);
        $this->assertNotContains('business_ownership', $saMe);
        $this->assertNotContains('business_reporting', $saMe);

        Sanctum::actingAs($this->owner);
        $ownerMe = $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.role', 'owner')
            ->json('user.permission_groups');
        $this->assertContains('business_ownership', $ownerMe);
        $this->assertContains('business_reporting', $ownerMe);
        $this->assertNotContains('technical_administration', $ownerMe);

        Sanctum::actingAs($this->supplierUserA);
        $supMe = $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.role', 'supplier')
            ->assertJsonPath('user.supplier_id', $this->supplierA->id)
            ->json('user.permission_groups');
        $this->assertContains('purchasing_suppliers', $supMe);
        $this->assertNotContains('business_ownership', $supMe);
        $this->assertNotContains('inventory_operations', $supMe);
    }

    public function test_staff_direct_adjustment_endpoints_blocked_and_maker_checker_enforced(): void
    {
        // Staff cannot hit direct adjustment endpoints via Web or API
        $this->actingAsWeb($this->staff)
            ->get(route('staff.transactions.adjustment'))
            ->assertForbidden();

        $this->actingAsWeb($this->staff)
            ->post(route('staff.transactions.adjustmentStore'), [
                'inventory_item_id' => $this->item->id,
                'current_quantity' => 5,
                'reason' => 'Unauthorized Web bypass',
            ])->assertForbidden();

        $this->actingAsWeb($this->staff)
            ->post(route('owner.transactions.adjustmentStore'), [
                'inventory_item_id' => $this->item->id,
                'current_quantity' => 5,
                'reason' => 'Unauthorized Owner endpoint bypass',
            ])->assertForbidden();

        Sanctum::actingAs($this->staff);
        $this->postJson('/api/v1/transactions/adjustment', [
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 5,
            'reason' => 'Unauthorized API bypass',
        ])->assertForbidden();

        $this->assertEquals(25.0, (float) $this->item->fresh()->current_quantity);
        $this->assertSame(0, InventoryTransaction::where('transaction_type', 'adjustment')->count());

        // Unauthorized roles cannot call adjustment endpoints either
        foreach ([$this->sysAdmin, $this->supplierUserA] as $otherUser) {
            Sanctum::actingAs($otherUser);
            $this->postJson('/api/v1/transactions/adjustment', [
                'inventory_item_id' => $this->item->id,
                'current_quantity' => 5,
                'reason' => 'Unauthorized role',
            ])->assertForbidden();
            $this->postJson('/api/v1/adjustment-requests', [
                'inventory_item_id' => $this->item->id,
                'counted_quantity' => 5,
                'reason' => 'Unauthorized role',
            ])->assertForbidden();
        }

        // Staff submits request; Owner rejection leaves stock unchanged
        $this->actingAsWeb($this->staff)->post(route('staff.adjustments.store'), [
            'inventory_item_id' => $this->item->id,
            'counted_quantity' => 19,
            'request_type' => 'stock_count',
            'reason' => 'Morning freezer count mismatch',
        ])->assertRedirect(route('staff.adjustments.index'));

        $reqToReject = StockAdjustmentRequest::latest('id')->firstOrFail();
        $this->actingAsWeb($this->owner)->post(route('owner.adjustments.reject', $reqToReject), [
            'review_notes' => 'Recount bag cleanly',
        ])->assertRedirect(route('owner.adjustments.index'));

        $this->assertSame('rejected', $reqToReject->fresh()->status);
        $this->assertEquals(25.0, (float) $this->item->fresh()->current_quantity);
        $this->assertSame(0, InventoryTransaction::where('transaction_type', 'adjustment')->count());

        // Staff resubmits via API and Owner approves -> applies stock and logs adjustment transaction
        Sanctum::actingAs($this->staff);
        $res = $this->postJson('/api/v1/adjustment-requests', [
            'inventory_item_id' => $this->item->id,
            'counted_quantity' => 21,
            'request_type' => 'adjustment',
            'reason' => 'Verified scale recount after prep',
        ])->assertCreated();
        $reqId = $res->json('adjustment_request.id');

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/adjustment-requests/{$reqId}/approve")
            ->assertOk();

        $this->assertEquals(21.0, (float) $this->item->fresh()->current_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $this->item->id,
            'transaction_type' => 'adjustment',
            'updated_quantity' => 21,
        ]);

        // Owner still retains direct adjustment capability via Web and API
        $this->postJson('/api/v1/transactions/adjustment', [
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 23,
            'reason' => 'Owner final reconciliation',
        ])->assertCreated();
        $this->assertEquals(23.0, (float) $this->item->fresh()->current_quantity);
    }

    public function test_valuation_and_realized_purchase_totals_exclude_unverified_and_unreceived_orders(): void
    {
        // setUp has poA (pending, received_in_inventory=false, total=1500, unit_cost=150) & poB (pending)
        $rejectedPo = Purchase::create([
            'purchase_number' => 'PO-REJECTED-001',
            'supplier_id' => $this->supplierA->id,
            'created_by' => $this->owner->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 900,
            'fulfillment_mode' => 'supplier_po',
            'supplier_status' => 'rejected',
            'delivery_status' => 'rejected',
            'received_in_inventory' => false,
        ]);
        PurchaseItem::create([
            'purchase_id' => $rejectedPo->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_cost' => 90,
            'subtotal' => 900,
        ]);

        // With no realized purchases, valuation must NOT invent PHP 85.00 fallback (25 * 85 = 2125)
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonPath('summary.inventory_value', 0)
            ->assertJsonPath('summary.valuation_status', 'unvalued')
            ->assertJsonPath('summary.unknown_cost_items_count', 1)
            ->assertJsonPath('summary.total_purchases', 0)
            ->assertJsonPath('summary.monthly_purchases', 0);

        $this->actingAsWeb($this->owner)
            ->get(route('owner.reports.index'))
            ->assertOk()
            ->assertSee('No verified purchase costs yet')
            ->assertDontSee('2,125.00');

        // Record one realized purchase for Pork Bones (unit_cost=200, total=2000) and leave Miki Noodles unpriced (qty=10)
        $realizedPo = Purchase::create([
            'purchase_number' => 'PO-REALIZED-001',
            'supplier_id' => $this->supplierA->id,
            'created_by' => $this->owner->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 2000,
            'fulfillment_mode' => 'direct_received',
            'supplier_status' => 'confirmed',
            'delivery_status' => 'delivered',
            'received_in_inventory' => true,
        ]);
        PurchaseItem::create([
            'purchase_id' => $realizedPo->id,
            'inventory_item_id' => $this->item->id,
            'quantity' => 10,
            'unit_cost' => 200,
            'subtotal' => 2000,
        ]);

        InventoryItem::create([
            'name' => 'Miki Noodles',
            'sku' => 'MIKI-UNVALUED',
            'category_id' => $this->item->category_id,
            'unit_id' => $this->item->unit_id,
            'current_quantity' => 10,
            'minimum_stock_level' => 2,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        // Reports API & Web compute 25 * 200 = 5000 only and flag partial valuation
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonPath('summary.inventory_value', 5000)
            ->assertJsonPath('summary.valued_items_count', 1)
            ->assertJsonPath('summary.unknown_cost_items_count', 1)
            ->assertJsonPath('summary.valuation_status', 'partial')
            ->assertJsonPath('summary.total_purchases', 2000)
            ->assertJsonPath('summary.monthly_purchases', 2000);

        $this->actingAsWeb($this->owner)
            ->get(route('owner.reports.index'))
            ->assertOk()
            ->assertSee('Partial: 1 valued')
            ->assertSee('1 unknown cost excluded');

        // Dashboard API & Web and Suppliers API & Web exclude pending & rejected POs
        $this->getJson('/api/v1/dashboard?range=this_month')
            ->assertOk()
            ->assertJsonPath('summary.purchasing_expenses', 2000)
            ->assertJsonPath('summary.purchases_count', 1);

        $supRes = $this->getJson('/api/v1/suppliers')->assertOk();
        $supA = collect($supRes->json('suppliers'))->firstWhere('id', $this->supplierA->id);
        $this->assertSame(1, $supA['purchases_count']);
        $this->assertEquals(2000.0, (float) $supA['total_spent']);

        $this->actingAsWeb($this->owner)
            ->get(route('owner.suppliers.index'))
            ->assertOk()
            ->assertSee('2,000.00');
    }

    public function test_owner_cannot_inspect_or_revoke_system_admin_sessions(): void
    {
        $sysAdminTwo = User::factory()->create([
            'role_id' => $this->sysAdminRole->id,
            'name' => 'Second Technical Admin',
            'username' => 'sysadmin_two_sess',
            'email' => 'sysadmin2@mybatchoy.local',
            'password' => Hash::make('StrongPass#1234'),
            'is_active' => true,
        ]);

        UserActiveSession::create([
            'user_id' => $this->sysAdmin->id,
            'channel' => 'web',
            'session_hash' => hash('sha256', 'admin-one-sess'),
            'device_info' => 'Admin Browser 1',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        UserActiveSession::create([
            'user_id' => $sysAdminTwo->id,
            'channel' => 'web',
            'session_hash' => hash('sha256', 'admin-two-sess'),
            'device_info' => 'Admin Browser 2',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        UserActiveSession::create([
            'user_id' => $this->staff->id,
            'channel' => 'api',
            'session_hash' => hash('sha256', 'staff-api-sess'),
            'device_info' => 'Kitchen Tablet',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // Owner settings page & API exclude System Admin sessions while still showing Staff session
        $this->actingAsWeb($this->owner)
            ->get(route('owner.settings.index'))
            ->assertOk()
            ->assertSee('staff_kitchen')
            ->assertDontSee('sysadmin_dev')
            ->assertDontSee('sysadmin_two_sess');

        Sanctum::actingAs($this->owner);
        $settingsJson = $this->getJson('/api/v1/settings')->assertOk();
        $sessionUsernames = collect($settingsJson->json('active_sessions'))->pluck('username')->all();
        $this->assertContains('staff_kitchen', $sessionUsernames);
        $this->assertNotContains('sysadmin_dev', $sessionUsernames);
        $this->assertNotContains('sysadmin_two_sess', $sessionUsernames);

        // Owner cannot revoke System Admin session via Web or API -> 403 and session preserved
        $this->actingAsWeb($this->owner)
            ->post(route('owner.settings.sessions.revoke', $this->sysAdmin), [
                'current_password' => 'StrongPass#1234',
                'mfa_code' => '123456',
            ])->assertForbidden();

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/settings/security/sessions/{$this->sysAdmin->id}/revoke", [
            'current_password' => 'StrongPass#1234',
            'mfa_code' => '123456',
        ])->assertForbidden();

        $this->assertSame(1, UserActiveSession::where('user_id', $this->sysAdmin->id)->count());

        // System Admin inspects & manages technical admin sessions cleanly under /admin/security
        $this->actingAsWeb($this->sysAdmin)
            ->get(route('sysadmin.security'))
            ->assertOk()
            ->assertSee('sysadmin_two_sess');

        $this->actingAsWeb($this->sysAdmin)
            ->post(route('sysadmin.security.sessions.revoke', $sysAdminTwo), [
                'current_password' => 'StrongPass#1234',
            ])->assertRedirect(route('sysadmin.security'));

        $this->assertSame(0, UserActiveSession::where('user_id', $sysAdminTwo->id)->count());
        $this->assertDatabaseHas('technical_audit_logs', [
            'user_id' => $this->sysAdmin->id,
            'category' => 'security',
            'action' => 'admin_session_revoked',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'session_revoked',
            'model_id' => $sysAdminTwo->id,
        ]);
    }
}
