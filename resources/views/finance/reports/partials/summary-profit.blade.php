<div class="payment-summary-main">
    <span class="payment-summary-range"><span data-i18n="net_profit">Net profit</span> · @include('traders.partials.range-label')</span>
    <strong @class(['payment-summary-total', 'dues-total-pending' => $summary['net'] < 0])><x-rupees :amount="$summary['net']" /></strong>
    <span class="payment-summary-count"><span data-i18n="income_word">Income</span> <x-rupees :amount="$summary['income']" /></span>
</div>
