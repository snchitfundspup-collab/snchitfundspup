{{-- "Agent" dropdown for list / report filters (name="agent", "All agents"
     first). $agentFilterSubmit: submit the form on change (forms that are
     not live-filtered by payments.js). --}}
<label class="payment-filter">
    <span data-i18n="agent_word">Agent</span>
    <select name="agent" class="input payment-filter-select" @if ($agentFilterSubmit ?? false) onchange="this.form.submit()" @endif>
        <option value="" data-i18n="all_agents">All agents</option>
        @foreach (\App\Support\AgentFilter::staff() as $agentFilterId => $agentFilterName)
            <option value="{{ $agentFilterId }}" @selected((string) request('agent') === (string) $agentFilterId)>{{ $agentFilterName }}</option>
        @endforeach
    </select>
</label>
