@extends('layouts.app')

@section('title', 'Run Draw | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/groups.css',
        'resources/css/draws.css'
    ])
@endpush

@section('content')

<div class="group-show-page draws-page">


    {{-- =====================================================
         FLASH MESSAGES
    ====================================================== --}}

    @if (session('success'))
        <div class="group-flash group-flash-success" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="group-flash group-flash-error" role="alert">
            <x-icon name="info" />
            {{ $errors->first() }}
        </div>
    @endif

    <div
        class="group-flash group-flash-error"
        id="drawError"
        role="alert"
        hidden
    ></div>


    {{-- =====================================================
         HEADER + GROUP PICKER
    ====================================================== --}}

    <section class="group-hero glass">

        <a
            href="{{ route('draws.index') }}"
            class="group-back-link"
        >
            <x-icon name="arrow-left" />
            <span data-i18n="back_to_draw_details">Back to Draw Details</span>
        </a>

        <div class="group-hero-main">

            <span class="group-hero-icon icon-3d icon-3d-orange">
                <x-icon name="trophy" />
            </span>

            <div class="group-hero-text">
                <h1 class="group-hero-title">
                    <span data-i18n="run_draw">Run Draw</span>
                </h1>
                <p
                    class="group-hero-meta"
                    data-i18n="run_draw_subtitle"
                >
                    Tick the interested members, then spin the wheel.
                </p>
            </div>

            @if ($groups->isNotEmpty())
                <form
                    method="GET"
                    action="{{ route('draws.create') }}"
                    class="draw-group-picker"
                >
                    <label>
                        <span data-i18n="group_word">Group</span>
                        <select
                            name="group"
                            class="draw-select"
                            onchange="this.form.submit()"
                        >
                            @foreach ($groups as $option)
                                <option
                                    value="{{ $option->id }}"
                                    @selected($group && $option->id === $group->id)
                                >
                                    {{ $option->name }}{{ $option->canDrawNow() ? ' · draw due' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </form>
            @endif

        </div>

    </section>


    @if ($groups->isEmpty())

        <div class="groups-empty glass draw-block">
            <span class="groups-empty-icon icon-3d icon-3d-orange">
                <x-icon name="trophy" />
            </span>
            <strong data-i18n="draw_no_groups">No running groups</strong>
            <p data-i18n="draw_no_groups_text">Draws are held for groups that have started.</p>
        </div>

    @else

        {{-- =================================================
             THIS DRAW
        ================================================== --}}

        <section class="draw-facts">

            <div class="group-fact glass">
                <span class="group-fact-icon icon-3d icon-3d-blue"><x-icon name="calendar" /></span>
                <span class="group-fact-body">
                    <span class="group-fact-value">
                        @if ($month)
                            <span data-i18n="month_number">Month</span> {{ $month }} / {{ $group->months }}
                        @else
                            —
                        @endif
                    </span>
                    <span class="group-fact-label">
                        {{ $month ? $group->monthPeriodLabel($month) : 'All months drawn' }}
                    </span>
                </span>
            </div>

            <div class="group-fact glass">
                <span class="group-fact-icon icon-3d icon-3d-green"><x-icon name="rupee" /></span>
                <span class="group-fact-body">
                    <span class="group-fact-value"><x-rupees :amount="$withdrawal" /></span>
                    <span class="group-fact-label" data-i18n="prize_amount">Prize (withdrawal) amount</span>
                </span>
            </div>

            <div class="group-fact glass">
                <span class="group-fact-icon icon-3d icon-3d-purple"><x-icon name="users" /></span>
                <span class="group-fact-body">
                    <span class="group-fact-value">{{ $eligible->count() }}</span>
                    <span class="group-fact-label" data-i18n="yet_to_win">Members yet to win</span>
                </span>
            </div>

            @if ($lastDraw)
                <a
                    href="{{ route('draws.show', $lastDraw) }}"
                    class="group-fact glass"
                >
                    <span class="group-fact-icon icon-3d icon-3d-orange"><x-icon name="trophy" /></span>
                    <span class="group-fact-body">
                        <span class="group-fact-value draw-last-winner">
                            <x-customer-name :customer="$lastDraw->winner->customer" />
                        </span>
                        <span class="group-fact-label">
                            <span data-i18n="last_winner">Last winner</span> · <span data-i18n="month_number">Month</span> {{ $lastDraw->month_number }}
                        </span>
                    </span>
                </a>
            @endif

        </section>


        @if (! $canDraw)

            <div class="groups-empty glass draw-block">
                <span class="groups-empty-icon icon-3d icon-3d-blue">
                    <x-icon name="calendar" />
                </span>

                @if ($month === null)
                    <strong data-i18n="draw_all_done">Every month of this group has been drawn.</strong>
                @else
                    <strong>
                        <span data-i18n="draw_not_yet">The draw for month</span> {{ $month }}
                        <span data-i18n="draw_opens_on">opens on</span> {{ $group->dateForMonth($month)->format('d M Y') }}.
                    </strong>
                    <p data-i18n="draw_not_yet_text">Each month's draw can be run once that group month has begun.</p>
                @endif
            </div>

        @else

            <form
                method="POST"
                action="{{ route('draws.store') }}"
                class="draw-layout"
                id="drawForm"
            >

                @csrf

                <input type="hidden" name="chit_group_id" value="{{ $group->id }}">


                {{-- -------- interested members -------- --}}

                <section class="group-panel glass draw-members">

                    <div class="group-panel-header">

                        <h2 data-i18n="interested_members">Interested members</h2>

                        <span class="draw-selected-count">
                            <strong id="drawSelectedCount">0</strong> / {{ $eligible->count() }}
                        </span>

                    </div>

                    <div class="draw-select-actions">
                        <button type="button" class="group-action" id="drawSelectAll">
                            <x-icon name="check" />
                            <span data-i18n="select_all">Select all</span>
                        </button>
                        <button type="button" class="group-action" id="drawSelectNone">
                            <x-icon name="x" />
                            <span data-i18n="clear">Clear</span>
                        </button>
                    </div>

                    <p class="draw-hint" data-i18n="draw_hint">
                        Members who have already won are not listed. Members with dues can still take part.
                    </p>

                    <div class="draw-member-list">

                        @foreach ($eligible as $member)

                            <label class="draw-member">

                                <input
                                    type="checkbox"
                                    name="participant_ids[]"
                                    value="{{ $member->id }}"
                                    data-label="{{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::before($member->customer->name.' ', ' '), 11, '…') }}"
                                    @checked(in_array($member->id, old('participant_ids', [])))
                                >

                                <span class="draw-member-card">

                                    <span class="draw-check"><x-icon name="check" /></span>

                                    <x-customer-avatar :customer="$member->customer" />

                                    <span class="member-card-info">
                                        <span class="member-card-code">{{ $member->member_code }}</span>
                                        <strong class="member-card-name">
                                            <x-customer-name :customer="$member->customer" />
                                        </strong>
                                    </span>

                                </span>

                            </label>

                        @endforeach

                    </div>

                </section>


                {{-- -------- wheel -------- --}}

                <section class="group-panel glass draw-wheel-panel">

                    <div class="wheel-stage">

                        <div class="wheel-pointer" aria-hidden="true"></div>

                        <svg
                            class="wheel"
                            id="drawWheel"
                            viewBox="-110 -110 220 220"
                            role="img"
                            aria-label="Draw wheel"
                        >
                            <circle r="104" class="wheel-rim"></circle>
                            <g id="drawWheelSegments"></g>
                            <circle r="16" class="wheel-hub"></circle>
                        </svg>

                        <p
                            class="wheel-empty"
                            id="drawWheelEmpty"
                            data-i18n="wheel_empty"
                        >
                            Tick members to fill the wheel
                        </p>

                    </div>

                    <button
                        type="submit"
                        class="spin-button"
                        id="drawSpinButton"
                        disabled
                    >
                        <x-icon name="trophy" />
                        <span data-i18n="spin_wheel">Spin the wheel</span>
                    </button>

                    <p class="draw-fair-note" data-i18n="draw_fair_note">
                        The winner is picked at random by the system and saved before the wheel stops.
                    </p>

                </section>

            </form>

        @endif

    @endif

</div>


{{-- =========================================================
     WINNER POPUP (shown by draws.js when the wheel stops)
========================================================= --}}

<div
    class="winner-modal"
    id="winnerModal"
    hidden
>
    <div class="winner-modal-overlay"></div>

    <div
        class="winner-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="winnerName"
    >
        <span class="winner-trophy icon-3d icon-3d-orange">
            <x-icon name="trophy" />
        </span>

        <p class="winner-kicker" data-i18n="winner_is">And the winner is…</p>

        <h2 id="winnerName"></h2>

        <p class="winner-code" id="winnerCode"></p>

        <p class="winner-prize">
            <span data-i18n="prize_amount">Prize (withdrawal) amount</span>
            <strong><x-rupees :amount="$withdrawal ?? 0" /></strong>
        </p>

        <a
            href="#"
            class="add-group-button"
            id="winnerLink"
        >
            <x-icon name="rupee" />
            <span data-i18n="view_and_pay">View draw &amp; pay out</span>
        </a>
    </div>

    <div class="confetti" id="confetti" aria-hidden="true"></div>
</div>

@endsection


@push('scripts')
    @vite('resources/js/draws.js')
@endpush
