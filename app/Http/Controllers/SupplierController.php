<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $suppliers = Supplier::query()
            ->withCount('purchases')
            ->withSum('purchases as total_spent', 'total_amount')
            ->with(['purchases' => function ($q) {
                $q->latest('purchase_date')->take(5);
            }]);

        if (!empty($validated['search'])) {
            $search = trim((string) $validated['search']);
            $suppliers->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        return view('suppliers.index', [
            'suppliers' => $suppliers->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $supplier = Supplier::create($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'supplier_created',
            'model_type' => Supplier::class,
            'model_id' => $supplier->id,
            'details' => 'Created supplier: ' . $supplier->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('owner.suppliers.index')->with('success', 'Supplier saved successfully.');
    }

    public function edit(Supplier $supplier)
    {
        $supplier->load(['purchases' => function ($q) {
            $q->latest('purchase_date')->take(10);
        }]);

        return view('suppliers.form', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $supplier->update($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'supplier_updated',
            'model_type' => Supplier::class,
            'model_id' => $supplier->id,
            'details' => 'Updated supplier: ' . $supplier->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('owner.suppliers.index')->with('success', 'Supplier updated successfully.');
    }
}
