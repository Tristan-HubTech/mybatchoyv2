<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query();

        if ($request->filled('search')) {
            $suppliers->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('contact_person', 'like', '%' . $request->search . '%');
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
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
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
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return redirect()->route('owner.suppliers.index')->with('success', 'Supplier updated successfully.');
    }
}
