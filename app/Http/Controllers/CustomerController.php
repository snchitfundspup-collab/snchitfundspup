<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $lastCustomer = Customer::orderByDesc('id')->first();

        $nextNumber = $lastCustomer
            ? ((int) substr($lastCustomer->customer_code, 2)) + 1
            : 2601;

        $customerCode = 'SN' . $nextNumber;

        Customer::create([
            'customer_code' => $customerCode,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('customers.create')
            ->with('success', "Customer {$customerCode} created successfully.");
    }
}