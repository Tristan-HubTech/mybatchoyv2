<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessAccessRequest;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\OwnerMfaChallenge;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\TechnicalAuditLog;
use App\Models\TechnicalSetting;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserActiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ProductionLogicAndTechnicalWiringTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $sysAdmin;
    private Supplier $supplier;
    private InventoryCategory $category;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $sysRole = Role::firstOrCreate(['name' => 'system_admin'], ['label' => 'System Admin']);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'username' => 'p7-owner',
            'email' => 'p7-owner@example.test',
            'password' => Hash::make('OwnerPass2026!'),
            'is_active' => true,
        ]);

        $this->sysAdmin = User::factory()->create([
            'role_id' => $sysRole->id,
            'username' => 'p7-sysadmin',
            'email' => 'p7-sysadmin@example.test',
            'password' => Hash::make('AdminPass2026!'),
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create(['name' => 'Verified Poultry Supply', 'is_active' => true]);
        $this->category = InventoryCategory::create(['name' => 'Proteins', 'is_active' => true]);
        $this->unit = Unit::create(['name' => 'Kilogram', 'short_name' => 'kg', 'is_active' => true]);
    }

    public function test_purchase_index_renders_real_line_total_cost_and_excludes_unrealized_pos_from_combined_spend(): void
    {
        $item = InventoryItem::create([
            'name' => 'Chicken Neck',
            'sku' => 'CHK-001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 10,
            'minimum_stock_level' => 2,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        // Realized purchase (420.00)
        $realized = Purchase::create([
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->owner->id,
            'purchase_number' => 'PO-REAL-001',
            'purchase_date' => now()->toDateString(),
            'total_amount' => 420.00,
            'supplier_status' => 'confirmed',
            'delivery_status' => 'delivered',
            'received_in_inventory' => true,
        ]);
        PurchaseItem::create([
            'purchase_id' => $realized->id,
            'inventory_item_id' => $item->id,
            'quantity' => 3.5,
            'unit_cost' => 120.00,
            'total_cost' => 420.00,
        ]);

        // Pending PO (must NOT inflate Combined Spend)
        Purchase::create([
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->owner->id,
            'purchase_number' => 'PO-PEND-002',
            'purchase_date' => now()->toDateString(),
            'total_amount' => 999.00,
            'supplier_status' => 'pending',
            'delivery_status' => 'pending',
            'received_in_inventory' => false,
        ]);

        // Rejected PO (must NOT inflate Combined Spend)
        Purchase::create([
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->owner->id,
            'purchase_number' => 'PO-REJ-003',
            'purchase_date' => now()->toDateString(),
            'total_amount' => 555.00,
            'supplier_status' => 'rejected',
            'delivery_status' => 'cancelled',
            'received_in_inventory' => false,
        ]);

        $response = $this->actingAs($this->owner)->get(route('owner.purchases.index'));
        $response->assertOk()
            ->assertViewHas('combinedTotal', fn (float $total): bool => abs($total - 420.00) < 0.01)
            ->assertSee('"subtotal":"420.00"', false);
    }

    public function test_web_and_api_csv_exports_sanitize_formulas_and_stream_across_chunks(): void
    {
        InventoryItem::create([
            'name' => '=CMD|calc',
            'sku' => '+FORMULA-1',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_quantity' => 5,
            'minimum_stock_level' => 1,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        // Insert 205 additional items to cross the chunk(200) boundary.
        $now = now();
        $bulkRows = [];
        for ($i = 1; $i <= 205; $i++) {
            $bulkRows[] = [
                'name' => "Bulk Item {$i}",
                'sku' => "BLK-{$i}",
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'current_quantity' => 10,
                'minimum_stock_level' => 2,
                'reorder_level' => 4,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        InventoryItem::insert($bulkRows);

        $webCsv = $this->actingAs($this->owner)->get(route('owner.reports.export'));
        $webCsv->assertOk();
        $webBody = $webCsv->streamedContent();
        $this->assertStringContainsString("'=CMD|calc", $webBody);
        $this->assertStringContainsString("'+FORMULA-1", $webBody);
        $this->assertStringContainsString('Bulk Item 205', $webBody);

        $token = $this->issueApiToken($this->owner->username, 'OwnerPass2026!');

        $apiCsv = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/v1/reports/export');
        $apiCsv->assertOk();
        $apiBody = $apiCsv->streamedContent();
        $this->assertStringContainsString("'=CMD|calc", $apiBody);
        $this->assertStringContainsString('Bulk Item 205', $apiBody);
    }

    public function test_backup_stream_exports_more_than_fifty_rows_cleanly_without_chunk_offset_errors(): void
    {
        $rows = [];
        $now = now();
        for ($i = 1; $i <= 75; $i++) {
            $rows[] = [
                'user_id' => $this->owner->id,
                'action' => 'bulk_backup_probe',
                'details' => "Backup audit row #{$i}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        AuditLog::insert($rows);

        $response = $this->actingAs($this->owner)->post(route('owner.backup.download'), [
            'current_password' => 'OwnerPass2026!',
        ]);
        $response->assertOk();
        $sql = $response->streamedContent();

        $this->assertStringContainsString('Backup audit row #1', $sql);
        $this->assertStringContainsString('Backup audit row #75', $sql);
        $this->assertStringNotContainsString('INSERT INTO `sessions`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `personal_access_tokens`', $sql);
        $this->assertStringNotContainsString('INSERT INTO `cache`', $sql);
    }

    public function test_technical_maintenance_mode_blocks_operational_users_while_allowing_system_admin(): void
    {
        $ownerToken = $this->issueApiToken($this->owner->username, 'OwnerPass2026!');
        $sysToken = $this->issueApiToken($this->sysAdmin->username, 'AdminPass2026!');

        TechnicalSetting::current()->update([
            'maintenance_mode' => true,
            'maintenance_notice' => 'Scheduled TiDB maintenance window.',
        ]);

        // Guest & Health/Login endpoints stay accessible so System Admin can sign in.
        $this->getJson('/api/v1/health')->assertOk();
        $this->get(route('login'))->assertOk();

        // System Admin bypasses maintenance mode on both web and API
        $this->actingAs($this->sysAdmin)->get(route('sysadmin.dashboard'))->assertOk();
        $this->withHeader('Authorization', 'Bearer ' . $sysToken)
            ->getJson('/api/v1/sysadmin/health')
            ->assertOk();

        // Switch session cleanly to Owner -> receives 503 Maintenance Notice on both web and API
        \Illuminate\Support\Facades\Auth::forgetGuards();
        \Illuminate\Support\Facades\Auth::shouldUse('web');
        $this->flushSession();

        $this->actingAs($this->owner)
            ->get(route('owner.dashboard'))
            ->assertStatus(503)
            ->assertSee('Scheduled TiDB maintenance window.');

        $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->getJson('/api/v1/dashboard')
            ->assertStatus(503)
            ->assertJson([
                'maintenance_mode' => true,
                'message' => 'Scheduled TiDB maintenance window.',
            ]);
    }

    public function test_api_rate_limit_honors_technical_setting_and_retention_pruner_preserves_business_history(): void
    {
        TechnicalSetting::current()->update([
            'api_rate_limit_per_minute' => 20,
            'log_retention_days' => 14,
            'backup_retention_days' => 14,
        ]);
        RateLimiter::clear(md5('api' . $this->owner->id));

        $token = $this->issueApiToken($this->owner->username, 'OwnerPass2026!');

        // Hit an API endpoint cleanly until configurable limit of 20/min is enforced on request #21
        for ($i = 0; $i < 20; $i++) {
            $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/v1/auth/me')
                ->assertOk();
        }

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(429);

        // Seed aged technical logs & expired security records alongside aged business audit log
        $oldDate = now()->subDays(30);
        $oldTechLog = TechnicalAuditLog::create([
            'user_id' => $this->sysAdmin->id,
            'category' => 'security',
            'action' => 'old_event',
            'details' => 'Aged technical log',
        ]);
        TechnicalAuditLog::whereKey($oldTechLog->id)->update(['created_at' => $oldDate, 'updated_at' => $oldDate]);

        $recentTechLog = TechnicalAuditLog::create([
            'user_id' => $this->sysAdmin->id,
            'category' => 'security',
            'action' => 'recent_event',
            'details' => 'Fresh technical log',
        ]);

        $oldBizLog = AuditLog::create([
            'user_id' => $this->owner->id,
            'action' => 'historical_business_event',
            'details' => 'Must never be pruned',
        ]);
        AuditLog::whereKey($oldBizLog->id)->update(['created_at' => $oldDate, 'updated_at' => $oldDate]);

        OwnerMfaChallenge::create([
            'user_id' => $this->owner->id,
            'code_hash' => 'hash',
            'purpose' => 'session_recovery',
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('app:prune-technical-retention')->assertExitCode(0);

        $this->assertDatabaseMissing('technical_audit_logs', ['id' => $oldTechLog->id]);
        $this->assertDatabaseHas('technical_audit_logs', ['id' => $recentTechLog->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $oldBizLog->id]);
        $this->assertSame(0, OwnerMfaChallenge::count());
    }

    public function test_sysadmin_granted_reports_exclude_pending_and_rejected_pos(): void
    {
        Purchase::create([
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->owner->id,
            'purchase_number' => 'PO-SYS-REAL',
            'purchase_date' => now()->toDateString(),
            'total_amount' => 300.00,
            'supplier_status' => 'confirmed',
            'delivery_status' => 'delivered',
            'received_in_inventory' => true,
        ]);
        Purchase::create([
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->owner->id,
            'purchase_number' => 'PO-SYS-PEND',
            'purchase_date' => now()->toDateString(),
            'total_amount' => 800.00,
            'supplier_status' => 'pending',
            'delivery_status' => 'pending',
            'received_in_inventory' => false,
        ]);

        $grant = BusinessAccessRequest::create([
            'system_admin_id' => $this->sysAdmin->id,
            'scope' => 'reports',
            'reason' => 'Verify financial report totals cleanly',
            'status' => 'approved',
            'reviewed_by' => $this->owner->id,
            'reviewed_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $token = $this->issueApiToken($this->sysAdmin->username, 'AdminPass2026!');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/sysadmin/access-requests/{$grant->id}/view")
            ->assertOk()
            ->assertJsonPath('records.purchases_count', 1)
            ->assertJsonPath('records.purchases_total', 300);
    }

    public function test_api_inventory_and_reports_support_bounded_pagination_without_breaking_calculations(): void
    {
        $now = now();
        $bulkRows = [];
        for ($i = 1; $i <= 18; $i++) {
            $bulkRows[] = [
                'name' => "Paginated Item {$i}",
                'sku' => sprintf('PAG-%03d', $i),
                'category_id' => $this->category->id,
                'unit_id' => $this->unit->id,
                'current_quantity' => $i <= 2 ? 0 : 10,
                'minimum_stock_level' => 3,
                'reorder_level' => 5,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        InventoryItem::insert($bulkRows);

        $token = $this->issueApiToken($this->owner->username, 'OwnerPass2026!');

        $page1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/inventory?per_page=5&page=1')
            ->assertOk()
            ->assertJsonCount(5, 'items')
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonPath('pagination.total', 18)
            ->assertJsonStructure(['items', 'pagination', 'categories', 'units', 'defaults']);

        $page2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/inventory?per_page=5&page=2')
            ->assertOk()
            ->assertJsonCount(5, 'items')
            ->assertJsonPath('pagination.current_page', 2);

        $this->assertNotSame(
            collect($page1->json('items'))->pluck('id')->all(),
            collect($page2->json('items'))->pluck('id')->all()
        );

        $reportRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports?per_page=10&page=1')
            ->assertOk()
            ->assertJsonCount(10, 'items')
            ->assertJsonPath('pagination.total', 18)
            ->assertJsonPath('summary.total_items_count', 18)
            ->assertJsonPath('summary.out_of_stock_count', 2)
            ->assertJsonPath('summary.low_stock_count', 0);

        $this->assertIsArray($reportRes->json('categories'));
    }

    private function issueApiToken(string $username, string $password): string
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
            'device_name' => 'PHPUnit-P7',
        ]);
        $res->assertOk();

        return (string) $res->json('token');
    }
}

