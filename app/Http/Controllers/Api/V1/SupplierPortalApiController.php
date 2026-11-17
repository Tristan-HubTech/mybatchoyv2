<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPortalApiController extends Controller
{
    public function orders(Request $request): JsonResponse
    {
        $supplier = $this->resolveLinkedSupplier($request);

        $orders = Purchase::with(['items.inventoryItem:id,name,unit_id', 'items.inventoryItem.unit'])
            ->where('supplier_id', $supplier->id)
            ->latest('purchase_date')
            ->latest('id')
            ->take(50)
            ->get()
            ->map(fn (Purchase $po) => [
                'id' => $po->id,
                'purchase_number' => $po->purchase_number,
                'purchase_date' => $po->purchase_date?->format('Y-m-d'),
                'supplier_status' => $po->supplier_status,
                'delivery_status' => $po->delivery_status,
                'invoice_number' => $po->invoice_number,
                'delivery_notes' => $po->delivery_notes,
                'received_in_inventory' => (bool) $po->received_in_inventory,
                'total_amount' => (float) $po->total_amount,
                'items' => $po->items->map(fn ($line) => [
                    'item_name' => $line->inventoryItem?->name,
                    'unit' => $line->inventoryItem?->unit?->short_name ?? 'units',
                    'quantity' => (float) $line->quantity,
                    'unit_cost' => (float) $line->unit_cost,
                    'total_cost' => (float) $line->total_cost,
                ])->values(),
            ]);

        return response()->json([
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
            ],
            'orders' => $orders,
        ]);
    }

    public function confirm(Request $request, Purchase $purchase): JsonResponse
    {
        $supplier = $this->resolveLinkedSupplier($request);
        if ((int) $purchase->supplier_id !== (int) $supplier->id) {
            return response()->json(['message' => 'You can only confirm purchase orders assigned to your supplier account.'], 403);
        }

        if ($purchase->supplier_status !== 'pending') {
            return response()->json(['message' => 'Only pending purchase orders can be confirmed.'], 422);
        }

        $purchase->update([
            'supplier_status' => 'confirmed',
            'delivery_status' => 'preparing',
            'supplier_responded_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'supplier_po_confirmed',
            'model_type' => Purchase::class,
            'model_id' => $purchase->id,
            'details' => "Supplier {$supplier->name} confirmed PO {$purchase->purchase_number} via API.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Purchase order confirmed.',
            'order' => $purchase->fresh(),
        ]);
    }

    public function reject(Request $request, Purchase $purchase): JsonResponse
    {
        $supplier = $this->resolveLinkedSupplier($request);
        if ((int) $purchase->supplier_id !== (int) $supplier->id) {
            return response()->json(['message' => 'You can only reject purchase orders assigned to your supplier account.'], 403);
        }

        if ($purchase->supplier_status !== 'pending') {
            return response()->json(['message' => 'Only pending purchase orders can be rejected.'], 422);
        }

        $validated = $request->validate([
            'delivery_notes' => 'required|string|max:500',
        ]);

        $purchase->update([
            'supplier_status' => 'rejected',
            'delivery_status' => 'rejected',
            'delivery_notes' => trim($validated['delivery_notes']),
            'supplier_responded_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'supplier_po_rejected',
            'model_type' => Purchase::class,
            'model_id' => $purchase->id,
            'details' => "Supplier {$supplier->name} rejected PO {$purchase->purchase_number} via API.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Purchase order rejected.',
            'order' => $purchase->fresh(),
        ]);
    }

    public function updateDelivery(Request $request, Purchase $purchase): JsonResponse
    {
        $supplier = $this->resolveLinkedSupplier($request);
        if ((int) $purchase->supplier_id !== (int) $supplier->id) {
            return response()->json(['message' => 'You can only update deliveries for your own purchase orders.'], 403);
        }

        if ($purchase->supplier_status !== 'confirmed') {
            return response()->json(['message' => 'Purchase order must be confirmed before updating delivery status.'], 422);
        }

        $validated = $request->validate([
            'delivery_status' => 'required|in:preparing,in_transit,delivered',
            'invoice_number' => 'nullable|string|max:100',
            'delivery_notes' => 'nullable|string|max:500',
        ]);

        $purchase->update([
            'delivery_status' => $validated['delivery_status'],
            'invoice_number' => $validated['invoice_number'] ?? $purchase->invoice_number,
            'delivery_notes' => $validated['delivery_notes'] ?? $purchase->delivery_notes,
            'delivered_at' => $validated['delivery_status'] === 'delivered' ? ($purchase->delivered_at ?? now()) : null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'supplier_delivery_updated',
            'model_type' => Purchase::class,
            'model_id' => $purchase->id,
            'details' => "Supplier {$supplier->name} updated PO {$purchase->purchase_number} delivery to {$validated['delivery_status']} via API.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Delivery status updated.',
            'order' => $purchase->fresh(),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $supplier = $this->resolveLinkedSupplier($request);

        return response()->json([
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $supplier = $this->resolveLinkedSupplier($request);

        $validated = $request->validate([
            'supplier_id' => 'prohibited',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $supplier->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'supplier_contact_updated',
            'model_type' => Supplier::class,
            'model_id' => $supplier->id,
            'details' => "Supplier account {$request->user()->username} updated contact information via API.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Supplier contact details updated.',
            'supplier' => $supplier->fresh(),
        ]);
    }

    private function resolveLinkedSupplier(Request $request): Supplier
    {
        $user = $request->user();
        if (!$user || !$user->supplier_id) {
            abort(403, 'Supplier account is not linked to an active supplier.');
        }

        $supplier = Supplier::whereKey($user->supplier_id)->where('is_active', true)->first();
        if (!$supplier) {
            abort(403, 'Linked supplier profile is inactive or missing.');
        }

        return $supplier;
    }
}

