<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display customers.
     *
     * Includes:
     * - Search
     * - Pagination
     * - Search preserved while changing pages
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $customers = Customer::query()

            ->when($search !== '', function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where(
                        'customer_code',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'remarks',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'address',
                        'like',
                        "%{$search}%"
                    );

                });

            })

            ->orderByDesc('id')

            ->paginate(10)

            ->withQueryString();


        return view(
            'customers.index',
            compact(
                'customers',
                'search'
            )
        );
    }


    /**
     * Show create customer page.
     */
    public function create()
    {
        return view('customers.create');
    }


    /**
     * Store a new customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
         * Get the latest customer.
         */
        $lastCustomer = Customer::orderByDesc('id')->first();


        /*
         * Generate next customer number.
         *
         * First customer:
         * SN2601
         *
         * Next:
         * SN2602
         * SN2603
         * etc.
         */
        $nextNumber = $lastCustomer

            ? ((int) substr(
                $lastCustomer->customer_code,
                2
            )) + 1

            : 2601;


        $customerCode = 'SN' . $nextNumber;


        /*
         * Create customer.
         */
        Customer::create([

            'customer_code' => $customerCode,

            'name' => $validated['name'],

            'phone' => $validated['phone'],

            'email' => $validated['email'] ?? null,

            'address' => $validated['address'] ?? null,

            'remarks' => $validated['remarks'] ?? null,

            'is_active' => true,

        ]);


        /*
         * Stay on create page.
         */
        return redirect()

            ->route('customers.create')

            ->with(
                'success',
                "Customer {$customerCode} created successfully."
            );
    }


    /**
     * Update an existing customer.
     *
     * Used by the customer edit modal.
     */
    public function update(
        Request $request,
        Customer $customer
    ) {

        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

        ]);


        /*
         * Update customer.
         */
        $customer->update([

            'name' => $validated['name'],

            'phone' => $validated['phone'],

            'email' => $validated['email'] ?? null,

            'address' => $validated['address'] ?? null,

            'remarks' => $validated['remarks'] ?? null,

            'is_active' => $request->boolean('is_active'),

        ]);


        /*
         * Return JSON for JavaScript/AJAX.
         */
        return response()->json([

            'success' => true,

            'message' =>
                'Customer updated successfully.',

            'customer' => $customer->fresh(),

        ]);
    }
}