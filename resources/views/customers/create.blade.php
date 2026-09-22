<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Add Customer | SN Chit Funds</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>

    /* =========================================================
       ROOT
    ========================================================= */

    :root {
        --orange: #FE691E;
        --orange-dark: #e95712;

        --blue: #3d73ff;
        --blue-light: #76a2ff;

        --purple: #8255ff;
        --lavender: #a77cff;

        --page-bg: #101a3b;

        --text: #f8fbff;
        --text-soft: #b9c9ed;

        --glass: rgba(34, 55, 105, 0.58);
        --glass-light: rgba(255, 255, 255, 0.10);

        --border: rgba(170, 200, 255, 0.28);

        --input-bg: rgba(115, 142, 199, 0.24);
        --input-border: rgba(166, 197, 255, 0.32);

        --shadow:
            0 30px 90px rgba(0, 0, 0, 0.30);
    }


    /* =========================================================
       LIGHT MODE
    ========================================================= */

    html.light {
        --page-bg: #dfe7fa;

        --text: #172449;
        --text-soft: #53678f;

        --glass: rgba(255, 255, 255, 0.46);
        --glass-light: rgba(255, 255, 255, 0.38);

        --border: rgba(255, 255, 255, 0.55);

        --input-bg: rgba(255, 255, 255, 0.34);
        --input-border: rgba(87, 116, 177, 0.28);

        --shadow:
            0 30px 90px rgba(43, 62, 110, 0.20);
    }


    /* =========================================================
       RESET
    ========================================================= */

    * {
        box-sizing: border-box;
    }


    html {
        min-height: 100%;
        transition:
            background 0.5s ease,
            color 0.5s ease;
    }


    body {
        min-height: 100vh;
        margin: 0;

        color: var(--text);

        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            "Segoe UI",
            sans-serif;

        overflow-x: hidden;

        background:
            radial-gradient(
                circle at 10% 20%,
                rgba(254, 105, 30, 0.22),
                transparent 27%
            ),

            radial-gradient(
                circle at 50% 5%,
                rgba(62, 110, 255, 0.34),
                transparent 32%
            ),

            radial-gradient(
                circle at 90% 35%,
                rgba(132, 83, 255, 0.38),
                transparent 30%
            ),

            radial-gradient(
                circle at 25% 85%,
                rgba(70, 105, 255, 0.28),
                transparent 30%
            ),

            linear-gradient(
                135deg,
                #111a39 0%,
                #172754 40%,
                #151d43 70%,
                #211742 100%
            );

        transition:
            background 0.5s ease,
            color 0.5s ease;
    }


    html.light body {
        background:

            radial-gradient(
                circle at 5% 10%,
                rgba(254, 105, 30, 0.18),
                transparent 26%
            ),

            radial-gradient(
                circle at 50% 0%,
                rgba(70, 115, 255, 0.30),
                transparent 32%
            ),

            radial-gradient(
                circle at 100% 30%,
                rgba(139, 93, 255, 0.28),
                transparent 31%
            ),

            radial-gradient(
                circle at 15% 90%,
                rgba(71, 107, 255, 0.22),
                transparent 30%
            ),

            linear-gradient(
                135deg,
                #cfd9f5,
                #dfe7fb 45%,
                #d4d4f1 100%
            );
    }


    /* =========================================================
       ABSTRACT BACKGROUND
    ========================================================= */

    .background-shape {
        position: fixed;

        border-radius: 50%;

        pointer-events: none;

        z-index: 0;

        filter: blur(45px);

        animation:
            floatShape 12s ease-in-out infinite;
    }


    .shape-orange {
        width: 420px;
        height: 420px;

        left: -160px;
        top: 250px;

        background:
            radial-gradient(
                circle,
                rgba(254, 105, 30, 0.52),
                rgba(254, 105, 30, 0.12) 55%,
                transparent 75%
            );
    }


    .shape-blue {
        width: 500px;
        height: 500px;

        left: 20%;
        top: -220px;

        background:
            radial-gradient(
                circle,
                rgba(62, 110, 255, 0.48),
                rgba(62, 110, 255, 0.12) 55%,
                transparent 75%
            );

        animation-delay: -3s;
    }


    .shape-purple {
        width: 500px;
        height: 500px;

        right: -180px;
        top: 220px;

        background:
            radial-gradient(
                circle,
                rgba(138, 78, 255, 0.52),
                rgba(138, 78, 255, 0.12) 55%,
                transparent 75%
            );

        animation-delay: -6s;
    }


    .shape-bottom {
        width: 500px;
        height: 360px;

        left: 30%;
        bottom: -220px;

        background:
            radial-gradient(
                ellipse,
                rgba(254, 105, 30, 0.34),
                rgba(69, 101, 255, 0.24) 45%,
                transparent 75%
            );

        animation-delay: -8s;
    }


    @keyframes floatShape {

        0%,
        100% {
            transform:
                translate3d(0, 0, 0)
                scale(1);
        }

        50% {
            transform:
                translate3d(20px, -18px, 0)
                scale(1.06);
        }
    }


    /* =========================================================
       MAIN WRAPPER
    ========================================================= */

    .page-wrapper {
        position: relative;

        z-index: 2;

        width: 100%;

        max-width: 1180px;

        margin: auto;

        padding:
            24px 24px 50px;
    }


    /* =========================================================
       GLASS
    ========================================================= */

    .glass {
        background:
            linear-gradient(
                135deg,
                rgba(255,255,255,0.13),
                rgba(255,255,255,0.055)
            );

        border:
            1px solid var(--border);

        box-shadow:
            var(--shadow),

            inset 0 1px 0
            rgba(255,255,255,0.22);

        backdrop-filter:
            blur(25px)
            saturate(135%);

        -webkit-backdrop-filter:
            blur(25px)
            saturate(135%);
    }


    /* =========================================================
       HEADER
    ========================================================= */

    .header {
        min-height: 84px;

        border-radius: 22px;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

        padding:
            12px 20px;
    }


    .brand {
        display: flex;

        align-items: center;

        gap: 13px;
    }


    .brand-logo {
width: 58px;
height: 58px;

flex-shrink: 0;

display: flex;
align-items: center;
justify-content: center;

border-radius: 50%;

padding: 5px;

background: rgba(255, 255, 255, 0.10);

border: 1px solid rgba(255, 255, 255, 0.28);

box-shadow:
    0 8px 25px rgba(0, 0, 0, 0.22),
    inset 0 1px 2px rgba(255, 255, 255, 0.25);

backdrop-filter: blur(12px);
-webkit-backdrop-filter: blur(12px);

overflow: hidden;


}

.brand-logo img {
width: 100%;
height: 100%;


object-fit: contain;

display: block;

border-radius: 50%;


}

