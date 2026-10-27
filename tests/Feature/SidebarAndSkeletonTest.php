<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarAndSkeletonTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        $category = InventoryCategory::firstOrCreate(['name' => 'Broth & Seasoning']);
        $unit = Unit::firstOrCreate(['short_name' => 'pack'], ['name' => 'Pack', 'short_name' => 'pack']);

        $this->owner = User::factory()->create([
            'name' => 'Owner Test',
            'username' => 'ownertest',
            'email' => 'owner.test@example.com',
            'role_id' => $ownerRole->id,
            'password' => bcrypt('Password123!'),
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'name' => 'Staff Test',
            'username' => 'stafftest',
            'email' => 'staff.test@example.com',
            'role_id' => $staffRole->id,
            'password' => bcrypt('Password123!'),
            'is_active' => true,
        ]);

        InventoryItem::create([
            'name' => 'Miki Noodles',
            'sku' => 'MIKI-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 20,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);
    }

    public function test_sidebar_sections_and_tooltips_render_for_owner(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard'));

        $response->assertOk();

        // 6 Required Sidebar Sections
        $response->assertSee('OVERVIEW');
        $response->assertSee('INVENTORY');
        $response->assertSee('PURCHASING');
        $response->assertSee('ANALYTICS');
        $response->assertSee('ADMINISTRATION');
        $response->assertSee('ACCOUNT');

        // Navigation links
        $response->assertSee('Dashboard');
        $response->assertSee('Inventory');
        $response->assertSee('Stock Transactions');
        $response->assertSee('Suppliers');
        $response->assertSee('Purchases');
        $response->assertSee('Reports');
        $response->assertSee('Staff Management');
        $response->assertSee('Audit Logs');
        $response->assertSee('Profile');
        $response->assertSee('Settings');
        $response->assertSee('Logout');

        // Tooltips for collapsed mode
        $response->assertSee('data-tooltip="Dashboard"', false);
        $response->assertSee('data-tooltip="Inventory"', false);
        $response->assertSee('data-tooltip="Stock Transactions"', false);
        $response->assertSee('data-tooltip="Suppliers"', false);
        $response->assertSee('data-tooltip="Purchases"', false);
        $response->assertSee('data-tooltip="Reports"', false);
        $response->assertSee('data-tooltip="Staff Management"', false);
        $response->assertSee('data-tooltip="Audit Logs"', false);

        // Sidebar collapse control button & drawer elements
        $response->assertSee('id="sidebarToggleBtn"', false);
        $response->assertSee('id="sidebarBackdrop"', false);
        $response->assertSee('id="sidebar"', false);
    }

    public function test_sidebar_restricts_sections_for_staff(): void
    {
        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));

        $response->assertOk();

        // Staff sees authorized sections
        $response->assertSee('OVERVIEW');
        $response->assertSee('INVENTORY');
        $response->assertSee('ACCOUNT');

        // Staff does NOT see owner sections
        $response->assertDontSee('PURCHASING');
        $response->assertDontSee('ANALYTICS');
        $response->assertDontSee('ADMINISTRATION');
        $response->assertDontSee('Staff Management');
        $response->assertDontSee('Audit Logs');
        $response->assertDontSee('Settings');
    }

    public function test_dashboard_and_inventory_skeletons_are_present_and_hidden_by_default(): void
    {
        $ownerDash = $this->actingAs($this->owner)->get(route('owner.dashboard'));
        $ownerDash->assertOk();
        $ownerDash->assertSee('id="dashboardSkeletonWrapper"', false);
        $ownerDash->assertSee('dashboard-skeleton-placeholder', false);
        $ownerDash->assertSee('skeleton-shimmer', false);
        $ownerDash->assertSee('data-counter="', false);

        $inventoryPage = $this->actingAs($this->owner)->get(route('owner.inventory.index'));
        $inventoryPage->assertOk();
        $inventoryPage->assertSee('id="inventorySkeletonWrapper"', false);
        $inventoryPage->assertSee('inventory-skeleton-placeholder', false);
        $inventoryPage->assertSee('skeleton-shimmer', false);
    }

    public function test_other_pages_render_their_custom_skeletons(): void
    {
        // Transactions
        $tx = $this->actingAs($this->owner)->get(route('owner.transactions.index'));
        $tx->assertOk();
        $tx->assertSee('id="transactionsSkeletonWrapper"', false);

        // Suppliers
        $sup = $this->actingAs($this->owner)->get(route('owner.suppliers.index'));
        $sup->assertOk();
        $sup->assertSee('id="suppliersSkeletonWrapper"', false);

        // Purchases
        $pur = $this->actingAs($this->owner)->get(route('owner.purchases.index'));
        $pur->assertOk();
        $pur->assertSee('id="purchasesSkeletonWrapper"', false);

        // Reports
        $rep = $this->actingAs($this->owner)->get(route('owner.reports.index'));
        $rep->assertOk();
        $rep->assertSee('id="reportsSkeletonWrapper"', false);
        $rep->assertSee('data-counter="', false);

        // Staff Management
        $staffMgmt = $this->actingAs($this->owner)->get(route('owner.staff.index'));
        $staffMgmt->assertOk();
        $staffMgmt->assertSee('id="staffSkeletonWrapper"', false);

        // Audit Logs
        $audit = $this->actingAs($this->owner)->get(route('owner.audit.index'));
        $audit->assertOk();
        $audit->assertSee('id="auditSkeletonWrapper"', false);
    }
}
