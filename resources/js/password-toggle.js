/*
|--------------------------------------------------------------------------
| SHOW / HIDE PASSWORD
|--------------------------------------------------------------------------
| Shared by the login and change-password pages. The button names the
| input it controls with aria-controls="inputId"; the eye / eye-off icons
| swap via the .is-visible class (see .password-toggle CSS).
*/

window.togglePasswordVisibility = function (button) {

    const input =
        document.getElementById(
            button.getAttribute('aria-controls')
        );


    if (!input) {
        return;
    }


    const isHidden =
        input.type === 'password';


    input.type =
        isHidden
            ? 'text'
            : 'password';


    button.classList.toggle(
        'is-visible',
        isHidden
    );

    button.setAttribute(
        'aria-label',
        isHidden
            ? 'Hide password'
            : 'Show password'
    );

};
