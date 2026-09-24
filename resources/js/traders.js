/*
|--------------------------------------------------------------------------
| SN TRADERS — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| - Rice bill lines (purchase / sale): add and remove lines; amount =
|   bags × rate per bag; running total. Sale lines show the bags left.
| - "Received now" → Full fills the invoice total.
| - Customer picker: typing filters the customer list.
*/

const rupees = new Intl.NumberFormat('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

function bagsText(bags) {
    return bags + (Math.abs(bags) === 1 ? ' bag' : ' bags');
}

function toNumber(value) {
    const number = parseFloat(String(value || '').replace(/[^\d.]/g, ''));
    return Number.isFinite(number) ? number : 0;
}


/* =========================================================
   RICE BILL LINES
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const editor = document.querySelector('[data-bill-lines]');

    if (!editor) {
        return;
    }

    const body = editor.querySelector('[data-line-rows]');
    const template = document.getElementById('billLineTemplate');
    const totalOut = document.getElementById('billTotal');
    const bagsOut = document.getElementById('billBags');
    const receivedInput = document.getElementById('received_amount');
    const fullButton = document.getElementById('receivedFull');
    const receivedHint = document.getElementById('receivedHint');

    let nextIndex = body.querySelectorAll('[data-line]').length;
    let billTotal = 0;


    function computeRow(row) {

        const option = row.querySelector('[data-field="variety"]').selectedOptions[0];
        const bags = Math.floor(toNumber(row.querySelector('[data-field="bags"]').value));
        const rate = toNumber(row.querySelector('[data-field="rate"]').value);
        const amount = bags * rate;

        row.querySelector('[data-out="amount"]').textContent = amount ? '₹' + rupees.format(Math.round(amount * 100) / 100) : '—';

        /* sale lines: stock left for the variety */
        const stockOut = row.querySelector('[data-out="stock"]');

        if (stockOut) {
            if (option && option.dataset.stock !== undefined && option.value) {
                const left = (parseInt(option.dataset.stock, 10) || 0) - bags;
                stockOut.textContent = 'Stock after: ' + bagsText(left);
                stockOut.classList.toggle('is-error', left < 0);
            } else {
                stockOut.textContent = '';
            }
        }

        return { bags: bags, amount: Math.round(amount * 100) / 100 };

    }


    function computeAll() {

        let total = 0;
        let bags = 0;

        body.querySelectorAll('[data-line]').forEach(function (row) {
            const line = computeRow(row);
            total += line.amount;
            bags += line.bags;
        });

        billTotal = Math.round(total * 100) / 100;

        totalOut.textContent = '₹' + rupees.format(billTotal);
        bagsOut.textContent = bagsText(bags);

        describeReceived();

    }


    function describeReceived() {

        if (!receivedInput || !receivedHint) {
            return;
        }

        const received = toNumber(receivedInput.value);

        receivedHint.classList.remove('is-error');

        if (received > billTotal + 0.001) {
            receivedHint.textContent = 'More than the invoice total (₹' + rupees.format(billTotal) + ').';
            receivedHint.classList.add('is-error');
        } else if (billTotal > 0) {
            const credit = Math.round((billTotal - received) * 100) / 100;
            receivedHint.textContent = credit > 0
                ? '₹' + rupees.format(credit) + ' goes on the customer\'s credit.'
                : 'Fully paid now.';
        } else {
            receivedHint.textContent = '';
        }

    }


    function addLine() {

        const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
        body.insertAdjacentHTML('beforeend', html);

        const row = body.lastElementChild;
        row.querySelector('[data-field="variety"]').focus();

        computeAll();

    }


    editor.addEventListener('change', computeAll);

    editor.addEventListener('input', computeAll);

    editor.addEventListener('click', function (event) {

        if (event.target.closest('[data-add-line]')) {
            addLine();
            return;
        }

        const remove = event.target.closest('[data-remove-line]');

        if (remove) {
            const rows = body.querySelectorAll('[data-line]');

            if (rows.length > 1) {
                remove.closest('[data-line]').remove();
            } else {
                rows[0].querySelectorAll('input').forEach(function (input) { input.value = ''; });
                rows[0].querySelector('select').value = '';
            }

            computeAll();
        }

    });

    if (receivedInput) {
        receivedInput.addEventListener('input', describeReceived);
    }

    if (fullButton) {
        fullButton.addEventListener('click', function () {
            receivedInput.value = billTotal ? rupees.format(billTotal) : '';
            describeReceived();
        });
    }

    computeAll();

});


/* =========================================================
   CUSTOMER / SUPPLIER PICKER — type to filter the list
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('[data-filter-select]').forEach(function (input) {

        const select = document.getElementById(input.dataset.filterSelect);

        if (!select) {
            return;
        }

        const options = Array.from(select.options);

        input.addEventListener('input', function () {

            const term = input.value.trim().toLowerCase();
            let firstMatch = null;

            options.forEach(function (option) {
                if (!option.value) {
                    return;
                }

                const match = term === '' || option.textContent.toLowerCase().includes(term);
                option.hidden = !match;

                if (match && !firstMatch) {
                    firstMatch = option;
                }
            });

            if (term !== '' && firstMatch && (select.selectedOptions[0]?.hidden || !select.value)) {
                select.value = firstMatch.value;
                select.dispatchEvent(new Event('change'));
            }

        });

    });

});
