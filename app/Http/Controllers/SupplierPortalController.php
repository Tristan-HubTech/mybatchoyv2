<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $supplier = $this->resolveLinkedSupplier($request);

        $orders = Purchase::where('supplier_id', $supplier->id);
        $pendingCount = (clone $orders)->where('supplier_status', 'pending')->count();
        $activeDeliveriesCount = (clone $orders)
            ->where('supplier_status', 'confirmed')
            ->whereIn('delivery_status', ['preparing', 'in_transit'])
            ->count();
        $deliveredCount = (clone $orders)->where('delivery_status', 'delivered')->count();
        $totalPoCount = (clone $orders)->count();

        $recentOrders = Purchase::with('items.inventoryItem:id,name,unit_id', 'items.inventoryItem.unit')
            ->where('supplier_id', $supplier->id)
            ->latest('purchase_date')
            ->latest('id')
            ->take(8)
            ->get();

        return view('supplier.dashboard', [
            'supplier' => $supplier,
            'pendingCount' => $pendingCount,
            'activeDeliveriesCount' => $activeDeliveriesCount,
            'deliveredCount' => $deliveredCount,
            'totalPoCount' => $totalPoCount,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function orders(Request $request)
    {
        $supplier = $this->resolveLinkedSupplier($request);

        $validated = $request->validate([
            'status' => 'nullable|in:pending,confirmed,rejected,delivered',
        ]);

        $query = Purchase::with(['items.inventoryItem:id,name,unit_id', 'items.inventoryItem.unit'])
            ->where('supplier_id', $supplier->id)
            ->latest('purchase_date')
            ->latest('id');

        if (($validated['status'] ?? null) === 'delivered') {
            $query->where('delivery_status', 'delivered');
        } elseif (!empty($validated['status'])) {
            $query->where('supplier_status', $validated['status']);
        }

        return view('supplier.orders', [
            'supplier' => $supplier,
            'orders' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function confirm(Request $request, Purchase $purchase)
    {
        $supplier = $this->resolveLinkedSupplier($request);
        $this->authorizeOwnPurchase($purchase, $supplier);

        if ($purchase->supplier_status !== 'pending') {
            return redirect()->route('supplier.orders.index')
                ->withErrors(['order' => 'Only pending purchase orders can be confirmed.']);
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
            'details' => "Supplier {$supplier->name} confirmed PO {$purchase->purchase_number}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('supplier.orders.index')
            ->with('success', "Purchase order {$purchase->purchase_number} confirmed.");
    }

    public function reject(Request $request, Purchase $purchase)
    {
        $supplier = $this->resolveLinkedSupplier($request);
        $this->authorizeOwnPurchase($purchase, $supplier);

        if ($purchase->supplier_status !== 'pending') {
            return redirect()->route('supplier.orders.index')
                ->withErrors(['order' => 'Only pending purchase orders can be rejected.']);
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
            'details' => "Supplier {$supplier->name} rejected PO {$purchase->purchase_number}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('supplier.orders.index')
            ->with('success', "Purchase order {$purchase->purchase_number} rejected.");
    }

    public function updateDelivery(Request $request, Purchase $purchase)
    {
        $supplier = $this->resolveLinkedSupplier($request);
        $this->authorizeOwnPurchase($purchase, $supplier);

        if ($purchase->supplier_status !== 'confirmed') {
            return redirect()->route('supplier.orders.index')
                ->withErrors(['order' => 'Confirm this purchase order before submitting delivery updates.']);
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
            'details' => "Supplier {$supplier->name} updated PO {$purchase->purchase_number} delivery to {$validated['delivery_status']}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('supplier.orders.index')
            ->with('success', "Delivery status updated for {$purchase->purchase_number}.");
    }

    public function profile(Request $request)
    {
        $supplier = $this->resolveLinkedSupplier($request);

        return view('supplier.profile', [
            'supplier' => $supplier,
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request)
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
            'details' => "Supplier account {$request->user()->username} updated contact information for {$supplier->name}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('supplier.profile')
            ->with('success', 'Supplier contact details updated.');
    }

    private function resolveLinkedSupplier(Request $request): Supplier
    {
        $user = $request->user();
        if (!$user || !$user->supplier_id) {
            abort(403, 'Your supplier account is not linked to an active supplier record.');
        }

        $supplier = Supplier::whereKey($user->supplier_id)->where('is_active', true)->first();
        if (!$supplier) {
            abort(403, 'The linked supplier profile is inactive or unavailable.');
        }

        return $supplier;
    }

    private function authorizeOwnPurchase(Purchase $purchase, Supplier $supplier): void
    {
        if ((int) $purchase->supplier_id !== (int) $supplier->id) {
            abort(403, 'You can only view and manage purchase orders assigned to your supplier account.');
        }
    }
}

