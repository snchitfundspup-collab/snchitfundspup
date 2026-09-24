{{-- A labelled dropdown filter for the Traders lists. --}}
<label class="payment-filter">
    <span data-i18n="{{ $i18n }}">{{ $label }}</span>
    <select name="{{ $name }}" class="input payment-filter-select">
        <option value="">{{ $allLabel }}</option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
</label>
