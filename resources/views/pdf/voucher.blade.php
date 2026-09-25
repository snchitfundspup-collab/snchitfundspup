@extends('pdf.layout')

@section('title', 'Voucher '.$draw->voucher_number)

@section('page_margin', '10mm')

@section('doc_title', 'Prize Payout Voucher')

@section('doc_subtitle', $draw->voucher_number)

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

    .signatures td {
        padding-top: 34px;
        vertical-align: bottom;
        width: 33%;
    }

    .signature {
        border-top: 1px solid #111827;
        padding-top: 3px;
        text-align: center;
        font-size: 8px;
        color: #4b5563;
    }

    .recorded {
        text-align: center;
        font-size: 8px;
        color: #4b5563;
    }

    .recorded strong {
        display: block;
        font-size: 11px;
        color: #111827;
    }

    .thanks {
        margin-top: 14px;
        text-align: center;
        font-size: 9px;
        color: #4b5563;
    }
@endsection

@section('content')

    @php
        $group = $draw->chitGroup;
        $customer = $draw->winner->customer;
    @endphp

    <table class="meta">
        <tr>
            <td>
                <div class="label">{{ __('Voucher No.') }}</div>
                <div class="value">{{ $draw->voucher_number }}</div>
            </td>
            <td class="right">
                <div class="label">{{ __('Date & time') }}</div>
                <div class="value">{{ $draw->paid_at->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>


    <table class="lines">
        <tr>
            <th>{{ __('Paid to') }}</th>
            <td>
                {{ $customer->name }}
                @if (filled($customer->remarks))
                    <span class="ident">({{ $customer->remarks }})</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>{{ __('Customer ID') }}</th>
            <td>{{ $customer->customer_code }}</td>
        </tr>
        <tr>
            <th>{{ __('Phone') }}</th>
            <td>{{ $customer->phone }}</td>
        </tr>
        <tr>
            <th>{{ __('Group') }}</th>
            <td>{{ $group->name }}</td>
        </tr>
        <tr>
            <th>{{ __('Member ID') }}</th>
            <td>{{ $draw->winner->member_code }}</td>
        </tr>
        <tr>
            <th>{{ __('Draw') }}</th>
            <td>
                {{ __('Month') }} {{ $draw->month_number }} ({{ $group->monthPeriodLabel($draw->month_number) }})
                · Drawn on {{ $draw->drawn_at->format('d M Y') }}
            </td>
        </tr>
        <tr>
            <th>{{ __('Payment method') }}</th>
            <td>
                {{ $draw->payoutMethodLabel() }}
                @if ($draw->payout_reference)
                    · {{ $draw->payout_reference }}
                @endif
            </td>
        </tr>
        @if ($draw->payout_notes)
            <tr>
                <th>{{ __('Notes') }}</th>
                <td>{{ $draw->payout_notes }}</td>
            </tr>
        @endif
    </table>


    <table class="amount-box">
        <tr>
            <td class="amount-label">{{ __('Amount paid') }}</td>
            <td class="amount-figure"><x-rupees :amount="$draw->payout_amount" /></td>
        </tr>
        <tr>
            <td
                class="amount-words"
                colspan="2"
            >{{ $draw->payoutInWords() }}</td>
        </tr>
    </table>


    <table class="signatures">
        <tr>
            <td><div class="signature">{{ __('Received by (customer signature)') }}</div></td>
            <td>
                <div class="recorded">
                    {{ __('Recorded by') }}
                    <strong>{{ $draw->paidBy?->name ?? '—' }}</strong>
                </div>
            </td>
            <td><div class="signature">{{ __('Authorised signature') }}</div></td>
        </tr>
    </table>

    <div class="thanks">{{ __('Congratulations on your winning draw.') }}</div>

@endsection