.brand-logo img:hover {
transform: scale(1.06);


filter:
    drop-shadow(0 8px 18px rgba(254, 105, 30, 0.30));

}

    .brand-name {
        font-size: 23px;

        font-weight: 800;

        letter-spacing: -0.7px;

        white-space: nowrap;
    }


    .brand-name span {
        color: var(--orange);
    }


    .brand-tagline {
        margin-top: 2px;

        color: var(--text-soft);

        font-size: 12px;

        letter-spacing: 0.2px;
    }


    /* =========================================================
       HEADER CONTROLS
    ========================================================= */

    .header-controls {
        display: flex;

        align-items: center;

        gap: 14px;
    }


    .language-switch {
        display: flex;

        align-items: center;

        padding: 3px;

        border-radius: 999px;

        background:
            rgba(30, 51, 103, 0.42);

        border:
            1px solid rgba(255,255,255,0.14);
    }


    .language-button {
        border: 0;

        padding:
            10px 20px;

        border-radius: 999px;

        color: var(--text-soft);

        background: transparent;

        font-size: 13px;

        font-weight: 700;

        cursor: pointer;

        transition:
            all 0.25s ease;
    }


    .language-button.active {
        color: white;

        background:
            linear-gradient(
                135deg,
                #ff913e,
                #fe691e
            );

        box-shadow:
            0 7px 20px
            rgba(254,105,30,0.35);
    }


    .theme-switch {
        display: flex;

        padding: 3px;

        border-radius: 999px;

        background:
            rgba(30,51,103,0.40);

        border:
            1px solid rgba(255,255,255,0.14);
    }


    .theme-button {
        width: 34px;
        height: 32px;

        border: 0;

        border-radius: 999px;

        background: transparent;

        color: var(--text-soft);

        cursor: pointer;

        transition:
            all 0.25s ease;
    }


    .theme-button.active {
        background:
            rgba(255,255,255,0.16);

        color: white;

        box-shadow:
            0 4px 12px
            rgba(0,0,0,0.16);
    }


    .admin {
        display: flex;

        align-items: center;

        gap: 8px;

        color: var(--text);

        font-size: 13px;

        font-weight: 600;
    }


    .admin-avatar {
        width: 40px;
        height: 40px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        background:
            rgba(255,255,255,0.17);

        border:
            1px solid
            rgba(255,255,255,0.30);

        font-size: 18px;
    }


    /* =========================================================
       HERO
    ========================================================= */

    .hero {
        position: relative;

        min-height: 245px;

        margin-top: 32px;

        overflow: hidden;

        border-radius: 25px;

        display: grid;

        grid-template-columns:
            280px 1fr 220px;

        align-items: center;

        padding:
            25px 38px;
    }


    .hero-glow {
        position: absolute;

        width: 360px;
        height: 360px;

        border-radius: 50%;

        left: -40px;
        top: -50px;

        background:
            radial-gradient(
                circle,
                rgba(74,115,255,0.45),
                transparent 70%
            );

        filter:
            blur(20px);
    }


    .hero-glow-right {
        position: absolute;

        width: 350px;
        height: 350px;

        right: -100px;
        top: -100px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle,
                rgba(151,83,255,0.45),
                transparent 70%
            );

        filter:
            blur(20px);
    }


    /* =========================================================
       CSS CUSTOMER ILLUSTRATION
    ========================================================= */

    .customer-illustration {
        position: relative;

        width: 250px;
        height: 230px;

        margin: auto;
    }


    .customer-circle {
        position: absolute;

        width: 190px;
        height: 190px;

        left: 10px;
        bottom: -12px;

        border-radius: 50%;

        background:
            radial-gradient(
                circle at 35% 30%,
                #9ab7ff,
                #466be1 65%,
                #293e9d
            );

        box-shadow:
            0 20px 40px
            rgba(38,65,160,0.35);
    }


    .person-body {
        position: absolute;

        width: 125px;
        height: 105px;

        left: 40px;
        bottom: -8px;

        border-radius:
            65px 65px 20px 20px;

        background:
            linear-gradient(
                145deg,
                #1d65db,
                #1642a5
            );

        box-shadow:
            0 15px 30px
            rgba(12,36,112,0.30);
    }


    .person-neck {
        position: absolute;

        width: 30px;
        height: 35px;

        left: 88px;
        top: 92px;

        border-radius:
            0 0 13px 13px;

        background:
            #c98461;
    }


    .person-head {
        position: absolute;

        width: 72px;
        height: 88px;

        left: 67px;
        top: 34px;

        border-radius:
            45% 45% 48% 48%;

        background:
            linear-gradient(
                135deg,
                #f0b18a,
                #c87855
            );

        box-shadow:
            0 8px 15px
            rgba(25,30,50,0.20);

        z-index: 5;
    }


    .person-hair {
        position: absolute;

        width: 76px;
        height: 48px;

        left: -2px;
        top: -15px;

        border-radius:
            55% 60% 30% 30%;

        background:
            linear-gradient(
                145deg,
                #48332e,
                #201b20
            );

        transform:
            rotate(-5deg);
    }


    .eye {
        position: absolute;

        width: 7px;
        height: 9px;

        top: 43px;

        border-radius: 50%;

        background: #181722;
    }


    .eye-left {
        left: 17px;
    }


    .eye-right {
        right: 17px;
    }


    .smile {
        position: absolute;

        width: 25px;
        height: 12px;

        left: 24px;
        top: 62px;

        border-bottom:
            3px solid #713d37;

        border-radius: 0 0 50% 50%;
    }


    .tablet {
        position: absolute;

        width: 75px;
        height: 48px;

        right: 2px;
        bottom: 10px;

        border-radius: 7px;

        transform:
            rotate(-8deg);

        background:
            linear-gradient(
                135deg,
                #252a35,
                #11151f
            );

        border:
            2px solid
            rgba(255,255,255,0.15);

        box-shadow:
            0 10px 20px
            rgba(0,0,0,0.30);

        z-index: 10;
    }


    /* =========================================================
       HERO CONTENT
    ========================================================= */

    .hero-content {
        position: relative;

        z-index: 3;
    }


    .badge {
        display: inline-flex;

        align-items: center;

        gap: 9px;

        padding:
            8px 15px;

        border-radius: 999px;

        background:
            linear-gradient(
                135deg,
                rgba(254,105,30,0.35),
                rgba(255,255,255,0.10)
            );

        border:
            1px solid
            rgba(255,255,255,0.20);

        box-shadow:
            0 7px 20px
            rgba(0,0,0,0.15);

        font-size: 13px;

        font-weight: 700;
    }


    .badge-icon {
        width: 28px;
        height: 28px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        background:
            linear-gradient(
                135deg,
                #ff9c4c,
                #fe691e
            );

        box-shadow:
            0 5px 14px
            rgba(254,105,30,0.35);
    }


    .hero-title {
        margin:
            18px 0 12px;

        font-size:
            clamp(34px, 4vw, 48px);

        line-height: 1;

        letter-spacing:
            -1.8px;

        font-weight:
            800;

        color:
            #ffffff;

        text-shadow:
            0 4px 25px
            rgba(0,0,0,0.22);
    }


    .hero-description {
        max-width: 540px;

        color:
            #b9cbed;

        font-size: 16px;

        line-height: 1.65;
    }


    /* =========================================================
       HERO RIGHT CARD
    ========================================================= */

    .id-preview {
        position: relative;

        width: 155px;
        height: 100px;

        margin: auto;

        border-radius: 17px;

        background:
            linear-gradient(
                135deg,
                rgba(175,201,255,0.38),
                rgba(91,121,202,0.18)
            );

        border:
            1px solid
            rgba(200,220,255,0.40);

        box-shadow:
            0 20px 35px
            rgba(0,0,0,0.20);

        transform:
            rotate(-4deg);
    }


    .id-person {
        position: absolute;

        left: 20px;
        top: 25px;

        width: 27px;
        height: 27px;

        border-radius: 50%;

        background:
            rgba(240,248,255,0.85);
    }


    .id-person:after {
        content: "";

        position: absolute;

        width: 43px;
        height: 24px;

        left: -8px;
        top: 27px;

        border-radius:
            30px 30px 10px 10px;

        background:
            rgba(240,248,255,0.85);
    }


    .id-lines {
        position: absolute;

        right: 20px;
        top: 29px;

        width: 60px;
    }


    .id-lines span {
        display: block;

        height: 7px;

        margin-bottom: 11px;

        border-radius: 999px;

        background:
            rgba(240,248,255,0.78);
    }


    .id-plus {
        position: absolute;

        width: 45px;
        height: 45px;

        right: -24px;
        bottom: -18px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        background:
            linear-gradient(
                135deg,
                #ff9a48,
                #fe691e
            );

        color: white;

        font-size: 30px;

        box-shadow:
            0 10px 25px
            rgba(254,105,30,0.40);
    }


    /* =========================================================
       FORM CARD
    ========================================================= */

    .form-card {
        margin-top: 25px;

        overflow: hidden;

        border-radius: 25px;
    }


    .form-inner {
        padding:
            25px 34px 0;
    }


    /* =========================================================
       BACK BUTTON
    ========================================================= */

    .back-button {
        display: inline-flex;

        align-items: center;

        gap: 10px;

        padding:
            9px 17px 9px 10px;

        border-radius: 999px;

        color:
            #e6efff;

        background:
            rgba(68,104,181,0.28);

        border:
            1px solid
            rgba(150,185,255,0.30);

        text-decoration: none;

        font-size: 14px;

        font-weight: 700;

        transition:
            all 0.25s ease;
    }


    .back-button:hover {
        transform:
            translateX(-3px);

        background:
            rgba(78,116,203,0.38);
    }


    .back-icon {
        width: 32px;
        height: 32px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        background:
            rgba(115,155,240,0.25);

        font-size: 19px;
    }


    /* =========================================================
       CUSTOMER ID BOX
    ========================================================= */

    .info-box {
        display: flex;

        align-items: center;

        gap: 18px;

        margin-top: 24px;

        padding:
            17px;

        border-radius: 14px;

        background:
            linear-gradient(
                135deg,
                rgba(60,105,222,0.43),
                rgba(67,110,205,0.26)
            );

        border:
            1px solid
            rgba(86,147,255,0.55);

        box-shadow:
            inset 0 1px 0
            rgba(255,255,255,0.13);
    }


    .info-icon {
        flex:
            0 0 43px;

        width: 43px;
        height: 43px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        background:
            linear-gradient(
                135deg,
                #6c9cff,
                #315ce4
            );

        color: white;

        font-size: 23px;

        font-weight: 700;

        box-shadow:
            0 8px 20px
            rgba(47,95,226,0.35);
    }


    .info-title {
        font-size: 14px;

        font-weight: 800;

        color: white;
    }


    .info-text {
        margin-top: 5px;

        color:
            #b9caf0;

        font-size: 12px;

        line-height: 1.5;
    }


    /* =========================================================
       FORM
    ========================================================= */

    .customer-form {
        margin-top: 22px;
    }


    .field {
        display: grid;

        grid-template-columns:
            62px 1fr;

        gap: 18px;

        align-items: center;

        margin-bottom: 23px;
    }


    .field-icon {
        width: 58px;
        height: 58px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        font-size: 24px;

        border:
            1px solid
            rgba(255,255,255,0.12);
    }


    .icon-orange {
        background:
            radial-gradient(
                circle,
                rgba(254,105,30,0.42),
                rgba(254,105,30,0.12)
            );

        color:
            #ffab69;
    }


    .icon-blue {
        background:
            radial-gradient(
                circle,
                rgba(45,103,255,0.55),
                rgba(45,103,255,0.12)
            );

        color:
            #9fc0ff;
    }


    .icon-purple {
        background:
            radial-gradient(
                circle,
                rgba(126,73,255,0.58),
                rgba(126,73,255,0.12)
            );

        color:
            #c2a8ff;
    }


    .field-content {
        min-width: 0;
    }


    .field-label {
        display: block;

        margin-bottom: 8px;

        color:
            #f3f7ff;

        font-size: 14px;

        font-weight: 700;
    }


    .input {
        width: 100%;

        border-radius: 11px;

        border:
            1px solid
            var(--input-border);

        background:
            var(--input-bg);

        color:
            var(--text);

        padding:
            13px 14px;

        outline: none;

        font-size: 14px;

        transition:
            all 0.25s ease;

        backdrop-filter:
            blur(10px);
    }


    .input::placeholder {
        color:
            #aec0e6;
    }


    .input:hover {
        border-color:
            rgba(128,171,255,0.52);
    }


    .input:focus {
        border-color:
            rgba(254,105,30,0.85);

        background:
            rgba(91,124,191,0.30);

        box-shadow:
            0 0 0 4px
            rgba(254,105,30,0.09),

            0 8px 25px
            rgba(0,0,0,0.10);
    }


    textarea.input {
        resize:
            vertical;

        min-height:
            92px;
    }


    /* =========================================================
       FORM FOOTER
    ========================================================= */

    .form-footer {
        display: flex;

        justify-content: flex-end;

        align-items: center;

        gap: 16px;

        margin:
            5px -34px 0;

        padding:
            20px 34px 24px;

        border-top:
            1px solid
            rgba(255,255,255,0.12);
    }


    .cancel-button {
        display: inline-flex;

        align-items: center;

        justify-content: center;

        gap: 10px;

        min-width: 168px;

        padding:
            13px 22px;

        border-radius: 13px;

        border:
            1px solid
            rgba(159,189,247,0.34);

        background:
            rgba(67,91,145,0.32);

        color:
            white;

        text-decoration: none;

        font-size: 14px;

        font-weight: 700;

        transition:
            all 0.25s ease;
    }


    .cancel-button:hover {
        transform:
            translateY(-2px);

        background:
            rgba(82,108,168,0.43);
    }


    .save-button {
        min-width: 230px;

        padding:
            14px 26px;

        border: 0;

        border-radius: 13px;

        background:
            linear-gradient(
                135deg,
                #ff963d,
                #fe691e
            );

        color: white;

        font-size: 14px;

        font-weight: 800;

        cursor: pointer;

        box-shadow:
            0 12px 25px
            rgba(254,105,30,0.34),

            inset 0 1px 0
            rgba(255,255,255,0.35);

        transition:
            all 0.25s ease;
    }


    .save-button:hover {
        transform:
            translateY(-3px);

        box-shadow:
            0 17px 32px
            rgba(254,105,30,0.43);
    }


    .save-button:active {
        transform:
            translateY(0);
    }


    /* =========================================================
       FOOTER
    ========================================================= */

    .page-footer {
        display: flex;

        justify-content: space-between;

        align-items: center;

        padding:
            35px 48px 10px;

        color:
            #a7b9df;
    }


    .footer-brand {
        display: flex;

        align-items: center;

        gap: 12px;
    }


    .footer-logo {
        width: 42px;
        height: 42px;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .footer-logo img {
        width: 100%;
        height: 100%;

        object-fit: contain;
    }


    .footer-name {
        font-size: 18px;

        font-weight: 800;
    }


    .footer-tagline {
        font-size: 10px;

        margin-top: 3px;
    }


    .footer-right {
        display: flex;

        gap: 15px;

        font-size: 11px;
    }


    /* =========================================================
       VALIDATION
    ========================================================= */

    .alert-success,
    .alert-error {
        margin-top: 20px;

        padding: 14px 17px;

        border-radius: 13px;

        font-size: 13px;
    }


    .alert-success {
        color: #b9ffd1;

        background:
            rgba(35,180,100,0.15);

        border:
            1px solid
            rgba(75,220,130,0.25);
    }


    .alert-error {
        color: #ffd0d0;

        background:
            rgba(220,60,70,0.14);

        border:
            1px solid
            rgba(255,100,110,0.25);
    }


    .alert-error ul {
        margin:
            8px 0 0 20px;
    }


    /* =========================================================
       ANIMATIONS
    ========================================================= */

    .animate-page {
        animation:
            pageIn 0.65s ease-out;
    }


    @keyframes pageIn {

        from {
            opacity: 0;

            transform:
                translateY(18px);
        }

        to {
            opacity: 1;

            transform:
                translateY(0);
        }
    }


    .hero,
    .form-card {
        transition:
            background 0.5s ease,
            border-color 0.5s ease,
            box-shadow 0.5s ease;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 900px) {

        .hero {
            grid-template-columns:
                220px 1fr;

            padding:
                25px;
        }


        .id-preview {
            display: none;
        }

    }


    @media (max-width: 700px) {

        .page-wrapper {
            padding:
                12px;
        }


        .header {
            padding:
                10px 14px;

            border-radius:
                18px;
        }


        .brand-tagline {
            display: none;
        }


        .brand-name {
            font-size:
                18px;
        }


        .brand-logo {
            width:
                48px;

            height:
                48px;
        }


        .admin {
            display:
                none;
        }


        .hero {
            grid-template-columns:
                1fr;

            text-align:
                center;

            padding:
                25px 20px;
        }


        .customer-illustration {
            order:
                -1;

            transform:
                scale(0.85);
        }


        .hero-description {
            margin:
                auto;
        }


        .badge {
            margin:
                auto;
        }


        .form-inner {
            padding:
                20px 17px 0;
        }


        .field {
            grid-template-columns:
                48px 1fr;

            gap:
                12px;
        }


        .field-icon {
            width:
                48px;

            height:
                48px;

            font-size:
                20px;
        }


        .form-footer {
            margin:
                5px -17px 0;

            padding:
                18px;

            flex-direction:
                column-reverse;
        }


        .cancel-button,
        .save-button {
            width:
                100%;
        }


        .page-footer {
            padding:
                28px 10px;

            flex-direction:
                column;

            gap:
                15px;
        }

    }


    @media (max-width: 480px) {

        .language-button {
            padding:
                8px 11px;

            font-size:
                11px;
        }


        .theme-button {
            width:
                30px;
        }


        .hero-title {
            font-size:
                35px;
        }


        .field {
            grid-template-columns:
                1fr;
        }


        .field-icon {
            display:
                none;
        }
        /* =========================================================


FINAL RESPONSIVE + LIGHT MODE FIXES
========================================================= */

/* =========================================================
LIGHT / BRIGHT MODE TEXT
========================================================= */

html.light .hero-title {
color: #172449;
text-shadow: none;
}

html.light .hero-description {
color: #53678f;
}

html.light .field-label {
color: #172449;
}

html.light .info-title {
color: #172449;
}

html.light .info-text {
color: #53678f;
}

html.light .back-button {
color: #26375f;
}

html.light .cancel-button {
color: #26375f;
}

html.light .page-footer {
color: #53678f;
}

html.light .footer-name {
color: #172449;
}

html.light .footer-tagline {
color: #53678f;
}

html.light .footer-right {
color: #53678f;
}

html.light .brand-tagline {
color: #53678f;
}

html.light .language-button {
color: #53678f;
}

html.light .theme-button {
color: #53678f;
}

html.light .admin {
color: #172449;
}

/* Input text */

html.light .input {
color: #172449;
}

html.light .input::placeholder {
color: #66779c;
}

html.light .input:focus {
background: rgba(255,255,255,0.55);
}

/* Keep orange active language button white */

html.light .language-button.active {
color: #ffffff;
}

/* Keep save button white */

html.light .save-button {
color: #ffffff;
}

/* Keep info icon white */

html.light .info-icon {
color: #ffffff;
}

/* =========================================================
TABLET
========================================================= */

@media (max-width: 900px) {


.page-wrapper {
    padding-left: 18px;
    padding-right: 18px;
}

.header {
    gap: 12px;
}

.header-controls {
    gap: 9px;
}

.language-button {
    padding-left: 14px;
    padding-right: 14px;
}

.hero {
    grid-template-columns: 210px 1fr;
}

.customer-illustration {
    transform: scale(0.90);
}


}

/* =========================================================
PHONE
========================================================= */

@media (max-width: 700px) {


.page-wrapper {
    width: 100%;
    padding: 10px;
}


/* -----------------------------------------
   HEADER
   ----------------------------------------- */

.header {

    min-height: auto;

    padding: 12px;

    border-radius: 18px;

    /*
     * Allow controls to move below logo
     * instead of going outside screen.
     */
    flex-wrap: wrap;

    gap: 10px;
}


.brand {
    width: 100%;

    min-width: 0;
}


.brand-logo {
    width: 46px;
    height: 46px;

    flex-shrink: 0;
}


.brand-flower {
    transform: scale(0.85);
}


.brand-name {
    font-size: 18px;

    white-space: nowrap;
}


.brand-tagline {
    display: none;
}


/*
 * Controls get their own row.
 */

.header-controls {

    width: 100%;

    display: flex;

    justify-content: flex-end;

    gap: 8px;

    flex-wrap: nowrap;

    min-width: 0;
}


/*
 * Hide Admin on phone.
 */

.admin {
    display: none;
}


/*
 * Language switch
 */

.language-switch {
    flex-shrink: 0;
}


.language-button {
    padding: 8px 12px;

    font-size: 11px;

    white-space: nowrap;
}


/*
 * Theme switch
 */

.theme-switch {
    flex-shrink: 0;
}


.theme-button {
    width: 34px;
    height: 32px;

    flex-shrink: 0;
}


/* -----------------------------------------
   HERO
   ----------------------------------------- */

.hero {

    grid-template-columns: 1fr;

    min-height: auto;

    margin-top: 18px;

    padding: 24px 18px;

    text-align: center;

    border-radius: 20px;
}


.customer-illustration {

    order: -1;

    width: 210px;
    height: 195px;

    margin: -5px auto 0;

    transform: scale(0.82);
}


.hero-content {
    width: 100%;
}


.badge {
    margin-left: auto;
    margin-right: auto;

    width: fit-content;
}


.hero-title {
    font-size: clamp(30px, 9vw, 40px);

    letter-spacing: -1px;

    margin-top: 14px;
}


.hero-description {
    max-width: 500px;

    margin-left: auto;
    margin-right: auto;

    font-size: 14px;
}


.id-preview {
    display: none;
}


/* -----------------------------------------
   FORM
   ----------------------------------------- */

.form-card {
    margin-top: 18px;

    border-radius: 20px;
}


.form-inner {
    padding: 18px 16px 0;
}


.back-button {
    font-size: 13px;
}


.info-box {

    gap: 12px;

    padding: 14px;

    margin-top: 18px;
}


.info-icon {

    flex: 0 0 40px;

    width: 40px;
    height: 40px;

    font-size: 20px;
}


.info-title {
    font-size: 13px;
}


.info-text {
    font-size: 11px;
}


/* -----------------------------------------
   FORM FIELDS
   ----------------------------------------- */

.customer-form {
    margin-top: 20px;
}


.field {

    grid-template-columns: 46px 1fr;

    gap: 11px;

    margin-bottom: 20px;
}


.field-icon {

    width: 46px;
    height: 46px;

    font-size: 19px;
}


.field-label {
    font-size: 13px;

    margin-bottom: 7px;
}


.input {

    width: 100%;

    padding: 12px;

    font-size: 14px;

    min-width: 0;
}


textarea.input {
    min-height: 85px;
}


/* -----------------------------------------
   BUTTONS
   ----------------------------------------- */

.form-footer {

    margin: 5px -16px 0;

    padding: 16px;

    flex-direction: column-reverse;

    gap: 10px;
}


.cancel-button,
.save-button {

    width: 100%;

    min-width: 0;

    min-height: 50px;
}


/* -----------------------------------------
   FOOTER
   ----------------------------------------- */

.page-footer {

    padding: 25px 8px 10px;

    flex-direction: column;

    gap: 12px;

    text-align: center;
}


.footer-brand {
    justify-content: center;
}


.footer-right {

    justify-content: center;

    flex-wrap: wrap;

    gap: 8px;
}


}

/* =========================================================
SMALL PHONE
========================================================= */

@media (max-width: 480px) {

.page-wrapper {
    padding: 7px;
}


/* Header */

.header {
    padding: 10px;

    border-radius: 16px;
}


.brand-logo {
    width: 42px;
    height: 42px;
}


.brand-name {
    font-size: 16px;
}


.header-controls {
    justify-content: space-between;
}


.language-button {

    padding: 7px 10px;

    font-size: 10px;
}


.theme-button {

    width: 32px;
    height: 30px;

    font-size: 13px;
}


/* Hero */

.hero {
    padding: 18px 14px;

    margin-top: 14px;
}


.customer-illustration {

    width: 180px;
    height: 175px;

    transform: scale(0.72);
}


.hero-title {
    font-size: 31px;
}


.hero-description {
    font-size: 13px;

    line-height: 1.55;
}


/* Form */

.form-inner {
    padding: 15px 13px 0;
}


/* =========================================================
PHONE FORM FIELDS — FIXED SIZE
========================================================= */

@media (max-width: 700px) {


.field {
    display: block;

    margin-bottom: 22px;
}

.field-icon {
    display: none;
}

.field-label {
    display: block;

    font-size: 14px;

    margin-bottom: 8px;
}

.input {
    display: block;

    width: 100%;

    min-height: 48px;

    padding: 13px 14px;

    font-size: 15px;

    border-radius: 12px;

    box-sizing: border-box;
}

textarea.input {
    min-height: 110px;

    resize: vertical;
}


}


.form-footer {

    margin-left: -13px;
    margin-right: -13px;

    padding: 14px;
}


.cancel-button,
.save-button {
    min-height: 48px;

    font-size: 13px;
}


}

/* =========================================================
VERY SMALL PHONES
========================================================= */

@media (max-width: 360px) {


.brand-name {
    font-size: 15px;
}


.language-button {

    padding: 6px 8px;

    font-size: 9px;
}


.theme-button {

    width: 30px;
    height: 28px;
}


.hero-title {
    font-size: 28px;
}


.field {

    grid-template-columns: 1fr;
}


.field-icon {

    display: none;
}


}


    }
/* =========================================================
```

BRIGHT MODE — DARK TEXT
========================================================= */

html.light body {
color: #172449;
}

/* Header */

html.light .brand-name {
color: #172449;
}

html.light .brand-name span {
color: #fe691e;
}

html.light .brand-tagline {
color: #53678f;
}

html.light .admin {
color: #172449;
}

/* Hero */

html.light .hero-title {
color: #172449;
text-shadow: none;
}

html.light .hero-description {
color: #53678f;
}

/* Badge */

html.light .badge {
color: #172449;
}

/* Form */

html.light .field-label {
color: #172449;
}

html.light .info-title {
color: #172449;
}

html.light .info-text {
color: #53678f;
}

/* Inputs */

html.light .input {
color: #172449;
}

html.light .input::placeholder {
color: #64759a;
}

/* Back button */

html.light .back-button {
color: #172449;
}

/* Cancel */

html.light .cancel-button {
color: #172449;
}

/* Footer */

html.light .page-footer {
color: #53678f;
}

html.light .footer-name {
color: #172449;
}

html.light .footer-tagline {
color: #53678f;
}

html.light .footer-right {
color: #53678f;
}

/* =========================================================
   CUSTOMER SUCCESS POPUP
========================================================= */

.customer-success-overlay {

    position: fixed;

    inset: 0;

    z-index: 99999;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    background:
        rgba(5, 10, 25, 0.35);

    backdrop-filter: blur(6px);

    -webkit-backdrop-filter: blur(6px);

    opacity: 0;

    visibility: hidden;

    pointer-events: none;

    transition:
        opacity 0.25s ease,
        visibility 0.25s ease;

}


/* =========================================================
   SHOW POPUP
========================================================= */

.customer-success-overlay.show {

    opacity: 1;

    visibility: visible;

    pointer-events: auto;

}


/* =========================================================
   POPUP
========================================================= */

.customer-success-popup {

    position: relative;

    width: min(430px, 100%);

    padding: 30px 28px 26px;

    border-radius: 24px;

    background:
        rgba(255, 255, 255, 0.12);

    border:
        1px solid
        rgba(255, 255, 255, 0.18);

    backdrop-filter: blur(25px);

    -webkit-backdrop-filter: blur(25px);

    box-shadow:
        0 30px 80px rgba(0, 0, 0, 0.30),
        inset 0 1px 0 rgba(255, 255, 255, 0.16);

    color: white;

    text-align: center;

    transform:
        translateY(20px)
        scale(0.96);

    opacity: 0;

    transition:
        transform 0.3s ease,
        opacity 0.3s ease;

}


/* Popup animation */

.customer-success-overlay.show
.customer-success-popup {

    transform:
        translateY(0)
        scale(1);

    opacity: 1;

}


/* =========================================================
   CLOSE BUTTON
========================================================= */

.customer-success-close {

    position: absolute;

    top: 12px;

    right: 14px;

    width: 32px;

    height: 32px;

    border: none;

    border-radius: 50%;

    background:
        rgba(255, 255, 255, 0.08);

    color: inherit;

    font-size: 22px;

    line-height: 1;

    cursor: pointer;

    transition:
        background 0.2s ease,
        transform 0.2s ease;

}


.customer-success-close:hover {

    background:
        rgba(255, 255, 255, 0.16);

    transform: scale(1.05);

}


/* =========================================================
   SUCCESS ICON
========================================================= */

.customer-success-icon {

    width: 70px;

    height: 70px;

    margin: 0 auto 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        rgba(34, 197, 94, 0.16);

    border:
        1px solid
        rgba(34, 197, 94, 0.35);

    color: #4ade80;

    font-size: 34px;

    font-weight: 700;

    box-shadow:
        0 10px 30px
        rgba(34, 197, 94, 0.12);

}


/* =========================================================
   TEXT
========================================================= */

.customer-success-content h3 {

    margin: 0;

    font-size: 1.25rem;

    font-weight: 700;

    letter-spacing: -0.01em;

}


.customer-success-content p {

    margin: 8px 0 0;

    font-size: 0.92rem;

    line-height: 1.5;

    opacity: 0.72;

}


/* =========================================================
   PROGRESS BAR
========================================================= */

.customer-success-progress {

    position: absolute;

    bottom: 0;

    left: 0;

    height: 3px;

    width: 100%;

    border-radius:
        0 0 24px 24px;

    background:
        #FE691E;

    transform-origin: left;

}


/* =========================================================
   LIGHT MODE
========================================================= */

html.light .customer-success-overlay {

    background:
        rgba(30, 41, 59, 0.20);

}


html.light .customer-success-popup {

    background:
        rgba(255, 255, 255, 0.82);

    border-color:
        rgba(23, 36, 73, 0.10);

    color: #172449;

    box-shadow:
        0 30px 80px
        rgba(30, 41, 59, 0.18),

        inset 0 1px 0
        rgba(255, 255, 255, 0.90);

}


html.light .customer-success-close {

    background:
        rgba(23, 36, 73, 0.06);

}


html.light .customer-success-close:hover {

    background:
        rgba(23, 36, 73, 0.10);

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 480px) {

    .customer-success-overlay {

        padding: 16px;

    }


    .customer-success-popup {

        padding:
            28px 22px 24px;

        border-radius: 20px;

    }


    .customer-success-icon {

        width: 62px;

        height: 62px;

        font-size: 30px;

        margin-bottom: 15px;

    }


    .customer-success-content h3 {

        font-size: 1.1rem;

    }


    .customer-success-content p {

        font-size: 0.86rem;

    }

}

</style>


</head>

<body>


<!-- =========================================================
     BACKGROUND
========================================================== -->

<div class="background-shape shape-orange"></div>
<div class="background-shape shape-blue"></div>
<div class="background-shape shape-purple"></div>
<div class="background-shape shape-bottom"></div>


<!-- =========================================================
     PAGE
========================================================== -->

<div class="page-wrapper animate-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="header glass">

        <div class="brand">

            <div class="brand-logo">
                <img
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt="SN Chit Funds"
                >
            </div>


            <div>

                <div class="brand-name">
                    SN <span>Chit Funds</span>
                </div>

                <div
                    class="brand-tagline"
                    id="brandTagline"
                >
                    Trust • Growth • Together
                </div>

            </div>

        </div>


        <div class="header-controls">


            <!-- LANGUAGE -->

            <div class="language-switch">

                <button
                    type="button"
                    id="englishButton"
                    class="language-button active"
                    onclick="changeLanguage('en')"
                >
                    English
                </button>


                <button
                    type="button"
                    id="tamilButton"
                    class="language-button"
                    onclick="changeLanguage('ta')"
                >
                    தமிழ்
                </button>

            </div>


            <!-- THEME -->

            <div class="theme-switch">

                <button
                    type="button"
                    id="lightButton"
                    class="theme-button"
                    onclick="changeTheme('light')"
                    title="Light mode"
                >
                    ☀
                </button>


                <button
                    type="button"
                    id="darkButton"
                    class="theme-button active"
                    onclick="changeTheme('dark')"
                    title="Dark mode"
                >
                    ☾
                </button>

            </div>


           

        </div>

    </header>


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hero glass">

        <div class="hero-glow"></div>

        <div class="hero-glow-right"></div>


        <!-- CUSTOMER -->

        <div class="customer-illustration">

            <div class="customer-circle"></div>

            <div class="person-body"></div>

            <div class="person-neck"></div>

            <div class="person-head">

                <div class="person-hair"></div>

                <div class="eye eye-left"></div>

                <div class="eye eye-right"></div>

                <div class="smile"></div>

            </div>

            <div class="tablet"></div>

        </div>


        <!-- HERO CONTENT -->

        <div class="hero-content">

            <div class="badge">

                <span class="badge-icon">
                    ♙
                </span>

                <span id="heroBadge">
                    Customer Registration
                </span>

            </div>


            <h1
                class="hero-title"
                id="heroTitle"
            >
                Add Customer
            </h1>


            <p
                class="hero-description"
                id="heroDescription"
            >
                Create a new customer profile and keep all
                their chit fund information organized.
            </p>

        </div>


        <!-- ID CARD -->

        <div class="id-preview">

            <div class="id-person"></div>

            <div class="id-lines">

                <span></span>
                <span></span>
                <span style="width:40px;"></span>

            </div>


            <div class="id-plus">
                +
            </div>

        </div>

    </section>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <section class="form-card glass">

        <div class="form-inner">


            <!-- BACK -->

            <a
                href="{{ route('customers.index') }}"
                class="back-button"
            >

                <span class="back-icon">
                    ←
                </span>

                <span id="backText">
                    Back to Customers
                </span>

            </a>


            <!-- SUCCESS -->

            @if (session('success'))

                <div class="alert-success">

                    {{ session('success') }}

                </div>

            @endif


            <!-- ERRORS -->

            @if ($errors->any())

                <div class="alert-error">

                    <strong id="errorTitle">
                        Please correct the following:
                    </strong>

                    <ul>

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <!-- CUSTOMER ID -->

            <div class="info-box">

                <div class="info-icon">
                    i
                </div>


                <div>

                    <div
                        class="info-title"
                        id="customerIdTitle"
                    >
                        Customer ID
                    </div>


                    <div
                        class="info-text"
                        id="customerIdText"
                    >
                        A unique customer ID will be generated
                        automatically when you save the customer.
                    </div>

                </div>

            </div>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action="{{ route('customers.store') }}"
                class="customer-form"
            >

                @csrf


                <!-- NAME -->

                <div class="field">

                    <div class="field-icon icon-orange">
                        ♙
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="name"
                            id="nameLabel"
                        >
                            Name
                        </label>


                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="input"
                            placeholder="Enter customer name"
                            required
                        >

                    </div>

                </div>


                <!-- PHONE -->

                <div class="field">

                    <div class="field-icon icon-blue">
                        ☎
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="phone"
                            id="phoneLabel"
                        >
                            Phone Number
                        </label>


                        <input
                            id="phone"
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="input"
                            placeholder="Enter phone number"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="field">

                    <div class="field-icon icon-purple">
                        ✉
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="email"
                            id="emailLabel"
                        >
                            Email
                        </label>


                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="input"
                            placeholder="Enter email address"
                        >

                    </div>

                </div>


                <!-- ADDRESS -->

                <div class="field">

                    <div class="field-icon icon-blue">
                        ●
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="address"
                            id="addressLabel"
                        >
                            Address
                        </label>


                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="input"
                            placeholder="Enter customer address"
                        >{{ old('address') }}</textarea>

                    </div>

                </div>


                <!-- REMARKS -->

                <div class="field">

                    <div class="field-icon icon-purple">
                        ▤
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="remarks"
                            id="remarksLabel"
                        >
                            Remarks / Identification
                        </label>


                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="3"
                            class="input"
                            placeholder="Optional notes or identification"
                        >{{ old('remarks') }}</textarea>

                    </div>

                </div>


                <!-- FORM FOOTER -->

                <div class="form-footer">


                    <!-- CANCEL -->

                    <a
                        href="{{ route('customers.index') }}"
                        class="cancel-button"
                    >

                        <span>
                            ✕
                        </span>

                        <span id="cancelText">
                            Cancel
                        </span>

                    </a>


                    <!-- SAVE -->

                    <button
                        type="submit"
                        class="save-button"
                    >

                        <span>
                            ▣
                        </span>

                        <span id="saveText">
                            Save Customer
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </section>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="page-footer">

        <div class="footer-brand">

            <div class="footer-logo">
                <img
                    src="{{ asset('images/sn-chit-funds-logo.png') }}"
                    alt="SN Chit Funds"
                >
            </div>


            <div>

                <div class="footer-name">
                    SN Chit Funds
                </div>

                <div class="footer-tagline">
                    Trust • Growth • Together
                </div>

            </div>

        </div>


        <div class="footer-right">

            <span>
                • Secure
            </span>

            <span>
                • Reliable
            </span>

            <span>
                • Always With You
            </span>

        </div>

    </footer>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================== -->

<script>

    /* =========================================================
       TRANSLATIONS
    ========================================================= */

    const translations = {

        en: {

            brandTagline:
                "Trust • Growth • Together",

            heroBadge:
                "Customer Registration",

            heroTitle:
                "Add Customer",

            heroDescription:
                "Create a new customer profile and keep all their chit fund information organized.",

            backText:
                "Back to Customers",

            customerIdTitle:
                "Customer ID",

            customerIdText:
                "A unique customer ID will be generated automatically when you save the customer.",

            nameLabel:
                "Name",

            phoneLabel:
                "Phone Number",

            emailLabel:
                "Email",

            addressLabel:
                "Address",

            remarksLabel:
                "Remarks / Identification",

            cancelText:
                "Cancel",

            saveText:
                "Save Customer",

            errorTitle:
                "Please correct the following:",

            namePlaceholder:
                "Enter customer name",

            phonePlaceholder:
                "Enter phone number",

            emailPlaceholder:
                "Enter email address",

            addressPlaceholder:
                "Enter customer address",

            remarksPlaceholder:
                "Optional notes or identification"

        },


        ta: {

            brandTagline:
                "நம்பிக்கை • வளர்ச்சி • ஒன்றாக",

            heroBadge:
                "வாடிக்கையாளர் பதிவு",

            heroTitle:
                "வாடிக்கையாளரைச் சேர்க்கவும்",

            heroDescription:
                "புதிய வாடிக்கையாளர் சுயவிவரத்தை உருவாக்கி, அவர்களின் சீட்டு நிதி தகவல்களை ஒழுங்காக பராமரிக்கவும்.",

            backText:
                "வாடிக்கையாளர்களுக்குத் திரும்பு",

            customerIdTitle:
                "வாடிக்கையாளர் அடையாள எண்",

            customerIdText:
                "வாடிக்கையாளரை சேமிக்கும் போது தனிப்பட்ட அடையாள எண் தானாக உருவாக்கப்படும்.",

            nameLabel:
                "பெயர்",

            phoneLabel:
                "தொலைபேசி எண்",

            emailLabel:
                "மின்னஞ்சல்",

            addressLabel:
                "முகவரி",

            remarksLabel:
                "குறிப்புகள் / அடையாளம்",

            cancelText:
                "ரத்து செய்",

            saveText:
                "வாடிக்கையாளரை சேமிக்கவும்",

            errorTitle:
                "பின்வரும் தகவல்களை சரிபார்க்கவும்:",

            namePlaceholder:
                "வாடிக்கையாளர் பெயரை உள்ளிடவும்",

            phonePlaceholder:
                "தொலைபேசி எண்ணை உள்ளிடவும்",

            emailPlaceholder:
                "மின்னஞ்சல் முகவரியை உள்ளிடவும்",

            addressPlaceholder:
                "வாடிக்கையாளர் முகவரியை உள்ளிடவும்",

            remarksPlaceholder:
                "விருப்பமான குறிப்புகள் அல்லது அடையாளத்தை உள்ளிடவும்"

        }

    };


    /* =========================================================
       CHANGE LANGUAGE
    ========================================================== */

  window.changeCreatePageLanguage = function (language) {

        const t =
            translations[language];


        document.getElementById('brandTagline').textContent =
            t.brandTagline;


        document.getElementById('heroBadge').textContent =
            t.heroBadge;


        document.getElementById('heroTitle').textContent =
            t.heroTitle;


        document.getElementById('heroDescription').textContent =
            t.heroDescription;


        document.getElementById('backText').textContent =
            t.backText;


        document.getElementById('customerIdTitle').textContent =
            t.customerIdTitle;


        document.getElementById('customerIdText').textContent =
            t.customerIdText;


        document.getElementById('nameLabel').textContent =
            t.nameLabel;


        document.getElementById('phoneLabel').textContent =
            t.phoneLabel;


        document.getElementById('emailLabel').textContent =
            t.emailLabel;


        document.getElementById('addressLabel').textContent =
            t.addressLabel;


        document.getElementById('remarksLabel').textContent =
            t.remarksLabel;


        document.getElementById('cancelText').textContent =
            t.cancelText;


        document.getElementById('saveText').textContent =
            t.saveText;


        const errorTitle =
            document.getElementById('errorTitle');

        if (errorTitle) {
            errorTitle.textContent =
                t.errorTitle;
        }


        document.getElementById('name').placeholder =
            t.namePlaceholder;


        document.getElementById('phone').placeholder =
            t.phonePlaceholder;


        document.getElementById('email').placeholder =
            t.emailPlaceholder;


        document.getElementById('address').placeholder =
            t.addressPlaceholder;


        document.getElementById('remarks').placeholder =
            t.remarksPlaceholder;


        document.getElementById('englishButton')
            .classList.toggle(
                'active',
                language === 'en'
            );


        document.getElementById('tamilButton')
            .classList.toggle(
                'active',
                language === 'ta'
            );


        document.documentElement.lang =
            language;


        localStorage.setItem(
            'snchitfunds_language',
            language
        );

    }


    /* =========================================================
       CHANGE THEME
    ========================================================== */

    function changeTheme(theme) {

        const html =
            document.documentElement;


        html.classList.remove(
            'light',
            'dark'
        );


        html.classList.add(theme);


        document.getElementById('lightButton')
            .classList.toggle(
                'active',
                theme === 'light'
            );


        document.getElementById('darkButton')
            .classList.toggle(
                'active',
                theme === 'dark'
            );


        localStorage.setItem(
            'snchitfunds_theme',
            theme
        );

    }


    /* =========================================================
       INITIALIZE
    ========================================================== */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const savedTheme =
                localStorage.getItem(
                    'snchitfunds_theme'
                ) || 'dark';


            const savedLanguage =
                localStorage.getItem(
                    'snchitfunds_language'
                ) || 'en';


            changeTheme(
                savedTheme
            );


            changeCreatePageLanguage(
                savedLanguage
            );

        }
    );
    /* =========================================================
   CUSTOMER SUCCESS POPUP
========================================================= */

