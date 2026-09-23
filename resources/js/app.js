/*
|--------------------------------------------------------------------------
| GLOBAL APPLICATION JAVASCRIPT
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| MENU
|--------------------------------------------------------------------------
*/

window.toggleMenu = function () {

    const menu =
        document.getElementById('appMenu');

    const overlay =
        document.getElementById('menuOverlay');


    if (!menu) {
        return;
    }


    const isOpen =
        menu.classList.contains('open');


    if (isOpen) {

        menu.classList.remove('open');

        if (overlay) {
            overlay.classList.remove('open');
        }

        menu.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.classList.remove(
            'menu-open'
        );

    } else {

        menu.classList.add('open');

        if (overlay) {
            overlay.classList.add('open');
        }

        menu.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.classList.add(
            'menu-open'
        );

    }

};


/*
|--------------------------------------------------------------------------
| THEME
|--------------------------------------------------------------------------
*/

window.toggleTheme = function () {

    const html =
        document.documentElement;

    const currentTheme =
        html.classList.contains('dark')
            ? 'dark'
            : 'light';


    const newTheme =
        currentTheme === 'dark'
            ? 'light'
            : 'dark';


    html.classList.remove(
        'dark',
        'light'
    );


    html.classList.add(
        newTheme
    );


    localStorage.setItem(
        'sn-theme',
        newTheme
    );


    updateThemeIcon();

};


function updateThemeIcon() {

    const icon =
        document.getElementById('themeIcon');


    if (!icon) {
        return;
    }


    if (
        document.documentElement
            .classList
            .contains('dark')
    ) {

        icon.textContent = '☾';

    } else {

        icon.textContent = '☀';

    }

}


/*
|--------------------------------------------------------------------------
| TRANSLATIONS
|--------------------------------------------------------------------------
| Single, shared dictionary used across every page (header, menu,
| footer, customers list, add-customer form). An element opts in to
| translation with data-i18n="key" (for text) or
| data-i18n-placeholder="key" (for input placeholders).
*/

window.SN_TRANSLATIONS = {

    en: {

        // Header / menu / footer chrome
        brand_tagline: 'Trust · Growth · Together',
        admin: 'Admin',
        administrator: 'Administrator',
        logout: 'Logout',
        menu_main: 'MAIN',
        menu_home: 'Home',
        menu_customers: 'Customers',
        menu_groups: 'Groups',
        menu_payments: 'Payments',
        menu_draws: 'Draws',
        menu_management: 'MANAGEMENT',
        menu_reports: 'Reports',
        menu_settings: 'Settings',
        footer_secure: 'Secure',
        footer_reliable: 'Reliable',
        footer_always: 'Always With You',

        // Customers (index) page
        customers_title: 'Customers',
        customers_subtitle: 'Manage all your chit fund customers',
        add_customer: 'Add Customer',
        customer_id: 'Customer ID',
        name: 'Name',
        phone: 'Phone',
        identification: 'Identification',
        status: 'Status',
        groups: 'Groups',
        search_customers: 'Search customers...',

        // Add-customer page
        heroBadge: 'Customer Registration',
        heroTitle: 'Add Customer',
        heroDescription: 'Create a new customer profile and keep all their chit fund information organized.',
        backText: 'Back to Customers',
        customerIdTitle: 'Customer ID',
        customerIdText: 'A unique customer ID will be generated automatically when you save the customer.',
        nameLabel: 'Name',
        phoneLabel: 'Phone Number',
        emailLabel: 'Email',
        addressLabel: 'Address',
        remarksLabel: 'Remarks / Identification',
        cancelText: 'Cancel',
        saveText: 'Save Customer',
        errorTitle: 'Please correct the following:',
        namePlaceholder: 'Enter customer name',
        phonePlaceholder: 'Enter phone number',
        emailPlaceholder: 'Enter email address',
        addressPlaceholder: 'Enter customer address',
        remarksPlaceholder: 'Optional notes or identification',
        customerAddedTitle: 'Customer Added Successfully',
        customerAddedMessage: 'The customer has been added successfully.'

    },

    ta: {

        // Header / menu / footer chrome
        brand_tagline: 'நம்பிக்கை · வளர்ச்சி · ஒன்றாக',
        admin: 'நிர்வாகி',
        administrator: 'நிர்வாகி',
        logout: 'வெளியேறு',
        menu_main: 'முதன்மை',
        menu_home: 'முகப்பு',
        menu_customers: 'வாடிக்கையாளர்கள்',
        menu_groups: 'குழுக்கள்',
        menu_payments: 'கட்டணங்கள்',
        menu_draws: 'சீட்டு',
        menu_management: 'மேலாண்மை',
        menu_reports: 'அறிக்கைகள்',
        menu_settings: 'அமைப்புகள்',
        footer_secure: 'பாதுகாப்பானது',
        footer_reliable: 'நம்பகமானது',
        footer_always: 'எப்போதும் உங்களுடன்',

        // Customers (index) page
        customers_title: 'வாடிக்கையாளர்கள்',
        customers_subtitle: 'உங்கள் சீட்டு நிதி வாடிக்கையாளர்களை நிர்வகிக்கவும்',
        add_customer: 'வாடிக்கையாளரைச் சேர்க்கவும்',
        customer_id: 'வாடிக்கையாளர் அடையாள எண்',
        name: 'பெயர்',
        phone: 'தொலைபேசி',
        identification: 'அடையாளம்',
        status: 'நிலை',
        groups: 'குழுக்கள்',
        search_customers: 'வாடிக்கையாளர்களைத் தேடுங்கள்...',

        // Add-customer page
        heroBadge: 'வாடிக்கையாளர் பதிவு',
        heroTitle: 'வாடிக்கையாளரைச் சேர்க்கவும்',
        heroDescription: 'புதிய வாடிக்கையாளர் சுயவிவரத்தை உருவாக்கி, அவர்களின் சீட்டு நிதி தகவல்களை ஒழுங்காக பராமரிக்கவும்.',
        backText: 'வாடிக்கையாளர்களுக்குத் திரும்பு',
        customerIdTitle: 'வாடிக்கையாளர் அடையாள எண்',
        customerIdText: 'வாடிக்கையாளரை சேமிக்கும் போது தனிப்பட்ட அடையாள எண் தானாக உருவாக்கப்படும்.',
        nameLabel: 'பெயர்',
        phoneLabel: 'தொலைபேசி எண்',
        emailLabel: 'மின்னஞ்சல்',
        addressLabel: 'முகவரி',
        remarksLabel: 'குறிப்புகள் / அடையாளம்',
        cancelText: 'ரத்து செய்',
        saveText: 'வாடிக்கையாளரை சேமிக்கவும்',
        errorTitle: 'பின்வரும் தகவல்களை சரிபார்க்கவும்:',
        namePlaceholder: 'வாடிக்கையாளர் பெயரை உள்ளிடவும்',
        phonePlaceholder: 'தொலைபேசி எண்ணை உள்ளிடவும்',
        emailPlaceholder: 'மின்னஞ்சல் முகவரியை உள்ளிடவும்',
        addressPlaceholder: 'வாடிக்கையாளர் முகவரியை உள்ளிடவும்',
        remarksPlaceholder: 'விருப்பமான குறிப்புகள் அல்லது அடையாளத்தை உள்ளிடவும்',
        customerAddedTitle: 'வாடிக்கையாளர் வெற்றிகரமாக சேர்க்கப்பட்டார்',
        customerAddedMessage: 'வாடிக்கையாளர் விவரங்கள் வெற்றிகரமாக சேமிக்கப்பட்டுள்ளன.'

    }

};


