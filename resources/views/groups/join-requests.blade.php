@extends('layouts.app')

@section('title', 'Join Requests | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/payments.css'
    ])
@endpush

@php
    $tabKeys = ['pending' => 'requests_waiting', 'approved' => 'requests_added', 'dismissed' => 'requests_dismissed'];
@endphp

@section('content')

<div class="groups-list-page payments-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="join_requests">Join Requests</h1>
            <p class="groups-subtitle" data-i18n="join_requests_subtitle">Customers who asked to join a group that is forming. Add them to the group or dismiss the request.</p>
        </div>
    </div>


    <nav class="status-tabs collect-tabs" aria-label="Request status">
        @foreach (\App\Http\Controllers\JoinRequestController::TABS as $tabStatus => $tabLabel)
            <a
                href="{{ route('groups.requests.index', $tabStatus === 'pending' ? [] : ['status' => $tabStatus]) }}"
                @class(['status-tab', 'active' => $status === $tabStatus])
            >
                <span data-i18n="{{ $tabKeys[$tabStatus] }}">{{ $tabLabel }}</span>
                <span class="status-tab-count">{{ $counts[$tabStatus] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>


    @if ($requests->isEmpty())

        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="check" /></span>
            <strong data-i18n="no_requests_here">No requests here.</strong>
        </div>

    @else

        <div class="join-request-list">
            @foreach ($requests as $joinRequest)
                @php
                    $group = $joinRequest->chitGroup;
                    $customer = $joinRequest->customer;
                    $seatsLeft = max(0, $group->member_count - $group->members_count);
                    $canAdd = $joinRequest->isPending() && $group->isForming() && $seatsLeft > 0;
                @endphp

                <section class="join-request glass">

                    <div class="join-request-head">
                        <x-customer-avatar :customer="$customer" />
                        <div class="join-request-who">
                            <strong><x-customer-name :customer="$customer" /></strong>
                            <span>{{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}</span>
                        </div>
                        @if ($customer->phone)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $customer->phone) }}" class="member-call-button collect-call-button" aria-label="Call {{ $customer->name }}">
                                <x-icon name="phone" />
                                <span data-i18n="call">Call</span>
                            </a>
                        @endif
                    </div>

                    <div class="join-request-facts">
                        <span>
                            <small data-i18n="group_word">Group</small>
                            <a href="{{ route('groups.show', $group) }}" class="statement-receipt-link">{{ $group->name }}</a>
                        </span>
                        <span>
                            <small data-i18n="starts_on">Starts on</small>
                            <strong>{{ $group->start_date?->format('d M Y') ?? '—' }}</strong>
                        </span>
                        <span>
                            <small data-i18n="seats_wanted">Seats</small>
                            <strong>{{ $joinRequest->seats }}</strong>
                        </span>
                        <span>
                            <small data-i18n="seats_left">seats left</small>
                            <strong @class(['ledger-total-due' => $seatsLeft === 0])>{{ $seatsLeft }} / {{ $group->member_count }}</strong>
                        </span>
                        <span>
                            <small data-i18n="asked_on">Asked on</small>
                            <strong>{{ $joinRequest->created_at->timezone(config('app.business_timezone'))->format('d M Y, h:i A') }}</strong>
                        </span>
                    </div>

                    @if ($joinRequest->note)
                        <p class="join-request-note">“{{ $joinRequest->note }}”</p>
                    @endif

                    @if ($joinRequest->isPending())

                        @unless ($group->isForming())
                            <p class="join-request-warning" data-i18n="group_already_started">This group has already started, so no one can be added.</p>
                        @endunless

                        <div class="join-request-actions">

                            @if ($canAdd)
                                <form method="POST" action="{{ route('groups.requests.approve', $joinRequest) }}" class="join-request-approve">
                                    @csrf
                                    <label>
                                        <span data-i18n="seats_wanted">Seats</span>
                                        <select name="seats" class="input">
                                            @foreach (range(1, min(\App\Models\ChitJoinRequest::MAX_SEATS, $seatsLeft)) as $seatCount)
                                                <option value="{{ $seatCount }}" @selected($seatCount === min($joinRequest->seats, $seatsLeft))>{{ $seatCount }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <button type="submit" class="add-group-button">
                                        <x-icon name="plus" />
                                        <span data-i18n="add_to_group">Add to group</span>
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('groups.requests.dismiss', $joinRequest) }}" class="join-request-dismiss">
                                @csrf
                                <input type="text" name="reply" class="input" maxlength="500" placeholder="Reason for the customer (optional)" data-i18n-placeholder="dismiss_reason">
                                <button type="submit" class="group-action">
                                    <x-icon name="x" />
                                    <span data-i18n="dismiss_word">Dismiss</span>
                                </button>
                            </form>

                        </div>

                    @else

                        <p class="join-request-decided">
                            <span @class(['due-badge', 'due-badge-ok' => $joinRequest->status === 'approved', 'due-badge-upcoming' => $joinRequest->status !== 'approved'])>
                                {{ $joinRequest->status === 'approved' ? 'Added — '.$joinRequest->seats.' '.($joinRequest->seats === 1 ? 'seat' : 'seats') : 'Dismissed' }}
                            </span>
                            {{ $joinRequest->decider?->name }} · {{ $joinRequest->decided_at?->timezone(config('app.business_timezone'))->format('d M Y, h:i A') }}
                            @if ($joinRequest->reply)
                                · “{{ $joinRequest->reply }}”
                            @endif
                        </p>

                    @endif

                </section>
            @endforeach
        </div>

        <x-pagination :paginator="$requests" label="Request pages" />

    @endif

</div>

@endsection
