<?php

namespace App\Http\Controllers;

use App\Models\ChitGroup;
use App\Models\ChitGroupMember;
use App\Models\Customer;
use App\Models\PaymentAllocation;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Customer Statement (Reports): find a customer by name, then see every
 * group they are in, month by month, with each payment towards that month
 * on its own line (Month 2: 16 Sep ₹200, 17 Sep ₹300 …), the month's total,
 * balance and status. Printable and downloadable as an A4 PDF.
 */
class CustomerStatementController extends Controller
{
    private const SEARCH_LIMIT = 20;

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $customer = $request->filled('customer')
            ? Customer::find($request->integer('customer'))
            : null;

        $results = $customer === null && $search !== ''
            ? Customer::query()
                ->search($search)
                ->whereHas('memberships')
                ->withCount('memberships')
                ->orderBy('name')
                ->limit(self::SEARCH_LIMIT)
                ->get()
            : collect();

        return view('reports.customer-statement', [
            'search' => $search,
            'customer' => $customer,
            'results' => $results,
            'seats' => $customer ? self::seatsFor($customer) : collect(),
        ]);
    }

    /**
     * Download the statement shown on screen.
     */
    public function pdf(Customer $customer): Response
    {
        return Pdf::loadView('pdf.customer-statement', [
            'customer' => $customer,
            'seats' => self::seatsFor($customer),
        ])
            ->setPaper('a4', 'portrait')
            ->download('Statement-'.$customer->customer_code.'-'.Str::slug($customer->name).'.pdf');
    }

    /**
     * Each seat in a started group with its months: the months that have
     * payments, are pending or are due now, each listing its payments in
     * date order.
     *
     * @return Collection<int, array{member: ChitGroupMember, group: ChitGroup, months: list<array<string, mixed>>, total_paid: int, pending: int, state: string}>
     */
    public static function seatsFor(Customer $customer): Collection
    {
        return $customer->memberships()
            ->with(['chitGroup', 'allocations.payment', 'wonDraw'])
            ->whereHas('chitGroup', fn ($group) => $group->whereIn('status', [ChitGroup::STATUS_RUNNING, ChitGroup::STATUS_COMPLETED]))
            ->orderBy('id')
            ->get()
            ->map(function (ChitGroupMember $member) {
                $entriesByMonth = $member->allocations
                    ->sortBy(fn (PaymentAllocation $allocation) => [$allocation->payment->paid_at->timestamp, $allocation->payment_id])
                    ->groupBy('month_number');

                /* month => pending / due / upcoming for the months that can be collected now */
                $collectStatuses = collect($member->collectableMonths())->pluck('status', 'month')->all();
                $collectableMonths = array_keys($collectStatuses);
                $status = $member->collectionStatus();

                $months = collect($member->ledger())
                    ->filter(fn (array $row) => $row['paid'] > 0 || $row['status'] === 'due' || in_array($row['month'], $collectableMonths, true))
                    ->map(fn (array $row) => $row + [
                        'collect_status' => $collectStatuses[$row['month']] ?? null,
                        'entries' => ($entriesByMonth[$row['month']] ?? collect())
                            ->map(fn (PaymentAllocation $allocation) => [
                                'payment' => $allocation->payment,
                                'amount' => $allocation->amount,
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all();

                return [
                    'member' => $member,
                    'group' => $member->chitGroup,
                    'months' => $months,
                    'total_paid' => $member->totalPaid(),
                    'pending' => $status['pending'],
                    'state' => $status['state'],
                ];
            });
    }
}
