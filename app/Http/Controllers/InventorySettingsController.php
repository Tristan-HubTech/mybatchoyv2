<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventorySettingsController extends Controller
{
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $category = InventoryCategory::create($validated);
            $this->recordChange($request, 'inventory_category_created', InventoryCategory::class, $category->id);
        });

        return $this->saved('Inventory category created.');
    }

    public function updateCategory(Request $request, InventoryCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('inventory_categories', 'name')->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $category, $request): void {
            $category->update($validated);
            $this->recordChange($request, 'inventory_category_updated', InventoryCategory::class, $category->id);
        });

        return $this->saved('Inventory category updated.');
    }

    public function updateCategoryStatus(Request $request, InventoryCategory $category)
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        DB::transaction(function () use ($validated, $category, $request): void {
            $category->update(['is_active' => (bool) $validated['is_active']]);
            $this->recordChange($request, 'inventory_category_status_updated', InventoryCategory::class, $category->id);
        });

        return $this->saved('Inventory category status updated.');
    }

    public function storeUnit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:units,name',
            'short_name' => 'required|string|max:255|unique:units,short_name',
            'description' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $unit = Unit::create($validated);
            $this->recordChange($request, 'inventory_unit_created', Unit::class, $unit->id);
        });

        return $this->saved('Measurement unit created.');
    }

    public function updateUnit(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($unit->id)],
            'short_name' => ['required', 'string', 'max:255', Rule::unique('units', 'short_name')->ignore($unit->id)],
            'description' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $unit, $request): void {
            $unit->update($validated);
            $this->recordChange($request, 'inventory_unit_updated', Unit::class, $unit->id);
        });

        return $this->saved('Measurement unit updated.');
    }

    public function updateUnitStatus(Request $request, Unit $unit)
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        DB::transaction(function () use ($validated, $unit, $request): void {
            $unit->update(['is_active' => (bool) $validated['is_active']]);
            $this->recordChange($request, 'inventory_unit_status_updated', Unit::class, $unit->id);
        });

        return $this->saved('Measurement unit status updated.');
    }

    private function recordChange(Request $request, string $action, string $modelType, int $modelId): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'details' => 'Inventory configuration updated.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function saved(string $message)
    {
        return redirect()->route('owner.settings.index')->with('success', $message);
    }
}
