{{-- One of the customer's Sri Lakshmi Micro Finance loans: amount, paid,
     left, and what to pay now (Overdue red / Due today orange).
     $row: loan, standing, owner (family sharing the phone, else null) --}}

@php
    [$loan, $standing] = [$row['loan'], $row['standing']];
@endphp

<a href="{{ route('portal.loans.show', $loan) }}" class="portal-card glass">

    @if ($row['owner'] ?? null)
        @include('portal.partials.owner', ['owner' => $row['owner']])
    @endif

    <div class="portal-card-top">
        <span class="portal-card-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
        <div class="portal-card-title">
            <strong>{{ $loan->loan_number }}</strong>
            <small>{{ $loan->loaned_on->format('d M Y') }} · <x-rupees :amount="$loan->principal" /></small>
        </div>
        @include('finance.partials.state-badge', ['state' => $standing['state']])
    </div>

    <div class="portal-card-facts">
        <span>
            <small data-i18n="{{ $loan->frequency === \App\Models\FinanceLoan::WEEKLY ? 'per_week' : 'per_day' }}">{{ $loan->frequency === \App\Models\FinanceLoan::WEEKLY ? 'Per week' : 'Per day' }}</small>
            <strong><x-rupees :amount="$loan->installment_amount" /></strong>
        </span>
        <span>
            <small data-i18n="paid">Paid</small>
            <strong>{{ $standing['installments_paid'] }} / {{ $loan->installments }}</strong>
        </span>
        <span>
            <small data-i18n="total_paid">Total paid</small>
            <strong><x-rupees :amount="$loan->collected()" /></strong>
        </span>
        <span>
            <small data-i18n="balance_word">Balance</small>
            <strong><x-rupees :amount="$loan->balance()" /></strong>
        </span>
        @if ($standing['to_collect'] > 0)
            <span>
                <small data-i18n="to_pay_now">To pay now</small>
                <strong @class(['ledger-total-due' => $standing['overdue'] > 0, 'ledger-total-open' => $standing['overdue'] === 0])><x-rupees :amount="$standing['to_collect']" /></strong>
            </span>
        @endif
    </div>

    @if ($standing['next_due'] && ! $loan->isClosed() && $standing['to_collect'] === 0)
        <p class="portal-card-note">
            <span data-i18n="next_due">Next due</span>: {{ $standing['next_due']->format('d M Y') }}
        </p>
    @endif

</a>
