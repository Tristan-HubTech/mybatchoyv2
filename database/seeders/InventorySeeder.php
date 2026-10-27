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
        $categoryNames = [
            'Meat and Poultry',
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
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['short_name' => $unit['short_name']], $unit);
        }

        $meat = InventoryCategory::where('name', 'Meat and Poultry')->first();
        $produce = InventoryCategory::where('name', 'Produce')->first();
        $dry = InventoryCategory::where('name', 'Dry Goods')->first();
        $seasoning = InventoryCategory::where('name', 'Seasonings and Condiments')->first();

        $kg = Unit::where('short_name', 'kg')->first();
        $g = Unit::where('short_name', 'g')->first();
        $l = Unit::where('short_name', 'L')->first();
        $pcs = Unit::where('short_name', 'pcs')->first();

        $inventory = [
            ['name' => 'Beef', 'sku' => 'BEEF-001', 'category_id' => $meat->id, 'unit_id' => $kg->id, 'current_quantity' => 15, 'minimum_stock_level' => 5, 'reorder_level' => 12],
            ['name' => 'Chicken', 'sku' => 'CHICK-001', 'category_id' => $meat->id, 'unit_id' => $kg->id, 'current_quantity' => 10, 'minimum_stock_level' => 4, 'reorder_level' => 8],
            ['name' => 'Onions', 'sku' => 'PROD-001', 'category_id' => $produce->id, 'unit_id' => $kg->id, 'current_quantity' => 7, 'minimum_stock_level' => 3, 'reorder_level' => 6],
            ['name' => 'Garlic', 'sku' => 'PROD-002', 'category_id' => $produce->id, 'unit_id' => $kg->id, 'current_quantity' => 4, 'minimum_stock_level' => 2, 'reorder_level' => 4],
            ['name' => 'Rice Noodles', 'sku' => 'DRY-001', 'category_id' => $dry->id, 'unit_id' => $kg->id, 'current_quantity' => 20, 'minimum_stock_level' => 5, 'reorder_level' => 10],
            ['name' => 'Soy Sauce', 'sku' => 'SEAS-001', 'category_id' => $seasoning->id, 'unit_id' => $l->id, 'current_quantity' => 12, 'minimum_stock_level' => 3, 'reorder_level' => 7],
            ['name' => 'Eggs', 'sku' => 'PROD-003', 'category_id' => $produce->id, 'unit_id' => $pcs->id, 'current_quantity' => 30, 'minimum_stock_level' => 12, 'reorder_level' => 20],
        ];

        foreach ($inventory as $item) {
            InventoryItem::firstOrCreate(['sku' => $item['sku']], $item);
        }
    }
}
