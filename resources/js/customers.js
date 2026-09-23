document.addEventListener('DOMContentLoaded', function () {

    console.log('Customers JS loaded');


    /* =========================================================
       ELEMENTS
    ========================================================= */

    const searchInput =
        document.getElementById('customerSearch');

    const clearSearchButton =
        document.getElementById('clearCustomerSearch');


    /* =========================================================
       LIVE SEARCH
    ========================================================= */

    let searchTimer = null;

    if (searchInput) {

        searchInput.addEventListener('input', function () {

            clearTimeout(searchTimer);

            const searchValue =
                searchInput.value.trim();

            searchTimer = setTimeout(function () {

                performLiveSearch(searchValue);

            }, 300);

        });

    }


    /* =========================================================
       SORTING
       (dropdown + clickable table headings — both reload only
       the results card, keeping the current search)
    ========================================================= */

    const sortSelect =
        document.getElementById('customerSort');


    function applySort(
        sortValue
    ) {

        const url =
            new URL(window.location.href);


        if (sortValue === 'newest') {

            url.searchParams.delete('sort');

        } else {

            url.searchParams.set(
                'sort',
                sortValue
            );

        }


        window.history.replaceState(
            {},
            '',
            url.toString()
        );


        if (sortSelect) {
            sortSelect.value = sortValue;
        }


        performLiveSearch(
            searchInput
                ? searchInput.value.trim()
                : '',
            false
        );

    }


    if (sortSelect) {

        sortSelect.addEventListener(
            'change',
            function () {

                applySort(
                    sortSelect.value
                );

            }
        );

    }


    document.addEventListener(
        'click',
        function (event) {

            const headerLink =
                event.target.closest(
                    '.sort-header-link'
                );


            if (!headerLink) {
                return;
            }


            event.preventDefault();

            applySort(
                headerLink.dataset.sort
            );

        }
    );


    async function performLiveSearch(searchValue, keepSearchFocus = true) {

        const url =
            new URL(window.location.href);


        /*
         * Always start search from page 1.
         */

        url.searchParams.delete('page');


        if (searchValue !== '') {

            url.searchParams.set(
                'search',
                searchValue
            );

        } else {

            url.searchParams.delete(
                'search'
            );

        }


        try {

            const response =
                await fetch(
                    url.toString(),
                    {
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',

                            'Accept':
                                'text/html'
                        }
                    }
                );


            if (!response.ok) {

                throw new Error(
                    'Search request failed'
                );

            }


            const html =
                await response.text();


            const parser =
                new DOMParser();


            const newDocument =
                parser.parseFromString(
                    html,
                    'text/html'
                );


            const newCustomerCard =
                newDocument.querySelector(
                    '.customers-card'
                );


            const currentCustomerCard =
                document.querySelector(
                    '.customers-card'
                );


            /*
             * Replace ONLY the results card.
             *
             * Search input is outside this card,
             * so it will never lose focus.
             */

            if (
                newCustomerCard &&
                currentCustomerCard
            ) {

                currentCustomerCard.innerHTML =
                    newCustomerCard.innerHTML;


                /*
                 * New HTML arrives in English — re-apply
                 * the chosen language.
                 */

                if (window.changeLanguage) {

                    window.changeLanguage(
                        localStorage.getItem('sn-language') || 'en'
                    );

                }

            }


            /*
             * Update URL.
             */

            window.history.replaceState(
                {},
                '',
                url.toString()
            );


            /*
             * Update clear button.
             */

            updateClearButton(
                searchValue
            );


            /*
             * Keep cursor in search box.
             */

            if (keepSearchFocus && searchInput) {

                searchInput.focus();

                const length =
                    searchInput.value.length;

                searchInput.setSelectionRange(
                    length,
                    length
                );

            }


            /*
             * Update result count.
             */

            const newResultsInfo =
                newDocument.querySelector(
                    '#customerResultsInfo'
                );

            const currentResultsInfo =
                document.getElementById(
                    'customerResultsInfo'
                );

            if (
                newResultsInfo &&
                currentResultsInfo
            ) {

                currentResultsInfo.textContent =
                    newResultsInfo.textContent;

            }


        } catch (error) {

            console.error(
                'Live search error:',
                error
            );

        }

    }


    /* =========================================================
       CLEAR SEARCH
    ========================================================= */

    function updateClearButton(
        searchValue
    ) {

        if (!clearSearchButton) {
            return;
        }


        clearSearchButton.style.display =
            searchValue !== ''
                ? 'flex'
                : 'none';

    }


    if (clearSearchButton) {

        clearSearchButton.addEventListener(
            'click',
            function () {

                searchInput.value = '';

                performLiveSearch('');

                searchInput.focus();

            }
        );

    }


    updateClearButton(
        searchInput
            ? searchInput.value.trim()
            : ''
    );


    /* =========================================================
       CUSTOMER MODAL ELEMENTS
    ========================================================= */

    const customerModal =
        document.getElementById(
            'customerModal'
        );

    const customerModalOverlay =
        document.getElementById(
            'customerModalOverlay'
        );

    const customerModalClose =
        document.getElementById(
            'customerModalClose'
        );

    const customerModalCancel =
        document.getElementById(
            'customerModalCancel'
        );


    const editForm =
        document.getElementById(
            'customerEditForm'
        );

    const editCustomerId =
        document.getElementById(
            'editCustomerId'
        );

    const editCustomerCodeDisplay =
        document.getElementById(
            'editCustomerCodeDisplay'
        );

    const editCustomerName =
        document.getElementById(
            'editCustomerName'
        );

    const editCustomerPhone =
        document.getElementById(
            'editCustomerPhone'
        );

    const editCustomerEmail =
        document.getElementById(
            'editCustomerEmail'
        );

    const editCustomerRemarks =
        document.getElementById(
            'editCustomerRemarks'
        );

    const editCustomerAddress =
        document.getElementById(
            'editCustomerAddress'
        );

    const editCustomerActive =
        document.getElementById(
            'editCustomerActive'
        );

    const customerFormError =
        document.getElementById(
            'customerFormError'
        );

    const customerSaveButton =
        document.getElementById(
            'customerSaveButton'
        );

    const customerSaveText =
        document.getElementById(
            'customerSaveText'
        );

    const customerSaveLoading =
        document.getElementById(
            'customerSaveLoading'
        );


    /* =========================================================
       GET CUSTOMER DATA
    ========================================================= */

    function getCustomerData(
        customerId
    ) {

        const element =
            document.querySelector(
                `[data-customer-id="${customerId}"]`
            );


        if (!element) {

            console.error(
                'Customer not found:',
                customerId
            );

            return null;

        }


        return {

            id:
                customerId,

            customer_code:
                element.dataset.customerCode || '',

            name:
                element.dataset.customerName || '',

            phone:
                element.dataset.customerPhone || '',

            email:
                element.dataset.customerEmail || '',

            remarks:
                element.dataset.customerRemarks || '',

            address:
                element.dataset.customerAddress || '',

            is_active:
                element.dataset.customerActive === '1'

        };

    }


    /* =========================================================
       3D INITIAL THUMBNAILS
       (same initial + tone rules as the <x-customer-avatar>
       Blade component)
    ========================================================= */

    const AVATAR_TONES = [
        'orange',
        'blue',
        'purple'
    ];


    function customerInitial(
        name
    ) {

        return (
            (name || '').trim().charAt(0).toUpperCase() ||
            '?'
        );

    }


    function updateModalAvatar(
        customer
    ) {

        const avatar =
            document.getElementById(
                'editCustomerAvatar'
            );


        if (!avatar) {
            return;
        }


        const tone =
            AVATAR_TONES[
                Number(customer.id) % AVATAR_TONES.length
            ] || 'orange';


        AVATAR_TONES.forEach(function (name) {

            avatar.classList.remove(
                'icon-3d-' + name
            );

        });


        avatar.classList.add(
            'icon-3d-' + tone
        );


        avatar.textContent =
            customerInitial(
                customer.name
            );

    }


    /* =========================================================
       OPEN CUSTOMER MODAL
    ========================================================= */

    function openCustomerModal(
        customer
    ) {

        if (!customerModal) {
            return;
        }


        editCustomerId.value =
            customer.id || '';


        editCustomerCodeDisplay.textContent =
            customer.customer_code || 'CUSTOMER';


        updateModalAvatar(
            customer
        );


        editCustomerName.value =
            customer.name || '';


        editCustomerPhone.value =
            customer.phone || '';


        editCustomerEmail.value =
            customer.email || '';


        editCustomerRemarks.value =
            customer.remarks || '';


        editCustomerAddress.value =
            customer.address || '';


        editCustomerActive.checked =
            Boolean(customer.is_active);


        hideFormError();


        customerModal.classList.add(
            'show'
        );


        customerModal.setAttribute(
            'aria-hidden',
            'false'
        );


        document.body.classList.add(
            'customer-modal-open'
        );


        setTimeout(function () {

            editCustomerName.focus();

        }, 100);

    }


    /* =========================================================
       CUSTOMER NAME / VIEW EDIT CLICK
    ========================================================= */

    document.addEventListener(
        'click',
        function (event) {

            const button =
                event.target.closest(
                    '.customer-name-button'
                );


            if (!button) {
                return;
            }


            const customerId =
                button.dataset.customerId;


            if (!customerId) {
                return;
            }


            const customer =
                getCustomerData(
                    customerId
                );


            if (customer) {

                openCustomerModal(
                    customer
                );

            }

        }
    );


    /* =========================================================
       CLOSE CUSTOMER MODAL
    ========================================================= */

    function closeCustomerModal() {

        if (!customerModal) {
            return;
        }


        customerModal.classList.remove(
            'show'
        );


        customerModal.setAttribute(
            'aria-hidden',
            'true'
        );


        document.body.classList.remove(
            'customer-modal-open'
        );

    }


    if (customerModalClose) {

        customerModalClose.addEventListener(
            'click',
            closeCustomerModal
        );

    }


    if (customerModalCancel) {

        customerModalCancel.addEventListener(
            'click',
            closeCustomerModal
        );

    }


    if (customerModalOverlay) {

        customerModalOverlay.addEventListener(
            'click',
            closeCustomerModal
        );

    }


    /* =========================================================
       ESC KEY
    ========================================================= */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
            ) {

                closeCustomerModal();

                closeGroupsModal();

            }

        }
    );


    /* =========================================================
       FORM ERROR
    ========================================================= */

    function showFormError(
        message
    ) {

        if (!customerFormError) {
            return;
        }


        customerFormError.textContent =
            message;


        customerFormError.style.display =
            'block';

    }


    function hideFormError() {

        if (!customerFormError) {
            return;
        }


        customerFormError.textContent =
            '';


        customerFormError.style.display =
            'none';

    }


    /* =========================================================
       SAVE CUSTOMER
    ========================================================= */

    if (editForm) {

        editForm.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();


                hideFormError();


                const customerId =
                    editCustomerId.value;


                if (!customerId) {

                    showFormError(
                        'Customer ID is missing.'
                    );

                    return;

                }


                /*
                 * Loading state.
                 */

                customerSaveButton.disabled =
                    true;


                customerSaveText.style.display =
                    'none';


                customerSaveLoading.style.display =
                    'inline';


                /*
                 * CSRF.
                 */

                const csrfToken =
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.getAttribute(
                        'content'
                    );


                /*
                 * Form data.
                 */

                const formData =
                    new FormData();


                formData.append(
                    '_method',
                    'PUT'
                );


                formData.append(
                    '_token',
                    csrfToken || ''
                );


                formData.append(
                    'name',
                    editCustomerName.value.trim()
                );


                formData.append(
                    'phone',
                    editCustomerPhone.value.trim()
                );


                formData.append(
                    'email',
                    editCustomerEmail.value.trim()
                );


                formData.append(
                    'remarks',
                    editCustomerRemarks.value.trim()
                );


                formData.append(
                    'address',
                    editCustomerAddress.value.trim()
                );


                formData.append(
                    'is_active',
                    editCustomerActive.checked
                        ? '1'
                        : '0'
                );


                try {

                    const response =
                        await fetch(
                            `/customers/${customerId}`,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                body:
                                    formData
                            }
                        );


                    const data =
                        await response.json();


                    if (!response.ok) {

                        if (
                            data.errors
                        ) {

                            const firstError =
                                Object.values(
                                    data.errors
                                )[0]?.[0];


                            showFormError(
                                firstError ||
                                'Please check the form.'
                            );

                        } else {

                            showFormError(
                                data.message ||
                                'Unable to update customer.'
                            );

                        }

                        return;

                    }


                    if (data.success) {

                        updateCustomerOnPage(
                            data.customer
                        );


                        closeCustomerModal();


                        showSuccessPopup(
                            data.message ||
                            'Customer updated successfully.'
                        );

                    }


                } catch (error) {

                    console.error(
                        'Customer update error:',
                        error
                    );


                    showFormError(
                        'Something went wrong. Please try again.'
                    );


                } finally {

                    customerSaveButton.disabled =
                        false;


                    customerSaveText.style.display =
                        'inline';


                    customerSaveLoading.style.display =
                        'none';

                }

            }
        );

    }


    /* =========================================================
       UPDATE CUSTOMER ON PAGE
    ========================================================= */

    function updateCustomerOnPage(
        customer
    ) {

        /*
         * Update every desktop row/card
         * belonging to this customer.
         */

        const elements =
            document.querySelectorAll(
                `[data-customer-id="${customer.id}"]`
            );


        elements.forEach(function (element) {

            /*
             * Update stored data attributes.
             */

            element.dataset.customerCode =
                customer.customer_code || '';

            element.dataset.customerName =
                customer.name || '';

            element.dataset.customerPhone =
                customer.phone || '';

            element.dataset.customerEmail =
                customer.email || '';

            element.dataset.customerRemarks =
                customer.remarks || '';

            element.dataset.customerAddress =
                customer.address || '';

            element.dataset.customerActive =
                customer.is_active
                    ? '1'
                    : '0';


            /*
             * Desktop name.
             */

            const nameButton =
                element.querySelector(
                    '.customer-name-button'
                );


            if (nameButton) {

                nameButton.textContent =
                    customer.name;

            }


            /*
             * Thumbnail initial.
             */

            const avatar =
                element.querySelector(
                    '.customer-avatar'
                );


            if (avatar) {

                avatar.textContent =
                    customerInitial(
                        customer.name
                    );

            }


            /*
             * Identification.
             */

            const identification =
                element.querySelector(
                    '.customer-identification'
                );


            if (identification) {

                identification.textContent =
                    customer.remarks ||
                    '-';

            }


            /*
             * Phone.
             */

            const phone =
                element.querySelector(
                    '.customer-phone'
                );


            if (phone) {

                phone.textContent =
                    customer.phone;

                phone.href =
                    'tel:' + customer.phone;

            }


            /*
             * Mobile details.
             */

            const details =
                element.querySelectorAll(
                    '.mobile-detail'
                );


            details.forEach(
                function (detail) {

                    const label =
                        detail.querySelector(
                            '.mobile-detail-label'
                        )?.textContent
                        .trim();


                    const value =
                        detail.querySelector(
                            '.mobile-detail-value'
                        );


                    if (!value) {
                        return;
                    }


                    if (
                        label ===
                        'Identification'
                    ) {

                        value.textContent =
                            customer.remarks ||
                            '-';

                    }


                    if (
                        label ===
                        'Phone'
                    ) {

                        value.textContent =
                            customer.phone;

                        value.href =
                            'tel:' +
                            customer.phone;

                    }


                    if (
                        label ===
                        'Email'
                    ) {

                        value.textContent =
                            customer.email ||
                            '-';

                    }


                    if (
                        label ===
                        'Address'
                    ) {

                        value.textContent =
                            customer.address ||
                            '-';

                    }

                }
            );


            /*
             * Status.
             */

            const status =
                element.querySelector(
                    '.customer-status'
                );


            if (status) {

                status.classList.toggle(
                    'active',
                    Boolean(
                        customer.is_active
                    )
                );


                status.classList.toggle(
                    'inactive',
                    !customer.is_active
                );


                status.innerHTML =
                    customer.is_active
                        ? '<span></span>Active'
                        : '<span></span>Inactive';

            }

        });

    }


    /* =========================================================
       SUCCESS POPUP
    ========================================================= */

    const successPopup =
        document.getElementById(
            'customerSuccessPopup'
        );


    let successTimer = null;


    function showSuccessPopup(
        message
    ) {

        if (!successPopup) {
            return;
        }


        const messageElement =
            successPopup.querySelector(
                '.success-popup-content span'
            );


        if (messageElement) {

            messageElement.textContent =
                message;

        }


        successPopup.classList.add(
            'show'
        );


        successPopup.setAttribute(
            'aria-hidden',
            'false'
        );


        clearTimeout(
            successTimer
        );


        successTimer =
            setTimeout(
                hideSuccessPopup,
                3500
            );

    }


    function hideSuccessPopup() {

        if (!successPopup) {
            return;
        }


        successPopup.classList.remove(
            'show'
        );


        successPopup.setAttribute(
            'aria-hidden',
            'true'
        );

    }


    /* =========================================================
       GROUPS MODAL
    ========================================================= */

    const groupsModal =
        document.getElementById(
            'groupsModal'
        );


    const groupsModalOverlay =
        document.getElementById(
            'groupsModalOverlay'
        );


    const groupsModalClose =
        document.getElementById(
            'groupsModalClose'
        );


    function openGroupsModal() {

        if (!groupsModal) {
            return;
        }


        groupsModal.classList.add(
            'show'
        );


        groupsModal.setAttribute(
            'aria-hidden',
            'false'
        );


        document.body.classList.add(
            'customer-modal-open'
        );

    }


    function closeGroupsModal() {

        if (!groupsModal) {
            return;
        }


        groupsModal.classList.remove(
            'show'
        );


        groupsModal.setAttribute(
            'aria-hidden',
            'true'
        );


        if (
            !customerModal ||
            !customerModal.classList.contains(
                'show'
            )
        ) {

            document.body.classList.remove(
                'customer-modal-open'
            );

        }

    }


    document.addEventListener(
        'click',
        function (event) {

            const button =
                event.target.closest(
                    '.view-groups-button'
                );


            if (!button) {
                return;
            }


            openGroupsModal();

        }
    );


    if (groupsModalClose) {

        groupsModalClose.addEventListener(
            'click',
            closeGroupsModal
        );

    }


    if (groupsModalOverlay) {

        groupsModalOverlay.addEventListener(
            'click',
            closeGroupsModal
        );

    }

});