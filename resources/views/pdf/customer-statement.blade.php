@extends($layout ?? 'pdf.layout')

@section('title', 'Customer Statement – '.$customer->name)

@section('page_margin', '12mm')

@section('font_size', '9.5px')

@section('doc_title', 'Customer Statement')

@section('doc_subtitle', now(config('app.business_timezone'))->format('d M Y, h:i A'))

@section('styles')
    .info td {
        padding: 2px 16px 8px 0;
        vertical-align: top;
        width: auto;
    }

    .info .label {
        display: block;
        font-size: 7px;
        font-weight: bold;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: #4b5563;
    }

    .info strong {
        font-size: 11px;
    }

    .seat-title {
        margin: 12px 0 4px;
        font-size: 12px;
        font-weight: bold;
    }

    .seat-title small {
        font-size: 8.5px;
        font-weight: normal;
        color: #4b5563;
    }

    .grid {
        border: 1px solid #111827;
    }

    .grid th,
    .grid td {
        border: 1px solid #9ca3af;
        padding: 3px 5px;
        text-align: left;
        vertical-align: top;
    }

    .grid thead th,
    .grid tfoot th,
    .grid tfoot td {
        background: #f1f3f6;
        font-weight: bold;
    }

    .month-head th {
        background: #e5e7eb;
        font-weight: bold;
    }

    .month-head small {
        font-weight: normal;
        color: #4b5563;
    }

    .month-total td {
        background: #fafafa;
        font-size: 8.5px;
    }

    .amount {
        text-align: right !important;
        white-space: nowrap;
    }

    .none {
        color: #6b7280;
        font-style: italic;
    }

    .status-paid { color: #166534; font-weight: bold; }
    .status-partial { color: #9a3412; font-weight: bold; }
    .status-due { color: #b91c1c; font-weight: bold; }
    .status-open { color: #c2410c; font-weight: bold; }
    .status-upcoming { color: #6b7280; font-weight: bold; }

    .pending {
        color: #b91c1c;
        font-weight: bold;
    }

    tr {
        page-break-inside: avoid;
    }

    .prize {
        color: #b45309;
        font-weight: bold;
    }

    .prize-box {
        margin-top: 6px;
        border: 1.5px solid #b45309;
        background: #fff7ed;
    }

    .prize-box td {
        padding: 6px 8px;
        vertical-align: middle;
    }

    .prize-box .amount {
        width: 22%;
        font-size: 12px;
    }

    .prize-box .meta {
        display: block;
        font-size: 8px;
        color: #4b5563;
    }

    .prize-box .meta.pending {
        color: #b91c1c;
    }
@endsection

@section('content')

    @php
        $statusLabels = ['paid' => 'Paid', 'partial' => 'Part paid', 'due' => 'Pending', 'open' => 'Due', 'upcoming' => 'Upcoming'];
    @endphp

    <table class="info">
        <tr>
            <td>
                <span class="label">{{ __('Name') }}</span>
                <strong>{{ $customer->name }}</strong>
                @if (filled($customer->remarks))
                    <span class="ident">({{ $customer->remarks }})</span>
                @endif
            </td>
            <td><span class="label">{{ __('Customer ID') }}</span><strong>{{ $customer->customer_code }}</strong></td>
            <td><span class="label">{{ __('Phone') }}</span><strong>{{ $customer->phone ?: '—' }}</strong></td>
            <td><span class="label">{{ __('Total paid') }}</span><strong><x-rupees :amount="$seats->sum('total_paid')" /></strong></td>
            <td><span class="label">{{ __('Pending') }}</span><strong class="pending"><x-rupees :amount="$seats->sum('pending')" /></strong></td>
            @if ($seats->contains(fn ($seat) => $seat['member']->wonDraw))
                <td><span class="label">{{ __('Prizes won') }}</span><strong class="prize"><x-rupees :amount="$seats->sum(fn ($seat) => $seat['member']->wonDraw?->prizeAmount() ?? 0)" /></strong></td>
            @endif
        </tr>
        @if ($customer->address)
            <tr>
                <td colspan="5"><span class="label">{{ __('Address') }}</span>{{ $customer->address }}</td>
            </tr>
        @endif
    </table>

    @forelse ($seats as $seat)

        @php
            $group = $seat['group'];
            $member = $seat['member'];
        @endphp

        <div class="seat-title">
            {{ $group->name }}
            <small>
                · {{ $member->member_code }} · <x-rupees :amount="$group->installment_amount" />/{{ __('month') }} · {{ __('Paid') }} <x-rupees :amount="$seat['total_paid']" />
                @if ($seat['pending'] > 0)
                    · <span class="pending">{{ __('Pending') }} <x-rupees :amount="$seat['pending']" /></span>
                @endif
                @if ($member->wonDraw)
                    · {{ __('Won') }} <x-rupees :amount="$member->wonDraw->prizeAmount()" /> (M{{ $member->wonDraw->month_number }})
                @endif
            </small>
        </div>

        <table class="grid">

            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th>{{ __('Date & time') }}</th>
                    <th>{{ __('Receipt') }}</th>
                    <th>{{ __('Method') }}</th>
                    <th class="amount">{{ __('Amount') }}</th>
                </tr>
            </thead>

            <tbody>

                @forelse ($seat['months'] as $month)

                    @php
                        $monthStatus = $month['status'] === 'upcoming' && $month['collect_status'] === 'due' ? 'open' : $month['status'];
                    @endphp

                    <tr class="month-head">
                        <th colspan="4">
                            {{ __('Month') }} {{ $month['month'] }}
                            <small>· {{ $month['period'] }} · {{ __('due') }} {{ $month['due_on']->format('d M Y') }}</small>
                        </th>
                        <th class="amount"><span class="status-{{ $monthStatus }}">{{ __($statusLabels[$monthStatus]) }}</span></th>
                    </tr>

                    @forelse ($month['entries'] as $entry)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $entry['payment']->paid_at->format('d M Y, h:i A') }}</td>
                            <td>{{ $entry['payment']->receipt_number }}</td>
                            <td>{{ $entry['payment']->methodLabel() }}</td>
                            <td class="amount"><x-rupees :amount="$entry['amount']" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="none">{{ __('No payments yet') }}</td>
                        </tr>
                    @endforelse

                    <tr class="month-total">
                        <td colspan="4">
                            {{ __('Paid') }} <x-rupees :amount="$month['paid']" /> {{ __('of') }} <x-rupees :amount="$month['installment']" />
                            @if ($month['balance'] > 0) · {{ __('Balance') }} <strong @class(['pending' => $month['status'] === 'due'])><x-rupees :amount="$month['balance']" /></strong>
                            @endif
                        </td>
                        <td class="amount"><strong><x-rupees :amount="$month['paid']" /></strong></td>
                    </tr>


                @empty

                    <tr>
                        <td colspan="5" class="none">{{ __('No payments yet') }}</td>
                    </tr>

                @endforelse

            </tbody>

            <tfoot>
                <tr>
                    <th colspan="4">{{ __('Total paid') }}</th>
                    <td class="amount"><x-rupees :amount="$seat['total_paid']" /></td>
                </tr>
            </tfoot>

        </table>

        @if ($wonDraw = $member->wonDraw)
            <table class="prize-box">
                <tr>
                    <td>
                        <strong>{{ __('Prize won') }} · {{ __('Month') }} {{ $wonDraw->month_number }}</strong>
                        <span class="meta">({{ $group->monthPeriodLabel($wonDraw->month_number) }}) · Drawn on {{ $wonDraw->drawn_at->format('d M Y, h:i A') }}</span>
                        @if ($wonDraw->isPaidOut())
                            <span class="meta">Paid out {{ $wonDraw->paid_at->format('d M Y, h:i A') }} · {{ $wonDraw->payoutMethodLabel() }}@if ($wonDraw->payout_reference) ({{ $wonDraw->payout_reference }})@endif · Voucher No. {{ $wonDraw->voucher_number }}</span>
                        @else
                            <span class="meta pending">{{ __('Awaiting payout') }}</span>
                        @endif
                    </td>
                    <td class="amount prize">
                        <x-rupees :amount="$wonDraw->prizeAmount()" />
                    </td>
                </tr>
            </table>
        @endif


    @empty

        <p class="none">{{ __('This customer is not in any started group yet.') }}</p>

    @endforelse

@endsection
