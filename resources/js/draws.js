/*
|--------------------------------------------------------------------------
| RUN DRAW — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| - The wheel shows one slice per ticked member.
| - "Spin" sends the chosen members to the server, which picks and SAVES
|   the winner at random first; the wheel then spins and stops on that
|   member, and the winner popup (with confetti) links to the draw page.
| - Without JavaScript the form still posts and opens the draw page.
|
| DRAW PAGE: printing the payout voucher tells the server it was printed,
| which moves the draw from Draw Details to Past Winners.
*/

document.addEventListener('DOMContentLoaded', function () {

    const printButton = document.getElementById('printVoucherButton');

    if (!printButton) {
        return;
    }

    const note = document.getElementById('voucherPrintedNote');
    const printedAt = document.getElementById('voucherPrintedAt');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    async function markPrinted() {

        if (!note.hidden) {
            return;
        }

        try {

            const response = await fetch(printButton.dataset.printedUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });

            if (response.ok) {
                const data = await response.json();
                printedAt.textContent = data.voucher_printed_at;
                note.hidden = false;
            }

        } catch (error) {
            /* printing still works; it can be marked on the next print */
        }
    }

    printButton.addEventListener('click', function () {
        markPrinted();
        window.print();
    });

    /* the PDF download marks it on the server; just show the note */
    document.getElementById('downloadVoucherButton')?.addEventListener('click', function () {
        setTimeout(markPrinted, 800);
    });

});


