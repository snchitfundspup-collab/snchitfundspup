{{-- The group's withdrawal plan, folded away until opened ($planOpen to
     start open): each month's date and the amount paid out that month.
     Months whose withdrawal is over are muted and marked Done; the month
     this customer won is green. Other members' names are never shown.
     $plan: month, date, withdrawal, over, mine --}}

@php
    $overCount = $plan->where('over', true)->count();
@endphp

<details class="group-panel glass payment-step portal-plan" @if ($planOpen ?? false) open @endif>

    <summary class="portal-plan-summary">
        <span class="portal-plan-title">
            <span data-i18n="withdrawal_plan">Withdrawal plan</span>
            <small>
                {{ $plan->count() }} <span data-i18n="months_word">months</span>
                @if ($overCount > 0)
                    · {{ $overCount }} <span data-i18n="done_word">done</span>
                @endif
            </small>
        </span>
        <x-icon name="chevron-down" class="portal-plan-caret" />
    </summary>

    <div class="ledger-table-wrapper">
        <table class="ledger-table statement-table portal-table">
            <thead>
                <tr>
                    <th data-i18n="month_number">Month</th>
                    <th data-i18n="date">Date</th>
                    <th class="ledger-col-total" data-i18n="withdrawal_amount">Withdrawal amount</th>
                    <th data-i18n="status">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plan as $row)
                    <tr @class(['portal-plan-mine' => $row['mine'], 'portal-plan-over' => $row['over'] && ! $row['mine']])>
                        <td data-label="Month"><strong>{{ $row['month'] }}</strong></td>
                        <td data-label="Date">{{ $row['date']?->format('d M Y') ?? '—' }}</td>
                        <td class="ledger-col-total" data-label="Withdrawal"><strong><x-rupees :amount="$row['withdrawal']" /></strong></td>
                        <td data-label="Status">
                            @if ($row['mine'])
                                <span class="due-badge due-badge-ok" data-i18n="you_won">You won</span>
                            @elseif ($row['over'])
                                <span class="due-badge due-badge-upcoming" data-i18n="done_title">Done</span>
                            @else
                                <span class="portal-plan-dash">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</details>
