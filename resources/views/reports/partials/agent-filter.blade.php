{{-- Agent dropdown for the Agent Statement filters ($staff, $agent). --}}
<label class="payment-filter">
    <span data-i18n="agent_word">Agent</span>
    <select name="agent" class="input payment-filter-select">
        @foreach ($staff as $person)
            <option value="{{ $person->id }}" @selected($person->id === $agent->id)>{{ $person->name }}</option>
        @endforeach
    </select>
</label>
