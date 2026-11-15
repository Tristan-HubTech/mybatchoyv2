<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\TestCase;

class UsabilityImprovementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $staff;
    private InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'name' => 'Owner Admin',
            'username' => 'owner-usability',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Kitchen Staff',
            'username' => 'staff-usability',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create(['name' => 'Meats']);
        $unit = Unit::create(['name' => 'Kilograms', 'short_name' => 'kg']);

        $this->item = InventoryItem::create([
            'name' => 'Pork Belly',
            'sku' => 'PORK-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 20,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        Supplier::create([
            'name' => 'Iloilo Fresh Meat Supply',
            'contact_person' => 'Mang Pedro',
            'phone' => '09171234567',
        ]);
    }

    public function test_staff_ui_uses_plain_language_and_hides_owner_only_inventory_and_financial_controls(): void
    {
        $inventoryResponse = $this->actingAs($this->staff)->get(route('staff.inventory.index'));
        $inventoryResponse->assertOk();
        $inventoryResponse->assertSee('Manage Stock');
        $inventoryResponse->assertSee('Pork Belly');
        $inventoryResponse->assertDontSee('Add New Item');
        $inventoryResponse->assertDontSee(route('owner.inventory.create'), false);

        $dashboardResponse = $this->actingAs($this->staff)->get(route('staff.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('What Needs Attention Today?');
        $dashboardResponse->assertSee('Add Stock');
        $dashboardResponse->assertSee('Remove Stock');
        $dashboardResponse->assertSee('Correct Stock');
        $dashboardResponse->assertSee('Record Spoilage');
        $dashboardResponse->assertSee('firstTimeGuideBanner', false);
        $dashboardResponse->assertSee('Quick Help');
        $dashboardResponse->assertDontSee('Purchasing Expense Timeline');
        $dashboardResponse->assertDontSee('Sales Report');
        $dashboardResponse->assertDontSee('Total Sales');
    }

    public function test_stock_transactions_report_actual_saved_quantity_and_friendly_overstock_errors(): void
    {
        $stockInResponse = $this->actingAs($this->staff)->post(route('staff.transactions.stockInStore'), [
            'inventory_item_id' => $this->item->id,
            'quantity' => 5,
            'reason' => 'Morning market delivery',
        ]);
        $stockInResponse->assertRedirect(route('staff.transactions.index'));
        $stockInResponse->assertSessionHas(
            'success',
            'Stock successfully added for Pork Belly. The new quantity is now 25 kg.'
        );

        $stockOutResponse = $this->actingAs($this->staff)->post(route('staff.transactions.stockOutStore'), [
            'inventory_item_id' => $this->item->id,
            'quantity' => 3,
            'reason' => 'Kitchen cooking prep',
        ]);
        $stockOutResponse->assertRedirect(route('staff.transactions.index'));
        $stockOutResponse->assertSessionHas(
            'success',
            'Stock successfully removed for Pork Belly. The remaining quantity is now 22 kg.'
        );

        $overStockResponse = $this->actingAs($this->staff)
            ->from(route('staff.transactions.stockOut'))
            ->post(route('staff.transactions.stockOutStore'), [
                'inventory_item_id' => $this->item->id,
                'quantity' => 99,
                'reason' => 'Kitchen cooking prep',
            ]);
        $overStockResponse->assertRedirect(route('staff.transactions.stockOut'));
        $overStockResponse->assertSessionHasErrors([
            'quantity' => 'Cannot remove 99 kg. Only 22 kg of Pork Belly is currently in stock.',
        ]);

        $adjustResponse = $this->actingAs($this->owner)->post(route('owner.transactions.adjustmentStore'), [
            'inventory_item_id' => $this->item->id,
            'current_quantity' => 18,
            'reason' => 'Afternoon physical recount',
        ]);
        $adjustResponse->assertRedirect(route('owner.transactions.index'));
        $adjustResponse->assertSessionHas(
            'success',
            'Stock quantity corrected for Pork Belly. The new quantity is now 18 kg.'
        );

        $spoilageResponse = $this->actingAs($this->owner)->post(route('owner.transactions.spoilageStore'), [
            'inventory_item_id' => $this->item->id,
            'quantity' => 2,
            'reason' => 'Expired batch',
        ]);
        $spoilageResponse->assertRedirect(route('owner.transactions.index'));
        $spoilageResponse->assertSessionHas(
            'success',
            'Spoilage recorded for Pork Belly. The remaining quantity is now 16 kg.'
        );
    }

    public function test_reports_page_has_plain_explanations_date_filters_and_never_displays_sales(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.reports.index', [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Operational Reports');
        $response->assertSee('Est. Inventory Value');
        $response->assertDontSee('Daily Sales');
        $response->assertDontSee('Total Sales');
        $response->assertDontSee('Sales Report');
    }

    public function test_database_exceptions_are_logged_privately_and_never_leak_sql_details_to_users(): void
    {
        Log::spy();

        Route::get('/_test/db-fail', function () {
            throw new QueryException(
                'sqlite',
                'select * from broken_secret_table',
                [],
                new PDOException('SQLSTATE[HY000]: General error: 1 no such table: broken_secret_table')
            );
        });

        Route::post('/_test/db-fail-submit', function () {
            throw new PDOException('SQLSTATE[23000]: Integrity constraint violation on secret_column');
        });

        $getResponse = $this->actingAs($this->owner)->get('/_test/db-fail');
        $getResponse->assertStatus(500);
        $getResponse->assertSee("couldn&#039;t load this page right now", false);
        $getResponse->assertDontSee('SQLSTATE');
        $getResponse->assertDontSee('broken_secret_table');

        $postResponse = $this->actingAs($this->owner)
            ->from('/owner/dashboard')
            ->post('/_test/db-fail-submit');
        $postResponse->assertRedirect('/owner/dashboard');
        $postResponse->assertSessionHas(
            'error',
            "We couldn't save your changes. Please try again or contact the system administrator."
        );

        $jsonResponse = $this->actingAs($this->owner)->getJson('/_test/db-fail');
        $jsonResponse->assertStatus(500);
        $jsonResponse->assertJson([
            'message' => "We couldn't save your changes. Please try again or contact the system administrator.",
        ]);
        $jsonResponse->assertDontSee('SQLSTATE');

        Log::shouldHaveReceived('error')
            ->with('Database error handled safely', \Mockery::type('array'))
            ->times(3);
    }
}
