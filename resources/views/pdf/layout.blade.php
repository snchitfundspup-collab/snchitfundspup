{{-- Shared shell for downloadable PDFs (DomPDF): plain black-on-white
     page with the colour logo + orange "SN" letterhead. DomPDF supports
     tables and basic CSS only (no flexbox / grid), so layouts use tables.
     The logo is a small 240px copy (sn-chit-funds-logo-pdf.png) so each
     PDF stays light instead of embedding the 1 MB original. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __(html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES)) }}</title>

    <style>
        @page {
            margin: @yield('page_margin', '12mm');
        }

        * {
            font-family: 'DejaVu Sans', sans-serif;
        }

        body {
            margin: 0;

            color: #111827;

            font-size: @yield('font_size', '11px');
        }

        table {
            width: 100%;

            border-collapse: collapse;
        }

        .letterhead td {
            vertical-align: middle;
        }

        .letterhead {
            border-bottom: 2px solid #111827;

            margin-bottom: 10px;
        }

        .logo {
            width: 48px;
            height: 48px;
        }

        .company {
            font-size: 18px;

            font-weight: bold;
        }

        .company .sn {
            color: #FE691E;
        }

        .tagline {
            font-size: 9px;

            color: #4b5563;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title strong {
            font-size: 13px;

            letter-spacing: 1px;

            text-transform: uppercase;
        }

        .doc-title span {
            display: block;

            font-size: 9px;

            color: #4b5563;
        }

        .muted {
            color: #4b5563;
        }

        .ident {
            color: #4b5563;

            font-weight: normal;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        @yield('styles')
    </style>
</head>
<body>

    <table class="letterhead">
        <tr>
            <td style="width: 56px; padding-bottom: 8px;">
                <img
                    class="logo"
                    src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sn-chit-funds-logo-pdf.png'))) }}"
                    alt="SN {{ $companyName ?? 'Chit Funds' }}"
                >
            </td>
            <td style="padding-bottom: 8px;">
                <div class="company"><span class="sn">SN</span> {{ $companyName ?? 'Chit Funds' }}</div>
                <div class="tagline">{{ __(($companyName ?? '') === 'Traders' ? 'Quality Rice · Fair Price' : 'Trust · Growth · Together') }}</div>
            </td>
            <td class="doc-title" style="padding-bottom: 8px;">
                <strong>{{ __(html_entity_decode(trim($__env->yieldContent('doc_title')), ENT_QUOTES)) }}</strong>
                <span>@yield('doc_subtitle')</span>
            </td>
        </tr>
    </table>

    @yield('content')

</body>
</html>
