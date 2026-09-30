<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
     * Customers already using a phone number, for the warning on Add
     * Customer: family may share a phone, the same person may not get a
     * second ID (same_name).
     */
    public function phoneCheck(Request $request): JsonResponse
    {
        $name = Customer::normalizedName($request->string('name')->value());

        $customers = strlen(Customer::phoneDigits($request->string('phone')->value())) < 10
            ? collect()
            : Customer::sharingPhone($request->string('phone')->value(), $request->integer('except') ?: null);

        return response()->json([
            'customers' => $customers->map(fn (Customer $customer) => [
                'code' => $customer->customer_code,
                'name' => $customer->name,
                'remarks' => $customer->remarks,
                'active' => $customer->is_active,
                'same_name' => $name !== '' && Customer::normalizedName($customer->name) === $name,
            ])->values(),
        ]);
    }

    /**
     * The same person (same name and phone number) must not get a second
     * customer ID; different names may share a phone.
     */
    private function notADuplicate(Request $request, ?Customer $customer = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request, $customer) {
            $name = (string) $request->input('name');

            /* editing: only when the name or phone changes, so an older duplicate can still be edited */
            if ($customer
                && Customer::normalizedName($customer->name) === Customer::normalizedName($name)
                && Customer::phoneDigits($customer->phone) === Customer::phoneDigits((string) $value)) {
                return;
            }

            $duplicate = Customer::duplicateOf($name, (string) $value, $customer?->id);

            if ($duplicate) {
                $fail("{$duplicate->name} ({$duplicate->customer_code}".(filled($duplicate->remarks) ? " · {$duplicate->remarks}" : '').') already has this phone number. One person keeps one customer ID — use '.$duplicate->customer_code.'.');
            }
        };
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
                $this->notADuplicate($request),
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
        $sharing = Customer::sharingPhone($validated['phone']);

        $customer = Customer::create([

            'customer_code' => $customerCode,

            'name' => $validated['name'],

            'phone' => $validated['phone'],

            'email' => $validated['email'] ?? null,

            'address' => $validated['address'] ?? null,

            'remarks' => $validated['remarks'] ?? null,

            'is_active' => true,

        ]);

        /* family on one phone keep one password */
        $customer->adoptPhoneLogin();

        $message = "Customer {$customerCode} created successfully.";

        if ($sharing->isNotEmpty()) {
            $message .= ' This phone number is also used by '
                .$sharing->map(fn (Customer $other) => "{$other->name} ({$other->customer_code})")->implode(', ')
                .' — they sign in together and see each other\'s details.';
        }

        /*
         * Stay on create page.
         */
        return redirect()

            ->route('customers.create')

            ->with('success', $message);
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
                $this->notADuplicate($request, $customer),
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

            /* optional: a new password for the customer's own sign-in */
            'password' => [
                'nullable',
                'string',
                'min:6',
                'max:100',
            ],

        ]);

        $phoneChanged = Customer::phoneDigits($customer->phone) !== Customer::phoneDigits($validated['phone']);

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

        /* moved to a phone the family already uses: take its password */
        if ($phoneChanged) {
            $customer->adoptPhoneLogin();
        }

        if (filled($validated['password'] ?? null)) {
            $customer->setPasswordByOffice($validated['password']);
        }

        /*
         * Return JSON for JavaScript/AJAX.
         */
        return response()->json([

            'success' => true,

            'message' => 'Customer updated successfully.',

            'customer' => $customer->fresh(),

            'uses_default_password' => $customer->usesDefaultPassword(),

            'password_state' => $customer->passwordState(),

            /* the password is shared by everyone on the phone */
            'phone_customer_ids' => Customer::sharingPhone($customer->phone)->pluck('id'),

        ]);
    }

    /**
     * Put the customer's sign-in back to the default password — for everyone
     * sharing the phone number.
     */
    public function resetPassword(Customer $customer): JsonResponse
    {
        $customer->resetPassword();

        $sharing = Customer::sharingPhone($customer->phone);

        $message = $sharing->count() > 1
            ? $sharing->pluck('name')->implode(', ').' (same phone) can sign in again with the default password.'
            : "{$customer->name} can sign in again with the default password.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'uses_default_password' => true,
            'password_state' => 'default',
            'phone_customer_ids' => $sharing->pluck('id'),
        ]);
    }
}
