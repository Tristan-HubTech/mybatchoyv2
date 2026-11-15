<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        BusinessSetting::updateOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Eustace Batchoy',
                'phone' => '+63 917 555 0148',
                'email' => 'owner@mybatchoy.local',
                'address' => 'Jaro Plaza, Iloilo City, Philippines',
                'session_timeout_minutes' => 30,
                'default_minimum_stock_level' => 5,
                'default_reorder_level' => 10,
            ]
        );

        $suppliersData = [
            [
                'name' => 'Iloilo Public Market Meats',
                'contact_person' => 'Mang Roberto Cruz',
                'phone' => '+63 917 440 2211',
                'email' => 'meats@iloilomarket.test',
                'address' => 'Stall 14, Iloilo Central Market',
                'notes' => 'Early morning fresh pork belly, liver, and beef bone deliveries (5:30 AM).',
                'is_active' => true,
            ],
            [
                'name' => 'Molo Fresh Noodle Mill',
                'contact_person' => 'Aling Nena Villanueva',
                'phone' => '+63 918 320 9944',
                'email' => 'orders@molonoodles.test',
                'address' => 'M.H. del Pilar St., Molo, Iloilo City',
                'notes' => 'Daily batch of fresh egg miki noodles and bihon packs; COD terms.',
                'is_active' => true,
            ],
            [
                'name' => 'La Paz Dry Goods & Spices',
                'contact_person' => 'Carlo Tan',
                'phone' => '+63 920 882 1170',
                'email' => 'sales@lapazspices.test',
                'address' => 'Rizal St., La Paz, Iloilo City',
                'notes' => 'Chicharon, soy sauce, peppercorn, onions, and bulk condiments; 7-day terms.',
                'is_active' => true,
            ],
        ];

        $suppliers = [];
        foreach ($suppliersData as $s) {
            $suppliers[$s['name']] = Supplier::firstOrCreate(['name' => $s['name']], $s);
        }

        $owner = User::where('username', 'owner')->first();
        $maria = User::where('username', 'staff_maria')->first() ?? $owner;
        $juan = User::where('username', 'staff_juan')->first() ?? $owner;
        $elena = User::where('username', 'staff_elena')->first() ?? $owner;

        if (! $owner) {
            return;
        }

        $items = InventoryItem::all()->keyBy('sku');
        $meatSupplier = $suppliers['Iloilo Public Market Meats'];
        $noodleSupplier = $suppliers['Molo Fresh Noodle Mill'];
        $drySupplier = $suppliers['La Paz Dry Goods & Spices'];

        if (Purchase::count() === 0 && $items->has('MEAT-001')) {
            $poBlueprints = [
                [
                    'purchase_number' => 'PO-2026-0001',
                    'supplier_id' => $meatSupplier->id,
                    'created_by' => $owner->id,
                    'purchase_date' => now()->subDays(9)->toDateString(),
                    'notes' => 'Weekly bone & pork belly replenishment.',
                    'lines' => [
                        ['sku' => 'MEAT-001', 'qty' => 15, 'unit_cost' => 340.00],
                        ['sku' => 'MEAT-002', 'qty' => 20, 'unit_cost' => 190.00],
                    ],
                ],
                [
                    'purchase_number' => 'PO-2026-0002',
                    'supplier_id' => $noodleSupplier->id,
                    'created_by' => $owner->id,
                    'purchase_date' => now()->subDays(7)->toDateString(),
                    'notes' => 'Fresh miki noodles & bihon restock.',
                    'lines' => [
                        ['sku' => 'NOOD-001', 'qty' => 25, 'unit_cost' => 95.00],
                        ['sku' => 'NOOD-002', 'qty' => 15, 'unit_cost' => 65.00],
                    ],
                ],
                [
                    'purchase_number' => 'PO-2026-0003',
                    'supplier_id' => $drySupplier->id,
                    'created_by' => $owner->id,
                    'purchase_date' => now()->subDays(5)->toDateString(),
                    'notes' => 'Soy sauce gallons, onions & cracklings.',
                    'lines' => [
                        ['sku' => 'SEAS-001', 'qty' => 12, 'unit_cost' => 110.00],
                        ['sku' => 'PROD-001', 'qty' => 8, 'unit_cost' => 145.00],
                        ['sku' => 'DRY-001', 'qty' => 6, 'unit_cost' => 310.00],
                    ],
                ],
                [
                    'purchase_number' => 'PO-2026-0004',
                    'supplier_id' => $meatSupplier->id,
                    'created_by' => $owner->id,
                    'purchase_date' => now()->subDays(3)->toDateString(),
                    'notes' => 'Mid-week liver and chicken delivery.',
                    'lines' => [
                        ['sku' => 'MEAT-003', 'qty' => 10, 'unit_cost' => 220.00],
                        ['sku' => 'MEAT-004', 'qty' => 12, 'unit_cost' => 210.00],
                    ],
                ],
                [
                    'purchase_number' => 'PO-2026-0005',
                    'supplier_id' => $noodleSupplier->id,
                    'created_by' => $owner->id,
                    'purchase_date' => now()->subDay()->toDateString(),
                    'notes' => 'Weekend rush miki noodles & egg tray.',
                    'lines' => [
                        ['sku' => 'NOOD-001', 'qty' => 18, 'unit_cost' => 95.00],
                        ['sku' => 'PROD-003', 'qty' => 60, 'unit_cost' => 9.50],
                    ],
                ],
            ];

            foreach ($poBlueprints as $poData) {
                $total = 0;
                foreach ($poData['lines'] as $line) {
                    $total += $line['qty'] * $line['unit_cost'];
                }

                $po = Purchase::create([
                    'supplier_id' => $poData['supplier_id'],
                    'created_by' => $poData['created_by'],
                    'purchase_number' => $poData['purchase_number'],
                    'purchase_date' => $poData['purchase_date'],
                    'total_amount' => $total,
                    'notes' => $poData['notes'],
                ]);

                foreach ($poData['lines'] as $line) {
                    $item = $items->get($line['sku']);
                    if (! $item) {
                        continue;
                    }

                    PurchaseItem::create([
                        'purchase_id' => $po->id,
                        'inventory_item_id' => $item->id,
                        'unit_id' => $item->unit_id,
                        'quantity' => $line['qty'],
                        'unit_cost' => $line['unit_cost'],
                        'total_cost' => $line['qty'] * $line['unit_cost'],
                    ]);
                }
            }
        }

        if (InventoryTransaction::count() === 0 && $items->has('MEAT-001')) {
            $txBlueprints = [
                ['sku' => 'MEAT-001', 'user' => $owner, 'supplier' => $meatSupplier, 'type' => 'stock_in', 'qty' => 15, 'prev' => 10, 'next' => 25, 'reason' => 'Morning market delivery (PO-2026-0001)', 'hours_ago' => 140],
                ['sku' => 'MEAT-002', 'user' => $juan, 'supplier' => $meatSupplier, 'type' => 'stock_in', 'qty' => 20, 'prev' => 14, 'next' => 34, 'reason' => 'Bulalo bone delivery for broth kettles', 'hours_ago' => 122],
                ['sku' => 'MEAT-001', 'user' => $maria, 'supplier' => null, 'type' => 'stock_out', 'qty' => 6.5, 'prev' => 25, 'next' => 18.5, 'reason' => 'Lunch & dinner batchoy prep', 'hours_ago' => 96],
                ['sku' => 'MEAT-002', 'user' => $elena, 'supplier' => null, 'type' => 'stock_out', 'qty' => 10, 'prev' => 34, 'next' => 24, 'reason' => 'Overnight Unli Sabaw broth boiling', 'hours_ago' => 78],
                ['sku' => 'NOOD-001', 'user' => $juan, 'supplier' => $noodleSupplier, 'type' => 'stock_in', 'qty' => 18, 'prev' => 16, 'next' => 34, 'reason' => 'Daily noodle mill drop-off', 'hours_ago' => 54],
                ['sku' => 'NOOD-001', 'user' => $maria, 'supplier' => null, 'type' => 'stock_out', 'qty' => 8, 'prev' => 34, 'next' => 26, 'reason' => 'Afternoon dine-in & takeout orders', 'hours_ago' => 38],
                ['sku' => 'PROD-002', 'user' => $elena, 'supplier' => null, 'type' => 'spoilage', 'qty' => 1.5, 'prev' => 3, 'next' => 1.5, 'reason' => 'Wilted green onion bundles discarded', 'hours_ago' => 26],
                ['sku' => 'DRY-001', 'user' => $maria, 'supplier' => null, 'type' => 'stock_out', 'qty' => 3.5, 'prev' => 6, 'next' => 2.5, 'reason' => 'Evening service chicharon garnish', 'hours_ago' => 18],
                ['sku' => 'PROD-003', 'user' => $juan, 'supplier' => null, 'type' => 'adjustment', 'qty' => 2, 'prev' => 16, 'next' => 14, 'reason' => 'Morning tray recount (2 cracked eggs removed)', 'hours_ago' => 10],
                ['sku' => 'DRY-002', 'user' => $elena, 'supplier' => null, 'type' => 'stock_out', 'qty' => 2, 'prev' => 2, 'next' => 0, 'reason' => 'Used remaining fried garlic batch for dinner', 'hours_ago' => 5],
                ['sku' => 'SEAS-002', 'user' => $maria, 'supplier' => null, 'type' => 'stock_out', 'qty' => 1.5, 'prev' => 1.5, 'next' => 0, 'reason' => 'Ground black pepper for broth seasoning', 'hours_ago' => 2],
            ];

            foreach ($txBlueprints as $tx) {
                $item = $items->get($tx['sku']);
                if (! $item) {
                    continue;
                }

                InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'user_id' => $tx['user']->id,
                    'supplier_id' => $tx['supplier']?->id,
                    'transaction_type' => $tx['type'],
                    'quantity' => $tx['qty'],
                    'previous_quantity' => $tx['prev'],
                    'updated_quantity' => $tx['next'],
                    'reason' => $tx['reason'],
                    'transaction_date' => now()->subHours($tx['hours_ago']),
                ]);
            }
        }

        if (AuditLog::count() === 0) {
            AuditLog::create([
                'user_id' => $owner->id,
                'action' => 'system.seeded',
                'model_type' => BusinessSetting::class,
                'model_id' => 1,
                'details' => 'Initialized Eustace Batchoy database with Owner, 3 Staff accounts, and test inventory data.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'ArtisanSeeder',
            ]);
            AuditLog::create([
                'user_id' => $owner->id,
                'action' => 'staff.created',
                'model_type' => User::class,
                'model_id' => $maria->id,
                'details' => 'Created active staff accounts: staff_maria, staff_juan, staff_elena.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'ArtisanSeeder',
            ]);
        }

        $this->command?->info('Eustace Batchoy test data (suppliers, purchases, transactions, settings, audit logs) seeded successfully.');
    }
}

