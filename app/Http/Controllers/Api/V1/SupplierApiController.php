<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $suppliers = Supplier::withCount('purchases');

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $suppliers->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        return response()->json([
            'suppliers' => $suppliers->latest()->get()->map(fn (Supplier $s) => self::formatSupplier($s))->values(),
        ]);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->loadCount('purchases');
        $purchases = Purchase::with(['creator', 'items.inventoryItem.unit'])
            ->where('supplier_id', $supplier->id)
            ->latest('purchase_date')
            ->take(20)
            ->get()
            ->map(fn (Purchase $p) => PurchaseApiController::formatPurchase($p));

        return response()->json([
            'supplier' => self::formatSupplier($supplier),
            'purchases' => $purchases,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['is_active'] = true;
        $supplier = Supplier::create($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'supplier_created',
            'model_type' => Supplier::class,
            'model_id' => $supplier->id,
            'details' => 'Created supplier: ' . $supplier->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Supplier saved successfully.',
            'supplier' => self::formatSupplier($supplier->loadCount('purchases')),
        ], 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        $supplier->update($validated);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'supplier_updated',
            'model_type' => Supplier::class,
            'model_id' => $supplier->id,
            'details' => 'Updated supplier: ' . $supplier->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'supplier' => self::formatSupplier($supplier->fresh()->loadCount('purchases')),
        ]);
    }

    public static function formatSupplier(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'contact_person' => $supplier->contact_person,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'address' => $supplier->address,
            'notes' => $supplier->notes,
            'is_active' => (bool) $supplier->is_active,
            'purchases_count' => (int) ($supplier->purchases_count ?? 0),
            'created_at' => $supplier->created_at?->toIso8601String(),
        ];
    }
}

