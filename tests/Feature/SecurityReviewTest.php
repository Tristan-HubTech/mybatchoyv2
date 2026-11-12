<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $staffOne;
    private User $staffTwo;
    private InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'owner'], ['label' => 'Owner / Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'name' => 'Owner Account',
            'username' => 'sec-owner',
            'email' => 'sec-owner@example.test',
            'is_active' => true,
        ]);

        $this->staffOne = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Staff One',
            'username' => 'sec-staff-1',
            'email' => 'sec-staff-1@example.test',
            'is_active' => true,
        ]);

        $this->staffTwo = User::factory()->create([
            'role_id' => $staffRole->id,
            'name' => 'Staff Two',
            'username' => 'sec-staff-2',
            'email' => 'sec-staff-2@example.test',
            'is_active' => true,
        ]);

        $category = InventoryCategory::create(['name' => 'Noodles']);
        $unit = Unit::create(['name' => 'Kilograms', 'short_name' => 'kg']);

        $this->item = InventoryItem::create([
            'name' => 'Fresh Miki',
            'sku' => 'SEC-MIKI-001',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'current_quantity' => 50,
            'minimum_stock_level' => 10,
            'reorder_level' => 20,
            'is_active' => true,
        ]);
    }
    public function test_check_1_passwords_are_always_hashed_and_staff_reset_rotates_tokens(): void
    {
        $this->assertContains(config('hashing.driver'), ['bcrypt', 'argon', 'argon2id']);

        $user = User::create([
            'role_id' => $this->staffOne->role_id,
            'name' => 'Auto Hash User',
            'username' => 'auto-hash-user',
            'email' => 'autohash@example.test',
            'password' => 'PlainTextPassword2026!',
            'is_active' => true,
        ]);

        $this->assertNotSame('PlainTextPassword2026!', $user->password);
        $this->assertTrue(Hash::check('PlainTextPassword2026!', $user->password));

        $oldRemember = Str::random(60);
        $this->staffOne->forceFill(['remember_token' => $oldRemember])->save();
        DB::table('password_resets')->insert([
            'email' => $this->staffOne->email,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->actingAs($this->owner)->put(route('owner.staff.password', $this->staffOne), [
            'password' => 'UpdatedStaffPass2026!',
            'password_confirmation' => 'UpdatedStaffPass2026!',
        ])->assertRedirect(route('owner.staff.edit', $this->staffOne));

        $this->assertNotSame($oldRemember, $this->staffOne->fresh()->remember_token);
        $this->assertDatabaseMissing('password_resets', ['email' => $this->staffOne->email]);
    }

    public function test_check_2_no_browser_api_keys_or_unprotected_api_routes_exist(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));
        $bootstrapJs = file_get_contents(base_path('resources/js/bootstrap.js'));
        $gitignore = file_get_contents(base_path('.gitignore'));

        $this->assertStringNotContainsString('VITE_PUSHER_APP_KEY', (string) $envExample);
        $this->assertStringNotContainsString('VITE_PUSHER_APP_KEY', (string) $bootstrapJs);
        $this->assertStringContainsString('.env.*', (string) $gitignore);
        $this->assertStringContainsString('*.sql', (string) $gitignore);

        $this->get('/api/user')->assertNotFound();
    }

    public function test_check_3_staff_users_only_see_their_own_transaction_data(): void
    {
        InventoryTransaction::create([
            'inventory_item_id' => $this->item->id,
            'user_id' => $this->owner->id,
            'transaction_type' => 'stock_in',
            'quantity' => 10,
            'previous_quantity' => 40,
            'updated_quantity' => 50,
            'reason' => 'Owner confidential restock note',
            'transaction_date' => now(),
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $this->item->id,
            'user_id' => $this->staffOne->id,
            'transaction_type' => 'stock_out',
            'quantity' => 4,
            'previous_quantity' => 50,
            'updated_quantity' => 46,
            'reason' => 'Staff One kitchen dispatch',
            'transaction_date' => now(),
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $this->item->id,
            'user_id' => $this->staffTwo->id,
            'transaction_type' => 'stock_out',
            'quantity' => 2,
            'previous_quantity' => 46,
            'updated_quantity' => 44,
            'reason' => 'Staff Two private shift usage',
            'transaction_date' => now(),
        ]);

        $staffResponse = $this->actingAs($this->staffOne)->get(route('staff.transactions.index'));
        $staffResponse->assertOk()
            ->assertSee('Staff One kitchen dispatch')
            ->assertDontSee('Owner confidential restock note')
            ->assertDontSee('Staff Two private shift usage');

        $staffDash = $this->actingAs($this->staffOne)->get(route('staff.dashboard'));
        $staffDash->assertOk()
            ->assertSee('Staff One kitchen dispatch')
            ->assertDontSee('Owner confidential restock note')
            ->assertDontSee('Staff Two private shift usage');

        $ownerResponse = $this->actingAs($this->owner)->get(route('owner.transactions.index'));
        $ownerResponse->assertOk()
            ->assertSee('Owner confidential restock note')
            ->assertSee('Staff One kitchen dispatch')
            ->assertSee('Staff Two private shift usage');
    }

    public function test_check_4_https_redirection_headers_and_server_form_validation(): void
    {
        config(['app.force_https' => true]);

        $insecureResponse = $this->get('http://localhost/login');
        $insecureResponse->assertStatus(301);
        $this->assertStringStartsWith('https://', (string) $insecureResponse->headers->get('Location'));

        $secureResponse = $this->get('https://localhost/login');
        $secureResponse->assertOk();
        $secureResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $secureResponse->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $secureResponse->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $secureResponse->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        config(['app.force_https' => false]);

        $this->actingAs($this->owner)
            ->get(route('owner.inventory.index', ['category' => 'not-an-integer']))
            ->assertSessionHasErrors('category');

        $this->actingAs($this->owner)
            ->get(route('owner.purchases.index', ['supplier' => 999999]))
            ->assertSessionHasErrors('supplier');

        $this->actingAs($this->owner)
            ->get(route('owner.dashboard', ['range' => 'invalid_range']))
            ->assertSessionHasErrors('range');
    }

    public function test_check_5_xss_is_escaped_and_sql_injection_payloads_are_parameterized(): void
    {
        $xssPayload = '<script>alert("xss")</script>';

        $this->actingAs($this->owner)->post(route('owner.suppliers.store'), [
            'name' => $xssPayload,
            'contact_person' => "O'Reilly' OR 1=1 --",
        ])->assertRedirect(route('owner.suppliers.index'));

        $page = $this->actingAs($this->owner)->get(route('owner.suppliers.index', [
            'search' => "' OR 1=1 --",
        ]));

        $page->assertOk();
        $page->assertDontSee($xssPayload, false);

        $allPage = $this->actingAs($this->owner)->get(route('owner.suppliers.index'));
        $allPage->assertOk();
        $allPage->assertDontSee($xssPayload, false);
        $allPage->assertSee(e($xssPayload), false);
    }

    public function test_check_6_and_7_csrf_and_session_cookie_and_reset_expiry_settings(): void
    {
        $csrfMiddleware = app(VerifyCsrfToken::class);
        $reflection = new \ReflectionProperty($csrfMiddleware, 'except');
        $reflection->setAccessible(true);
        $this->assertSame([], $reflection->getValue($csrfMiddleware));

        $this->assertTrue((bool) config('session.http_only'));
        $this->assertTrue((bool) config('session.encrypt'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertSame(10, (int) config('auth.passwords.users.expire'));
    }

    public function test_check_8_file_uploads_reject_invalid_mime_types_and_oversized_files(): void
    {
        Storage::fake('public');

        $fakePhpFile = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $this->actingAs($this->owner)->put(route('owner.settings.update'), [
            'business_name' => 'MyBatchoy',
            'session_timeout_minutes' => 20,
            'default_minimum_stock_level' => 0,
            'default_reorder_level' => 0,
            'logo' => $fakePhpFile,
        ])->assertSessionHasErrors('logo');

        $oversizedPng = UploadedFile::fake()->create('huge.png', 3000, 'image/png');

        $this->actingAs($this->owner)->put(route('owner.settings.update'), [
            'business_name' => 'MyBatchoy',
            'session_timeout_minutes' => 20,
            'default_minimum_stock_level' => 0,
            'default_reorder_level' => 0,
            'logo' => $oversizedPng,
        ])->assertSessionHasErrors('logo');
    }

    public function test_check_9_admin_pages_locked_by_role_and_prices_recalculated_on_server(): void
    {
        $ownerRoutes = [
            route('owner.dashboard'),
            route('owner.inventory.index'),
            route('owner.suppliers.index'),
            route('owner.purchases.index'),
            route('owner.reports.index'),
            route('owner.backup.index'),
            route('owner.audit.index'),
            route('owner.staff.index'),
            route('owner.settings.index'),
        ];

        foreach ($ownerRoutes as $url) {
            $this->actingAs($this->staffOne)->get($url)->assertForbidden();
        }

        $supplier = Supplier::create(['name' => 'Trusted Supplier']);

        $this->actingAs($this->owner)->post(route('owner.purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 0.01,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'quantity' => 3.5,
                    'unit_cost' => 120.00,
                    'total_cost' => 0.01,
                ],
            ],
        ])->assertRedirect(route('owner.purchases.index'));

        $purchase = Purchase::latest('id')->firstOrFail();
        $this->assertEquals(420.00, (float) $purchase->total_amount);
        $this->assertEquals(420.00, (float) $purchase->items()->firstOrFail()->total_cost);
    }

    public function test_check_10_and_11_production_debug_is_off_and_unused_risky_packages_removed(): void
    {
        $envExample = (string) file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('APP_DEBUG=false', $envExample);

        $packageJson = json_decode((string) file_get_contents(base_path('package.json')), true);
        $devDeps = $packageJson['devDependencies'] ?? [];
        $this->assertArrayNotHasKey('lodash', $devDeps);
        $this->assertArrayNotHasKey('axios', $devDeps);
    }
}
