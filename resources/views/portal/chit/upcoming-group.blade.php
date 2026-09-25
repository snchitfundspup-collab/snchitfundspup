@extends('layouts.portal')

@section('title', $group->name)

@php
    $seatsLeft = max(0, $group->member_count - $group->members_count);
@endphp

@section('content')

<div class="group-show-page portal-page">

    <section class="group-hero glass">

        <a href="{{ route('portal.upcoming') }}" class="group-back-link">
            <x-icon name="arrow-left" />
            <span data-i18n="upcoming_groups">Upcoming Groups</span>
        </a>

        <div class="group-hero-main">
            <span class="group-hero-icon icon-3d icon-3d-purple"><x-icon name="calendar" /></span>
            <div class="group-hero-text">
                <h1 class="group-hero-title">{{ $group->name }}</h1>
                <p class="group-hero-meta">
                    <x-rupees :amount="$group->amount" />
                    · <x-rupees :amount="$group->installment_amount" />/<span data-i18n="month_word">month</span> × {{ $group->months }}
                </p>
            </div>
        </div>

        <div class="portal-facts">
            <span>
                <small data-i18n="starts_on">Starts on</small>
                <strong>{{ $group->start_date?->format('d M Y') ?? '—' }}</strong>
            </span>
            <span>
                <small data-i18n="ends_on">Ends on</small>
                <strong>{{ $group->start_date ? $group->endDate()->format('d M Y') : '—' }}</strong>
            </span>
            <span>
                <small data-i18n="members_word">Members</small>
                <strong>{{ $group->members_count }} / {{ $group->member_count }}</strong>
            </span>
            <span>
                <small data-i18n="seats_left">seats left</small>
                <strong>{{ $seatsLeft }}</strong>
            </span>
        </div>

        @if ($joined)

            <p class="portal-join-note">
                <span class="due-badge due-badge-ok" data-i18n="you_joined">You joined</span>
                <span data-i18n="joined_note">You have a seat in this group. Your first payment is due on the start date.</span>
            </p>

        @elseif ($joinRequest?->isPending())

            <div class="portal-join-box">
                <p class="portal-join-note">
                    <span class="due-badge due-badge-month" data-i18n="request_sent">Request sent</span>
                    <span>
                        <span data-i18n="request_waiting_note">The office will call you to confirm.</span>
                        ({{ $joinRequest->seats }} {{ $joinRequest->seats === 1 ? 'seat' : 'seats' }},
                        {{ $joinRequest->created_at->timezone(config('app.business_timezone'))->format('d M Y') }})
                    </span>
                </p>
                <div class="portal-join-actions">
                    @include('portal.partials.call-office')
                    <form method="POST" action="{{ route('portal.upcoming.withdraw', $joinRequest) }}" onsubmit="return confirm('Withdraw your request?')">
                        @csrf
                        <button type="submit" class="group-action portal-cancel-button">
                            <x-icon name="x" />
                            <span data-i18n="withdraw_request">Withdraw request</span>
                        </button>
                    </form>
                </div>
            </div>

        @elseif ($seatsLeft > 0)

            @if ($joinRequest?->status === 'dismissed')
                <p class="portal-join-note">
                    <span class="due-badge due-badge-upcoming" data-i18n="request_not_accepted">Not accepted</span>
                    <span>{{ $joinRequest->reply ?: 'The office could not add you to this group. Please call the office.' }}</span>
                </p>
            @endif

            <form method="POST" action="{{ route('portal.upcoming.interest', $group) }}" class="portal-join-box portal-join-form">
                @csrf
                <strong data-i18n="want_to_join">Want to join this group?</strong>
                <div class="portal-join-fields">
                    <label class="group-field">
                        <span class="field-label" data-i18n="seats_wanted">Seats</span>
                        <select name="seats" class="input">
                            @foreach (range(1, min($maxSeats, $seatsLeft)) as $seatCount)
                                <option value="{{ $seatCount }}" @selected((int) old('seats', 1) === $seatCount)>{{ $seatCount }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="group-field portal-join-note-field">
                        <span class="field-label" data-i18n="order_note">Note for the office (optional)</span>
                        <input type="text" name="note" class="input" maxlength="500" value="{{ old('note') }}">
                    </label>
                </div>
                <div class="portal-join-actions">
                    <button type="submit" class="add-group-button">
                        <x-icon name="check" />
                        <span data-i18n="im_interested">I'm interested</span>
                    </button>
                    @include('portal.partials.call-office')
                </div>
                <p class="portal-card-note" data-i18n="join_decided_by_office">The office confirms every new member and will call you.</p>
            </form>

        @else

            <p class="portal-join-note">
                <span class="due-badge due-badge-upcoming" data-i18n="group_full">Full</span>
                <span data-i18n="group_full_note">This group is full. Please call the office about the next group.</span>
            </p>

        @endif

    </section>

    @if ($plan->isNotEmpty())
        @include('portal.partials.withdrawal-plan', ['planOpen' => true])
    @endif

</div>

@endsection
