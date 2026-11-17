<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('InventorySeeder contains demonstration catalog items and cannot execute in production.');
        }

        $categoryNames = [
            'Meat and Poultry',
            'Noodles and Grains',
            'Produce',
            'Dry Goods',
            'Seasonings and Condiments',
        ];

        foreach ($categoryNames as $name) {
            InventoryCategory::firstOrCreate(['name' => $name]);
        }

        $units = [
            ['name' => 'Kilograms', 'short_name' => 'kg'],
            ['name' => 'Grams', 'short_name' => 'g'],
            ['name' => 'Liters', 'short_name' => 'L'],
            ['name' => 'Milliliters', 'short_name' => 'ml'],
            ['name' => 'Pieces', 'short_name' => 'pcs'],
            ['name' => 'Packs', 'short_name' => 'packs'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['short_name' => $unit['short_name']], $unit);
        }

        $meat = InventoryCategory::where('name', 'Meat and Poultry')->first();
        $noodles = InventoryCategory::where('name', 'Noodles and Grains')->first();
        $produce = InventoryCategory::where('name', 'Produce')->first();
        $dry = InventoryCategory::where('name', 'Dry Goods')->first();
        $seasoning = InventoryCategory::where('name', 'Seasonings and Condiments')->first();

        $kg = Unit::where('short_name', 'kg')->first();
        $l = Unit::where('short_name', 'L')->first();
        $pcs = Unit::where('short_name', 'pcs')->first();
        $packs = Unit::where('short_name', 'packs')->first();

        $inventory = [
            ['name' => 'Pork Belly (Liempo)', 'sku' => 'MEAT-001', 'category_id' => $meat->id, 'unit_id' => $kg->id, 'description' => 'Keep chilled at 0-4C; slice thin for batchoy toppings.', 'current_quantity' => 18.5, 'minimum_stock_level' => 6, 'reorder_level' => 12],
            ['name' => 'Beef Bones (Bulalo)', 'sku' => 'MEAT-002', 'category_id' => $meat->id, 'unit_id' => $kg->id, 'description' => 'Main stock pot bones for Unli Sabaw broth base.', 'current_quantity' => 24, 'minimum_stock_level' => 8, 'reorder_level' => 15],
            ['name' => 'Pork Liver', 'sku' => 'MEAT-003', 'category_id' => $meat->id, 'unit_id' => $kg->id, 'description' => 'Use within 2 days; store in freezer drawer.', 'current_quantity' => 9, 'minimum_stock_level' => 4, 'reorder_level' => 7],
            ['name' => 'Chicken Breast', 'sku' => 'MEAT-004', 'category_id' => $meat->id, 'unit_id' => $kg->id, 'description' => 'Secondary broth protein and shredded topping.', 'current_quantity' => 11, 'minimum_stock_level' => 4, 'reorder_level' => 8],
            ['name' => 'Fresh Egg Noodles (Miki)', 'sku' => 'NOOD-001', 'category_id' => $noodles->id, 'unit_id' => $kg->id, 'description' => 'Store in covered food-grade tubs; FIFO rotation.', 'current_quantity' => 26, 'minimum_stock_level' => 8, 'reorder_level' => 15],
            ['name' => 'Rice Noodles (Bihon)', 'sku' => 'NOOD-002', 'category_id' => $noodles->id, 'unit_id' => $packs->id, 'description' => 'Keep dry in sealed pantry racks.', 'current_quantity' => 22, 'minimum_stock_level' => 6, 'reorder_level' => 12],
            ['name' => 'Onions', 'sku' => 'PROD-001', 'category_id' => $produce->id, 'unit_id' => $kg->id, 'description' => 'Dry ventilated crate.', 'current_quantity' => 9, 'minimum_stock_level' => 3, 'reorder_level' => 6],
            ['name' => 'Spring Onions (Sibuyas Dahon)', 'sku' => 'PROD-002', 'category_id' => $produce->id, 'unit_id' => $kg->id, 'description' => 'Low stock warning — order fresh greens daily.', 'current_quantity' => 1.5, 'minimum_stock_level' => 3, 'reorder_level' => 6],
            ['name' => 'Chicken Eggs', 'sku' => 'PROD-003', 'category_id' => $produce->id, 'unit_id' => $pcs->id, 'description' => 'Soft-boiled / poached egg toppings per bowl.', 'current_quantity' => 14, 'minimum_stock_level' => 24, 'reorder_level' => 48],
            ['name' => 'Pork Cracklings (Chicharon)', 'sku' => 'DRY-001', 'category_id' => $dry->id, 'unit_id' => $kg->id, 'description' => 'Keep airtight to maintain crispness.', 'current_quantity' => 2.5, 'minimum_stock_level' => 5, 'reorder_level' => 10],
            ['name' => 'Soy Sauce', 'sku' => 'SEAS-001', 'category_id' => $seasoning->id, 'unit_id' => $l->id, 'description' => 'Broth seasoning and table condiments.', 'current_quantity' => 14, 'minimum_stock_level' => 4, 'reorder_level' => 8],
            ['name' => 'Fried Garlic Bits', 'sku' => 'DRY-002', 'category_id' => $dry->id, 'unit_id' => $kg->id, 'description' => 'Out of stock — prep or restock immediately.', 'current_quantity' => 0, 'minimum_stock_level' => 2, 'reorder_level' => 5],
            ['name' => 'Black Peppercorn (Paminta)', 'sku' => 'SEAS-002', 'category_id' => $seasoning->id, 'unit_id' => $kg->id, 'description' => 'Out of stock — replenish pantry jar.', 'current_quantity' => 0, 'minimum_stock_level' => 1.5, 'reorder_level' => 3],
        ];

        foreach ($inventory as $item) {
            InventoryItem::firstOrCreate(['sku' => $item['sku']], array_merge($item, [
                'is_active' => true,
                'last_counted_at' => now()->subDay(),
            ]));
        }
    }
}
