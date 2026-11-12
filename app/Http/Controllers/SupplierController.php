<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $suppliers = Supplier::query();

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $suppliers->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%');
            });
        }

        return view('suppliers.index', ['suppliers' => $suppliers->latest()->get()]);
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

        Supplier::create($validated);

        return redirect()->route('owner.suppliers.index')->with('success', 'Supplier saved successfully.');
    }

    public function edit(Supplier $supplier)
    {
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

        return redirect()->route('owner.suppliers.index')->with('success', 'Supplier updated successfully.');
    }
}
