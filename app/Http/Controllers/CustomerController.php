<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Sort options for the customer list. The first one is the default.
     *
     * @var list<string>
     */
    public const SORT_OPTIONS = [
        'newest',
        'oldest',
        'name_asc',
        'name_desc',
        'code_asc',
        'code_desc',
        'active_first',
        'inactive_first',
    ];

    /**
     * Display customers.
     *
     * Includes:
     * - Search
     * - Sorting
     * - Pagination
     * - Search + sort preserved while changing pages
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $sort = in_array($request->input('sort'), self::SORT_OPTIONS, true)
            ? $request->input('sort')
            : self::SORT_OPTIONS[0];

        $customers = Customer::query()

            ->search($search)

            ->tap(fn (Builder $query) => $this->applySort($query, $sort))

            ->paginate(10)

            ->withQueryString();

        return view(
            'customers.index',
            compact(
                'customers',
                'search',
                'sort'
            )
        );
    }

    /**
     * Apply one of the SORT_OPTIONS to the customer query. Ties fall back
     * to newest first so the order is always stable across pages.
     *
     * @param  Builder<Customer>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('id'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),

            /*
             * Codes are "SN" + number, so sort by length first:
             * SN9999 must come before SN10000.
             */
            'code_asc' => $query->orderByRaw('LENGTH(customer_code)')->orderBy('customer_code'),
            'code_desc' => $query->orderByRaw('LENGTH(customer_code) DESC')->orderByDesc('customer_code'),

            'active_first' => $query->orderByDesc('is_active')->orderBy('name'),
            'inactive_first' => $query->orderBy('is_active')->orderBy('name'),

            default => null,
        };

        $query->orderByDesc('id');
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

        $customerCode = 'SN'.$nextNumber;

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

            'message' => 'Customer updated successfully.',

            'customer' => $customer->fresh(),

        ]);
    }
}
