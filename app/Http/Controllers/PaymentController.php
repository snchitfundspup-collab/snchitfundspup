<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * Seats per page on the Collect Payment list.
     */
    private const COLLECT_PER_PAGE = 20;

    /**
     * All Payments: receipts, newest first, with search / method / date
     * filters and today's collection.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $method = array_key_exists((string) $request->input('method'), Payment::METHODS) ? $request->input('method') : '';
        $date = $request->date('date')?->toDateString();

        $payments = Payment::query()
            ->with(['customer', 'chitGroup', 'member', 'allocations'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->search($search))
                        ->orWhereHas('chitGroup', fn ($group) => $group->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($method !== '', fn ($query) => $query->where('method', $method))
            ->when($date, fn ($query) => $query->whereDate('paid_at', $date))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $today = today(config('app.business_timezone'))->toDateString();

        $todayTotal = (int) Payment::whereDate('paid_at', $today)->sum('amount');
        $todayCount = Payment::whereDate('paid_at', $today)->count();

        return view('payments.index', compact('payments', 'search', 'method', 'date', 'todayTotal', 'todayCount'));
    }

    /**
     * Collect Payment: a list of seats that still owe money (due, part paid
     * or pending), narrowed by search → pick one → form. Seats paid up to
     * the current month are not listed.
     */
    public function create(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $customer = $request->filled('customer')
            ? Customer::with([
                'memberships' => fn ($query) => $query->orderBy('id'),
                'memberships.chitGroup',
                'memberships.allocations',
            ])->find($request->integer('customer'))
            : null;

        $dueSeats = $customer === null ? $this->seatsToCollect($search) : collect();

        $page = max(1, $request->integer('page', 1));

        $results = (new LengthAwarePaginator(
            $dueSeats->forPage($page, self::COLLECT_PER_PAGE)->values(),
            $dueSeats->count(),
            self::COLLECT_PER_PAGE,
            $page,
            ['path' => route('payments.create')],
        ))->appends(array_filter(['q' => $search]));

        $member = $customer?->memberships->firstWhere('id', $request->integer('member'));

        if ($member && ! $member->chitGroup->isRunning()) {
            $member = null;
        }

        return view('payments.create', compact('search', 'customer', 'results', 'member'));
    }

    /**
     * Seats in running groups with something to collect up to the current
     * month, matching the search (customer name / ID / phone /
     * identification, member ID or group name). Pending (older unpaid
     * months) first, then by name.
     *
     * @return Collection<int, ChitGroupMember>
     */
    private function seatsToCollect(string $search): Collection
    {
        return ChitGroupMember::query()
            ->whereHas('chitGroup', fn ($group) => $group->where('status', ChitGroup::STATUS_RUNNING))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('customer', fn ($customer) => $customer->search($search))
                        ->orWhereHas('chitGroup', fn ($group) => $group->where('name', 'like', "%{$search}%"))
                        ->orWhere('member_code', 'like', "%{$search}%");
                });
            })
            ->with(['customer', 'chitGroup', 'allocations'])
            ->get()
            ->each(fn (ChitGroupMember $seat) => $seat->setAttribute('collection_status', $seat->collectionStatus()))
            ->reject(fn (ChitGroupMember $seat) => $seat->collection_status['state'] === 'clear')
            ->sortBy([
                fn (ChitGroupMember $a, ChitGroupMember $b) => ($b->collection_status['state'] === 'pending') <=> ($a->collection_status['state'] === 'pending'),
                fn (ChitGroupMember $a, ChitGroupMember $b) => strcasecmp($a->customer->name, $b->customer->name),
            ])
            ->values();
    }

    /**
     * Save a payment and open its receipt.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = Payment::record($request->member(), $request->validated(), $request->user());

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', "Payment saved. Receipt {$payment->receipt_number}.");
    }

    /**
     * Receipt page (printable).
     */
    public function show(Payment $payment): View
    {
        $payment->load(['customer', 'chitGroup', 'member.allocations', 'allocations', 'recorder']);

        $payment->member->setRelation('chitGroup', $payment->chitGroup);

        return view('payments.show', compact('payment'));
    }

    /**
     * Download the receipt as a PDF (A5 portrait).
     */
    public function receiptPdf(Payment $payment): Response
    {
        $payment->load(['customer', 'chitGroup', 'member.allocations', 'allocations', 'recorder']);

        $payment->member->setRelation('chitGroup', $payment->chitGroup);

        return Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'balanceNow' => $payment->member->balanceDue(),
        ])
            ->setPaper('a5', 'portrait')
            ->download("Receipt-{$payment->receipt_number}.pdf");
    }

    /**
     * Cancel a receipt entered by mistake: the money is taken off the
     * months it covered.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $receiptNumber = $payment->receipt_number;
        $customerId = $payment->customer_id;
        $memberId = $payment->chit_group_member_id;

        DB::transaction(fn () => $payment->delete());

        return redirect()
            ->route('payments.create', ['customer' => $customerId, 'member' => $memberId])
            ->with('success', "Receipt {$receiptNumber} cancelled.");
    }
}
