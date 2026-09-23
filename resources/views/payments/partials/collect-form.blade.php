{{-- Collect form for one member seat: full month(s) or a partial amount,
     method, date, reference and notes, plus the seat's month ledger. --}}

@php
    $group = $member->chitGroup;
    $openMonths = $member->openMonths();
    $balanceDue = $member->balanceDue();
    $ledger = $member->ledger();

    /* the next month already has part of its installment paid → only
       partial payments are offered until that month is complete */
    $nextMonth = $member->nextUnpaidMonth();
    $nextMonthPaid = $nextMonth ? ($member->paidByMonth()[$nextMonth] ?? 0) : 0;
    $partialOnly = $nextMonthPaid > 0;

    $isPartial = $partialOnly || old('pay_mode') === 'partial';
@endphp

<section
    class="group-panel glass payment-step"
    id="collect"
>

    <h2 class="payment-seats-title">
        <span class="step-number">3</span>
        <span data-i18n="record_payment">Record payment</span>
        <span class="payment-form-seat">— {{ $member->member_code }} · {{ $group->name }}</span>
    </h2>


    {{-- SUMMARY --}}

    <div class="payment-summary">

        <div>
            <span data-i18n="due_now">Due now</span>
            <strong @class(['is-due' => $balanceDue > 0])><x-rupees :amount="$balanceDue" /></strong>
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


    <form
        method="POST"
        action="{{ route('payments.store') }}"
        class="payment-form"
        id="paymentForm"
    >

        @csrf

        <input type="hidden" name="chit_group_member_id" value="{{ $member->id }}">


        {{-- FULL / PARTIAL --}}

        @if ($partialOnly)
            <p class="partial-only-note">
                <x-icon name="info" />
                <span>
                    <span data-i18n="month_number">Month</span> {{ $nextMonth }}
                    <span data-i18n="partly_paid_note">is part paid</span>
                    (<x-rupees :amount="$nextMonthPaid" /> <span data-i18n="of">of</span> <x-rupees :amount="$group->installment_amount" />).
                    <span data-i18n="continue_partial">Continue with a partial payment.</span>
                </span>
            </p>
        @endif

        <div @class(['pay-mode', 'pay-mode-single' => $partialOnly]) role="radiogroup" aria-label="Payment type">

            @unless ($partialOnly)
                <label class="pay-mode-option">
                    <input
                        type="radio"
                        name="pay_mode"
                        value="full"
                        @checked(! $isPartial)
                    >
                    <span>
                        <x-icon name="calendar" />
                        <span data-i18n="pay_full">Full month(s)</span>
                    </span>
                </label>
            @endunless

            <label class="pay-mode-option">
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


            {{-- MONTHS (full mode) --}}

            <div
                class="group-field"
                id="monthsField"
                @if ($isPartial) hidden @endif
                @if ($partialOnly) data-partial-only @endif
            >

                <label
                    class="field-label"
                    for="months_count"
                    data-i18n="how_many_months"
                >
                    How many months?
                </label>

                <div class="month-stepper">

                    <button
                        type="button"
                        class="month-stepper-button"
                        data-step="-1"
                        aria-label="One month less"
                    ><x-icon name="chevron-left" /></button>

                    <input
                        type="number"
                        id="months_count"
                        class="input month-stepper-input"
                        value="1"
                        min="1"
                        max="{{ max(1, count($openMonths)) }}"
                    >

                    <button
                        type="button"
                        class="month-stepper-button"
                        data-step="1"
                        aria-label="One month more"
                    ><x-icon name="chevron-right" /></button>

                </div>

                <small
                    class="installment-hint"
                    id="monthsCovered"
                ></small>

            </div>


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
                    value="{{ $isPartial ? old('amount') : '' }}"
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
                                >{{ ucfirst($row['status']) }}</span>
                            </td>
                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </details>

</section>


<script type="application/json" id="openMonths">@json($openMonths)</script>
