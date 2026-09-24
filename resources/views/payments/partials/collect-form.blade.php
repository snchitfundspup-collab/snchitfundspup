{{-- Collect form for one member seat: pick a pending / due month, then the
     full month or (from month 2) a partial amount — never more than that
     month's balance — plus method, date, reference, notes and the ledger. --}}

@php
    $group = $member->chitGroup;
    $collectable = $member->collectableMonths();
    $balanceDue = $member->balanceDue();
    $hasPending = $member->collectionStatus()['pending'] > 0;
    $ledger = $member->ledger();

    $selected = collect($collectable)->firstWhere('month', (int) old('month_number'))
        ?? ($collectable[0] ?? null);

    /* month 1 → full only; a month already part paid → partial only */
    $fullOnly = $selected['full_only'] ?? false;
    $partialOnly = ! $fullOnly && ($selected['paid'] ?? 0) > 0;
    $isPartial = ! $fullOnly && ($partialOnly || old('pay_mode') === 'partial');
@endphp

<section
    class="group-panel glass payment-step"
    id="collect"
>

    <h2 class="payment-seats-title">
        <span class="step-number">{{ $stepNumber ?? 3 }}</span>
        <span data-i18n="record_payment">Record payment</span>
        <span class="payment-form-seat">— {{ $member->member_code }} · {{ $group->name }}</span>
    </h2>


    {{-- SUMMARY --}}

    <div class="payment-summary">

        <div>
            <span data-i18n="due_now">Due now</span>
            <strong @class(['is-due' => $hasPending, 'is-open' => ! $hasPending && $balanceDue > 0])><x-rupees :amount="$balanceDue" /></strong>
        </div>

        <div>
            <span data-i18n="monthly_installment">Monthly Installment</span>
            <strong><x-rupees :amount="$group->installment_amount" /></strong>
        </div>

        <div>
            <span data-i18n="paid_so_far">Paid so far</span>
            <strong><x-rupees :amount="$member->totalPaid()" /></strong>
        </div>

        <div>
            <span data-i18n="left_for_group">Left for the whole group</span>
            <strong><x-rupees :amount="$member->remainingForGroup()" /></strong>
        </div>

    </div>


    @if ($selected === null)

        <p class="partial-only-note">
            <x-icon name="check" />
            <span data-i18n="all_months_paid">Every month of this group is paid.</span>
        </p>

    @else

    <form
        method="POST"
        action="{{ route('payments.store') }}"
        class="payment-form"
        id="paymentForm"
    >

        @csrf

        <input type="hidden" name="chit_group_member_id" value="{{ $member->id }}">


        {{-- MONTH PICKER: pending months (past due) and the month due next --}}

        <span class="field-label" data-i18n="choose_month">Which month is this for?</span>

        <div class="month-picker" role="radiogroup" aria-label="Month">

            @foreach ($collectable as $open)

                @php
                    $openState = match (true) {
                        $open['status'] === 'pending' => 'pending',
                        $open['paid'] > 0 => 'partial',
                        default => $open['status'],
                    };
                @endphp

                <label @class(['month-choice', 'month-choice-'.$openState])>

                    <input
                        type="radio"
                        name="month_number"
                        value="{{ $open['month'] }}"
                        data-balance="{{ $open['balance'] }}"
                        data-paid="{{ $open['paid'] }}"
                        data-full-only="{{ $open['full_only'] ? '1' : '0' }}"
                        @checked($open['month'] === $selected['month'])
                    >

                    <span class="month-choice-body">

                        <span class="month-choice-top">
                            <strong><span data-i18n="month_number">Month</span> {{ $open['month'] }}</strong>
                            <span @class([
                                'due-badge',
                                'due-badge-due' => $openState === 'pending',
                                'due-badge-part' => $openState === 'partial',
                                'due-badge-month' => $openState === 'due',
                                'due-badge-upcoming' => $openState === 'upcoming',
                            ]) data-i18n="collect_state_{{ $openState }}">{{ ['pending' => 'Pending', 'partial' => 'Part paid', 'due' => 'Due', 'upcoming' => 'Upcoming'][$openState] }}</span>
                        </span>

                        <small>{{ $open['period'] }} · <span data-i18n="due_on">due</span> {{ $open['due_on'] }}</small>

                        <span class="month-choice-amount">
                            <x-rupees :amount="$open['balance']" />
                            @if ($open['paid'] > 0)
                                <small>(<x-rupees :amount="$open['paid']" /> <span data-i18n="paid">paid</span>)</small>
                            @endif
                        </span>

                    </span>

                </label>

            @endforeach

        </div>


        {{-- FULL / PARTIAL --}}

        <p
            class="partial-only-note"
            id="fullOnlyNote"
            @unless ($fullOnly) hidden @endunless
        >
            <x-icon name="info" />
            <span data-i18n="month_one_full_only">Month 1 is paid in full — no partial payments.</span>
        </p>

        <p
            class="partial-only-note"
            id="partialOnlyNote"
            @unless ($partialOnly) hidden @endunless
        >
            <x-icon name="info" />
            <span data-i18n="continue_partial_month">This month is part paid. Continue with a partial payment.</span>
        </p>

        <div class="pay-mode" id="payModes" role="radiogroup" aria-label="Payment type">

            <label class="pay-mode-option" id="payModeFull" @if ($partialOnly) hidden @endif>
                <input
                    type="radio"
                    name="pay_mode"
                    value="full"
                    @checked(! $isPartial)
                >
                <span>
                    <x-icon name="calendar" />
                    <span data-i18n="pay_full_month">Full month</span>
                </span>
            </label>

            <label class="pay-mode-option" id="payModePartial" @if ($fullOnly) hidden @endif>
                <input
                    type="radio"
                    name="pay_mode"
                    value="partial"
                    @checked($isPartial)
                >
                <span>
                    <x-icon name="rupee" />
                    <span data-i18n="pay_partial">Partial / daily amount</span>
                </span>
            </label>

        </div>


        <div class="group-form-grid payment-fields">


            {{-- AMOUNT --}}

            <div class="group-field">

                <label
                    class="field-label"
                    for="amount"
                    data-i18n="amount_rupees"
                >
                    Amount (₹)
                </label>

                <input
                    type="text"
                    id="amount"
                    name="amount"
                    value="{{ $isPartial ? old('amount') : number_format($selected['balance']) }}"
                    class="input money-input payment-amount-input"
                    inputmode="numeric"
                    placeholder="Enter amount"
                    autocomplete="off"
                    required
                    @unless ($isPartial) readonly @endunless
                >

                <small
                    class="installment-hint"
                    id="amountCovers"
                ></small>

            </div>


            {{-- METHOD --}}

            <div class="group-field group-field-full">

                <span
                    class="field-label"
                    data-i18n="payment_method"
                >
                    Payment method
                </span>

                <div class="method-options">

                    @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)

                        <label class="method-option">
                            <input
                                type="radio"
                                name="method"
                                value="{{ $methodValue }}"
                                @checked(old('method', 'cash') === $methodValue)
                            >
                            <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                        </label>

                    @endforeach

                </div>

            </div>


            {{-- REFERENCE --}}

            <div
                class="group-field"
                id="referenceField"
            >

                <label
                    class="field-label"
                    for="reference"
                    data-i18n="reference_number"
                >
                    Reference / UPI / cheque no.
                </label>

                <input
                    type="text"
                    id="reference"
                    name="reference"
                    value="{{ old('reference') }}"
                    class="input"
                    maxlength="100"
                >

            </div>


        </div>


        {{-- MORE DETAILS: date & time (defaults to now) and notes --}}

        <details
            class="payment-more"
            @if ($errors->has('paid_at') || old('notes')) open @endif
        >

            <summary>
                <span data-i18n="more_details">More details</span>
                <small data-i18n="more_details_hint">date &amp; time, notes</small>
                <x-icon name="chevron-down" class="schedule-summary-caret" />
            </summary>

            <div class="group-form-grid payment-fields">

            {{-- DATE --}}

            <div class="group-field">

                <label
                    class="field-label"
                    for="paid_at"
                    data-i18n="payment_date_time"
                >
                    Payment date &amp; time
                </label>

                <input
                    type="datetime-local"
                    id="paid_at"
                    name="paid_at"
                    value="{{ old('paid_at') ? str_replace(' ', 'T', old('paid_at')) : now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}"
                    max="{{ now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}"
                    class="input"
                    required
                >

            </div>


            {{-- NOTES --}}

            <div class="group-field group-field-full">

                <label
                    class="field-label"
                    for="notes"
                    data-i18n="notes"
                >
                    Notes
                </label>

                <input
                    type="text"
                    id="notes"
                    name="notes"
                    value="{{ old('notes') }}"
                    class="input"
                    maxlength="1000"
                >

            </div>

            </div>

        </details>


        <div class="form-footer payment-form-footer">

            <button
                type="submit"
                class="save-button"
            >
                <x-icon name="save" />
                <span data-i18n="save_payment">Save &amp; get receipt</span>
            </button>

        </div>

    </form>

    @endif


    {{-- LEDGER --}}

    <details class="payment-ledger">

        <summary>
            <span data-i18n="month_by_month">Month-by-month</span>
            <x-icon name="chevron-down" class="schedule-summary-caret" />
        </summary>

        <div class="schedule-table-wrapper">

            <table class="schedule-table schedule-table-readonly">

                <thead>
                    <tr>
                        <th data-i18n="month_number">Month</th>
                        <th data-i18n="period">Period</th>
                        <th class="text-right" data-i18n="paid">Paid</th>
                        <th class="text-right" data-i18n="balance">Balance</th>
                        <th data-i18n="status">Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($ledger as $row)

                        <tr>
                            <td class="schedule-month">{{ $row['month'] }}</td>
                            <td class="schedule-date">{{ $row['period'] }}</td>
                            <td class="text-right"><x-rupees :amount="$row['paid']" /></td>
                            <td class="text-right"><x-rupees :amount="$row['balance']" /></td>
                            <td>
                                <span
                                    class="ledger-status ledger-status-{{ $row['status'] }}"
                                    data-i18n="ledger_{{ $row['status'] }}"
                                >{{ ['paid' => 'Paid', 'partial' => 'Part paid', 'due' => 'Pending', 'upcoming' => 'Upcoming'][$row['status']] }}</span>
                            </td>
                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </details>

</section>