document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('drawForm');

    if (!form) {
        return;
    }


    const segmentsGroup = document.getElementById('drawWheelSegments');
    const wheel = document.getElementById('drawWheel');
    const emptyNote = document.getElementById('drawWheelEmpty');
    const spinButton = document.getElementById('drawSpinButton');
    const countLabel = document.getElementById('drawSelectedCount');
    const errorBox = document.getElementById('drawError');

    const COLORS = ['#fe691e', '#3d73ff', '#8255ff', '#22c55e', '#f59e0b', '#ec4899', '#14b8a6', '#6366f1'];
    const RADIUS = 100;
    const SPIN_SECONDS = 7;
    const FULL_TURNS = 7;

    const SVG_NS = 'http://www.w3.org/2000/svg';

    let rotation = 0;
    let spinning = false;


    function checkboxes() {
        return Array.from(form.querySelectorAll('input[name="participant_ids[]"]'));
    }


    function chosen() {
        return checkboxes().filter(function (box) {
            return box.checked;
        });
    }


    /* point on the circle, angle in degrees clockwise from the top */
    function point(angle, radius) {

        const radians = (angle * Math.PI) / 180;

        return [radius * Math.sin(radians), -radius * Math.cos(radians)];

    }


    function colorFor(index, count) {

        let color = COLORS[index % COLORS.length];

        /* first and last slices touch — avoid the same colour side by side */
        if (count > 1 && index === count - 1 && color === COLORS[0]) {
            color = COLORS[(index + 1) % COLORS.length];
        }

        return color;

    }


    function drawWheel() {

        const members = chosen();
        const count = members.length;

        segmentsGroup.innerHTML = '';

        countLabel.textContent = count;
        spinButton.disabled = count === 0 || spinning;
        emptyNote.hidden = count > 0;
        wheel.classList.toggle('is-empty', count === 0);

        if (count === 0) {
            return;
        }

        const slice = 360 / count;
        const fontSize = count <= 6 ? 10 : count <= 10 ? 8 : count <= 16 ? 6.5 : 5.2;

        members.forEach(function (box, index) {

            const start = index * slice;
            const end = start + slice;
            const middle = start + slice / 2;

            let shape;

            if (count === 1) {

                shape = document.createElementNS(SVG_NS, 'circle');
                shape.setAttribute('r', RADIUS);

            } else {

                const [x1, y1] = point(start, RADIUS);
                const [x2, y2] = point(end, RADIUS);

                shape = document.createElementNS(SVG_NS, 'path');
                shape.setAttribute(
                    'd',
                    'M 0 0 L ' + x1 + ' ' + y1 +
                    ' A ' + RADIUS + ' ' + RADIUS + ' 0 ' + (slice > 180 ? 1 : 0) + ' 1 ' + x2 + ' ' + y2 + ' Z'
                );

            }

            shape.setAttribute('fill', colorFor(index, count));
            shape.setAttribute('class', 'wheel-slice');

            const label = document.createElementNS(SVG_NS, 'text');
            label.textContent = box.dataset.label;
            label.setAttribute('class', 'wheel-label');
            label.setAttribute('font-size', fontSize);
            label.setAttribute('text-anchor', 'end');
            label.setAttribute('dominant-baseline', 'middle');
            label.setAttribute('transform', 'rotate(' + (middle - 90) + ') translate(' + (RADIUS - 8) + ' 0)');

            segmentsGroup.append(shape, label);

        });

    }


    /* spin so the winner's slice stops under the pointer at the top */
    function spinTo(winnerIndex, count) {

        return new Promise(function (resolve) {

            const slice = 360 / count;
            const jitter = (Math.random() - 0.5) * slice * 0.6;
            const target = (winnerIndex + 0.5) * slice + jitter;

            const current = ((rotation % 360) + 360) % 360;
            const extra = (360 - target - current + 720) % 360;

            rotation += FULL_TURNS * 360 + extra;

            wheel.style.transition = 'transform ' + SPIN_SECONDS + 's cubic-bezier(0.12, 0.72, 0.1, 1)';
            wheel.style.transform = 'rotate(' + rotation + 'deg)';

            wheel.addEventListener('transitionend', function done() {
                wheel.removeEventListener('transitionend', done);
                resolve();
            });

        });

    }


    function showError(message) {

        errorBox.textContent = message;
        errorBox.hidden = false;
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });

    }


    function confetti() {

        const box = document.getElementById('confetti');

        box.innerHTML = '';

        for (let i = 0; i < 90; i++) {

            const piece = document.createElement('span');
            piece.style.left = Math.random() * 100 + '%';
            piece.style.background = COLORS[i % COLORS.length];
            piece.style.animationDelay = Math.random() * 0.6 + 's';
            piece.style.animationDuration = 2.2 + Math.random() * 1.6 + 's';
            piece.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
            box.appendChild(piece);

        }

    }


    function showWinner(result) {

        document.getElementById('winnerName').textContent = result.winner_name;
        document.getElementById('winnerCode').textContent = result.winner_code;
        document.getElementById('winnerLink').href = result.url;

        const modal = document.getElementById('winnerModal');

        modal.hidden = false;

        confetti();

        document.getElementById('winnerLink').focus();

    }


    /* ---------- events ---------- */

    form.addEventListener('change', function (event) {

        if (event.target.name === 'participant_ids[]') {
            drawWheel();
        }

    });


    document.getElementById('drawSelectAll').addEventListener('click', function () {
        checkboxes().forEach(function (box) { box.checked = true; });
        drawWheel();
    });


    document.getElementById('drawSelectNone').addEventListener('click', function () {
        checkboxes().forEach(function (box) { box.checked = false; });
        drawWheel();
    });


    form.addEventListener('submit', async function (event) {

        event.preventDefault();

        if (spinning || chosen().length === 0) {
            return;
        }

        const members = chosen();

        spinning = true;
        spinButton.disabled = true;
        errorBox.hidden = true;
        form.classList.add('is-spinning');

        /* on phones the wheel sits below the member list: bring it into view */
        wheel.scrollIntoView({ behavior: 'smooth', block: 'center' });

        try {

            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            });

            const data = await response.json().catch(function () { return {}; });

            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(firstError || data.message || 'The draw could not be run.');
            }

            const winnerIndex = members.findIndex(function (box) {
                return Number(box.value) === Number(data.winner_id);
            });

            await spinTo(Math.max(0, winnerIndex), members.length);

            showWinner(data);

        } catch (error) {

            spinning = false;
            form.classList.remove('is-spinning');
            drawWheel();
            showError(error.message);

        }

    });


    drawWheel();

});
