<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MobileApiVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $staff;
    private User $otherStaff;
    private InventoryCategory $category;
    private Unit $unit;
    private InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        BusinessSetting::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => 'MyBatchoy Kalibo',
                'session_timeout_minutes' => 60,
                'default_minimum_stock_level' => 5,
                'default_reorder_level' => 10,
            ]
        );

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'name' => 'Maria Owner',
            'username' => 'mob-owner',
            'email' => 'mob-owner@mybatchoy.test',
            'password' => 'Password123!',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Juan Staff',
            'username' => 'mob-staff',
            'email' => 'mob-staff@mybatchoy.test',
            'password' => 'Password123!',
            'is_active' => true,
        ]);

        $this->otherStaff = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Pedro Staff',
            'username' => 'mob-staff-2',
            'email' => 'mob-staff-2@mybatchoy.test',
            'password' => 'Password123!',
            'is_active' => true,
        ]);

        $this->category = InventoryCategory::create([
            'name' => 'Noodles & Wrappers',
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'name' => 'Kilogram',
            'short_name' => 'kg',
            'is_active' => true,
        ]);

        $this->item = InventoryItem::create([
            'name' => 'Fresh Miki Noodles',
            'sku' => 'MIKI-001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 40,
            'minimum_stock_level' => 10,
            'reorder_level' => 15,
            'is_active' => true,
        ]);
    }

    private function apiWithToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    private function webActingAs(User $user): self
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        return $this->actingAs($user, 'web');
    }

    public function test_checks_1_and_2_owner_and_staff_can_log_in_using_same_account_on_web_and_mobile(): void
    {
        // 1. Owner logs in on Mobile API
        $ownerMobileLogin = $this->postJson('/api/v1/auth/login', [
            'username' => 'mob-owner',
            'password' => 'Password123!',
            'device_name' => 'Expo Go iOS',
        ]);
        $ownerMobileLogin->assertOk()
            ->assertJsonPath('user.username', 'mob-owner')
            ->assertJsonPath('user.role', 'owner');
        $ownerMobileToken = (string) $ownerMobileLogin->json('token');
        $this->assertNotEmpty($ownerMobileToken);

        // Log out mobile session before logging in on Web under Single Active Session policy
        $this->apiWithToken($ownerMobileToken)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        // Owner logs in on Website with the same credentials
        $this->withoutToken()->post('/login', [
            'username' => 'mob-owner',
            'password' => 'Password123!',
        ])->assertRedirect(route('owner.dashboard'));
        $this->post('/logout');

        // 2. Staff logs in on Mobile API
        $staffMobileLogin = $this->postJson('/api/v1/auth/login', [
            'username' => 'mob-staff',
            'password' => 'Password123!',
            'device_name' => 'Expo Go Android',
        ]);
        $staffMobileLogin->assertOk()
            ->assertJsonPath('user.username', 'mob-staff')
            ->assertJsonPath('user.role', 'staff');
        $staffMobileToken = (string) $staffMobileLogin->json('token');
        $this->assertNotEmpty($staffMobileToken);

        // Log out mobile session before logging in on Web
        $this->apiWithToken($staffMobileToken)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        // Staff logs in on Website with the same credentials
        $this->withoutToken()->post('/login', [
            'username' => 'mob-staff',
            'password' => 'Password123!',
        ])->assertRedirect(route('staff.dashboard'));
    }

    public function test_checks_3_and_4_owner_and_staff_permissions_are_consistent_on_mobile_api(): void
    {
        $ownerToken = $this->owner->createToken('OwnerPhone')->plainTextToken;
        $staffToken = $this->staff->createToken('StaffPhone')->plainTextToken;

        // Owner can access all owner endpoints
        $this->apiWithToken($ownerToken)->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'owner');
        $this->apiWithToken($ownerToken)->getJson('/api/v1/suppliers')->assertOk();
        $this->apiWithToken($ownerToken)->getJson('/api/v1/purchases')->assertOk();
        $this->apiWithToken($ownerToken)->getJson('/api/v1/reports')->assertOk();
        $this->apiWithToken($ownerToken)->getJson('/api/v1/staff')->assertOk();
        $this->apiWithToken($ownerToken)->getJson('/api/v1/audit-logs')->assertOk();
        $this->apiWithToken($ownerToken)->getJson('/api/v1/settings')->assertOk();

        // Staff can access staff dashboard, inventory view, transactions, and profile
        $this->apiWithToken($staffToken)->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('role', 'staff');
        $this->apiWithToken($staffToken)->getJson('/api/v1/inventory')->assertOk();
        $this->apiWithToken($staffToken)->getJson('/api/v1/transactions')->assertOk();
        $this->apiWithToken($staffToken)->getJson('/api/v1/profile')->assertOk();

        // Staff is forbidden (403) from owner-only endpoints
        $this->apiWithToken($staffToken)->postJson('/api/v1/inventory', [
            'name' => 'Unauthorized Item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 5,
            'minimum_stock_level' => 2,
        ])->assertForbidden();
        $this->apiWithToken($staffToken)->putJson("/api/v1/inventory/{$this->item->id}", [])->assertForbidden();
        $this->apiWithToken($staffToken)->getJson('/api/v1/suppliers')->assertForbidden();
        $this->apiWithToken($staffToken)->getJson('/api/v1/purchases')->assertForbidden();
        $this->apiWithToken($staffToken)->getJson('/api/v1/reports')->assertForbidden();
        $this->apiWithToken($staffToken)->getJson('/api/v1/staff')->assertForbidden();
        $this->apiWithToken($staffToken)->getJson('/api/v1/audit-logs')->assertForbidden();
        $this->apiWithToken($staffToken)->getJson('/api/v1/settings')->assertForbidden();

        // Staff only sees their own transactions in API history
        InventoryTransaction::create([
            'inventory_item_id' => $this->item->id,
            'user_id' => $this->otherStaff->id,
            'transaction_type' => 'stock_out',
            'quantity' => 3,
            'previous_quantity' => 40,
            'updated_quantity' => 37,
            'reason' => 'Other staff secret movement',
            'transaction_date' => now(),
        ]);
        InventoryTransaction::create([
            'inventory_item_id' => $this->item->id,
            'user_id' => $this->staff->id,
            'transaction_type' => 'stock_out',
            'quantity' => 2,
            'previous_quantity' => 37,
            'updated_quantity' => 35,
            'reason' => 'Juan shift usage',
            'transaction_date' => now(),
        ]);

        $staffTxResponse = $this->apiWithToken($staffToken)->getJson('/api/v1/transactions')->assertOk();
        $reasons = collect($staffTxResponse->json('transactions'))->pluck('reason')->all();
        $this->assertContains('Juan shift usage', $reasons);
        $this->assertNotContains('Other staff secret movement', $reasons);
    }

    public function test_checks_5_and_6_inventory_changes_sync_bidirectional_between_mobile_and_website(): void
    {
        $ownerToken = $this->owner->createToken('OwnerPhone')->plainTextToken;

        // Check 5: Create & update inventory on mobile -> appears on website
        $createRes = $this->apiWithToken($ownerToken)->postJson('/api/v1/inventory', [
            'name' => 'Crispy Chicharon',
            'sku' => 'CHICH-100',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 25,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
        ]);
        $createRes->assertCreated()->assertJsonPath('item.name', 'Crispy Chicharon');

        $webInventoryPage = $this->webActingAs($this->owner)->get(route('owner.inventory.index'));
        $webInventoryPage->assertOk()
            ->assertSee('Crispy Chicharon')
            ->assertSee('CHICH-100');

        // Check 6: Create & update inventory on website -> appears on mobile
        $this->webActingAs($this->owner)->post(route('owner.inventory.store'), [
            'name' => 'Fried Garlic Flakes',
            'sku' => 'GARLIC-200',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 18,
            'minimum_stock_level' => 4,
            'reorder_level' => 8,
        ])->assertRedirect(route('owner.inventory.index'));

        $mobileInventory = $this->apiWithToken($ownerToken)->getJson('/api/v1/inventory')->assertOk();
        $skus = collect($mobileInventory->json('items'))->pluck('sku')->all();
        $this->assertContains('GARLIC-200', $skus);
    }

    public function test_checks_7_8_9_10_12_stock_in_out_adjustment_spoilage_and_transaction_history(): void
    {
        $staffToken = $this->staff->createToken('StaffPhone')->plainTextToken;

        // Check 7: Stock In (+10 -> 50)
        $this->withToken($staffToken)->postJson('/api/v1/transactions/stock-in', [
            'inventory_item_id' => $this->item->id,
            'quantity' => 10,
            'reason' => 'Morning delivery',
        ])->assertCreated()->assertJsonPath('item.current_quantity', 50);

        // Check 8: Stock Out (-15 -> 35)
        $this->withToken($staffToken)->postJson('/api/v1/transactions/stock-out', [
            'inventory_item_id' => $this->item->id,
            'quantity' => 15,
            'reason' => 'Lunch service prep',
        ])->assertCreated()->assertJsonPath('item.current_quantity', 35);

        // Failed Stock Out (exceeds current stock) does not alter quantity or record transaction
        $txCountBeforeFail = InventoryTransaction::count();
        $this->withToken($staffToken)->postJson('/api/v1/transactions/stock-out', [
            'inventory_item_id' => $this->item->id,
            'quantity' => 999,
            'reason' => 'Impossible withdrawal',
        ])->assertStatus(422);
        $this->assertSame($txCountBeforeFail, InventoryTransaction::count());
        $this->assertEquals(35.0, (float) $this->item->fresh()->current_quantity);

        // Check 9: Stock Adjustment (35 -> 32)
        $this->withToken($staffToken)->postJson('/api/v1/transactions/adjustment', [
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 32,
            'reason' => 'Physical recount',
        ])->assertCreated()->assertJsonPath('item.current_quantity', 32);

        // Check 10: Spoilage (-2 -> 30)
        $this->withToken($staffToken)->postJson('/api/v1/transactions/spoilage', [
            'inventory_item_id' => $this->item->id,
            'quantity' => 2,
            'reason' => 'Damaged packaging',
        ])->assertCreated()->assertJsonPath('item.current_quantity', 30);

        // Check 12: Transaction history is preserved and immutable
        $this->assertSame(4, InventoryTransaction::where('inventory_item_id', $this->item->id)->count());
        $this->withToken($staffToken)->deleteJson('/api/v1/transactions/1')->assertStatus(404);

        // Website also reflects the updated quantity (30)
        $this->assertEquals(30.0, (float) $this->item->fresh()->current_quantity);
    }

    public function test_checks_11_13_14_15_purchases_audit_logs_staff_management_and_reports(): void
    {
        $ownerToken = $this->owner->createToken('OwnerPhone')->plainTextToken;

        // Create supplier on mobile
        $supplierRes = $this->withToken($ownerToken)->postJson('/api/v1/suppliers', [
            'name' => 'Panay Noodle Supply',
            'contact_person' => 'Roberto Cruz',
            'phone' => '09171234567',
        ])->assertCreated();
        $supplierId = $supplierRes->json('supplier.id');

        // Check 11: Record purchase on mobile (+20 qty @ 90.00 = 1800.00 -> item becomes 60)
        $purchaseRes = $this->withToken($ownerToken)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplierId,
            'purchase_date' => now()->toDateString(),
            'notes' => 'Weekly bulk order',
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'quantity' => 20,
                    'unit_cost' => 90,
                ],
            ],
        ])->assertCreated();
        $this->assertEquals(1800.0, (float) $purchaseRes->json('purchase.total_amount'));
        $this->assertEquals(60.0, (float) $this->item->fresh()->current_quantity);

        // Check 14: Staff management (create, activate/deactivate, reset password)
        $newStaffRes = $this->withToken($ownerToken)->postJson('/api/v1/staff', [
            'name' => 'Ana Staff',
            'username' => 'ana-staff',
            'email' => 'ana@mybatchoy.test',
            'phone' => '09180001111',
            'password' => 'StrongPass2026!',
            'password_confirmation' => 'StrongPass2026!',
            'is_active' => true,
        ])->assertCreated();
        $anaId = $newStaffRes->json('staff.id');

        // Deactivate staff member -> revokes any active tokens
        $anaUser = User::findOrFail($anaId);
        $anaToken = $anaUser->createToken('AnaPhone')->plainTextToken;

        $this->withToken($ownerToken)->patchJson("/api/v1/staff/{$anaId}/status", [
            'is_active' => false,
        ])->assertOk()->assertJsonPath('staff.is_active', false);

        $this->assertSame(0, $anaUser->tokens()->count());

        // Reactivate staff member
        $this->withToken($ownerToken)->patchJson("/api/v1/staff/{$anaId}/status", [
            'is_active' => true,
        ])->assertOk()->assertJsonPath('staff.is_active', true);

        // Check 15: Reports & CSV export
        $reportsRes = $this->withToken($ownerToken)->getJson('/api/v1/reports')->assertOk();
        $this->assertEquals(1800.0, (float) $reportsRes->json('summary.total_purchases'));
        $this->assertEquals(5400.0, (float) $reportsRes->json('summary.inventory_value')); // 60 * 90.00

        $csvRes = $this->withToken($ownerToken)->get('/api/v1/reports/export');
        $csvRes->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvRes->headers->get('Content-Type'));

        // Check 13: Audit logs recorded all operations
        $auditRes = $this->withToken($ownerToken)->getJson('/api/v1/audit-logs')->assertOk();
        $actions = collect($auditRes->json('logs'))->pluck('action')->all();
        $this->assertContains('supplier_created', $actions);
        $this->assertContains('purchase_recorded', $actions);
        $this->assertContains('staff_created', $actions);
        $this->assertContains('staff_deactivated', $actions);
        $this->assertContains('staff_activated', $actions);
    }

    public function test_checks_16_and_17_logout_token_expiration_and_idempotency_protection(): void
    {
        // Check 17: Idempotency prevents duplicate stock transactions on network retry
        $ownerToken = $this->owner->createToken('OwnerPhone')->plainTextToken;
        $idempotencyKey = 'mob-tx-uuid-123456789';

        $firstAttempt = $this->withToken($ownerToken)
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/transactions/stock-in', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 5,
                'reason' => 'Idempotent delivery',
            ]);
        $firstAttempt->assertCreated()->assertJsonPath('item.current_quantity', 45);

        // Second attempt with same X-Idempotency-Key replays response and does NOT add another 5
        $secondAttempt = $this->withToken($ownerToken)
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/transactions/stock-in', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 5,
                'reason' => 'Idempotent delivery',
            ]);
        $secondAttempt->assertCreated()
            ->assertHeader('X-Idempotent-Replayed', 'true')
            ->assertJsonPath('item.current_quantity', 45);

        $this->assertEquals(45.0, (float) $this->item->fresh()->current_quantity);
        $this->assertSame(1, InventoryTransaction::where('reason', 'Idempotent delivery')->count());

        // Check 16a: Expired token is rejected
        $expiredToken = $this->staff->createToken('ExpiredPhone', ['*'], now()->subMinute());
        $this->apiWithToken($expiredToken->plainTextToken)
            ->getJson('/api/v1/dashboard')
            ->assertStatus(401);

        // Check 16b: Logout deletes the current token
        $this->apiWithToken($ownerToken)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $this->owner->id)->count());
    }
}
