{{-- Micro Finance: a customer's running loans (what is left) → their loan
     statement; none → give a new loan. $customer (financeLoans: running,
     with collected_total), $mobile (optional) --}}

@php
    $runningLoans = $customer->financeLoans;
    $left = $runningLoans->sum(fn ($loan) => $loan->balance());
@endphp

@if ($runningLoans->isNotEmpty())
    <a href="{{ route('finance.accounts.show', $customer) }}" @class(['view-loans-button', 'mobile-groups-button' => $mobile ?? false])>
        {{ $runningLoans->count() }} <span data-i18n="{{ $runningLoans->count() === 1 ? 'loan_word' : 'loans_word' }}">{{ $runningLoans->count() === 1 ? 'loan' : 'loans' }}</span>
        · <x-rupees :amount="$left" /> <span data-i18n="left_word">left</span>
    </a>
@else
    <a href="{{ route('finance.loans.create', ['customer' => $customer->id]) }}" @class(['view-loans-button', 'is-new', 'mobile-groups-button' => $mobile ?? false])>
        + <span data-i18n="new_loan">New Loan</span>
    </a>
@endif
