@extends('layouts.portal')

@section('title', 'My Loans')

@section('content')

<div class="groups-list-page portal-page">

    <div class="groups-list-header">
        <div>
            @include('portal.partials.back')
            <h1 class="groups-title" data-i18n="my_loans">My Loans</h1>
            <p class="groups-subtitle" data-i18n="my_loans_subtitle">Your loans with Sri Lakshmi Micro Finance: what you have paid, what is left and what to pay now.</p>
        </div>
        <div class="ledger-actions">
            @include('portal.partials.call-office')
        </div>
    </div>

    @if ($loans->isEmpty())
        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
            <strong data-i18n="no_loans_yet">You have no loans.</strong>
        </div>
    @else
        <div class="portal-grid">
            @foreach ($loans as $row)
                @include('portal.partials.loan-card')
            @endforeach
        </div>
    @endif

</div>

@endsection
