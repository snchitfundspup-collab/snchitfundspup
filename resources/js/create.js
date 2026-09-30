/*
|--------------------------------------------------------------------------
| ADD CUSTOMER PAGE — PAGE-SPECIFIC JAVASCRIPT
|--------------------------------------------------------------------------
| Loaded only on the Add Customer page. Theme + language are handled
| globally by app.js (window.toggleTheme / window.changeLanguage) — this
| file only owns the "customer added" success popup.
*/

let customerSuccessTimer = null;


/**
 * Show success popup
 */
function showCustomerSuccessPopup() {

    const popup =
        document.getElementById('customerSuccessPopup');

    const title =
        document.getElementById('customerSuccessTitle');

    const message =
        document.getElementById('customerSuccessMessage');

    const progress =
        document.querySelector('.customer-success-progress');


    if (!popup) {
        return;
    }


    /* -----------------------------------------
       Current language (shared "sn-language" key —
       same one app.js uses everywhere else)
    ----------------------------------------- */

    const language =
        localStorage.getItem('sn-language') || 'en';

    const dictionary =
        (window.SN_TRANSLATIONS && window.SN_TRANSLATIONS[language]) ||
        (window.SN_TRANSLATIONS && window.SN_TRANSLATIONS.en) ||
        {};

    title.textContent =
        dictionary.customerAddedTitle || 'Customer Added Successfully';

    message.textContent =
        dictionary.customerAddedMessage || 'The customer has been added successfully.';


    /* -----------------------------------------
       Show
    ----------------------------------------- */

    popup.classList.add('show');

    popup.setAttribute('aria-hidden', 'false');


    /* -----------------------------------------
       Progress animation
    ----------------------------------------- */

    if (progress) {

        progress.style.transition = 'none';
        progress.style.transform = 'scaleX(1)';

        requestAnimationFrame(() => {

            requestAnimationFrame(() => {

                progress.style.transition = 'transform 4s linear';
                progress.style.transform = 'scaleX(0)';

            });

        });

    }


    /* -----------------------------------------
       Auto close
    ----------------------------------------- */

    clearTimeout(customerSuccessTimer);

    customerSuccessTimer =
        setTimeout(() => {

            closeCustomerSuccessPopup();

        }, 4000);

}


/**
 * Close success popup
 */
function closeCustomerSuccessPopup() {

    const popup =
        document.getElementById('customerSuccessPopup');

    if (!popup) {
        return;
    }

    popup.classList.remove('show');

    popup.setAttribute('aria-hidden', 'true');

    clearTimeout(customerSuccessTimer);

}


/* =========================================================
   PHONE ALREADY USED
   Family may share a phone (they sign in together); the same
   name with the same phone is the same person — no second ID.
========================================================= */

function translate(key, fallback) {

    const language =
        localStorage.getItem('sn-language') || 'en';

    const dictionary =
        (window.SN_TRANSLATIONS && window.SN_TRANSLATIONS[language]) || {};

    return dictionary[key] || fallback;

}


function renderPhoneWarning(box, customers) {

    box.replaceChildren();

    if (customers.length === 0) {
        box.hidden = true;
        return;
    }

    const duplicate =
        customers.some((customer) => customer.same_name);

    box.classList.toggle('phone-warning-duplicate', duplicate);

    const title =
        document.createElement('strong');

    title.textContent =
        translate('phone_used_by', 'This phone number is already used by:');

    const list =
        document.createElement('ul');

    customers.forEach((customer) => {

        const item =
            document.createElement('li');

        item.textContent =
            customer.name + ' — ' + customer.code +
            (customer.remarks ? ' — ' + customer.remarks : '') +
            (customer.active ? '' : ' (' + translate('inactive', 'Inactive') + ')');

        list.append(item);

    });

    const note =
        document.createElement('p');

    note.textContent = duplicate
        ? translate('phone_same_person', 'The same name and phone number is already saved. One person keeps one customer ID — do not add them again.')
        : translate('phone_shared_note', 'Different people can share a phone. They sign in together with one password and see each other\'s details, each under their own name and ID.');

    box.append(title, list, note);

    box.hidden = false;

}


(function watchPhone() {

    const box =
        document.getElementById('phoneWarning');

    const phone =
        document.getElementById('phone');

    const name =
        document.getElementById('name');

    if (!box || !phone) {
        return;
    }

    let timer = null;

    let latest = 0;

    const check = () => {

        const digits =
            phone.value.replace(/\D/g, '');

        if (digits.length < 10) {
            renderPhoneWarning(box, []);
            return;
        }

        const request = ++latest;

        const url =
            box.dataset.url + '?' + new URLSearchParams({ phone: phone.value, name: name ? name.value : '' });

        fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => (response.ok ? response.json() : { customers: [] }))
            .then((data) => {
                if (request === latest) {
                    renderPhoneWarning(box, data.customers || []);
                }
            })
            .catch(() => {});

    };

    const later = () => {
        clearTimeout(timer);
        timer = setTimeout(check, 300);
    };

    phone.addEventListener('input', later);

    if (name) {
        name.addEventListener('input', later);
    }

    /* after a failed save the phone is filled in again */
    check();

})();


/* =========================================================
   CLOSE WITH ESCAPE
========================================================= */

document.addEventListener('keydown', function (event) {

    if (event.key !== 'Escape') {
        return;
    }

    closeCustomerSuccessPopup();

});