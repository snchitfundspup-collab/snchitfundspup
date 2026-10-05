<div class="payment-summary-main">
    <span class="payment-summary-range" data-i18n="overdue_word">Overdue</span>
    <strong class="payment-summary-total dues-total-pending"><x-rupees :amount="$summary['overdue']" /></strong>
    <span class="payment-summary-count">{{ $summary['loans'] }} <span data-i18n="{{ $summary['loans'] === 1 ? 'loan_word' : 'loans_word' }}">{{ $summary['loans'] === 1 ? 'loan' : 'loans' }}</span> · <span data-i18n="balance_word">Balance</span> <x-rupees :amount="$summary['balance']" /></span>
</div>
