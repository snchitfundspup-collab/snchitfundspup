{{-- Rice bill lines (purchase / sale): variety, bags and rate per bag;
     amount and total worked out by traders.js.
     $varieties, $isSale, $stock (sale: variety id → bags in stock),
     $prefillLines (optional: lines from a customer's order). --}}

@php
    $lines = old('lines', ($prefillLines ?? null) ?: [['variety_id' => '', 'bags' => '', 'rate' => '']]);
    $stock = $stock ?? collect();
@endphp

<div class="bill-lines" data-bill-lines>

    <div class="bill-line-head" aria-hidden="true">
        <span data-i18n="rice_variety">Rice variety</span>
        <span data-i18n="bags_word">Bags</span>
        <span data-i18n="rate_per_bag_rupees">Rate per bag (₹)</span>
        <span class="text-right" data-i18n="amount_word">Amount</span>
        <span></span>
    </div>

    <div data-line-rows>
        @foreach ($lines as $index => $line)
            @include('traders.partials.bill-line', ['index' => $index, 'line' => $line])
        @endforeach
    </div>

    <template id="billLineTemplate">
        @include('traders.partials.bill-line', ['index' => '__INDEX__', 'line' => ['variety_id' => '', 'bags' => '', 'rate' => '']])
    </template>

    <div class="bill-lines-footer">

        <button type="button" class="group-action" data-add-line>
            <x-icon name="plus" />
            <span data-i18n="add_line">Add another rice</span>
        </button>

        <div class="bill-total">
            <span data-i18n="total_word">Total</span>
            <strong id="billTotal">₹0</strong>
            <span id="billBags">0 bags</span>
            @if ($isSale)
                <span class="bill-line-profit" id="billProfit"></span>
            @endif
        </div>

    </div>

</div>
