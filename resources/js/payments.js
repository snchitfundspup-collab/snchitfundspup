/*
|--------------------------------------------------------------------------
| PAYMENTS — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| - All Payments: live search / group / method / from–to date filters
| - Collect: live customer search (swaps in only the results list)
| - Month picker: pending months (past due) and the month due next
| - Full month: amount = the chosen month's balance (read-only)
| - Partial / daily: starts empty, the admin types an amount up to the
|   month's balance. Month 1 is full only; a part-paid month is partial
|   only.
| - Reference field only for non-cash methods
*/


const rupees = new Intl.NumberFormat('en-IN');

function parseRupees(value) {

    const digits = String(value || '').replace(/[^\d]/g, '');

    return digits === '' ? 0 : Number(digits);

}


/* =========================================================
   LIVE CUSTOMER SEARCH
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('paymentSearchForm');
    const input = document.getElementById('paymentSearchInput');
    const results = document.getElementById('paymentSearchResults');

    if (!form || !input || !results) {
        return;
    }

    let timer = null;

    async function search(term) {

        const url = new URL(form.action);

        if (term !== '') {
            url.searchParams.set('q', term);
        }

        try {

            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            });

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = page.getElementById('paymentSearchResults');

            if (fresh) {
                results.innerHTML = fresh.innerHTML;
            }

            window.history.replaceState({}, '', url.toString());

            if (window.changeLanguage) {
                window.changeLanguage(localStorage.getItem('sn-language') || 'en');
            }

        } catch (error) {

            console.error('Payment search error:', error);

        }

    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { search(input.value.trim()); }, 300);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        search(input.value.trim());
    });

});


/* =========================================================
   ALL PAYMENTS — LIVE FILTERS
   Typing in the search box (receipt no., customer or group) or
   changing the method / date refreshes only the results.
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('paymentFilterForm');
    const results = document.getElementById('paymentsResults');
    const clearLink = document.getElementById('paymentFilterClear');

    if (!form || !results) {
        return;
    }

    let timer = null;
    let latestRequest = 0;


    async function refresh() {

        const url = new URL(form.action);

        new FormData(form).forEach(function (value, key) {
            if (String(value).trim() !== '') {
                url.searchParams.set(key, String(value).trim());
            }
        });

        const requestNumber = ++latestRequest;

        try {

            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            });

            if (!response.ok) {
                throw new Error('Filter request failed');
            }

            const html = await response.text();

            /* a slower, older request must not overwrite newer results */
            if (requestNumber !== latestRequest) {
                return;
            }

            const page = new DOMParser().parseFromString(html, 'text/html');
            const fresh = page.getElementById('paymentsResults');
            const freshRanges = page.getElementById('paymentRanges');
            const ranges = document.getElementById('paymentRanges');
            const freshClear = page.getElementById('paymentFilterClear');

            if (fresh) {
                results.innerHTML = fresh.innerHTML;
            }

            /* the quick range buttons keep the other filters */
            if (freshRanges && ranges) {
                ranges.innerHTML = freshRanges.innerHTML;
            }

            if (clearLink && freshClear) {
                clearLink.hidden = freshClear.hidden;
            }

            window.history.replaceState({}, '', url.toString());

            if (window.changeLanguage) {
                window.changeLanguage(localStorage.getItem('sn-language') || 'en');
            }

        } catch (error) {

            console.error('Payment filter error:', error);

        }

    }


    form.addEventListener('input', function (event) {

        if (event.target.name !== 'q') {
            return;
        }

        clearTimeout(timer);
        timer = setTimeout(refresh, 300);

    });

    form.addEventListener('change', function (event) {

        if (['method', 'group', 'from', 'to', 'partner', 'supplier', 'view'].includes(event.target.name)) {
            refresh();
        }

    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        clearTimeout(timer);
        refresh();
    });

});


