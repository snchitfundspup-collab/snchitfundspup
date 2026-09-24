{{-- One seat on the Customer Statement: each month with every payment
     towards it (numbered, with date and time), then the month total and
     balance. --}}

@php
    $group = $seat['group'];
    $member = $seat['member'];
    $wonDraw = $member->wonDraw;
    $statusLabels = ['paid' => 'Paid', 'partial' => 'Part paid', 'due' => 'Pending', 'open' => 'Due', 'upcoming' => 'Upcoming'];
@endphp

<div class="statement-seat">

    <h2 class="winners-group-title">
        {{ $group->name }}
        <small>
            {{ $member->member_code }} ·
            <x-rupees :amount="$group->installment_amount" />/<span data-i18n="month_word">month</span> ·
            <span data-i18n="paid">Paid</span> <x-rupees :amount="$seat['total_paid']" />
            @if ($seat['pending'] > 0)
                · <span class="ledger-total-due"><span data-i18n="collect_state_pending">Pending</span> <x-rupees :amount="$seat['pending']" /></span>
            @endif
            @if ($member->wonDraw)
                · <span data-i18n="won_word">Won</span> <x-rupees :amount="$member->wonDraw->prizeAmount()" /> (M{{ $member->wonDraw->month_number }})
            @endif
        </small>
    </h2>

    <div class="ledger-table-wrapper">

        <table class="ledger-table statement-table">

            <thead>
                <tr>
                    <th>#</th>
                    <th data-i18n="payment_date_time">Date &amp; time</th>
                    <th data-i18n="receipt_no">Receipt</th>
                    <th data-i18n="payment_method">Method</th>
                    <th class="ledger-col-total" data-i18n="amount_rupees">Amount</th>
                </tr>
            </thead>

            @forelse ($seat['months'] as $month)

                @php
                    $monthStatus = $month['status'] === 'upcoming' && $month['collect_status'] === 'due' ? 'open' : $month['status'];
                @endphp

                <tbody class="statement-month">

                    <tr class="statement-month-head">
                        <th colspan="4">
                            <span data-i18n="month_number">Month</span> {{ $month['month'] }}
                            <small>{{ $month['period'] }} · <span data-i18n="due_on">due</span> {{ $month['due_on']->format('d M Y') }}</small>
                        </th>
                        <th class="ledger-col-total">
                            <span class="ledger-status ledger-status-{{ $monthStatus }}" data-i18n="{{ ['open' => 'collect_state_due', 'upcoming' => 'collect_state_upcoming'][$monthStatus] ?? 'ledger_'.$monthStatus }}">{{ $statusLabels[$monthStatus] }}</span>
                        </th>
                    </tr>

                    @forelse ($month['entries'] as $entry)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $entry['payment']->paid_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <a href="{{ route('payments.show', $entry['payment']) }}" class="statement-receipt-link">{{ $entry['payment']->receipt_number }}</a>
                            </td>
                            <td>{{ $entry['payment']->methodLabel() }}</td>
                            <td class="ledger-col-total"><x-rupees :amount="$entry['amount']" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="statement-no-payments" data-i18n="no_payments_yet">No payments yet</td>
                        </tr>
                    @endforelse

                    <tr class="statement-month-total">
                        <td colspan="4">
                            <span data-i18n="paid">Paid</span> <x-rupees :amount="$month['paid']" />
                            <span data-i18n="of">of</span> <x-rupees :amount="$month['installment']" />
                            @if ($month['balance'] > 0)
                                · <span data-i18n="balance">Balance</span>
                                <strong @class(['ledger-total-due' => $month['status'] === 'due'])><x-rupees :amount="$month['balance']" /></strong>
                            @endif
                        </td>
                        <td class="ledger-col-total"><strong><x-rupees :amount="$month['paid']" /></strong></td>
                    </tr>

                </tbody>

            @empty

                <tbody>
                    <tr>
                        <td colspan="5" class="statement-no-payments" data-i18n="no_payments_yet">No payments yet</td>
                    </tr>
                </tbody>

            @endforelse

            <tfoot>
                <tr>
                    <th colspan="4" data-i18n="total_paid">Total paid</th>
                    <td class="ledger-col-total"><strong><x-rupees :amount="$seat['total_paid']" /></strong></td>
                </tr>
            </tfoot>

        </table>

    </div>


    {{-- PRIZE WON: the draw this seat won and its payout, in its own box --}}

    @if ($wonDraw)

        <div class="statement-prize">

            <span class="statement-prize-icon icon-3d icon-3d-orange"><x-icon name="trophy" /></span>

            <div class="statement-prize-body">

                <strong>
                    <span data-i18n="prize_won">Prize won</span>
                    · <span data-i18n="month_number">Month</span> {{ $wonDraw->month_number }}
                    <small>({{ $group->monthPeriodLabel($wonDraw->month_number) }})</small>
                </strong>

                <span>
                    <span data-i18n="drawn_on">Drawn on</span> {{ $wonDraw->drawn_at->format('d M Y, h:i A') }}
                </span>

                @if ($wonDraw->isPaidOut())
                    <span class="statement-prize-paid">
                        <span data-i18n="payout_paid">Paid out</span>
                        {{ $wonDraw->paid_at->format('d M Y, h:i A') }}
                        · {{ $wonDraw->payoutMethodLabel() }}@if ($wonDraw->payout_reference) ({{ $wonDraw->payout_reference }})@endif
                    </span>
                    <span>
                        <span data-i18n="voucher_no">Voucher No.</span>
                        <a href="{{ route('draws.show', $wonDraw) }}" class="statement-receipt-link">{{ $wonDraw->voucher_number }}</a>
                    </span>
                @else
                    <span class="statement-prize-pending">
                        <span data-i18n="payout_pending">Awaiting payout</span>
                    </span>
                    <span>
                        <a href="{{ route('draws.show', $wonDraw) }}" class="statement-receipt-link" data-i18n="view_and_pay">View draw &amp; pay out</a>
                    </span>
                @endif

            </div>

            <strong class="statement-prize-amount"><x-rupees :amount="$wonDraw->prizeAmount()" /></strong>

        </div>

    @endif

</div>