let customerSuccessTimer = null;


/**
 * Show success popup
 */
function showCustomerSuccessPopup() {

    const popup =
        document.getElementById(
            'customerSuccessPopup'
        );

    const title =
        document.getElementById(
            'customerSuccessTitle'
        );

    const message =
        document.getElementById(
            'customerSuccessMessage'
        );

    const progress =
        document.querySelector(
            '.customer-success-progress'
        );


    if (!popup) {
        return;
    }


    /* -----------------------------------------
       Current language
    ----------------------------------------- */

    const language =
        localStorage.getItem(
            'sn_language'
        ) || 'en';


    /* -----------------------------------------
       English
    ----------------------------------------- */

    if (language === 'ta') {

        title.textContent =
            'வாடிக்கையாளர் வெற்றிகரமாக சேர்க்கப்பட்டார்';

        message.textContent =
            'வாடிக்கையாளர் விவரங்கள் வெற்றிகரமாக சேமிக்கப்பட்டுள்ளன.';

    } else {

        title.textContent =
            'Customer Added Successfully';

        message.textContent =
            'The customer has been added successfully.';

    }


    /* -----------------------------------------
       Show
    ----------------------------------------- */

    popup.classList.add('show');

    popup.setAttribute(
        'aria-hidden',
        'false'
    );


    /* -----------------------------------------
       Progress animation
    ----------------------------------------- */

    if (progress) {

        progress.style.transition = 'none';

        progress.style.transform =
            'scaleX(1)';

        requestAnimationFrame(() => {

            requestAnimationFrame(() => {

                progress.style.transition =
                    'transform 4s linear';

                progress.style.transform =
                    'scaleX(0)';

            });

        });

    }


    /* -----------------------------------------
       Auto close
    ----------------------------------------- */

    clearTimeout(
        customerSuccessTimer
    );

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
        document.getElementById(
            'customerSuccessPopup'
        );

    if (!popup) {
        return;
    }


    popup.classList.remove('show');

    popup.setAttribute(
        'aria-hidden',
        'true'
    );


    clearTimeout(
        customerSuccessTimer
    );

}


/* =========================================================
   CLOSE WITH ESCAPE
========================================================= */

document.addEventListener(
    'keydown',
    function (event) {

        if (event.key !== 'Escape') {
            return;
        }

        closeCustomerSuccessPopup();

    }
);
</script>

<!-- =========================================================
     CUSTOMER SUCCESS POPUP
========================================================= -->

<div
    id="customerSuccessPopup"
    class="customer-success-overlay"
    aria-hidden="true"
>
    <div
        class="customer-success-popup"
        role="alert"
        aria-live="polite"
    >

        <button
            type="button"
            class="customer-success-close"
            onclick="closeCustomerSuccessPopup()"
            aria-label="Close"
        >
            ×
        </button>


        <div class="customer-success-icon">
            ✓
        </div>


        <div class="customer-success-content">

            <h3 id="customerSuccessTitle">
                Customer Added Successfully
            </h3>

            <p id="customerSuccessMessage">
                The customer has been added successfully.
            </p>

        </div>


        <div class="customer-success-progress"></div>

    </div>
</div>
</body>
@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        showCustomerSuccessPopup();
    });
</script>
@endif

</html>
