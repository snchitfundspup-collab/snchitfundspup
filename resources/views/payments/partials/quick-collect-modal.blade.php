{{-- Quick collect: opened by the ₹ button on a Collect list row. The same
     rules as the full form (payments.js fills it from the row's data):
     pick a pending / due month, Full (month 1 always) or Partial (never
     more than the month's balance), method → Save & get receipt. Date and
     time default to now; the full form has the extra fields. --}}

<div
    class="member-modal"
    id="quickCollectModal"
    data-server-now="{{ now(config('app.business_timezone'))->format('Y-m-d\TH:i:s') }}"
    hidden
>

    <div
        class="member-modal-overlay"
        data-close-quick-collect
    ></div>

    <div
        class="member-modal-dialog quick-collect-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="quickCollectName"
    >

        <div class="member-modal-header">

            <span
                class="member-modal-avatar avatar-3d icon-3d icon-3d-green"
                aria-hidden="true"
            ><x-icon name="rupee" /></span>

            <div class="member-modal-heading">
                <span
                    class="member-modal-code"
                    id="quickCollectCode"
                ></span>
                <h2 id="quickCollectName"></h2>
                <span
                    class="quick-collect-group"
                    id="quickCollectGroup"
                ></span>
            </div>

            <button
                type="button"
                class="member-modal-close"
                data-close-quick-collect
                aria-label="Close"
            >
                <x-icon name="x" />
            </button>

        </div>


        <form
            method="POST"
            action="{{ route('payments.store') }}"
            class="member-modal-body payment-form"
            id="quickCollectForm"
        >

            @csrf

            <input type="hidden" name="chit_group_member_id" id="quickCollectMember">
            <input type="hidden" name="paid_at" id="quickCollectPaidAt">


            <span class="field-label" data-i18n="choose_month">Which month is this for?</span>

            <div
                class="month-picker"
                id="quickCollectMonths"
                role="radiogroup"
                aria-label="Month"
            ></div>


            <p class="partial-only-note" id="quickFullOnlyNote" hidden>
                <x-icon name="info" />
                <span data-i18n="month_one_full_only">Month 1 is paid in full — no partial payments.</span>
            </p>

            <p class="partial-only-note" id="quickPartialOnlyNote" hidden>
                <x-icon name="info" />
                <span data-i18n="continue_partial_month">This month is part paid. Continue with a partial payment.</span>
            </p>


            <div class="pay-mode" role="radiogroup" aria-label="Payment type">

                <label class="pay-mode-option" id="quickPayModeFull">
                    <input type="radio" name="pay_mode" value="full" checked>
                    <span>
                        <x-icon name="calendar" />
                        <span data-i18n="pay_full_month">Full month</span>
                    </span>
                </label>

                <label class="pay-mode-option" id="quickPayModePartial">
                    <input type="radio" name="pay_mode" value="partial">
                    <span>
                        <x-icon name="rupee" />
                        <span data-i18n="pay_partial">Partial / daily amount</span>
                    </span>
                </label>

            </div>


            <div class="group-form-grid payment-fields">

                <div class="group-field">

                    <label class="field-label" for="quickCollectAmount" data-i18n="amount_rupees">Amount (₹)</label>

                    <input
                        type="text"
                        id="quickCollectAmount"
                        name="amount"
                        class="input money-input payment-amount-input"
                        inputmode="numeric"
                        placeholder="Enter amount"
                        autocomplete="off"
                        required
                    >

                    <small class="installment-hint" id="quickCollectHint"></small>

                </div>


                <div class="group-field">

                    <span class="field-label" data-i18n="payment_method">Payment method</span>

                    <div class="method-options">
                        @foreach (\App\Models\Payment::METHODS as $methodValue => $methodLabel)
                            <label class="method-option">
                                <input
                                    type="radio"
                                    name="method"
                                    value="{{ $methodValue }}"
                                    @checked($methodValue === 'cash')
                                >
                                <span data-i18n="method_{{ $methodValue }}">{{ $methodLabel }}</span>
                            </label>
                        @endforeach
                    </div>

                </div>


                <div class="group-field group-field-full" id="quickReferenceField" hidden>

                    <label class="field-label" for="quickCollectReference" data-i18n="reference_number">Reference / UPI / cheque no.</label>

                    <input
                        type="text"
                        id="quickCollectReference"
                        name="reference"
                        class="input"
                        maxlength="100"
                    >

                </div>

            </div>


            <div class="quick-collect-actions">

                <a
                    href="#"
                    class="group-action"
                    id="quickCollectFullForm"
                >
                    <span data-i18n="open_full_form">Open full form</span>
                </a>

                <button type="submit" class="save-button">
                    <x-icon name="save" />
                    <span data-i18n="save_payment">Save &amp; get receipt</span>
                </button>

            </div>

        </form>

    </div>

</div>
