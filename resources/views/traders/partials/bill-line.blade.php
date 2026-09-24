{{-- One rice line (used for the rows and the "add line" template). --}}

<div class="bill-line" data-line>

    <label>
        <span class="bill-line-label" data-i18n="rice_variety">Rice variety</span>
        <select name="lines[{{ $index }}][variety_id]" class="input" data-field="variety">
            <option value="">Choose rice…</option>
            @foreach ($varieties as $variety)
                <option
                    value="{{ $variety->id }}"
                    @if ($isSale) data-stock="{{ (float) ($stock[$variety->id] ?? 0) }}" @endif
                    @selected((string) ($line['variety_id'] ?? '') === (string) $variety->id)
                >{{ $variety->name }}</option>
            @endforeach
        </select>
    </label>

    <label>
        <span class="bill-line-label" data-i18n="bags_word">Bags</span>
        <input type="number" name="lines[{{ $index }}][bags]" class="input" min="1" step="1" inputmode="numeric" value="{{ $line['bags'] ?? '' }}" data-field="bags" placeholder="0">
    </label>

    <label>
        <span class="bill-line-label" data-i18n="rate_per_bag_rupees">Rate per bag (₹)</span>
        <input type="text" name="lines[{{ $index }}][rate]" class="input money-input" inputmode="decimal" value="{{ $line['rate'] ?? '' }}" data-field="rate" placeholder="0.00">
    </label>

    <span class="bill-line-amount" data-out="amount">—</span>

    <button type="button" class="bill-line-remove" data-remove-line aria-label="Remove this line" title="Remove">
        <x-icon name="x" />
    </button>

    @if ($isSale)
        <span class="bill-line-stock" data-out="stock"></span>
    @endif

</div>
