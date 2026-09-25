@extends('pdf.layout')

@section('title', 'Receipt '.$payment->receipt_number)

@section('page_margin', '10mm')

@section('doc_title', 'Payment Receipt')

@section('doc_subtitle', $payment->receipt_number)

@section('styles')
    .meta td {
        padding: 6px 0;
        border-bottom: 1px dashed #9ca3af;
    }

    .meta .label {
        font-size: 8px;
        font-weight: bold;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: #4b5563;
    }

    .meta .value {
        font-size: 13px;
        font-weight: bold;
    }

    .lines th,
    .lines td {
        padding: 6px 0;
        border-bottom: 1px dotted #9ca3af;
        text-align: left;
        vertical-align: top;
    }

    .lines th {
        width: 36%;
        font-weight: normal;
        color: #4b5563;
    }

    .lines td {
        font-weight: bold;
    }

    .amount-box {
        margin-top: 12px;
        border: 2px solid #111827;
    }

    .amount-box td {
        padding: 10px 12px;
    }

    .amount-label {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .amount-figure {
        font-size: 22px;
        font-weight: bold;
        text-align: right;
    }

    .amount-words {
        font-style: italic;
        color: #4b5563;
        padding-top: 0 !important;
    }

    .footer td {
        padding-top: 22px;
        vertical-align: bottom;
    }

    .footer .label {
        display: block;
        font-size: 8px;
        color: #4b5563;
    }

    .due {
        color: #b91c1c;
    }

    .open {
        color: #c2410c;
    }

    .signature {
        border-top: 1px solid #111827;
        padding-top: 3px;
        text-align: center;
        font-size: 8px;
        color: #4b5563;
    }

    .thanks {
        margin-top: 14px;
        text-align: center;
        font-size: 9px;
        color: #4b5563;
    }
@endsection

@section('content')

    <table class="meta">
        <tr>
            <td>
                <div class="label">{{ __('Receipt No.') }}</div>
                <div class="value">{{ $payment->receipt_number }}</div>
            </td>
            <td class="right">
                <div class="label">{{ __('Date & time') }}</div>
                <div class="value">{{ $payment->paid_at->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>


    <table class="lines">
        <tr>
            <th>{{ __('Received from') }}</th>
            <td>
                {{ $payment->customer->name }}
                @if (filled($payment->customer->remarks))
                    <span class="ident">({{ $payment->customer->remarks }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('Customer ID') }}</th>
            <td>{{ $payment->customer->customer_code }}</td>
        </tr>
        <tr>
            <th>{{ __('Phone') }}</th>
            <td>{{ $payment->customer->phone }}</td>
        </tr>
        <tr>
            <th>{{ __('Group') }}</th>
            <td>{{ $payment->chitGroup->name }}</td>
        </tr>
        <tr>
            <th>{{ __('Member ID') }}</th>
            <td>{{ $payment->member->member_code }}</td>
        </tr>
        <tr>
            <th>{{ __('For') }}</th>
            <td>
                @foreach ($payment->allocations as $allocation)
                    {{ __('Month') }} {{ $allocation->month_number }}
                    ({{ $payment->chitGroup->monthPeriodLabel($allocation->month_number) }}):
                    <x-rupees :amount="$allocation->amount" />
                    @if ($allocation->amount < $payment->chitGroup->installment_amount)
                        <span class="ident">{{ __('(part)') }}</span>
                    @endif
                    <br>
                @endforeach
            </td>
        </tr>
        <tr>
            <th>{{ __('Payment method') }}</th>
            <td>
                {{ $payment->methodLabel() }}
                @if ($payment->reference)
                    · {{ $payment->reference }}
                @endif
            </td>
        </tr>
        @if ($payment->notes)
            <tr>
                <th>{{ __('Notes') }}</th>
                <td>{{ $payment->notes }}</td>
            </tr>
        @endif
    </table>


    <table class="amount-box">
        <tr>
            <td class="amount-label">{{ __('Amount received') }}</td>
            <td class="amount-figure"><x-rupees :amount="$payment->amount" /></td>
        </tr>
        <tr>
            <td
                class="amount-words"
                colspan="2"
            >{{ $payment->amountInWords() }}</td>
        </tr>
    </table>


    <table class="footer">
        <tr>
            <td style="width: 32%;">
                <span class="label">{{ __('Balance due now') }}</span>
                <strong @class(['due' => $hasPending ?? false, 'open' => ! ($hasPending ?? false) && $balanceNow > 0])><x-rupees :amount="$balanceNow" /></strong>
            </td>
            <td style="width: 32%;">
                <span class="label">{{ __('Recorded by') }}</span>
                <strong>{{ $payment->recorder?->name ?? '—' }}</strong>
            </td>
            <td>
                <div class="signature">{{ __('Authorised signature') }}</div>
            </td>
        </tr>
    </table>

    <div class="thanks">{{ __('Thank you for your payment.') }}</div>

@endsection
