<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Tests\TestCase;

class ReportsAndTransactionsTest extends TestCase
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
            'username' => 'owner-reports',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'username' => 'staff-reports',
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
    }

    public function test_staff_stock_transactions_redirect_to_staff_transactions_index(): void
    {
        $response = $this->actingAs($this->staff)->post(route('staff.transactions.stockInStore'), [
            'inventory_item_id' => $this->item->id,
            'quantity' => 5,
            'reason' => 'Staff replenishment test',
        ]);

        $response->assertRedirect(route('staff.transactions.index'));
        $this->item->refresh();
        $this->assertEquals(25, $this->item->current_quantity);

        $responseOut = $this->actingAs($this->staff)->post(route('staff.transactions.stockOutStore'), [
            'inventory_item_id' => $this->item->id,
            'quantity' => 3,
            'reason' => 'Staff broth cooking',
        ]);

        $responseOut->assertRedirect(route('staff.transactions.index'));
        $this->item->refresh();
        $this->assertEquals(22, $this->item->current_quantity);
    }

    public function test_owner_can_view_reports_and_export_csv(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.reports.index'));
        $response->assertOk();
        $response->assertSee('Operational Reports');
        $response->assertSee('Est. Inventory Value');
        $response->assertSee('Meats');

        $csvResponse = $this->actingAs($this->owner)->get(route('owner.reports.export'));
        $csvResponse->assertOk();
        $this->assertTrue(str_contains($csvResponse->headers->get('content-type'), 'text/csv'));
        $this->assertStringContainsString('Pork Belly', $csvResponse->streamedContent());
    }

    public function test_owner_can_view_backup_and_download_sql(): void
    {
        $response = $this->actingAs($this->owner)->get(route('owner.backup.index'));
        $response->assertOk();
        $response->assertSee('System Diagnostics');
        $response->assertSee('Database Tables Inventory');

        $this->actingAs($this->owner)
            ->post(route('owner.backup.download'), ['current_password' => 'WrongPassword1!'])
            ->assertSessionHasErrors('current_password');

        $sqlResponse = $this->actingAs($this->owner)->post(route('owner.backup.download'), [
            'current_password' => 'Password123!',
        ]);
        $sqlResponse->assertOk();
        $this->assertTrue(str_contains($sqlResponse->headers->get('content-type'), 'application/sql'));
        $sqlResponse->streamedContent();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'database_backup_downloaded',
        ]);
        $this->assertDatabaseMissing('technical_audit_logs', [
            'user_id' => $this->owner->id,
            'action' => 'database_backup_downloaded',
        ]);
        $this->get(route('owner.backup.index'))
            ->assertOk()
            ->assertSee('Recent backup exports')
            ->assertSee('Export generated');
    }

    public function test_sqlite_backup_restores_into_a_separate_temporary_database(): void
    {
        $this->item->update(['description' => "Owner's sample \\\\ inventory"]);

        $response = $this->actingAs($this->owner)->post(route('owner.backup.download'), [
            'current_password' => 'Password123!',
        ]);
        $backupSql = $response->streamedContent();
        $restorePath = tempnam(sys_get_temp_dir(), 'mybatchoy-restore-');
        $this->assertNotFalse($restorePath);

        try {
            $restoreDb = new PDO('sqlite:' . $restorePath);
            $restoreDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $restoreDb->exec($backupSql);

            $restoredDescription = $restoreDb->query(
                "SELECT description FROM inventory_items WHERE id = " . (int) $this->item->id
            )->fetchColumn();

            $this->assertSame("Owner's sample \\\\ inventory", $restoredDescription);
            $this->assertSame(
                'Pork Belly',
                $restoreDb->query("SELECT name FROM inventory_items WHERE id = " . (int) $this->item->id)->fetchColumn()
            );
            $restoreDb = null;
        } finally {
            if (is_file($restorePath)) {
                unlink($restorePath);
            }
        }
    }

    public function test_public_menu_and_marketing_routes_redirect_to_internal_system(): void
    {
        $response = $this->get(route('menu'));
        $response->assertRedirect('/login');

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertRedirect(route('login'));
    }
}
