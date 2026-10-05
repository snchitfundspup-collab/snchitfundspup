<div class="payment-summary-main">
    <span class="payment-summary-range"><span data-i18n="collected_word">Collected</span> · @include('traders.partials.range-label')</span>
    <strong class="payment-summary-total"><x-rupees :amount="$summary['in']" /></strong>
    <span class="payment-summary-count">{{ $summary['collections'] }} <span data-i18n="receipts_word">receipts</span></span>
</div>
<div class="payment-summary-main">
    <span class="payment-summary-range" data-i18n="lent_in_hand">Lent (in hand)</span>
    <strong class="payment-summary-total"><x-rupees :amount="$summary['out']" /></strong>
    <span class="payment-summary-count">{{ $summary['loans'] }} <span data-i18n="loans_word">loans</span> · <span data-i18n="fee_and_gst">Fee + GST</span> <x-rupees :amount="$summary['cut']" /></span>
</div>
