<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $category = InventoryCategory::firstOrCreate(['name' => 'Produce']);
        $unit = Unit::firstOrCreate(['short_name' => 'kg'], ['name' => 'Kilograms', 'short_name' => 'kg']);

        User::factory()->create([
            'name' => 'Owner Admin',
            'username' => 'owneradmin',
            'email' => 'owner@example.com',
            'role_id' => $ownerRole->id,
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        InventoryItem::create([
            'name' => 'Beef',
            'sku' => 'BEEF-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 15,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        InventoryItem::create([
            'name' => 'Chicken',
            'sku' => 'CHICK-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 4,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);
    }

    public function test_inventory_page_renders_responsive_table_and_sidebar_tooltips(): void
    {
        $owner = User::where('username', 'owneradmin')->first();

        $response = $this->actingAs($owner)->get(route('owner.inventory.index'));

        $response->assertOk();
        $response->assertSee('inventory-table', false);
        $response->assertSee('title="Inventory"', false);
        $response->assertSee('title="Dashboard"', false);
    }

    public function test_inventory_records_are_not_duplicated(): void
    {
        $owner = User::where('username', 'owneradmin')->first();

        $response = $this->actingAs($owner)->get(route('owner.inventory.index'));

        $response->assertOk();
        $this->assertSame(2, InventoryItem::count());
        $this->assertSame(2, InventoryItem::query()->select('sku')->distinct()->count());
    }
}