/* =========================================================
   COLLECT FORM
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('paymentForm');

    if (!form) {
        return;
    }

    const amountInput = document.getElementById('amount');
    const amountCovers = document.getElementById('amountCovers');
    const referenceField = document.getElementById('referenceField');
    const fullOption = document.getElementById('payModeFull');
    const partialOption = document.getElementById('payModePartial');
    const fullOnlyNote = document.getElementById('fullOnlyNote');
    const partialOnlyNote = document.getElementById('partialOnlyNote');


    function selectedMonth() {

        const radio = form.querySelector('input[name="month_number"]:checked');

        return {
            number: Number(radio.value),
            balance: Number(radio.dataset.balance),
            paid: Number(radio.dataset.paid),
            fullOnly: radio.dataset.fullOnly === '1'
        };

    }


    function currentMode() {
        return form.querySelector('input[name="pay_mode"]:checked')?.value || 'full';
    }


    function setMode(mode) {
        form.querySelector('input[name="pay_mode"][value="' + mode + '"]').checked = true;
    }


    /* the amount can never be more than the chosen month's balance */
    function describeAmount() {

        const month = selectedMonth();
        const amount = parseRupees(amountInput.value);

        amountCovers.classList.remove('is-error');

        if (!amount) {
            amountCovers.textContent = '';
            return;
        }

        if (amount > month.balance) {
            amountCovers.textContent = 'Month ' + month.number + ' only has ₹' + rupees.format(month.balance) + ' left — enter that much or less.';
            amountCovers.classList.add('is-error');
            return;
        }

        const left = month.balance - amount;

        amountCovers.textContent = left === 0
            ? 'Month ' + month.number + ' will be fully paid.'
            : 'For month ' + month.number + ' · ₹' + rupees.format(left) + ' still left after this.';

    }


    /* month 1 → full only; a part-paid month → partial only */
    function applyMonth() {

        const month = selectedMonth();
        const partialOnly = !month.fullOnly && month.paid > 0;

        fullOption.hidden = partialOnly;
        partialOption.hidden = month.fullOnly;
        fullOnlyNote.hidden = !month.fullOnly;
        partialOnlyNote.hidden = !partialOnly;

        if (month.fullOnly) {
            setMode('full');
        } else if (partialOnly) {
            setMode('partial');
        }

        applyMode();

    }


    function applyMode() {

        const isFull = currentMode() === 'full';

        amountInput.readOnly = isFull;

        if (isFull) {

            amountInput.value = rupees.format(selectedMonth().balance);

        } else {

            /* partial: the admin types the amount — never pre-filled */
            amountInput.value = '';
            amountInput.focus();

        }

        describeAmount();

    }


    function applyMethod() {

        const method = form.querySelector('input[name="method"]:checked')?.value;

        referenceField.hidden = method === 'cash';

    }


    form.querySelectorAll('input[name="month_number"]').forEach(function (radio) {
        radio.addEventListener('change', applyMonth);
    });

    form.querySelectorAll('input[name="pay_mode"]').forEach(function (radio) {
        radio.addEventListener('change', applyMode);
    });

    form.querySelectorAll('input[name="method"]').forEach(function (radio) {
        radio.addEventListener('change', applyMethod);
    });

    amountInput.addEventListener('input', describeAmount);

    amountInput.addEventListener('blur', function () {
        const amount = parseRupees(amountInput.value);
        amountInput.value = amount ? rupees.format(amount) : '';
    });


    applyMethod();
    describeAmount();

});


