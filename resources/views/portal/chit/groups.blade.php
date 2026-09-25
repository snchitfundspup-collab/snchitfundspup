@extends('layouts.portal')

@section('title', 'My Groups')

@section('content')

<div class="groups-list-page portal-page">

    <div class="groups-list-header">
        <div>
            @include('portal.partials.back')
            <h1 class="groups-title" data-i18n="my_groups">My Groups</h1>
            <p class="groups-subtitle" data-i18n="my_groups_subtitle">The chit groups you are in. Open one to see every month, your receipts and the withdrawal plan.</p>
        </div>
        @if ($seats->isNotEmpty())
            <div class="ledger-actions">
                <a href="{{ route('portal.statement.print') }}" class="group-action" target="_blank" rel="noopener">
                    <x-icon name="printer" />
                    <span data-i18n="print_statement">Print statement</span>
                </a>
                <a href="{{ route('portal.statement.pdf') }}" class="add-group-button">
                    <x-icon name="download" />
                    <span data-i18n="chit_statement">Chit statement</span>
                </a>
            </div>
        @endif
    </div>

    @if ($seats->isEmpty())
        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-blue"><x-icon name="layers" /></span>
            <strong data-i18n="no_groups_yet">You are not in a chit group yet. See the upcoming groups below.</strong>
            <a href="{{ route('portal.upcoming') }}" class="add-group-button">
                <x-icon name="calendar" />
                <span data-i18n="upcoming_groups">Upcoming Groups</span>
            </a>
        </div>
    @else
        <div class="portal-grid">
            @foreach ($seats as $seat)
                @include('portal.partials.seat-card')
            @endforeach
        </div>
    @endif

</div>

@endsection
