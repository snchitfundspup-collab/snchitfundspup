/*
|--------------------------------------------------------------------------
| CUSTOMER PAGES (/my) — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| Rice order: − / + buttons on each rice card, and the running number of
| bags and estimated total; "Send order" is enabled once a bag is chosen.
*/

const rupees = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 });

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('riceOrderForm');

    if (!form) {
        return;
    }

    const inputs = Array.from(form.querySelectorAll('input[name^="bags["]'));
    const totalOut = document.getElementById('riceOrderTotal');
    const bagsOut = document.getElementById('riceOrderBags');
    const submit = document.getElementById('riceOrderSubmit');


    function bagsIn(input) {
        const bags = parseInt(input.value, 10);
        return Number.isFinite(bags) && bags > 0 ? Math.min(bags, 500) : 0;
    }


    function refresh() {

        let bags = 0;
        let total = 0;

        inputs.forEach(function (input) {
            const count = bagsIn(input);
            bags += count;
            total += count * (parseFloat(input.dataset.price) || 0);
            input.closest('.rice-card').classList.toggle('is-chosen', count > 0);
        });

        totalOut.textContent = '₹' + rupees.format(total);
        bagsOut.textContent = bags + (bags === 1 ? ' bag' : ' bags');
        submit.disabled = bags === 0;

    }


    form.addEventListener('click', function (event) {

        const step = event.target.closest('.rice-step');

        if (!step) {
            return;
        }

        const input = step.parentElement.querySelector('input');
        input.value = String(Math.max(0, Math.min(500, bagsIn(input) + parseInt(step.dataset.step, 10))));
        refresh();

    });

    form.addEventListener('input', refresh);

    refresh();

});