/* =========================================================
   QUICK COLLECT (₹ button on a Collect list row)
   Same rules as the full form: pick a pending / due month;
   month 1 is full only, a part-paid month partial only; the
   amount never goes over the month's balance. Date and time
   default to now (office clock).
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('quickCollectModal');

    if (!modal) {
        return;
    }

    const form = document.getElementById('quickCollectForm');
    const monthsBox = document.getElementById('quickCollectMonths');
    const amountInput = document.getElementById('quickCollectAmount');
    const hint = document.getElementById('quickCollectHint');
    const fullOption = document.getElementById('quickPayModeFull');
    const partialOption = document.getElementById('quickPayModePartial');
    const fullOnlyNote = document.getElementById('quickFullOnlyNote');
    const partialOnlyNote = document.getElementById('quickPartialOnlyNote');
    const referenceField = document.getElementById('quickReferenceField');

    /* office time: the server's clock when the page loaded + time since */
    const serverNow = new Date(modal.dataset.serverNow).getTime();
    const loadedAt = Date.now();

    const BADGES = {
        pending: ['due-badge-due', 'collect_state_pending', 'Pending'],
        partial: ['due-badge-part', 'collect_state_partial', 'Part paid'],
        due: ['due-badge-month', 'collect_state_due', 'Due'],
        upcoming: ['due-badge-upcoming', 'collect_state_upcoming', 'Upcoming']
    };

    let months = [];


    function officeNow() {

        const now = new Date(serverNow + (Date.now() - loadedAt));
        const pad = function (n) { return String(n).padStart(2, '0'); };

        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) +
            'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());

    }


    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }


    function renderMonths() {

        monthsBox.innerHTML = months.map(function (month, index) {

            const state = month.status === 'pending' ? 'pending' : (month.paid > 0 ? 'partial' : month.status);
            const badge = BADGES[state] || BADGES.due;

            return '<label class="month-choice month-choice-' + state + '">' +
                '<input type="radio" name="month_number" value="' + month.month + '"' + (index === 0 ? ' checked' : '') + '>' +
                '<span class="month-choice-body">' +
                    '<span class="month-choice-top">' +
                        '<strong><span data-i18n="month_number">Month</span> ' + month.month + '</strong>' +
                        '<span class="due-badge ' + badge[0] + '" data-i18n="' + badge[1] + '">' + badge[2] + '</span>' +
                    '</span>' +
                    '<small>' + escapeHtml(month.period) + '</small>' +
                    '<span class="month-choice-amount">₹' + rupees.format(month.balance) + '</span>' +
                '</span>' +
            '</label>';

        }).join('');

    }


    function selectedMonth() {
        const value = Number(form.querySelector('input[name="month_number"]:checked')?.value);
        return months.find(function (month) { return month.month === value; }) || months[0];
    }


    function setMode(mode) {
        form.querySelector('input[name="pay_mode"][value="' + mode + '"]').checked = true;
    }


    function describeAmount() {

        const month = selectedMonth();
        const amount = parseRupees(amountInput.value);

        hint.classList.remove('is-error');

        if (!amount) {
            hint.textContent = '';
            return;
        }

        if (amount > month.balance) {
            hint.textContent = 'Month ' + month.month + ' only has ₹' + rupees.format(month.balance) + ' left — enter that much or less.';
            hint.classList.add('is-error');
            return;
        }

        const left = month.balance - amount;

        hint.textContent = left === 0
            ? 'Month ' + month.month + ' will be fully paid.'
            : 'For month ' + month.month + ' · ₹' + rupees.format(left) + ' still left after this.';

    }


    function applyMode() {

        const isFull = form.querySelector('input[name="pay_mode"]:checked').value === 'full';

        amountInput.readOnly = isFull;
        amountInput.value = isFull ? rupees.format(selectedMonth().balance) : '';

        if (!isFull) {
            amountInput.focus();
        }

        describeAmount();

    }


    function applyMonth() {

        const month = selectedMonth();
        const partialOnly = !month.full_only && month.paid > 0;

        fullOption.hidden = partialOnly;
        partialOption.hidden = month.full_only;
        fullOnlyNote.hidden = !month.full_only;
        partialOnlyNote.hidden = !partialOnly;

        if (month.full_only) {
            setMode('full');
        } else if (partialOnly) {
            setMode('partial');
        }

        applyMode();

    }


    function applyMethod() {
        const method = form.querySelector('input[name="method"]:checked')?.value;
        referenceField.hidden = method === 'cash';
    }


    function open(button) {

        months = JSON.parse(button.dataset.months || '[]');

        if (months.length === 0) {
            return;
        }

        document.getElementById('quickCollectMember').value = button.dataset.member;
        document.getElementById('quickCollectPaidAt').value = officeNow();
        document.getElementById('quickCollectCode').textContent = button.dataset.code;
        document.getElementById('quickCollectName').textContent = button.dataset.name;
        document.getElementById('quickCollectGroup').textContent = button.dataset.group;
        document.getElementById('quickCollectFullForm').href = button.dataset.fullForm;

        form.querySelector('input[name="method"][value="cash"]').checked = true;
        document.getElementById('quickCollectReference').value = '';

        renderMonths();
        setMode('full');
        applyMonth();
        applyMethod();

        if (window.changeLanguage) {
            window.changeLanguage(localStorage.getItem('sn-language') || 'en');
        }

        modal.hidden = false;
        document.body.classList.add('modal-open');

    }


    function close() {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    }


    /* rows are replaced by the live search, so listen on the document */
    document.addEventListener('click', function (event) {

        const button = event.target.closest('[data-quick-collect]');

        if (button) {
            event.preventDefault();
            open(button);
            return;
        }

        if (event.target.closest('[data-close-quick-collect]')) {
            close();
        }

    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            close();
        }
    });

    monthsBox.addEventListener('change', applyMonth);

    form.querySelectorAll('input[name="pay_mode"]').forEach(function (radio) {
        radio.addEventListener('change', applyMode);
    });

    form.querySelectorAll('input[name="method"]').forEach(function (radio) {
        radio.addEventListener('change', applyMethod);
    });

    amountInput.addEventListener('input', describeAmount);

    amountInput.addEventListener('blur', function () {
        const amount = parseRupees(amountInput.value);
        amountInput.value = amount ? rupees.format(amount) : '';
    });

    form.addEventListener('submit', function (event) {

        const month = selectedMonth();
        const amount = parseRupees(amountInput.value);

        if (!amount || amount > month.balance) {
            event.preventDefault();
            describeAmount();
            amountInput.focus();
            return;
        }

        /* the time the money was actually taken */
        document.getElementById('quickCollectPaidAt').value = officeNow();

    });

});
