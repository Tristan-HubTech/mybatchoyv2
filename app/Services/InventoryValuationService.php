<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseItem;
use Illuminate\Support\Collection;

class InventoryValuationService
{
    /**
     * Retrieve the latest verified purchase unit_cost per inventory item ID,
     * considering only realized purchases (received into inventory and not rejected).
     *
     * @return Collection<int, float|string>
     */
    public function latestVerifiedUnitCosts(): Collection
    {
        $latestIdsSubquery = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.received_in_inventory', true)
            ->where('purchases.supplier_status', '!=', 'rejected')
            ->groupBy('purchase_items.inventory_item_id')
            ->selectRaw('MAX(purchase_items.id)');

        return PurchaseItem::query()
            ->whereIn('id', $latestIdsSubquery)
            ->pluck('unit_cost', 'inventory_item_id');
    }

    public function computeDatabaseValuation(): array
    {
        return $this->computeValuation(
            InventoryItem::query()
                ->select(['id', 'current_quantity'])
                ->orderBy('id')
                ->lazyById(250)
        );
    }

    /**
     * Calculate inventory valuation strictly from verified purchase costs without
     * inventing default unit prices for unpriced or unreceived items.
     *
     * @param  iterable<int, InventoryItem>  $items
     * @return array{
     *     inventory_value: float,
     *     total_items_count: int,
     *     valued_items_count: int,
     *     unknown_cost_items_count: int,
     *     valuation_status: string
     * }
     */
    public function computeValuation(iterable $items): array
    {
        $latestPrices = $this->latestVerifiedUnitCosts();

        $inventoryValue = 0.0;
        $totalItemsCount = 0;
        $valuedItemsCount = 0;
        $unknownCostItemsCount = 0;

        foreach ($items as $item) {
            $totalItemsCount++;
            if ($latestPrices->has($item->id)) {
                $valuedItemsCount++;
                $qty = max(0.0, (float) $item->current_quantity);
                $inventoryValue += $qty * (float) $latestPrices->get($item->id);
            } else {
                $unknownCostItemsCount++;
            }
        }

        $valuationStatus = match (true) {
            $totalItemsCount === 0 => 'empty',
            $unknownCostItemsCount === 0 => 'complete',
            $valuedItemsCount > 0 => 'partial',
            default => 'unvalued',
        };

        return [
            'inventory_value' => round($inventoryValue, 2),
            'total_items_count' => $totalItemsCount,
            'valued_items_count' => $valuedItemsCount,
            'unknown_cost_items_count' => $unknownCostItemsCount,
            'valuation_status' => $valuationStatus,
        ];
    }
}

