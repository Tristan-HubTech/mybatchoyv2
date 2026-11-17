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

    public function test_inventory_item_edit_includes_its_inactive_category_and_unit(): void
    {
        $owner = User::where('username', 'owneradmin')->firstOrFail();
        $item = InventoryItem::where('sku', 'BEEF-001')->firstOrFail();

        $item->category->update(['is_active' => false]);
        $item->unit->update(['is_active' => false]);

        $response = $this->actingAs($owner)->get(route('owner.inventory.edit', $item));

        $response->assertOk()
            ->assertSee('value="' . $item->category_id . '" selected', false)
            ->assertSee('value="' . $item->unit_id . '" selected', false);
    }

    public function test_inventory_edit_never_overwrites_stock_and_validates_sku_and_active_references(): void
    {
        $owner = User::where('username', 'owneradmin')->firstOrFail();
        $item = InventoryItem::where('sku', 'BEEF-001')->firstOrFail();
        $originalQty = (float) $item->current_quantity;

        // Seed transaction history to verify preservation across metadata edits.
        \App\Models\InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'user_id' => $owner->id,
            'transaction_type' => 'stock_in',
            'quantity' => 5,
            'previous_quantity' => 5,
            'updated_quantity' => $originalQty,
            'reason' => 'Existing delivery log',
            'transaction_date' => now(),
        ]);

        // 1. Web edit form does not submit or alter current stock; direct spoof in POST payload is ignored.
        $this->actingAs($owner)->put(route('owner.inventory.update', $item), [
            'name' => 'Beef Bone Marrow Updated',
            'sku' => '   ', // blank SKU should preserve existing BEEF-001
            'category_id' => $item->category_id,
            'unit_id' => $item->unit_id,
            'current_quantity' => 999,
            'minimum_stock_level' => 6,
            'reorder_level' => 12,
            'description' => 'Updated storage notes',
        ])->assertRedirect(route('owner.inventory.index'));

        $item->refresh();
        $this->assertSame('Beef Bone Marrow Updated', $item->name);
        $this->assertSame('BEEF-001', $item->sku);
        $this->assertEquals($originalQty, (float) $item->current_quantity);
        $this->assertSame(1, \App\Models\InventoryTransaction::where('inventory_item_id', $item->id)->count());

        // 2. API edit also preserves current_quantity and validates references.
        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $this->putJson("/api/v1/inventory/{$item->id}", [
            'name' => 'Beef Marrow API',
            'sku' => '',
            'category_id' => $item->category_id,
            'unit_id' => $item->unit_id,
            'current_quantity' => 0,
            'minimum_stock_level' => 4,
            'reorder_level' => 8,
        ])->assertOk();

        $this->assertEquals($originalQty, (float) $item->fresh()->current_quantity);
        $this->assertSame('BEEF-001', $item->fresh()->sku);

        // 3. Blank SKU on create generates unique non-empty SKU; duplicate SKU rejected.
        app('auth')->forgetGuards();
        $this->actingAs($owner, 'web')->post(route('owner.inventory.store'), [
            'name' => 'Crispy Pork Skin',
            'sku' => '   ',
            'category_id' => $item->category_id,
            'unit_id' => $item->unit_id,
            'current_quantity' => 12,
            'minimum_stock_level' => 3,
            'reorder_level' => 6,
        ])->assertRedirect(route('owner.inventory.index'));

        $created = InventoryItem::where('name', 'Crispy Pork Skin')->firstOrFail();
        $this->assertNotEmpty($created->sku);

        $this->actingAs($owner, 'web')->post(route('owner.inventory.store'), [
            'name' => 'Duplicate Code Item',
            'sku' => 'BEEF-001',
            'category_id' => $item->category_id,
            'unit_id' => $item->unit_id,
            'current_quantity' => 5,
            'minimum_stock_level' => 1,
        ])->assertSessionHasErrors('sku');

        // 4. Rejects inactive category/unit on create and rejects switching to another inactive category/unit on edit.
        $inactiveCategory = InventoryCategory::create(['name' => 'Archived Category', 'is_active' => false]);
        $inactiveUnit = Unit::create(['name' => 'Archived Pack', 'short_name' => 'apk', 'is_active' => false]);

        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $this->postJson('/api/v1/inventory', [
            'name' => 'Invalid Category Item',
            'category_id' => $inactiveCategory->id,
            'unit_id' => $item->unit_id,
            'current_quantity' => 1,
            'minimum_stock_level' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('category_id');

        $this->putJson("/api/v1/inventory/{$item->id}", [
            'name' => 'Beef Marrow API',
            'category_id' => $inactiveCategory->id,
            'unit_id' => $inactiveUnit->id,
            'minimum_stock_level' => 4,
        ])->assertStatus(422)->assertJsonValidationErrors(['category_id', 'unit_id']);

        // Keeping own deactivated category/unit still succeeds cleanly.
        $item->category->update(['is_active' => false]);
        $item->unit->update(['is_active' => false]);

        app('auth')->forgetGuards();
        $this->actingAs($owner, 'web')->put(route('owner.inventory.update', $item), [
            'name' => 'Beef Keeps Own Inactive Category',
            'sku' => 'BEEF-001',
            'category_id' => $item->category_id,
            'unit_id' => $item->unit_id,
            'minimum_stock_level' => 4,
        ])->assertRedirect(route('owner.inventory.index'));
    }
}
