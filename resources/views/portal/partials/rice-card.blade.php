{{-- A rice brand card: name, bag size, price per bag and whether it is in
     stock; with $orderable, a bags box to add it to the order.
     $card: variety, available --}}

@php
    $variety = $card['variety'];
@endphp

<div @class(['portal-card', 'glass', 'rice-card', 'is-out' => ! $card['available']])>

    <div class="portal-card-top">
        <span class="portal-card-icon icon-3d icon-3d-green"><x-icon name="package" /></span>
        <div class="portal-card-title">
            <strong>{{ $variety->name }}</strong>
            <small>{{ rtrim(rtrim(number_format((float) $variety->bag_kg, 2), '0'), '.') }} <span data-i18n="kg_bag">kg bag</span></small>
        </div>
        @if ($card['available'])
            <span class="due-badge due-badge-ok" data-i18n="available_word">Available</span>
        @else
            <span class="due-badge due-badge-upcoming" data-i18n="out_of_stock">Out of stock</span>
        @endif
    </div>

    <div class="rice-card-price">
        @if ($variety->selling_price !== null)
            <strong><x-rupees :amount="$variety->selling_price" /></strong>
            <small data-i18n="per_bag">per bag</small>
        @else
            <small data-i18n="price_on_request">Price on request</small>
        @endif
    </div>

    @if ($orderable ?? false)
        <label class="rice-card-order">
            <span data-i18n="bags_word">Bags</span>
            <span class="rice-card-stepper">
                <button type="button" class="rice-step" data-step="-1" aria-label="One bag less">−</button>
                <input
                    type="number"
                    name="bags[{{ $variety->id }}]"
                    class="input"
                    min="0"
                    max="500"
                    step="1"
                    inputmode="numeric"
                    value="{{ old('bags.'.$variety->id, 0) }}"
                    data-price="{{ $variety->selling_price }}"
                    aria-label="Bags of {{ $variety->name }}"
                >
                <button type="button" class="rice-step" data-step="1" aria-label="One bag more">+</button>
            </span>
        </label>
    @endif

</div>
