/*
|--------------------------------------------------------------------------
| EDIT MEMBERS — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| - Live customer search: as the admin types, fetch the page for that
|   search and swap in only the results list (the box keeps focus).
| - Drag to reorder "Current members" (mouse + touch via Pointer Events,
|   arrow keys on the handle); serial numbers update and the order saves.
*/


/* =========================================================
   DRAG TO REORDER
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const list = document.getElementById('memberList');
    const status = document.getElementById('reorderStatus');

    if (!list) {
        return;
    }


    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content;

    const EDGE_SCROLL_ZONE = 70;
    const EDGE_SCROLL_STEP = 12;

    let dragging = null;
    let orderBeforeDrag = '';
    let saveTimer = null;
    let statusTimer = null;


    function items() {
        return Array.from(list.querySelectorAll('.member-sortable'));
    }


    function currentOrder() {
        return items().map(function (item) {
            return Number(item.dataset.memberId);
        });
    }


    function renumber() {
        items().forEach(function (item, index) {
            item.querySelector('.member-list-number').textContent = index + 1;
        });
    }


    function showStatus(text, state) {

        clearTimeout(statusTimer);

        status.textContent = text;
        status.dataset.state = state;

        if (state === 'saved') {
            statusTimer = setTimeout(function () {
                status.textContent = '';
            }, 2500);
        }

    }


    async function saveOrder() {

        showStatus('Saving…', 'saving');

        try {

            const response = await fetch(list.dataset.reorderUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ member_ids: currentOrder() })
            });

            const data = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                throw new Error(data.message || 'Could not save the order.');
            }

            showStatus('✓ Order saved', 'saved');

        } catch (error) {

            showStatus(error.message + ' Reload the page and try again.', 'error');

        }

    }


    function queueSave() {

        clearTimeout(saveTimer);

        saveTimer = setTimeout(saveOrder, 350);

    }


    /* ---------- pointer drag (mouse, pen and touch) ---------- */

    list.addEventListener('pointerdown', function (event) {

        const handle = event.target.closest('.drag-handle');

        if (!handle || event.button > 0) {
            return;
        }

        event.preventDefault();

        dragging = handle.closest('.member-sortable');
        orderBeforeDrag = currentOrder().join(',');

        dragging.classList.add('is-dragging');
        list.classList.add('is-sorting');

        handle.setPointerCapture(event.pointerId);

    });


    list.addEventListener('pointermove', function (event) {

        if (!dragging) {
            return;
        }

        const pointerY = event.clientY;

        const nextItem = items().find(function (item) {

            if (item === dragging) {
                return false;
            }

            const box = item.getBoundingClientRect();

            return pointerY < box.top + box.height / 2;

        });

        if (nextItem) {

            if (nextItem !== dragging.nextElementSibling) {
                list.insertBefore(dragging, nextItem);
                renumber();
            }

        } else if (dragging !== list.lastElementChild) {

            list.appendChild(dragging);
            renumber();

        }


        /* keep scrolling while dragging near the top/bottom edge */

        if (pointerY < EDGE_SCROLL_ZONE) {
            window.scrollBy(0, -EDGE_SCROLL_STEP);
        } else if (pointerY > window.innerHeight - EDGE_SCROLL_ZONE) {
            window.scrollBy(0, EDGE_SCROLL_STEP);
        }

    });


    function endDrag() {

        if (!dragging) {
            return;
        }

        dragging.classList.remove('is-dragging');
        list.classList.remove('is-sorting');

        dragging = null;

        if (currentOrder().join(',') !== orderBeforeDrag) {
            queueSave();
        }

    }

    list.addEventListener('pointerup', endDrag);
    list.addEventListener('pointercancel', endDrag);


    /* ---------- keyboard: arrow keys on the handle ---------- */

    list.addEventListener('keydown', function (event) {

        const handle = event.target.closest('.drag-handle');

        if (!handle || (event.key !== 'ArrowUp' && event.key !== 'ArrowDown')) {
            return;
        }

        event.preventDefault();

        const item = handle.closest('.member-sortable');

        if (event.key === 'ArrowUp' && item.previousElementSibling) {
            list.insertBefore(item, item.previousElementSibling);
        } else if (event.key === 'ArrowDown' && item.nextElementSibling) {
            list.insertBefore(item.nextElementSibling, item);
        } else {
            return;
        }

        renumber();
        handle.focus();
        queueSave();

    });

});


/* =========================================================
   LIVE CUSTOMER SEARCH
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('memberSearchForm');
    const input = document.getElementById('memberSearchInput');
    const results = document.getElementById('memberSearchResults');

    if (!form || !input || !results) {
        return;
    }


    let searchTimer = null;


    async function search(term) {

        const url = new URL(form.action);

        if (term !== '') {
            url.searchParams.set('q', term);
        }

        try {

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            const page = new DOMParser().parseFromString(
                await response.text(),
                'text/html'
            );

            const newResults = page.getElementById('memberSearchResults');

            if (newResults) {
                results.innerHTML = newResults.innerHTML;
            }

            window.history.replaceState({}, '', url.toString());

            if (window.changeLanguage) {
                window.changeLanguage(
                    localStorage.getItem('sn-language') || 'en'
                );
            }

        } catch (error) {

            console.error('Member search error:', error);

        }

    }


    input.addEventListener('input', function () {

        clearTimeout(searchTimer);

        searchTimer = setTimeout(function () {
            search(input.value.trim());
        }, 300);

    });


    form.addEventListener('submit', function (event) {

        event.preventDefault();

        clearTimeout(searchTimer);

        search(input.value.trim());

    });


    /* after adding/removing, keep the cursor at the end of the search */

    if (input.value) {
        input.setSelectionRange(input.value.length, input.value.length);
    }

});
