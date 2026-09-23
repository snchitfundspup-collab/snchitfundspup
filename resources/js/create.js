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
   CLOSE WITH ESCAPE
========================================================= */

document.addEventListener('keydown', function (event) {

    if (event.key !== 'Escape') {
        return;
    }

    closeCustomerSuccessPopup();

});