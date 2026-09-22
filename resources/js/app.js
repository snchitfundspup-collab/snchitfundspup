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
    | Change normal translated text
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
    | Change placeholders
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