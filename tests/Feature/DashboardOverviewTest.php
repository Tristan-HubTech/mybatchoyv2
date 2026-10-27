<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $staff;
    private InventoryItem $inStockItem;
    private InventoryItem $lowStockItem;
    private InventoryItem $outOfStockItem;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'owner-dash',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'username' => 'staff-dash',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create(['name' => 'Broth & Meat']);
        $unit = Unit::create(['name' => 'Kilograms', 'short_name' => 'kg']);
        $supplier = Supplier::create([
            'name' => 'Iloilo Farm Fresh',
            'contact_person' => 'Juan Dela Cruz',
            'phone' => '09171234567',
        ]);

        // Healthy Item (current > minimum)
        $this->inStockItem = InventoryItem::create([
            'name' => 'Beef Bone Marrow',
            'sku' => 'BEEF-BONE',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 25.0,
            'minimum_stock_level' => 5.0,
            'reorder_level' => 10.0,
            'is_active' => true,
        ]);

        // Low Stock Item (0 < current <= minimum)
        $this->lowStockItem = InventoryItem::create([
            'name' => 'Egg Noodles (Miki)',
            'sku' => 'MIKI-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 3.0,
            'minimum_stock_level' => 8.0,
            'reorder_level' => 12.0,
            'is_active' => true,
        ]);

        // Out of Stock Item (current <= 0)
        $this->outOfStockItem = InventoryItem::create([
            'name' => 'Pork Cracklings (Chicharon)',
            'sku' => 'CHICHA-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 0.0,
            'minimum_stock_level' => 6.0,
            'reorder_level' => 10.0,
            'is_active' => true,
        ]);

        // Record a sample Purchase
        Purchase::create([
            'supplier_id' => $supplier->id,
            'created_by' => $this->owner->id,
            'purchase_number' => 'PO-TEST-001',
            'purchase_date' => now()->toDateString(),
            'total_amount' => 4500.00,
            'notes' => 'Weekly replenishment',
        ]);

        // Record sample stock transactions
        InventoryTransaction::create([
            'inventory_item_id' => $this->inStockItem->id,
            'user_id' => $this->owner->id,
            'transaction_type' => 'stock_in',
            'quantity' => 10,
            'previous_quantity' => 15,
            'updated_quantity' => 25,
            'reason' => 'Supplier delivery',
            'transaction_date' => now(),
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $this->lowStockItem->id,
            'user_id' => $this->staff->id,
            'transaction_type' => 'stock_out',
            'quantity' => 5,
            'previous_quantity' => 8,
            'updated_quantity' => 3,
            'reason' => 'Lunch service cooking',
            'transaction_date' => now(),
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_dashboard_renders_metrics_date_filters_and_charts(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', ['range' => '7days']));

        $response->assertOk();
        $response->assertSee('Dashboard Overview');
        $response->assertSee('Owner / Admin');
        $response->assertSee('Total Items');
        $response->assertSee('Items In Stock');
        $response->assertSee('Low Stock');
        $response->assertSee('Out of Stock');
        $response->assertSee('Purchases');
        $response->assertSee('4,500.00');

        // Charts present
        $response->assertSee('id="stockMovementChart"', false);
        $response->assertSee('id="inventoryStatusChart"', false);
        $response->assertSee('id="purchaseExpenseChart"', false);

        // Urgent alerts (Chicharon out of stock must appear before Miki noodles)
        $content = $response->getContent();
        $chicharonPos = strpos($content, 'Pork Cracklings (Chicharon)');
        $mikiPos = strpos($content, 'Egg Noodles (Miki)');
        $this->assertNotFalse($chicharonPos);
        $this->assertNotFalse($mikiPos);
        $this->assertTrue($chicharonPos < $mikiPos, 'Out of stock item must appear before low stock item in urgent alerts');

        // Quick Actions
        $response->assertSee(route('owner.inventory.index'));
        $response->assertSee(route('owner.transactions.stockIn'));
        $response->assertSee(route('owner.transactions.stockOut'));
        $response->assertSee(route('owner.purchases.index'));
        $response->assertSee(route('owner.reports.index'));
    }

    public function test_owner_dashboard_supports_custom_date_filtering(): void
    {
        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', [
            'range' => 'custom',
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertSee('Dashboard Overview');
        $response->assertSee('4,500.00');
    }

    public function test_staff_dashboard_renders_operational_metrics_and_hides_purchasing_finances(): void
    {
        $response = $this->actingAs($this->staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Overview');
        $response->assertSee('Staff Member');
        $response->assertSee('Total Items');
        $response->assertSee('Items In Stock');
        $response->assertSee('Low Stock');
        $response->assertSee('Out of Stock');

        // Staff sees operational stock movements chart and status chart
        $response->assertSee('id="stockMovementChart"', false);
        $response->assertSee('id="inventoryStatusChart"', false);

        // Staff CANNOT see purchasing expense chart or purchases total
        $response->assertDontSee('id="purchaseExpenseChart"', false);
        $response->assertDontSee('Purchasing Expense Timeline');
        $response->assertDontSee('4,500.00');

        // Staff Quick Actions
        $response->assertSee(route('staff.inventory.index'));
        $response->assertSee(route('staff.transactions.stockIn'));
        $response->assertSee(route('staff.transactions.stockOut'));
        $response->assertSee(route('staff.transactions.adjustment'));
        $response->assertSee(route('staff.transactions.spoilage'));

        // Staff cannot access owner-only routes
        $response->assertDontSee(route('owner.purchases.index'));
        $response->assertDontSee(route('owner.reports.index'));
    }

    public function test_dashboard_handles_empty_states_gracefully(): void
    {
        // Query a future date range where no transactions or purchases exist
        $futureFrom = now()->addMonths(2)->toDateString();
        $futureTo = now()->addMonths(3)->toDateString();

        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', [
            'range' => 'custom',
            'from' => $futureFrom,
            'to' => $futureTo,
        ]));

        $response->assertOk();
        $response->assertSee('No Stock Movements in');
        $response->assertSee('No Purchase Orders in');
        $response->assertSee('0.00');
    }

    public function test_dashboard_handles_inverted_custom_dates_safely(): void
    {
        // Pass inverted dates (from > to)
        $response = $this->actingAs($this->owner)->get(route('owner.dashboard', [
            'range' => 'custom',
            'from' => now()->toDateString(),
            'to' => now()->subDays(5)->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Dashboard Overview');
    }
}
