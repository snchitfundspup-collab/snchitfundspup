/*
|--------------------------------------------------------------------------
| SRI LAKSHMI MICRO FINANCE — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| New Loan / Edit Loan, worked out live (the server works it out the same
| way again — FinanceLoan::termsFromInstallment()):
|   fee = loan × fee %, GST = fee × GST %, in hand = loan − fee − GST
|   total = instalment × weeks / days (the office types the instalment)
|   interest = total − loan; yearly % = interest ÷ loan × 365 ÷ days
|   (weeks count 7 days)
| Changing weekly / daily suggests 14 weeks or 100 days and the first due
| date (not on Edit Loan, which keeps what was saved).
*/

const form = document.getElementById('loanForm');

const rupees = (value) => '₹' + Math.round(Number(value)).toLocaleString('en-IN');

const whole = (input) => {
    const value = parseInt(String(input?.value || '').replace(/[^\d]/g, ''), 10);

    return Number.isFinite(value) ? value : 0;
};

const decimal = (input) => {
    const value = parseFloat(String(input?.value || '').replace(/[^\d.]/g, ''));

    return Number.isFinite(value) ? value : 0;
};

const translate = (key, fallback) => {
    const language = localStorage.getItem('sn-language') || 'en';
    const dictionary = (window.SN_TRANSLATIONS && window.SN_TRANSLATIONS[language]) || {};

    return dictionary[key] || fallback;
};

function addDays(isoDate, days) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return new Date(Date.UTC(year, month - 1, day + days));
}

function formatDate(date) {
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
}

if (form) {
    const field = (id) => form.querySelector('#' + id);

    const principal = field('principal');
    const feeRate = field('processing_fee_rate');
    const gstRate = field('gst_rate');
    const interestRate = field('interest_rate');
    const installments = field('installments');
    const installment = field('installment_amount');
    const firstDue = field('first_due_on');
    const loanedOn = field('loaned_on');
    const plan = document.getElementById('loanPlan');
    const show = (id, text) => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = text;
        }
    };

    const frequency = () => form.querySelector('input[name="frequency"]:checked')?.value || 'weekly';

    /* Edit Loan keeps the saved dates and number of instalments */
    const editing = form.dataset.editing === '1';
    let firstDueTouched = editing;
    let countTouched = editing;

    const update = () => {
        const loan = whole(principal);
        const count = whole(installments);
        const each = whole(installment);
        const weekly = frequency() === 'weekly';

        const fee = Math.round(loan * decimal(feeRate) / 100);
        const gst = Math.round(fee * decimal(gstRate) / 100);
        const days = weekly ? count * 7 : count;
        const total = each * count;
        const interest = total - loan;
        const ready = loan > 0 && count > 0 && each > 0;

        /* more than the money available to lend? */
        const availableBox = document.getElementById('loanAvailable');

        if (availableBox) {
            const short = loan > 0 && (loan - fee - gst) > Number(availableBox.dataset.available);

            document.getElementById('loanAvailableWarning').hidden = !short;
            availableBox.classList.toggle('is-short', short);
        }

        show('loanFee', rupees(fee));
        show('loanGst', rupees(gst));
        show('loanInHand', rupees(Math.max(0, loan - fee - gst)));
        show('loanInterest', ready ? rupees(interest) : '₹0');
        show('loanInterestNote', ready && days > 0 ? days + ' ' + translate('days_word', 'days') : '');
        show('loanTotal', rupees(ready ? total : 0));

        interestRate.value = ready && days > 0
            ? (Math.round(interest / loan * 365 / days * 100 * 100) / 100) + '%'
            : '—';

        document.getElementById('installmentsDaysLabel').hidden = weekly;
        document.getElementById('installmentsWeeksLabel').hidden = !weekly;

        if (!ready || !firstDue.value) {
            plan.hidden = true;

            return;
        }

        const fits = total >= loan;
        const lastDate = addDays(firstDue.value, (count - 1) * (weekly ? 7 : 1));

        plan.hidden = false;
        plan.classList.toggle('is-wrong', !fits);
        plan.textContent = fits
            ? count + ' × ' + rupees(each) + ' = ' + rupees(total)
                + ' — ' + translate('last_instalment_on', 'last instalment on') + ' ' + formatDate(lastDate)
            : count + ' × ' + rupees(each) + ' = ' + rupees(total) + ' — '
                + translate('installment_below_loan', 'the instalments come to less than the loan; use at least') + ' ' + rupees(Math.ceil(loan / count));
    };

    const suggestFirstDue = () => {
        if (firstDueTouched || !loanedOn.value) {
            return;
        }

        firstDue.value = addDays(loanedOn.value, frequency() === 'weekly' ? 7 : 1).toISOString().slice(0, 10);
    };

    form.querySelectorAll('input[name="frequency"]').forEach((radio) => radio.addEventListener('change', () => {
        if (!countTouched) {
            installments.value = radio.dataset.installments;
        }

        suggestFirstDue();
        update();
    }));

    installments.addEventListener('input', () => {
        countTouched = true;
        update();
    });

    firstDue.addEventListener('input', () => {
        firstDueTouched = true;
        update();
    });

    loanedOn.addEventListener('input', () => {
        suggestFirstDue();
        update();
    });

    [principal, feeRate, gstRate, installment].forEach((input) => input.addEventListener('input', update));

    update();
}


/*
| Loan Plans (customer pages): a calculator on the standard terms — the
| same working-out as above.
*/

const calculator = document.getElementById('loanCalculator');


if (calculator) {
    const amountInput = document.getElementById('calcAmount');
    const set = (id, text) => {
        document.getElementById(id).textContent = text;
    };

    const update = () => {
        const weekly = calculator.querySelector('input[name="calcFrequency"]:checked')?.value === 'weekly';
        const loan = whole(amountInput);
        const count = Number(weekly ? calculator.dataset.weekly : calculator.dataset.daily);

        if (loan <= 0) {
            ['calcCharges', 'calcInHand', 'calcInterest', 'calcTotal', 'calcInstallment'].forEach((id) => set(id, '—'));

            return;
        }

        const fee = Math.round(loan * Number(calculator.dataset.fee) / 100);
        const gst = Math.round(fee * Number(calculator.dataset.gst) / 100);
        const days = weekly ? count * 7 : count;
        const interest = Math.round(loan * Number(calculator.dataset.interest) / 100 * days / 365);
        const total = loan + interest;
        const each = Math.ceil(total / count);
        const inHand = loan - fee - gst;


        set('calcCharges', rupees(fee + gst));
        set('calcInHand', rupees(inHand));
        set('calcInterest', rupees(interest));
        set('calcTotal', rupees(total));
        set('calcInstallment', rupees(each) + ' × ' + count);
    };

    amountInput.addEventListener('input', update);
    calculator.querySelectorAll('input[name="calcFrequency"]').forEach((radio) => radio.addEventListener('change', update));

    update();
}
