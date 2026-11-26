{{-- Collect page: ticking a customer opens this box to confirm the
     collection — date and amount (one instalment to start), cash.
     finance.js fills it from the ticked row; Cancel clears the tick. --}}

<div class="member-modal" id="financeQuickCollect" hidden>

    <div class="member-modal-overlay" data-cancel-quick-collect></div>

    <div class="member-modal-dialog quick-collect-dialog" role="dialog" aria-modal="true" aria-labelledby="financeQuickName">

        <div class="member-modal-header">
            <span class="member-modal-avatar avatar-3d icon-3d icon-3d-green" aria-hidden="true"><x-icon name="rupee" /></span>
            <div class="member-modal-heading">
                <span class="member-modal-code" id="financeQuickCode"></span>
                <h2 id="financeQuickName"></h2>
                <span class="quick-collect-group" data-i18n="confirm_collection">Confirm the collection</span>
            </div>
            <button type="button" class="member-modal-close" data-cancel-quick-collect aria-label="Close"><x-icon name="x" /></button>
        </div>

        <form method="POST" action="#" class="member-modal-body payment-form" id="financeQuickForm">

            @csrf
            <input type="hidden" name="method" value="cash">
            <input type="hidden" name="back" value="collect">

            <div class="group-form-grid payment-fields">

                <div class="group-field">
                    <label class="field-label" for="financeQuickDate" data-i18n="payment_date_time">Date &amp; time</label>
                    <input type="datetime-local" id="financeQuickDate" name="collected_at" class="input" required
                        value="{{ now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}"
                        max="{{ now(config('app.business_timezone'))->format('Y-m-d\TH:i') }}">
                </div>

                <div class="group-field">
                    <label class="field-label" for="financeQuickAmount" data-i18n="amount_rupees">Amount (₹)</label>
                    <input type="text" id="financeQuickAmount" name="amount" class="input money-input payment-amount-input" inputmode="numeric" autocomplete="off" required>
                    <small class="installment-hint"><span data-i18n="balance_word">Balance</span> <span id="financeQuickBalance"></span></small>
                </div>

            </div>

            <div class="quick-collect-actions">
                <button type="button" class="group-action" data-cancel-quick-collect>
                    <x-icon name="x" />
                    <span data-i18n="cancel_order">Cancel</span>
                </button>
                <button type="submit" class="save-button">
                    <x-icon name="check" />
                    <span data-i18n="submit_word">Submit</span>
                </button>
            </div>

        </form>

    </div>

</div>
