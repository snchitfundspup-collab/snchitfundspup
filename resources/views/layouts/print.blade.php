{{-- Shell for printable lists (payments, pending & due): the letterhead
     and plain black-on-white table styles, opening the print dialog. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('components.partials.site-icons')

    <title>@yield('title') | SN {{ $companyName ?? 'Chit Funds' }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        * {
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            margin: 16px;
            color: #111827;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .letterhead {
            border-bottom: 2px solid #111827;
            margin-bottom: 10px;
        }

        .letterhead td {
            vertical-align: middle;
            padding-bottom: 8px;
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

        .tagline,
        .muted,
        .ident {
            color: #4b5563;
            font-size: 10px;
            font-weight: normal;
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
            font-size: 10px;
            color: #4b5563;
        }

        .print-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .print-bar button {
            padding: 8px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            body {
                margin: 0;
            }

            .print-bar {
                display: none;
            }

            .logo,
            .company .sn {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @include('payments.partials.list-styles')

        @yield('styles')
    </style>
</head>
<body>

    <div class="print-bar">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    <table class="letterhead">
        <tr>
            <td style="width: 56px;">
                <img class="logo" src="{{ asset('images/sn-chit-funds-logo-pdf.png') }}" alt="SN {{ $companyName ?? 'Chit Funds' }}">
            </td>
            <td>
                <div class="company"><span class="sn">SN</span> {{ $companyName ?? 'Chit Funds' }}</div>
                <div class="tagline">{{ ($companyName ?? '') === 'Traders' ? 'Quality Rice · Fair Price' : 'Trust · Growth · Together' }}</div>
            </td>
            <td class="doc-title">
                <strong>@yield('doc_title')</strong>
                <span>{{ now(config('app.business_timezone'))->format('d M Y, h:i A') }}</span>
            </td>
        </tr>
    </table>

    @yield('content')

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>

</body>
</html>
