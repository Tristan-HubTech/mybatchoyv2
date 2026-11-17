<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserActiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionCacheAndSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private InventoryItem $item;

    public function createApplication()
    {
        $_ENV['CACHE_DRIVER'] = 'database';
        $_SERVER['CACHE_DRIVER'] = 'database';
        $_ENV['SESSION_DRIVER'] = 'database';
        $_SERVER['SESSION_DRIVER'] = 'database';

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        $this->staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'username' => 'serverless-staff',
            'email' => 'serverless-staff@mybatchoy.test',
            'password' => Hash::make('StaffStrong#2026'),
            'is_active' => true,
        ]);

        $cat = InventoryCategory::create(['name' => 'Proteins']);
        $unit = Unit::create(['name' => 'Kilogram', 'short_name' => 'kg']);

        $this->item = InventoryItem::create([
            'name' => 'Pork Belly',
            'sku' => 'PORK-SRV-1',
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'current_quantity' => 25,
            'minimum_stock_level' => 5,
            'reorder_level' => 10,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $_ENV['CACHE_DRIVER'] = 'array';
        $_SERVER['CACHE_DRIVER'] = 'array';
        $_ENV['SESSION_DRIVER'] = 'array';
        $_SERVER['SESSION_DRIVER'] = 'array';
    }

    public function test_vercel_config_uses_persistent_shared_cache_and_session_drivers(): void
    {
        $vercel = json_decode((string) file_get_contents(base_path('vercel.json')), true);

        $this->assertIsArray($vercel);
        $this->assertSame('database', $vercel['env']['CACHE_DRIVER'] ?? null);
        $this->assertSame('database', $vercel['env']['SESSION_DRIVER'] ?? null);
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('cache_locks'));
        $this->assertTrue(Schema::hasTable('sessions'));
    }

    public function test_database_cache_and_session_preserve_single_active_session_and_rate_limiting(): void
    {
        // Failed logins persist in database cache even when cache manager instance is cleared.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'username' => $this->staff->username,
                'password' => 'WrongPassword1!',
            ])->assertSessionHasErrors('username');
        }

        Cache::forgetDriver('database');
        $this->assertGreaterThan(0, DB::table('cache')->count());

        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffStrong#2026',
        ])->assertSessionHasErrors('username');

        // Clear rate limit cache and sign in validly -> persists DB session and active session record.
        DB::table('cache')->delete();
        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffStrong#2026',
        ])->assertRedirect(route('staff.dashboard'));

        $this->assertSame(1, UserActiveSession::where('user_id', $this->staff->id)->count());
        $this->assertDatabaseHas('sessions', ['user_id' => $this->staff->id]);

        // Second login attempt on another device while first session is active is rejected.
        $this->postJson('/api/v1/auth/login', [
            'username' => $this->staff->username,
            'password' => 'StaffStrong#2026',
            'device_name' => 'Second Device',
        ])->assertStatus(422)->assertJsonValidationErrors('username');

        // Logout releases the active session cleanly.
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertSame(0, UserActiveSession::where('user_id', $this->staff->id)->count());
    }

    public function test_database_cache_locks_enforce_idempotency_and_release_on_failure(): void
    {
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'username' => $this->staff->username,
            'password' => 'StaffStrong#2026',
            'device_name' => 'Serverless Phone',
        ])->assertOk();

        $token = $loginRes->json('token');
        $key = 'idem-db-tx-1001';

        $this->withToken($token)->withHeaders(['X-Idempotency-Key' => $key])
            ->postJson('/api/v1/transactions/stock-in', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 5,
                'reason' => 'Idempotent delivery test',
            ])->assertCreated()->assertJsonPath('item.current_quantity', 30);

        // Simulate fresh serverless worker instance with cleared driver instance.
        Cache::forgetDriver('database');

        $replay = $this->withToken($token)->withHeaders(['X-Idempotency-Key' => $key])
            ->postJson('/api/v1/transactions/stock-in', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 5,
                'reason' => 'Idempotent delivery test',
            ])->assertCreated();

        $this->assertSame('true', $replay->headers->get('X-Idempotent-Replayed'));
        $this->assertEquals(30.0, (float) $this->item->fresh()->current_quantity);
        $this->assertSame(1, InventoryTransaction::where('inventory_item_id', $this->item->id)->count());
        $this->assertSame(0, DB::table('cache_locks')->count());

        // Failed 422 request must not stick a lock or poison the idempotency key.
        $badKey = 'idem-db-fail-1002';
        $this->withToken($token)->withHeaders(['X-Idempotency-Key' => $badKey])
            ->postJson('/api/v1/transactions/stock-out', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 9999,
                'reason' => 'Exceeds available',
            ])->assertStatus(422);

        $this->assertSame(0, DB::table('cache_locks')->count());

        $this->withToken($token)->withHeaders(['X-Idempotency-Key' => $badKey])
            ->postJson('/api/v1/transactions/stock-out', [
                'inventory_item_id' => $this->item->id,
                'quantity' => 4,
                'reason' => 'Valid corrected withdrawal',
            ])->assertCreated()->assertJsonPath('item.current_quantity', 26);

        $this->assertSame(0, DB::table('cache_locks')->count());
    }

    public function test_api_password_change_cleans_obsolete_db_sessions_while_preserving_current_token(): void
    {
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'username' => $this->staff->username,
            'password' => 'StaffStrong#2026',
            'device_name' => 'Mobile Active Device',
        ])->assertOk();

        $activeToken = (string) $loginRes->json('token');

        // Seed an obsolete web DB session row for the same account
        DB::table('sessions')->insert([
            'id' => 'stale-web-session-id-999',
            'user_id' => $this->staff->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'OldBrowser',
            'payload' => base64_encode('stale'),
            'last_activity' => now()->timestamp,
        ]);

        $this->withToken($activeToken)
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'StaffStrong#2026',
                'password' => 'NewStaffStrong#2026!',
                'password_confirmation' => 'NewStaffStrong#2026!',
            ])->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'stale-web-session-id-999']);
        $this->assertSame(1, UserActiveSession::where('user_id', $this->staff->id)->count());

        // Current authenticated mobile token remains usable after password update.
        $this->withToken($activeToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk();
    }

    public function test_web_password_change_cleans_prior_db_sessions_and_preserves_rotated_web_session(): void
    {
        $this->post(route('login'), [
            'username' => $this->staff->username,
            'password' => 'StaffStrong#2026',
        ])->assertRedirect(route('staff.dashboard'));

        $oldSessionId = session()->getId();
        $this->assertDatabaseHas('sessions', ['id' => $oldSessionId, 'user_id' => $this->staff->id]);

        DB::table('sessions')->insert([
            'id' => 'obsolete-prior-session-888',
            'user_id' => $this->staff->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PriorBrowser',
            'payload' => base64_encode('old'),
            'last_activity' => now()->subMinutes(5)->timestamp,
        ]);

        $this->put(route('profile.password'), [
            'current_password' => 'StaffStrong#2026',
            'password' => 'WebRotatedStrong#2026!',
            'password_confirmation' => 'WebRotatedStrong#2026!',
        ])->assertRedirect(route('profile.show'));

        $this->assertDatabaseMissing('sessions', ['id' => 'obsolete-prior-session-888']);
        $this->get(route('staff.dashboard'))->assertOk();
    }
}
