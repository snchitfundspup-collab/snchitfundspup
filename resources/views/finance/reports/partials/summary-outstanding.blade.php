<div class="payment-summary-main">
    <span class="payment-summary-range" data-i18n="money_outstanding">Money with customers</span>
    <strong class="payment-summary-total"><x-rupees :amount="$summary['balance']" /></strong>
    <span class="payment-summary-count">{{ $summary['loans'] }} <span data-i18n="running_loans">running loans</span> · {{ $summary['customers'] }} <span data-i18n="customers_word">customers</span></span>
</div>
<div class="payment-summary-main">
    <span class="payment-summary-range" data-i18n="overdue_word">Overdue</span>
    <strong class="payment-summary-total dues-total-pending"><x-rupees :amount="$summary['overdue']" /></strong>
</div>
