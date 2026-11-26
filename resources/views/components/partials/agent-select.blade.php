{{-- Staff dropdown for agent statements: who brought the customer in
     (agent_id) or who collected the money (collected_by). Starts on the
     signed-in person. $agentName, $agentId, $agentI18n, $agentLabel,
     $agentSelected (optional), $agentClass (optional wrapper class) —
     prefixed so they never clash with the including page's variables.
     Not shown to an agent in Micro Finance: they record under their own name. --}}
@if (\App\Support\AgentFilter::seesAll(request()))
@php
    $agentStaff = \App\Models\User::query()->active()->orderBy('name')->get(['id', 'name']);
    $agentChosen = (string) old($agentName, $agentSelected ?? auth()->id());
@endphp

<div class="{{ $agentClass ?? 'group-field' }}">
    <label class="field-label" for="{{ $agentId }}" data-i18n="{{ $agentI18n }}">{{ $agentLabel }}</label>
    <select id="{{ $agentId }}" name="{{ $agentName }}" class="input">
        @foreach ($agentStaff as $agentPerson)
            <option value="{{ $agentPerson->id }}" @selected($agentChosen === (string) $agentPerson->id)>{{ $agentPerson->name }}</option>
        @endforeach
    </select>
</div>
@endif
