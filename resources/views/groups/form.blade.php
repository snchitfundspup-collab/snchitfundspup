@extends('layouts.app')

@php
    $isEditing = $group->exists;

    $scheduleValues = old('payouts', $schedule);

    $monthsValue = (int) old('months', $group->months);

    $startDateValue = old('start_date', $group->start_date?->format('Y-m-d'));
@endphp

@section('title', ($isEditing ? 'Edit Group' : 'Add Group').' | SN Chit Funds')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css'
    ])
@endpush

@section('content')

<div class="page-wrapper groups-page">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hero hero-compact glass">

        <div class="hero-glow"></div>

        <div class="hero-glow-right"></div>

        <div class="hero-content">

            <div class="badge">

                <span class="badge-icon icon-3d icon-3d-purple">
                    <x-icon name="layers" />
                </span>

                <span data-i18n="groups_badge">
                    Chit Groups
                </span>

            </div>


            <h1
                class="hero-title"
                data-i18n="{{ $isEditing ? 'edit_group' : 'add_group' }}"
            >
                {{ $isEditing ? 'Edit Group' : 'Add Group' }}
            </h1>


            <p
                class="hero-description"
                data-i18n="group_form_description"
            >
                Set up the chit amount, members and the month-by-month withdrawal schedule.
            </p>

        </div>

    </section>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <section class="form-card glass">

        <div class="form-inner">


            <!-- BACK -->

            <a
                href="{{ $isEditing ? route('groups.show', $group) : route('groups.index') }}"
                class="back-button"
            >
                <span class="back-icon icon-3d icon-3d-blue">
                    <x-icon name="arrow-left" />
                </span>

                <span data-i18n="{{ $isEditing ? 'back_to_group' : 'back_to_groups' }}">
                    {{ $isEditing ? 'Back to Group' : 'Back to Groups' }}
                </span>
            </a>


            <!-- ERRORS -->

            @if ($errors->any())

                <div class="alert-error" role="alert">

                    <strong data-i18n="errorTitle">
                        Please correct the following:
                    </strong>

                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif


            <form
                method="POST"
                action="{{ $isEditing ? route('groups.update', $group) : route('groups.store') }}"
                class="group-form"
                id="groupForm"
            >

                @csrf

                @if ($isEditing)
                    @method('PUT')
                @endif


                <!-- =================================================
                     GROUP DETAILS
                ================================================== -->

                <h2 class="group-form-section">
                    <span class="group-section-icon icon-3d icon-3d-orange">
                        <x-icon name="info" />
                    </span>
                    <span data-i18n="group_details">Group details</span>
                </h2>


                <div class="group-form-grid">


                    <!-- NAME -->

                    <div class="group-field group-field-full">

                        <label
                            class="field-label"
                            for="name"
                            data-i18n="group_name"
                        >
                            Group Name
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $group->name) }}"
                            class="input"
                            placeholder="e.g. 2 Lakh – Jan 2027"
                            data-i18n-placeholder="group_name_placeholder"
                            maxlength="100"
                            required
                        >

                    </div>


                    <!-- TYPE -->

                    <div class="group-field group-field-full">

                        <span
                            class="field-label"
                            data-i18n="group_type"
                        >
                            Group Type
                        </span>

                        <div class="type-options">

                            @foreach ([
                                \App\Models\ChitGroup::TYPE_DRAW => ['trophy', 'orange', 'type_draw', 'Draw', 'type_draw_hint', 'Winner picked by draw each month'],
                                \App\Models\ChitGroup::TYPE_AUCTION => ['rupee', 'purple', 'type_auction', 'Auction', 'type_auction_hint', 'Auction steps coming later'],
                            ] as $typeValue => [$typeIcon, $typeTone, $typeKey, $typeLabel, $typeHintKey, $typeHint])

                                <label class="type-option">

                                    <input
                                        type="radio"
                                        name="type"
                                        value="{{ $typeValue }}"
                                        @checked(old('type', $group->type) === $typeValue)
                                    >

                                    <span class="type-option-card">

                                        <span class="type-option-icon icon-3d icon-3d-{{ $typeTone }}">
                                            <x-icon :name="$typeIcon" />
                                        </span>

                                        <span>
                                            <strong data-i18n="{{ $typeKey }}">{{ $typeLabel }}</strong>
                                            <small data-i18n="{{ $typeHintKey }}">{{ $typeHint }}</small>
                                        </span>

                                    </span>

                                </label>

                            @endforeach

                        </div>

                    </div>


                    <!-- AMOUNT -->

                    <div class="group-field">

                        <label
                            class="field-label"
                            for="amount"
                            data-i18n="chit_amount"
                        >
                            Chit Amount (₹)
                        </label>

                        <input
                            id="amount"
                            type="text"
                            name="amount"
                            value="{{ old('amount', $group->amount) }}"
                            class="input money-input"
                            inputmode="numeric"
                            placeholder="e.g. 2,00,000"
                            required
                        >

                    </div>


                    <!-- MONTHS -->

                    <div class="group-field">

                        <label
                            class="field-label"
                            for="months"
                            data-i18n="group_months"
                        >
                            Number of Months
                        </label>

                        <input
                            id="months"
                            type="number"
                            name="months"
                            value="{{ $monthsValue }}"
                            class="input"
                            min="1"
                            max="120"
                            required
                        >

                    </div>


                    <!-- MEMBERS -->

                    <div class="group-field">

                        <label
                            class="field-label"
                            for="member_count"
                            data-i18n="group_member_count"
                        >
                            Number of Members
                        </label>

                        <input
                            id="member_count"
                            type="number"
                            name="member_count"
                            value="{{ old('member_count', $group->member_count) }}"
                            class="input"
                            min="1"
                            max="200"
                            required
                        >

                    </div>


                    <!-- COMMISSION -->

                    <div class="group-field">

                        <label
                            class="field-label"
                            for="commission_amount"
                            data-i18n="commission"
                        >
                            Company Commission (₹ per month)
                        </label>

                        <input
                            id="commission_amount"
                            type="text"
                            name="commission_amount"
                            value="{{ old('commission_amount', $group->commission_amount ?: '') }}"
                            class="input money-input"
                            inputmode="numeric"
                            placeholder="0"
                        >

                    </div>


                    <!-- START DATE -->

                    <div class="group-field">

                        <label
                            class="field-label"
                            for="start_date"
                            data-i18n="start_date"
                        >
                            Start Date
                        </label>

                        <input
                            id="start_date"
                            type="date"
                            name="start_date"
                            value="{{ $startDateValue }}"
                            class="input"
                            required
                        >

                    </div>


                    <!-- MONTHLY INSTALLMENT (typed by the admin) -->

                    <div class="group-field">

                        <label
                            class="field-label"
                            for="installment_amount"
                            data-i18n="installment_label"
                        >
                            Monthly Installment (₹)
                        </label>

                        <input
                            id="installment_amount"
                            type="text"
                            name="installment_amount"
                            value="{{ old('installment_amount', $group->installment_amount) }}"
                            class="input money-input installment-input"
                            inputmode="numeric"
                            placeholder="e.g. 9,500"
                            required
                        >

                        <small
                            class="installment-hint"
                            id="installmentHint"
                        ></small>

                    </div>


                    <!-- REMARKS -->

                    <div class="group-field group-field-full">

                        <label
                            class="field-label"
                            for="remarks"
                            data-i18n="remarksLabel"
                        >
                            Remarks
                        </label>

                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="2"
                            class="input"
                        >{{ old('remarks', $group->remarks) }}</textarea>

                    </div>

                </div>


                <!-- =================================================
                     WITHDRAWAL SCHEDULE
                ================================================== -->

                <div class="schedule-header">

                    <h2 class="group-form-section">
                        <span class="group-section-icon icon-3d icon-3d-green">
                            <x-icon name="rupee" />
                        </span>
                        <span data-i18n="withdrawal_schedule">Withdrawal schedule</span>
                    </h2>


                    @if (count($copySources) > 0)

                        <div class="copy-schedule">

                            <select
                                id="copySourceSelect"
                                class="input copy-schedule-select"
                                aria-label="Copy withdrawal amounts from an existing group"
                            >
                                <option
                                    value=""
                                    data-i18n="copy_from_group"
                                >Copy from existing group…</option>

                                @foreach ($copySources as $source)
                                    <option value="{{ $source['id'] }}">
                                        {{ $source['name'] }} ({{ $source['months'] }} months)
                                    </option>
                                @endforeach
                            </select>

                            <button
                                type="button"
                                class="copy-schedule-button"
                                id="copyScheduleButton"
                            >
                                <x-icon name="layers" />
                                <span data-i18n="copy">Copy</span>
                            </button>

                        </div>

                    @endif

                </div>


                <p
                    class="schedule-hint"
                    data-i18n="schedule_hint"
                >
                    Type the amount the winner takes home in each month. Rows follow the number of months above.
                </p>


                <div
                    class="copy-notice"
                    id="copyNotice"
                    hidden
                ></div>


                <div class="schedule-table-wrapper">

                    <table class="schedule-table">

                        <thead>
                            <tr>
                                <th data-i18n="month_number">Month</th>
                                <th data-i18n="month_date">Period</th>
                                <th data-i18n="withdrawal_amount">Withdrawal Amount (₹)</th>
                            </tr>
                        </thead>

                        <tbody id="scheduleBody">

                            @foreach (range(1, max(1, $monthsValue)) as $monthNumber)

                                <tr>
                                    <td class="schedule-month">{{ $monthNumber }}</td>

                                    <td class="schedule-date" data-month="{{ $monthNumber }}">
                                        @if ($startDateValue)
                                            {{ (new \App\Models\ChitGroup(['start_date' => $startDateValue]))->monthPeriodLabel($monthNumber) }}
                                        @endif
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            name="payouts[{{ $monthNumber }}]"
                                            value="{{ $scheduleValues[$monthNumber] ?? '' }}"
                                            class="input money-input schedule-input"
                                            inputmode="numeric"
                                            aria-label="Withdrawal amount for month {{ $monthNumber }}"
                                            required
                                        >
                                    </td>
                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                <!-- FOOTER -->

                <div class="form-footer">

                    <a
                        href="{{ $isEditing ? route('groups.show', $group) : route('groups.index') }}"
                        class="cancel-button"
                    >
                        <x-icon name="x" />
                        <span data-i18n="cancelText">Cancel</span>
                    </a>

                    <button
                        type="submit"
                        class="save-button"
                    >
                        <x-icon name="save" />
                        <span data-i18n="{{ $isEditing ? 'save_changes' : 'save_group' }}">
                            {{ $isEditing ? 'Save Changes' : 'Save Group' }}
                        </span>
                    </button>

                </div>

            </form>

        </div>

    </section>

</div>


<script type="application/json" id="copySources">@json($copySources)</script>

@endsection


@push('scripts')
    @vite('resources/js/groups-form.js')
@endpush
