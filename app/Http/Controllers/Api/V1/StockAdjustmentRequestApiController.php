<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\StockAdjustmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentRequestApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = StockAdjustmentRequest::with(['inventoryItem.unit', 'requester:id,name,username', 'reviewer:id,name,username'])
            ->latest();

        if (!$user->isOwner()) {
            $query->where('requested_by', $user->id);
        }

        return response()->json([
            'adjustment_requests' => $query->take(50)->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'counted_quantity' => 'required|numeric|min:0|max:999999999.999',
            'request_type' => 'nullable|in:adjustment,stock_count',
            'reason' => 'required|string|min:5|max:255',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        $adj = StockAdjustmentRequest::create([
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
            'model_id' => $adj->id,
            'details' => "Requested adjustment for {$item->name}: {$item->current_quantity} -> {$validated['counted_quantity']}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Stock adjustment request submitted for Owner approval.',
            'adjustment_request' => $adj->load('inventoryItem.unit'),
        ], 201);
    }

    public function approve(Request $request, StockAdjustmentRequest $adjustmentRequest): JsonResponse
    {
        if (!$request->user()?->isOwner()) {
            return response()->json(['message' => 'Only the Owner can approve stock adjustment requests.'], 403);
        }

        if ($adjustmentRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending adjustment requests can be approved.'], 422);
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
                'details' => "Approved adjustment request #{$adjustmentRequest->id} for {$item->name}: {$previousQty} -> {$newQty}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'message' => 'Stock adjustment request approved.',
            'adjustment_request' => $adjustmentRequest->fresh(['inventoryItem.unit', 'reviewer:id,name,username']),
        ]);
    }

    public function reject(Request $request, StockAdjustmentRequest $adjustmentRequest): JsonResponse
    {
        if (!$request->user()?->isOwner()) {
            return response()->json(['message' => 'Only the Owner can reject stock adjustment requests.'], 403);
        }

        if ($adjustmentRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending adjustment requests can be rejected.'], 422);
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
            'details' => "Rejected stock adjustment request #{$adjustmentRequest->id}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Stock adjustment request rejected.',
            'adjustment_request' => $adjustmentRequest->fresh(),
        ]);
    }
}

