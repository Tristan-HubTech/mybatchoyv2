<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\StockAdjustmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentRequestController extends Controller
{
    public function staffIndex()
    {
        $requests = StockAdjustmentRequest::with(['inventoryItem.unit', 'reviewer'])
            ->where('requested_by', auth()->id())
            ->latest()
            ->paginate(15);

        return view('staff.adjustments', [
            'requests' => $requests,
            'items' => InventoryItem::with('unit')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function staffStore(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'counted_quantity' => 'required|numeric|min:0|max:999999999.999',
            'request_type' => 'nullable|in:adjustment,stock_count',
            'reason' => 'required|string|min:5|max:255',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        $adjRequest = StockAdjustmentRequest::create([
            'inventory_item_id' => $item->id,
            'requested_by' => $request->user()->id,
            'request_type' => $validated['request_type'] ?? 'adjustment',
            'previous_quantity' => (float) $item->current_quantity,
            'counted_quantity' => (float) $validated['counted_quantity'],
            'reason' => trim($validated['reason']),
            'status' => 'pending',
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'stock_adjustment_requested',
            'model_type' => StockAdjustmentRequest::class,
            'model_id' => $adjRequest->id,
            'details' => "Staff submitted stock count/adjustment request for {$item->name}: {$item->current_quantity} -> {$validated['counted_quantity']}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('staff.adjustments.index')
            ->with('success', "Stock adjustment request for {$item->name} submitted for Owner approval.");
    }

    public function ownerIndex()
    {
        $requests = StockAdjustmentRequest::with(['inventoryItem.unit', 'requester', 'reviewer'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC")
            ->latest()
            ->paginate(20);

        return view('owner.adjustments', [
            'requests' => $requests,
        ]);
    }

    public function approve(Request $request, StockAdjustmentRequest $adjustmentRequest)
    {
        if (!$request->user()?->isOwner()) {
            abort(403, 'Only the Owner can approve stock count or adjustment requests.');
        }

        if ($adjustmentRequest->status !== 'pending') {
            return redirect()->route('owner.adjustments.index')
                ->withErrors(['adjustment' => 'Only pending adjustment requests can be approved.']);
        }

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($adjustmentRequest, $validated, $request): void {
            $item = InventoryItem::whereKey($adjustmentRequest->inventory_item_id)->lockForUpdate()->firstOrFail();
            $previousQty = (float) $item->current_quantity;
            $newQty = (float) $adjustmentRequest->counted_quantity;

            $item->update([
                'current_quantity' => $newQty,
                'last_counted_at' => now(),
            ]);

            InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => $adjustmentRequest->requested_by,
                'transaction_type' => 'adjustment',
                'quantity' => $newQty - $previousQty,
                'previous_quantity' => $previousQty,
                'updated_quantity' => $newQty,
                'reason' => $adjustmentRequest->reason . ' (Approved by Owner)',
                'transaction_date' => now(),
            ]);

            $adjustmentRequest->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'review_notes' => $validated['review_notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'stock_adjustment_approved',
                'model_type' => StockAdjustmentRequest::class,
                'model_id' => $adjustmentRequest->id,
                'details' => "Owner approved adjustment request #{$adjustmentRequest->id} for {$item->name}: {$previousQty} -> {$newQty}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('owner.adjustments.index')->with('success', 'Stock adjustment request approved and applied to inventory.');
    }

    public function reject(Request $request, StockAdjustmentRequest $adjustmentRequest)
    {
        if (!$request->user()?->isOwner()) {
            abort(403, 'Only the Owner can reject stock count or adjustment requests.');
        }

        if ($adjustmentRequest->status !== 'pending') {
            return redirect()->route('owner.adjustments.index')
                ->withErrors(['adjustment' => 'Only pending adjustment requests can be rejected.']);
        }

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:255',
        ]);

        $adjustmentRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'stock_adjustment_rejected',
            'model_type' => StockAdjustmentRequest::class,
            'model_id' => $adjustmentRequest->id,
            'details' => "Owner rejected stock adjustment request #{$adjustmentRequest->id}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('owner.adjustments.index')->with('success', 'Stock adjustment request rejected.');
    }
}

