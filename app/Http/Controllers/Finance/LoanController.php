<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLoanRequest;
use App\Models\Customer;
use App\Models\FinanceCapital;
use App\Models\FinanceLoan;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Loans (Sri Lakshmi Micro Finance): give a loan, see it month by month
 * with its collections, and print / download the acknowledgement the
 * customer signs when they receive the money.
 */
class LoanController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * List tabs: running loans (default), closed, all.
     *
     * @var list<string>
     */
    public const STATUSES = ['active', 'closed', 'all'];

    public function index(Request $request): View
    {
        $status = in_array($request->input('status'), self::STATUSES, true) ? $request->input('status') : 'active';
        $search = trim((string) $request->input('q', ''));

        $query = FinanceLoan::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('loan_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->search($search));
            }));

        $loans = (clone $query)
            ->with('customer')
            ->withSum('collections as collected_total', 'amount')
            ->latest('loaned_on')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('finance.loans.index', [
            'loans' => $loans,
            'status' => $status,
            'search' => $search,
            'counts' => [
                'active' => FinanceLoan::query()->where('status', FinanceLoan::STATUS_ACTIVE)->count(),
                'closed' => FinanceLoan::query()->where('status', FinanceLoan::STATUS_CLOSED)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $today = today(config('app.business_timezone'));

        return view('finance.loans.create', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'customer_code', 'name', 'phone', 'remarks']),
            'selectedCustomerId' => $request->integer('customer') ?: null,
            'today' => $today,
            'defaultFirstDue' => [
                FinanceLoan::DAILY => FinanceLoan::defaultFirstDue($today, FinanceLoan::DAILY)->toDateString(),
                FinanceLoan::WEEKLY => FinanceLoan::defaultFirstDue($today, FinanceLoan::WEEKLY)->toDateString(),
            ],
            'available' => FinanceCapital::position()['available'],
            'defaults' => [
                'processing_fee_rate' => FinanceLoan::DEFAULT_PROCESSING_FEE_RATE,
                'gst_rate' => FinanceLoan::DEFAULT_GST_RATE,
                'interest_rate' => FinanceLoan::DEFAULT_INTEREST_RATE,
                'installments' => FinanceLoan::DEFAULT_INSTALLMENTS,
            ],
        ]);
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $loan = FinanceLoan::give($request->validated(), $request->user());

        return redirect()
            ->route('finance.loans.show', $loan)
            ->with('success', "Loan {$loan->loan_number} saved. Print the acknowledgement for the customer to sign.");
    }

    public function show(FinanceLoan $loan): View
    {
        $loan->load(['customer', 'recorder', 'collections.recorder']);

        return view('finance.loans.show', [
            'loan' => $loan,
            'standing' => $loan->standing(),
            'collectedTotal' => $loan->collected(),
            'now' => now(config('app.business_timezone')),
        ]);
    }

    /**
     * The acknowledgement as a page that opens the print dialog.
     */
    public function acknowledgement(FinanceLoan $loan): View
    {
        return view('finance.loans.acknowledgement', $this->acknowledgementData($loan) + ['layout' => 'layouts.print']);
    }

    public function acknowledgementPdf(FinanceLoan $loan): Response
    {
        return Pdf::loadView('finance.loans.acknowledgement', $this->acknowledgementData($loan) + ['layout' => 'pdf.layout'])
            ->setPaper('a5', 'portrait')
            ->download("Loan-Acknowledgement-{$loan->loan_number}.pdf");
    }

    /**
     * Key Fact Statement and Collection Passbook (A4) as a print page.
     */
    public function kfs(FinanceLoan $loan): View
    {
        return view('finance.loans.kfs', self::kfsData($loan) + ['layout' => 'layouts.print']);
    }

    public function kfsPdf(FinanceLoan $loan): Response
    {
        return Pdf::loadView('finance.loans.kfs', self::kfsData($loan) + ['layout' => 'pdf.layout'])
            ->setPaper('a4', 'portrait')
            ->download("KFS-Passbook-{$loan->loan_number}.pdf");
    }

    /**
     * What the Key Fact Statement and passbook show (office and customer).
     *
     * @return array<string, mixed>
     */
    public static function kfsData(FinanceLoan $loan): array
    {
        $loan->load(['customer', 'recorder', 'collections.recorder']);

        return [
            'loan' => $loan,
            'customer' => $loan->customer,
            'schedule' => $loan->schedule(),
            'kfs' => (array) config('app.finance_kfs'),
            'companyName' => 'Micro Finance',
        ];
    }

    /**
     * Only a loan with nothing collected yet can be deleted (a mistake).
     */
    public function destroy(FinanceLoan $loan): RedirectResponse
    {
        if ($loan->collections()->exists()) {
            return back()->with('error', 'This loan has collections, so it cannot be deleted. Cancel the receipts first.');
        }

        $number = $loan->loan_number;
        $loan->delete();

        return redirect()->route('finance.loans.index')->with('success', "Loan {$number} deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function acknowledgementData(FinanceLoan $loan): array
    {
        $loan->load(['customer', 'recorder']);

        return [
            'loan' => $loan,
            'customer' => $loan->customer,
            'lastDue' => $loan->lastDueDate(),
            'lastInstallment' => $loan->lastInstallment(),
            'companyName' => 'Micro Finance',
        ];
    }
}
