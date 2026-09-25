@extends('layouts.portal')

@section('title', 'Upcoming Groups')

@section('content')

<div class="groups-list-page portal-page">

    <div class="groups-list-header">
        <div>
            @include('portal.partials.back')
            <h1 class="groups-title" data-i18n="upcoming_groups">Upcoming Groups</h1>
            <p class="groups-subtitle" data-i18n="upcoming_subtitle">New chit groups starting soon. Open one to see the monthly amount and the withdrawal plan.</p>
        </div>
    </div>

    @if ($groups->isEmpty())
        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-purple"><x-icon name="calendar" /></span>
            <strong data-i18n="no_upcoming_groups">No new groups right now. Please check again later.</strong>
        </div>
    @else
        <div class="portal-grid">
            @foreach ($groups as $group)
                @include('portal.partials.upcoming-card', ['joined' => in_array($group->id, $joinedGroupIds, true), 'joinRequest' => $requests->get($group->id)])
            @endforeach
        </div>
    @endif

</div>

@endsection
