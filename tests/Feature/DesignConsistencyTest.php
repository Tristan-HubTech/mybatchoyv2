<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;
    protected User $otherStaff;
    protected InventoryCategory $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff Member']);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'name' => 'Tristan Jasper',
            'username' => 'owner_design',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Maria Kitchen',
            'username' => 'staff_design',
            'is_active' => true,
        ]);

        $this->otherStaff = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Pedro Backroom',
            'username' => 'staff_other',
            'is_active' => true,
        ]);

        $this->category = InventoryCategory::create(['name' => 'Meats & Broth']);
        $this->unit = Unit::create(['name' => 'Kilograms', 'short_name' => 'kg']);
    }

    public function test_all_owner_pages_share_unified_design_system_without_offbrand_colors(): void
    {
        $item = InventoryItem::create([
            'name' => 'Pork Belly',
            'sku' => 'PORK-001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 12,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'La Paz Meat Shop',
            'contact_person' => 'Mang Jose',
            'phone' => '09171234567',
        ]);

        $ownerUrls = [
            route('owner.dashboard'),
            route('owner.inventory.index'),
            route('owner.inventory.create'),
            route('owner.inventory.edit', $item),
            route('owner.transactions.index'),
            route('owner.transactions.stockIn'),
            route('owner.transactions.stockOut'),
            route('owner.transactions.adjustment'),
            route('owner.transactions.spoilage'),
            route('owner.suppliers.index'),
            route('owner.suppliers.create'),
            route('owner.suppliers.edit', $supplier),
            route('owner.purchases.index'),
            route('owner.purchases.create'),
            route('owner.reports.index'),
            route('owner.staff.index'),
            route('owner.staff.create'),
            route('owner.audit.index'),
            route('owner.settings.index'),
            route('owner.backup.index'),
            route('profile.show'),
        ];

        foreach ($ownerUrls as $url) {
            $response = $this->actingAs($this->owner)->get($url);
            $response->assertOk();
            $response->assertSee('--bg: #111111;', false);
            $response->assertSee('--input-bg: #202020;', false);
            $response->assertSee('--primary: #E53935;', false);
            $response->assertSee('--secondary: #FFC107;', false);
            $response->assertDontSee('#6A1B9A', false);
            $response->assertDontSee('#1F6AE1', false);
            $response->assertDontSee('#c9ddff', false);

            $hasUnifiedHeader = str_contains($response->getContent(), 'eb-page-header')
                || str_contains($response->getContent(), 'eb-hero-banner');
            $this->assertTrue($hasUnifiedHeader, "Page {$url} is missing unified header shell.");
        }
    }

    public function test_staff_pages_share_unified_design_system_and_hide_owner_modules(): void
    {
        InventoryItem::create([
            'name' => 'Fresh Miki Noodles',
            'sku' => 'MIKI-001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 3,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        $staffUrls = [
            route('staff.dashboard'),
            route('staff.inventory.index'),
            route('staff.transactions.index'),
            route('staff.transactions.stockIn'),
            route('staff.transactions.stockOut'),
            route('staff.transactions.adjustment'),
            route('staff.transactions.spoilage'),
            route('profile.show'),
        ];

        foreach ($staffUrls as $url) {
            $response = $this->actingAs($this->staff)->get($url);
            $response->assertOk();
            $response->assertDontSee('PURCHASING');
            $response->assertDontSee('ANALYTICS');
            $response->assertDontSee('ADMINISTRATION');
            $response->assertDontSee('Staff Management');
            $response->assertDontSee('Audit Logs');
            $response->assertDontSee('Settings');
            $response->assertDontSee('#6A1B9A', false);
        }
    }

    public function test_guest_auth_views_use_unified_eustace_batchoy_brand_and_no_blue(): void
    {
        $guestUrls = [
            route('login'),
            route('password.request'),
            route('password.reset', ['email' => 'owner@mybatchoy.com']),
        ];

        foreach ($guestUrls as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertSee('eustace-mascot.jpg', false);
            $response->assertSee('MyBatchoy v1.0');
            $response->assertDontSee('#1F6AE1', false);
            $response->assertDontSee('#c9ddff', false);
        }
    }

    public function test_inventory_status_filter_details_modal_and_bootstrap_pagination(): void
    {
        InventoryItem::create([
            'name' => 'Beef Marrow Healthy',
            'sku' => 'BEEF-OK',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 25,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        InventoryItem::create([
            'name' => 'Pork Liver Low',
            'sku' => 'LIVER-LOW',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 4,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        InventoryItem::create([
            'name' => 'Chicharon Depleted',
            'sku' => 'CHICHA-OUT',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 0,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 14; $i++) {
            InventoryItem::create([
                'name' => "Extra Supply {$i}",
                'sku' => "SUP-{$i}",
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'current_quantity' => 15,
                'minimum_stock_level' => 5,
                'reorder_level' => 10,
                'is_active' => true,
            ]);
        }

        $lowResponse = $this->actingAs($this->owner)->get(route('owner.inventory.index', ['status' => 'low_stock']));
        $lowResponse->assertOk()
            ->assertSee('Pork Liver Low')
            ->assertDontSee('Beef Marrow Healthy')
            ->assertDontSee('Chicharon Depleted')
            ->assertSee('itemDetailModal');

        $outResponse = $this->actingAs($this->owner)->get(route('owner.inventory.index', ['status' => 'out_of_stock']));
        $outResponse->assertOk()
            ->assertSee('Chicharon Depleted')
            ->assertDontSee('Pork Liver Low');

        $paginatedResponse = $this->actingAs($this->owner)->get(route('owner.inventory.index'));
        $paginatedResponse->assertOk()
            ->assertSee('pagination', false)
            ->assertDontSee('aria-label="Pagination Navigation"');
    }

    public function test_transactions_filters_and_strict_staff_isolation(): void
    {
        $item = InventoryItem::create([
            'name' => 'Garlic Confit',
            'sku' => 'GAR-001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 20,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'user_id' => $this->staff->id,
            'transaction_type' => 'stock_in',
            'quantity' => 10,
            'previous_quantity' => 10,
            'updated_quantity' => 20,
            'reason' => 'Morning market dropoff',
            'transaction_date' => now(),
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $item->id,
            'user_id' => $this->otherStaff->id,
            'transaction_type' => 'stock_out',
            'quantity' => 3,
            'previous_quantity' => 20,
            'updated_quantity' => 17,
            'reason' => 'Other staff secret dispatch',
            'transaction_date' => now(),
        ]);

        $staffFiltered = $this->actingAs($this->staff)->get(route('staff.transactions.index', [
            'type' => 'stock_in',
            'search' => 'Morning',
        ]));
        $staffFiltered->assertOk()
            ->assertSee('Morning market dropoff')
            ->assertDontSee('Other staff secret dispatch');

        $ownerFiltered = $this->actingAs($this->owner)->get(route('owner.transactions.index', [
            'type' => 'stock_out',
        ]));
        $ownerFiltered->assertOk()
            ->assertSee('Other staff secret dispatch')
            ->assertDontSee('Morning market dropoff');
    }

    public function test_suppliers_and_purchases_show_real_aggregates_and_details_modals(): void
    {
        $item = InventoryItem::create([
            'name' => 'Batchoy Broth Bones',
            'sku' => 'BONE-001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 30,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Iloilo Butcher Direct',
            'contact_person' => 'Tatay Berto',
            'phone' => '09185554444',
        ]);

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'created_by' => $this->owner->id,
            'purchase_number' => 'PO-CONSISTENCY-001',
            'purchase_date' => now()->format('Y-m-d'),
            'total_amount' => 2450.50,
            'notes' => 'Delivered fresh chilled cuts',
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'inventory_item_id' => $item->id,
            'quantity' => 10,
            'unit_cost' => 245.05,
            'subtotal' => 2450.50,
        ]);

        $supIndex = $this->actingAs($this->owner)->get(route('owner.suppliers.index'));
        $supIndex->assertOk()
            ->assertSee('Iloilo Butcher Direct')
            ->assertSee('2,450.50')
            ->assertSee('1 order')
            ->assertSee('supplierDetailModal');

        $supEdit = $this->actingAs($this->owner)->get(route('owner.suppliers.edit', $supplier));
        $supEdit->assertOk()
            ->assertSee('Recent Purchase History')
            ->assertSee('PO-CONSISTENCY-001');

        $poIndex = $this->actingAs($this->owner)->get(route('owner.purchases.index', [
            'supplier' => $supplier->id,
            'search' => 'PO-CONSISTENCY-001',
        ]));
        $poIndex->assertOk()
            ->assertSee('PO-CONSISTENCY-001')
            ->assertSee('2,450.50')
            ->assertSee('purchaseDetailModal');
    }

    public function test_profile_and_authenticated_pages_render_distinct_titles_and_non_overflowing_panel_cards(): void
    {
        $profileResponse = $this->actingAs($this->owner)->get(route('profile.show'));
        $profileResponse->assertOk()
            ->assertSee('<title>MyBatchoy - My Profile</title>', false)
            ->assertSee('Personal Information')
            ->assertSee('Change Password')
            ->assertSee('overflow-x: clip', false);

        $profileHtml = (string) $profileResponse->getContent();
        $this->assertMatchesRegularExpression(
            '/<div[^>]*topbar-title-text[^>]*>\s*My Profile\s*<\/div>/i',
            $profileHtml
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.eb-panel-card\s*\{[^}]*height\s*:\s*100%/is',
            $profileHtml
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.page-fade-in\s*\{[^}]*forwards/is',
            $profileHtml
        );
        $this->assertSame(
            1,
            preg_match('/@keyframes\s+pageFadeIn\s*\{(?:\s*(?:from|to)\s*\{[^}]*\})*\s*\}/is', $profileHtml, $kfMatches)
        );
        $this->assertStringNotContainsString('transform', $kfMatches[0]);
        $this->assertStringContainsString('portalShellModals', $profileHtml);
        $this->assertStringContainsString('--sidebar-expanded-width: 260px;', $profileHtml);
        $this->assertStringContainsString('height: 100dvh;', $profileHtml);
        $this->assertStringContainsString('min-height: clamp(30px, 5.1vh, 46px);', $profileHtml);
        $this->assertStringContainsString('font-size: clamp(0.88rem, 1.75vh, 1.02rem);', $profileHtml);
        $this->assertStringNotContainsString('.nav-group:last-child', $profileHtml);

        $expectedTitles = [
            'owner.settings.index' => 'Settings',
            'owner.inventory.index' => 'Inventory',
            'owner.staff.index' => 'Staff Management',
            'owner.reports.index' => 'Reports',
        ];

        foreach ($expectedTitles as $routeName => $title) {
            $pageResponse = $this->actingAs($this->owner)->get(route($routeName));
            $pageResponse->assertOk()
                ->assertSee("<title>MyBatchoy - {$title}</title>", false);
            $this->assertMatchesRegularExpression(
                '/<div[^>]*topbar-title-text[^>]*>\s*' . preg_quote($title, '/') . '\s*<\/div>/i',
                (string) $pageResponse->getContent()
            );
        }
    }
}

