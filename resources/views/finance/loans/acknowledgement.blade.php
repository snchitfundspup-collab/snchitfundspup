{{-- Loan acknowledgement the customer signs when they receive the money:
     the loan, the amount cut, the amount in hand and the repayment plan.
     Opens as a print page ($layout = layouts.print) or a PDF. --}}
@extends($layout ?? 'pdf.layout')

@section('title', 'Loan Acknowledgement '.$loan->loan_number)

@section('page_margin', '10mm')

@section('font_size', '10px')

@section('doc_title', 'Loan Acknowledgement')

@section('doc_subtitle', $loan->loan_number)

@section('styles')
    @include('pdf.traders.partials.bill-styles')

    .ack-text {
        margin: 10px 0;
        line-height: 1.6;
    }

    .signature td {
        width: 50%;
        vertical-align: bottom;
    }
@endsection

@section('content')

    <table class="meta">
        <tr>
            <td><span class="label">{{ __('Loan No.') }}</span><strong>{{ $loan->loan_number }}</strong></td>
            <td class="right"><span class="label">{{ __('Loan date') }}</span><strong>{{ $loan->loaned_on->format('d M Y') }}</strong></td>
        </tr>
    </table>

    <table class="party">
        <tr><th>{{ __('Borrower') }}</th><td>{{ $customer->name }}@if (filled($customer->remarks)) ({{ $customer->remarks }})@endif</td></tr>
        <tr><th>{{ __('Customer ID') }}</th><td>{{ $customer->customer_code }}</td></tr>
        <tr><th>{{ __('Phone') }}</th><td>{{ $customer->phone ?: '—' }}</td></tr>
        @if (filled($customer->address))
            <tr><th>{{ __('Address') }}</th><td>{{ $customer->address }}</td></tr>
        @endif
    </table>

    <table class="party">
        <tr><th>{{ __('Loan amount') }}</th><td class="right"><x-rupees :amount="$loan->principal" /></td></tr>
        <tr><th>{{ __('Less: processing fee') }} ({{ \App\Models\FinanceLoan::rate($loan->processing_fee_rate) }})</th><td class="right">− <x-rupees :amount="$loan->processing_fee" /></td></tr>
        <tr><th>{{ __('Less: GST on the fee') }} ({{ \App\Models\FinanceLoan::rate($loan->gst_rate) }})</th><td class="right">− <x-rupees :amount="$loan->gst" /></td></tr>
    </table>

    <table class="amount-box">
        <tr>
            <td>{{ __('Amount received in hand') }}</td>
            <td class="right"><x-rupees :amount="$loan->amountGiven()" /></td>
        </tr>
    </table>

    <p class="words">{{ $loan->amountGivenInWords() }}</p>

    <table class="party">
        <tr><th>{{ __('Loan amount') }}</th><td class="right"><x-rupees :amount="$loan->principal" /></td></tr>
        <tr><th>{{ __('Add: interest') }} ({{ \App\Models\FinanceLoan::rate($loan->interest_rate) }} {{ __('a year') }}, {{ $loan->term_days }} {{ __('days') }})</th><td class="right">+ <x-rupees :amount="$loan->interest" /></td></tr>
        <tr><th><strong>{{ __('Total to repay') }}</strong></th><td class="right"><strong><x-rupees :amount="$loan->loan_amount" /></strong></td></tr>
    </table>

    <table class="party">
        <tr><th>{{ __('Repayment') }}</th><td>{{ $loan->frequencyLabel() }}</td></tr>
        <tr>
            <th>{{ __('Instalments') }}</th>
            <td>
                {{ $loan->installments }} × <x-rupees :amount="$loan->installment_amount" />@if ($lastInstallment !== $loan->installment_amount) ({{ __('last one') }} <x-rupees :amount="$lastInstallment" />)@endif
            </td>
        </tr>
        <tr><th>{{ __('First instalment on') }}</th><td>{{ $loan->first_due_on->format('d M Y') }}</td></tr>
        <tr><th>{{ __('Last instalment on') }}</th><td>{{ $lastDue->format('d M Y') }}</td></tr>
        @if ($loan->notes)
            <tr><th>{{ __('Notes') }}</th><td>{{ $loan->notes }}</td></tr>
        @endif
    </table>

    <p class="ack-text">
        {{ __('I, :name, have received :amount in hand from Sri Lakshmi Micro Finance on :date. I agree to repay :total in :count :frequency instalments as shown above.', [
            'name' => $customer->name,
            'amount' => '₹'.number_format($loan->amountGiven()),
            'date' => $loan->loaned_on->format('d M Y'),
            'total' => '₹'.number_format($loan->loan_amount),
            'count' => $loan->installments,
            'frequency' => mb_strtolower($loan->frequencyLabel()),
        ]) }}
    </p>

    <table class="signature">
        <tr>
            <td>
                <br><br>
                {{ __('Signature of the borrower') }}
            </td>
            <td class="right">
                <x-signature :user="$loan->recorder" :height="34" /><br>
                {{ __('Authorised signature') }}
            </td>
        </tr>
    </table>

@endsection
