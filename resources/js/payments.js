/*
|--------------------------------------------------------------------------
| PAYMENTS — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| - All Payments: live search / method / date filters
| - Collect: live customer search (swaps in only the results list)
| - Full month(s): amount = open balance of the next N months (read-only)
| - Partial / daily: starts empty, the admin types any amount; shows which
|   months it will cover (money always fills the oldest unpaid month
|   first). When the next month is already part paid, only Partial shows.
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

            const fresh = new DOMParser()
                .parseFromString(html, 'text/html')
                .getElementById('paymentsResults');

            if (fresh) {
                results.innerHTML = fresh.innerHTML;
            }

            if (clearLink) {
                clearLink.hidden = url.search === '';
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

        if (event.target.name === 'method' || event.target.name === 'date') {
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

    const openMonths = JSON.parse(document.getElementById('openMonths')?.textContent || '[]');

    const amountInput = document.getElementById('amount');
    const amountCovers = document.getElementById('amountCovers');
    const monthsField = document.getElementById('monthsField');
    const monthsInput = document.getElementById('months_count');
    const monthsCovered = document.getElementById('monthsCovered');
    const referenceField = document.getElementById('referenceField');


    function currentMode() {
        return form.querySelector('input[name="pay_mode"]:checked')?.value || 'full';
    }


    function monthRangeLabel(first, last) {
        return first === last
            ? 'Month ' + first
            : 'Months ' + first + '–' + last;
    }


    /* which months an amount fills, oldest first */
    function coverage(amount) {

        let left = amount;
        const touched = [];

        for (const month of openMonths) {

            if (left <= 0) {
                break;
            }

            const used = Math.min(month.balance, left);

            touched.push({ month: month.month, used: used, full: used === month.balance, balance: month.balance });

            left -= used;

        }

        return { touched: touched, extra: left };

    }


    function describeCoverage() {

        const amount = parseRupees(amountInput.value);

        if (!amount) {
            amountCovers.textContent = '';
            return;
        }

        const result = coverage(amount);

        if (result.extra > 0) {
            amountCovers.textContent = '₹' + rupees.format(result.extra) + ' more than the whole group — reduce the amount.';
            amountCovers.classList.add('is-error');
            return;
        }

        amountCovers.classList.remove('is-error');

        const first = result.touched[0];
        const last = result.touched[result.touched.length - 1];

        let text = 'Goes to ' + monthRangeLabel(first.month, last.month);

        if (!last.full) {
            text += ' (₹' + rupees.format(last.balance - last.used) + ' still left in month ' + last.month + ')';
        }

        amountCovers.textContent = text + '.';

    }


    function fillFullMonths() {

        const count = Math.min(
            Math.max(1, Number(monthsInput.value) || 1),
            Math.max(1, openMonths.length)
        );

        monthsInput.value = count;

        const chosen = openMonths.slice(0, count);

        const total = chosen.reduce(function (sum, month) {
            return sum + month.balance;
        }, 0);

        amountInput.value = total ? rupees.format(total) : '';

        monthsCovered.textContent = chosen.length
            ? monthRangeLabel(chosen[0].month, chosen[chosen.length - 1].month) +
              ' · ' + chosen[0].period
            : '';

        describeCoverage();

    }


    function applyMode() {

        const isFull = currentMode() === 'full';

        monthsField.hidden = !isFull;
        amountInput.readOnly = isFull;

        if (isFull) {

            fillFullMonths();

        } else {

            /* partial: the admin types the amount — never pre-filled */
            amountInput.value = '';
            describeCoverage();
            amountInput.focus();

        }

    }


    function applyMethod() {

        const method = form.querySelector('input[name="method"]:checked')?.value;

        referenceField.hidden = method === 'cash';

    }


    form.querySelectorAll('input[name="pay_mode"]').forEach(function (radio) {
        radio.addEventListener('change', applyMode);
    });

    form.querySelectorAll('input[name="method"]').forEach(function (radio) {
        radio.addEventListener('change', applyMethod);
    });

    form.querySelectorAll('.month-stepper-button').forEach(function (button) {
        button.addEventListener('click', function () {
            monthsInput.value = (Number(monthsInput.value) || 1) + Number(button.dataset.step);
            fillFullMonths();
        });
    });

    monthsInput.addEventListener('input', fillFullMonths);

    amountInput.addEventListener('input', describeCoverage);

    amountInput.addEventListener('blur', function () {
        const amount = parseRupees(amountInput.value);
        amountInput.value = amount ? rupees.format(amount) : '';
    });


    applyMethod();

    if (currentMode() === 'full') {
        fillFullMonths();
    } else {
        describeCoverage();
    }

});
