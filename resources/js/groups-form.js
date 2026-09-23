/*
|--------------------------------------------------------------------------
| ADD / EDIT GROUP — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| - Keeps one withdrawal row per month (rebuilt when "months" changes,
|   keeping what was already typed)
| - Shows each row's calendar month from the start date
| - Hint under the installment field (amount ÷ months, a guide only —
|   the admin types the real installment)
| - Indian number formatting for money inputs (2,00,000)
| - "Copy from existing group" fills months + withdrawal amounts
*/

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('groupForm');

    if (!form) {
        return;
    }


    const amountInput = document.getElementById('amount');
    const monthsInput = document.getElementById('months');
    const startDateInput = document.getElementById('start_date');
    const installmentInput = document.getElementById('installment_amount');
    const installmentHint = document.getElementById('installmentHint');
    const scheduleBody = document.getElementById('scheduleBody');
    const copySelect = document.getElementById('copySourceSelect');
    const copyButton = document.getElementById('copyScheduleButton');
    const copyNotice = document.getElementById('copyNotice');

    const copySources = JSON.parse(
        document.getElementById('copySources')?.textContent || '[]'
    );

    const MAX_MONTHS = 120;

    const rupeeFormatter = new Intl.NumberFormat('en-IN');

    /* fixed names so labels match the server's "15 Sep – 14 Oct 2026" */
    const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];


    /* =========================================================
       MONEY HELPERS
    ========================================================= */

    function parseRupees(value) {

        const digits = String(value || '').replace(/[^\d]/g, '');

        return digits === '' ? null : Number(digits);

    }


    function formatMoneyInput(input) {

        const value = parseRupees(input.value);

        input.value = value === null ? '' : rupeeFormatter.format(value);

    }


    /* =========================================================
       INSTALLMENT HINT (guide only)
    ========================================================= */

    function updateInstallment() {

        const amount = parseRupees(amountInput.value);
        const months = Number(monthsInput.value);

        if (!amount || !months) {

            installmentHint.textContent = '';

            return;

        }

        installmentHint.textContent =
            'Chit amount ÷ months = ₹' +
            rupeeFormatter.format(Math.round(amount / months));

    }


    /* =========================================================
       SCHEDULE ROWS
    ========================================================= */

    /* start date + N months, keeping the day but never spilling into the
       next month (31 Jan + 1 month = 28/29 Feb) — same as the server */
    function addMonthsNoOverflow(year, monthIndex, day, months) {

        const lastDay = new Date(year, monthIndex + months + 1, 0).getDate();

        return new Date(year, monthIndex + months, Math.min(day, lastDay));

    }


    /* group months run from the start date: "15 Sep – 14 Oct 2026" */
    function monthLabel(monthNumber) {

        if (!startDateInput.value) {
            return '';
        }

        const [year, month, day] = startDateInput.value.split('-').map(Number);

        const start = addMonthsNoOverflow(year, month - 1, day, monthNumber - 1);
        const end = addMonthsNoOverflow(year, month - 1, day, monthNumber);
        end.setDate(end.getDate() - 1);

        const short = function (date) {
            return date.getDate() + ' ' + MONTH_NAMES[date.getMonth()];
        };

        return start.getFullYear() === end.getFullYear()
            ? short(start) + ' – ' + short(end) + ' ' + end.getFullYear()
            : short(start) + ' ' + start.getFullYear() + ' – ' + short(end) + ' ' + end.getFullYear();

    }


    function currentScheduleValues() {

        const values = {};

        scheduleBody
            .querySelectorAll('.schedule-input')
            .forEach(function (input, index) {
                values[index + 1] = input.value;
            });

        return values;

    }


    function buildRows(months, values) {

        scheduleBody.innerHTML = '';

        for (let monthNumber = 1; monthNumber <= months; monthNumber++) {

            const row = document.createElement('tr');

            const monthCell = document.createElement('td');
            monthCell.className = 'schedule-month';
            monthCell.textContent = monthNumber;

            const dateCell = document.createElement('td');
            dateCell.className = 'schedule-date';
            dateCell.dataset.month = monthNumber;
            dateCell.textContent = monthLabel(monthNumber);

            const inputCell = document.createElement('td');

            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'payouts[' + monthNumber + ']';
            input.className = 'input money-input schedule-input';
            input.inputMode = 'numeric';
            input.required = true;
            input.value = values[monthNumber] || '';
            input.setAttribute(
                'aria-label',
                'Withdrawal amount for month ' + monthNumber
            );

            inputCell.appendChild(input);

            row.append(monthCell, dateCell, inputCell);

            scheduleBody.appendChild(row);

        }

    }


    function rebuildForMonths() {

        const months = Math.min(
            MAX_MONTHS,
            Math.max(1, Number(monthsInput.value) || 1)
        );

        if (scheduleBody.children.length === months) {
            return;
        }

        buildRows(months, currentScheduleValues());

    }


    function refreshMonthLabels() {

        scheduleBody
            .querySelectorAll('.schedule-date')
            .forEach(function (cell) {
                cell.textContent = monthLabel(Number(cell.dataset.month));
            });

    }


    /* =========================================================
       COPY FROM EXISTING GROUP
    ========================================================= */

    function copySchedule() {

        const source = copySources.find(function (group) {
            return String(group.id) === copySelect.value;
        });

        if (!source) {
            return;
        }

        monthsInput.value = source.months;

        if (!parseRupees(amountInput.value)) {
            amountInput.value = rupeeFormatter.format(source.amount);
        }

        if (!parseRupees(installmentInput.value) && source.installment) {
            installmentInput.value = rupeeFormatter.format(source.installment);
        }

        const values = {};

        source.payouts.forEach(function (withdrawalAmount, index) {
            values[index + 1] = rupeeFormatter.format(withdrawalAmount);
        });

        buildRows(source.months, values);

        updateInstallment();

        copyNotice.textContent =
            'Copied ' + source.months + ' withdrawal amounts from "' +
            source.name + '". You can still change any of them.';

        copyNotice.hidden = false;

    }


    /* =========================================================
       EVENTS
    ========================================================= */

    amountInput.addEventListener('input', updateInstallment);

    monthsInput.addEventListener('input', function () {
        rebuildForMonths();
        updateInstallment();
    });

    startDateInput.addEventListener('change', refreshMonthLabels);

    form.addEventListener('focusout', function (event) {
        if (event.target.classList.contains('money-input')) {
            formatMoneyInput(event.target);
        }
    });

    if (copyButton && copySelect) {
        copyButton.addEventListener('click', copySchedule);
    }


    /* first paint: format what the server sent back */

    form.querySelectorAll('.money-input').forEach(formatMoneyInput);

    updateInstallment();

});
