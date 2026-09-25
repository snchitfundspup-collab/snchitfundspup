@extends('pdf.layout')

@section('title', 'Receipt '.$receipt->receipt_number.'')

@section('page_margin', '10mm')

@section('font_size', '10px')

@section('doc_title', 'Payment Receipt')

@section('doc_subtitle', $receipt->receipt_number)

@section('styles')
    @include('pdf.traders.partials.bill-styles')
@endsection

@section('content')

    @php $customer = $receipt->customer; @endphp

    <table class="meta">
        <tr>
            <td><span class="label">{{ __('Receipt No.') }}</span><strong>{{ $receipt->receipt_number }}</strong></td>
            <td class="right"><span class="label">{{ __('Date & time') }}</span><strong>{{ $receipt->received_at->format('d M Y, h:i A') }}</strong></td>
        </tr>
    </table>

    <table class="party">
        <tr><th>{{ __('Received from') }}</th><td>{{ $customer->name }}@if (filled($customer->remarks)) ({{ $customer->remarks }})@endif</td></tr>
        <tr><th>{{ __('Customer ID') }}</th><td>{{ $customer->customer_code }}</td></tr>
        <tr><th>{{ __('Phone') }}</th><td>{{ $customer->phone ?: '—' }}</td></tr>
        @if ($receipt->sale)
            <tr><th>{{ __('For invoice') }}</th><td>{{ $receipt->sale->invoice_number }} · {{ $receipt->sale->sold_on->format('d M Y') }}</td></tr>
        @endif
        <tr><th>{{ __('Payment method') }}</th><td>{{ $receipt->methodLabel() }}@if ($receipt->reference) · {{ $receipt->reference }}@endif</td></tr>
        @if ($receipt->notes)
            <tr><th>{{ __('Notes') }}</th><td>{{ $receipt->notes }}</td></tr>
        @endif
    </table>

    <table class="amount-box">
        <tr>
            <td>{{ __('Amount received') }}</td>
            <td class="right"><x-rupees :amount="$receipt->amount" /></td>
        </tr>
    </table>

    <p class="words">{{ $receipt->amountInWords() }}</p>

    <table class="party">
        <tr>
            <th>{{ __($balanceNow < 0 ? 'Advance held' : 'Balance due now') }}</th>
            <td class="right"><strong><x-rupees :amount="abs($balanceNow)" /></strong></td>
        </tr>
    </table>

    <table class="signature">
        <tr>
            <td>{{ __('Recorded by') }}: {{ $receipt->recorder?->name ?? '—' }}</td>
            <td class="right">{{ __('Authorised signature') }}</td>
        </tr>
    </table>

@endsection
