@extends('pdf.layout')

@section('title', 'Receipt '.$collection->receipt_number)

@section('page_margin', '10mm')

@section('font_size', '10px')

@section('doc_title', 'Payment Receipt')

@section('doc_subtitle', $collection->receipt_number)

@section('styles')
    @include('pdf.traders.partials.bill-styles')
@endsection

@section('content')

    @php $customer = $collection->customer; @endphp

    <table class="meta">
        <tr>
            <td><span class="label">{{ __('Receipt No.') }}</span><strong>{{ $collection->receipt_number }}</strong></td>
            <td class="right"><span class="label">{{ __('Date & time') }}</span><strong>{{ $collection->collected_at->format('d M Y, h:i A') }}</strong></td>
        </tr>
    </table>

    <table class="party">
        <tr><th>{{ __('Received from') }}</th><td>{{ $customer->name }}@if (filled($customer->remarks)) ({{ $customer->remarks }})@endif</td></tr>
        <tr><th>{{ __('Customer ID') }}</th><td>{{ $customer->customer_code }}</td></tr>
        <tr><th>{{ __('Phone') }}</th><td>{{ $customer->phone ?: '—' }}</td></tr>
        <tr><th>{{ __('For loan') }}</th><td>{{ $loan->loan_number }} · <x-rupees :amount="$loan->principal" /> · {{ $loan->loaned_on->format('d M Y') }}</td></tr>
        <tr><th>{{ __('Payment method') }}</th><td>{{ $collection->methodLabel() }}@if ($collection->reference) · {{ $collection->reference }}@endif</td></tr>
        @if ($collection->notes)
            <tr><th>{{ __('Notes') }}</th><td>{{ $collection->notes }}</td></tr>
        @endif
    </table>

    <table class="amount-box">
        <tr>
            <td>{{ __('Amount received') }}</td>
            <td class="right"><x-rupees :amount="$collection->amount" /></td>
        </tr>
    </table>

    <p class="words">{{ $collection->amountInWords() }}</p>

    <table class="party">
        <tr>
            <th>{{ __('Loan balance after this') }}</th>
            <td class="right"><strong><x-rupees :amount="$balanceAfter" /></strong></td>
        </tr>
    </table>

    <table class="signature">
        <tr>
            <td>{{ __('Recorded by') }}: {{ $collection->recorder?->name ?? '—' }}</td>
            <td class="right">
                <x-signature :user="$collection->recorder" :height="34" /><br>
                {{ __('Authorised signature') }}
            </td>
        </tr>
    </table>

@endsection