function applyTranslations(language) {

    const dictionary =
        window.SN_TRANSLATIONS[language] ||
        window.SN_TRANSLATIONS.en;


    document
        .querySelectorAll('[data-i18n]')
        .forEach(function (element) {

            const key = element.dataset.i18n;

            if (dictionary[key] !== undefined) {
                element.textContent = dictionary[key];
            }

        });


    document
        .querySelectorAll('[data-i18n-placeholder]')
        .forEach(function (element) {

            const key = element.dataset.i18nPlaceholder;

            if (dictionary[key] !== undefined) {
                element.placeholder = dictionary[key];
            }

        });

}


/*
|--------------------------------------------------------------------------
| LANGUAGE
|--------------------------------------------------------------------------
*/

window.changeLanguage = function (language) {

    localStorage.setItem(
        'sn-language',
        language
    );


    /*
    |--------------------------------------------------------------------------
    | Change translated text driven by the shared dictionary
    |--------------------------------------------------------------------------
    */

    applyTranslations(language);


    /*
    |--------------------------------------------------------------------------
    | Legacy per-element translations (data-en / data-ta pairs)
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '[data-en][data-ta]'
        )
        .forEach(function (element) {

            if (language === 'ta') {

                element.textContent =
                    element.dataset.ta;

            } else {

                element.textContent =
                    element.dataset.en;

            }

        });


    /*
    |--------------------------------------------------------------------------
    | Change placeholders (legacy data-placeholder-en / -ta pairs)
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '[data-placeholder-en][data-placeholder-ta]'
        )
        .forEach(function (element) {

            if (language === 'ta') {

                element.placeholder =
                    element.dataset.placeholderTa;

            } else {

                element.placeholder =
                    element.dataset.placeholderEn;

            }

        });


    /*
    |--------------------------------------------------------------------------
    | Header language buttons
    |--------------------------------------------------------------------------
    */

    const englishButton =
        document.getElementById(
            'englishButton'
        );

    const tamilButton =
        document.getElementById(
            'tamilButton'
        );


    if (englishButton) {

        englishButton.classList.toggle(
            'active',
            language === 'en'
        );

    }


    if (tamilButton) {

        tamilButton.classList.toggle(
            'active',
            language === 'ta'
        );

    }


    document.documentElement.lang =
        language === 'ta'
            ? 'ta'
            : 'en';

};


/*
|--------------------------------------------------------------------------
| LOAD SAVED SETTINGS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | Theme
        |--------------------------------------------------------------------------
        */

        const savedTheme =
            localStorage.getItem(
                'sn-theme'
            );


        const theme =
            savedTheme === 'dark'
                ? 'dark'
                : 'light';


        document.documentElement.classList.remove(
            'dark',
            'light'
        );


        document.documentElement.classList.add(
            theme
        );


        updateThemeIcon();


        /*
        |--------------------------------------------------------------------------
        | Language
        |--------------------------------------------------------------------------
        */

        const savedLanguage =
            localStorage.getItem(
                'sn-language'
            ) || 'en';


        window.changeLanguage(
            savedLanguage
        );


        /*
        |--------------------------------------------------------------------------
        | Close menu when clicking overlay
        |--------------------------------------------------------------------------
        */

        const overlay =
            document.getElementById(
                'menuOverlay'
            );


        if (overlay) {

            overlay.addEventListener(
                'click',
                function () {

                    window.toggleMenu();

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Close menu with Escape
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape'
                ) {

                    const menu =
                        document.getElementById(
                            'appMenu'
                        );


                    if (
                        menu &&
                        menu.classList.contains(
                            'open'
                        )
                    ) {

                        window.toggleMenu();

                    }

                }

            }
        );

    }
);