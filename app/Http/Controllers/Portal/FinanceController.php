<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\CollectionController;
use App\Http\Controllers\Finance\LoanController;
use App\Models\Customer;
use App\Models\FinanceCollection;
use App\Models\FinanceLoan;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Sri Lakshmi Micro Finance for a signed-in customer (read-only): their
 * loans (and their family's on the same phone), what is paid and left,
 * what is due or overdue, and the receipts to download.
 */
class FinanceController extends Controller
{
    public function loans(Request $request): View
    {
        return view('portal.finance.loans', ['loans' => self::loansOf($this->customer($request))]);
    }

    public function loan(Request $request, FinanceLoan $loan): View
    {
        $this->ensureOwn($request, $loan->customer_id);

        $loan->load(['customer', 'collections']);

        return view('portal.finance.loan', [
            'loan' => $loan,
            'standing' => $loan->standing(),
            'schedule' => $loan->schedule(),
            'owner' => count($this->customer($request)->familyIds()) > 1 ? $loan->customer : null,
        ]);
    }

    /**
     * The loan's day-by-day passbook, to print (customers see only the
     * passbook for now, not the Key Fact Statement).
     */
    public function passbookPrint(Request $request, FinanceLoan $loan): View
    {
        $this->ensureOwn($request, $loan->customer_id);

        return view('finance.loans.kfs', LoanController::kfsData($loan) + ['layout' => 'layouts.print', 'passbookOnly' => true]);
    }

    public function passbookPdf(Request $request, FinanceLoan $loan): Response
    {
        $this->ensureOwn($request, $loan->customer_id);

        return Pdf::loadView('finance.loans.kfs', LoanController::kfsData($loan) + ['layout' => 'pdf.layout', 'passbookOnly' => true])
            ->setPaper('a4', 'portrait')
            ->download("Loan-Passbook-{$loan->loan_number}.pdf");
    }

    /**
     * The loans on offer: daily (100 days) and weekly (14 weeks) on the
     * standard terms, with a ₹10,000 example each and a calculator.
     */
    public function plans(): View|RedirectResponse
    {
        /* hidden from customers for now (PORTAL_LOAN_PLANS) */
        if (! config('app.portal_loan_plans')) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.finance.plans', [
            'plans' => self::planCards(),
            'rates' => [
                'fee' => FinanceLoan::DEFAULT_PROCESSING_FEE_RATE,
                'gst' => FinanceLoan::DEFAULT_GST_RATE,
                'interest' => FinanceLoan::DEFAULT_INTEREST_RATE,
                'installments' => FinanceLoan::DEFAULT_INSTALLMENTS,
            ],
        ]);
    }

    /**
     * Daily and weekly loans on the standard terms, each with a ₹10,000
     * example (not saved).
     *
     * @return Collection<int, array{frequency: string, example: FinanceLoan}>
     */
    public static function planCards(): Collection
    {
        $today = today(config('app.business_timezone'));

        return collect(FinanceLoan::FREQUENCIES)->map(function (string $label, string $frequency) use ($today) {
            $example = FinanceLoan::make([
                ...FinanceLoan::terms(10000, FinanceLoan::DEFAULT_PROCESSING_FEE_RATE, FinanceLoan::DEFAULT_GST_RATE, FinanceLoan::DEFAULT_INTEREST_RATE, $frequency, FinanceLoan::DEFAULT_INSTALLMENTS[$frequency]),
                'frequency' => $frequency,
                'loaned_on' => $today->toDateString(),
                'first_due_on' => FinanceLoan::defaultFirstDue($today, $frequency)->toDateString(),
            ]);

            return ['frequency' => $frequency, 'example' => $example];
        })->values();
    }

    public function receiptPdf(Request $request, FinanceCollection $collection): Response
    {
        $this->ensureOwn($request, $collection->customer_id);

        return Pdf::loadView('pdf.finance.receipt', CollectionController::receiptData($collection))
            ->setPaper('a5', 'portrait')
            ->download("Receipt-{$collection->receipt_number}.pdf");
    }

    /**
     * Loans of the customer and their family, running ones first, newest
     * first; with a family, each names its owner.
     *
     * @return Collection<int, array{loan: FinanceLoan, standing: array<string, mixed>, owner: ?Customer}>
     */
    public static function loansOf(Customer $customer): Collection
    {
        $showOwner = count($customer->familyIds()) > 1;

        return FinanceLoan::query()
            ->whereIn('customer_id', $customer->familyIds())
            ->with('customer')
            ->withSum('collections as collected_total', 'amount')
            ->orderBy('status')
            ->latest('loaned_on')
            ->latest('id')
            ->get()
            ->map(fn (FinanceLoan $loan) => [
                'loan' => $loan,
                'standing' => $loan->standing(),
                'owner' => $showOwner ? $loan->customer : null,
            ]);
    }

    private function customer(Request $request): Customer
    {
        return $request->user('customer');
    }

    /**
     * Customers only ever see their own (and their family's) loans.
     */
    private function ensureOwn(Request $request, ?int $customerId): void
    {
        abort_unless(in_array($customerId, $this->customer($request)->familyIds(), true), 404);
    }
}
