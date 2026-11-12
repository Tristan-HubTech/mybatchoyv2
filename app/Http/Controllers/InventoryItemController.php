<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:inventory_categories,id',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = InventoryItem::with(['category', 'unit'])
            ->select('inventory_items.*')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('inventory_items.name', 'like', '%' . $search . '%')
                        ->orWhere('inventory_items.sku', 'like', '%' . $search . '%');
                });
            })
            ->when(!empty($validated['category']), function ($q) use ($validated) {
                $q->where('inventory_items.category_id', $validated['category']);
            })
            ->distinct();

        $items = $query->latest('inventory_items.created_at')->get();
        $categories = InventoryCategory::orderBy('name')->get();

        return view('inventory.index', compact('items', 'categories'));
    }

    public function create()
    {
        $settings = BusinessSetting::find(1);

        return view('inventory.form', [
            'item' => null,
            'categories' => InventoryCategory::where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'defaultMinimumStockLevel' => $settings?->default_minimum_stock_level ?? 0,
            'defaultReorderLevel' => $settings?->default_reorder_level ?? 0,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_id' => 'required|exists:units,id',
            'sku' => 'nullable|string|max:100|unique:inventory_items,sku',
            'description' => 'nullable|string|max:2000',
            'current_quantity' => 'required|numeric|min:0|max:999999999.999',
            'minimum_stock_level' => 'required|numeric|min:0|max:999999999.999',
            'reorder_level' => 'nullable|numeric|min:0|max:999999999.999',
        ]);

        $validated['sku'] = $validated['sku'] ?? strtoupper(Str::slug($validated['name']) . '-' . random_int(100, 999));
        $validated['is_active'] = true;

        $item = InventoryItem::create($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'inventory_created',
            'model_type' => InventoryItem::class,
            'model_id' => $item->id,
            'details' => 'Created inventory item: ' . $item->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('owner.inventory.index')->with('success', 'Inventory item created successfully.');
    }

    public function edit(InventoryItem $item)
    {
        return view('inventory.form', [
            'item' => $item,
            'categories' => InventoryCategory::where('is_active', true)
                ->orWhere('id', $item->category_id)
                ->orderBy('name')
                ->get(),
            'units' => Unit::where('is_active', true)
                ->orWhere('id', $item->unit_id)
                ->orderBy('name')
                ->get(),
            'defaultMinimumStockLevel' => 0,
            'defaultReorderLevel' => 0,
        ]);
    }

    public function update(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_id' => 'required|exists:units,id',
            'sku' => 'nullable|string|max:100|unique:inventory_items,sku,' . $item->id,
            'description' => 'nullable|string|max:2000',
            'current_quantity' => 'required|numeric|min:0|max:999999999.999',
            'minimum_stock_level' => 'required|numeric|min:0|max:999999999.999',
            'reorder_level' => 'nullable|numeric|min:0|max:999999999.999',
        ]);

        $item->update($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'inventory_updated',
            'model_type' => InventoryItem::class,
            'model_id' => $item->id,
            'details' => 'Updated inventory item: ' . $item->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('owner.inventory.index')->with('success', 'Inventory item updated successfully.');
    }
}
