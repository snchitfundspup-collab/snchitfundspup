{{-- Site icons for every page's <head>. SN pages: a bold gold "SN" on
     peacock blue (favicon.ico, icon-192.png) so it reads at 16 px, and the
     full SN logo on the iPhone home screen. Sri Lakshmi Micro Finance (its
     office pages and prints, its own domain, and the customer pages while
     only Micro Finance is open to customers): a bold lotus drawn for each
     size (favicon-16-sl.png, favicon-32-sl.png, *-sl files). --}}

@php
    $sriLakshmiIcons = (($business['key'] ?? null) === 'finance')
        || (($companyName ?? null) === 'Micro Finance')
        || \App\Http\Middleware\SetBusinessContext::onFinanceDomain(request())
        || (request()->is('/', 'my', 'my/*') && \App\Http\Middleware\EnsurePortalSectionOpen::financeOnly());

    $iconSuffix = $sriLakshmiIcons ? '-sl' : '';
@endphp
@if ($sriLakshmiIcons)
    {{-- sharp images drawn for each tab size, so the browser does not shrink the big one (blurry) --}}
    <link rel="icon" href="{{ asset('favicon-32-sl.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon-16-sl.png') }}" type="image/png" sizes="16x16">
@endif
<link rel="icon" href="{{ asset('favicon'.$iconSuffix.'.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('icon-192'.$iconSuffix.'.png') }}" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon'.$iconSuffix.'.png') }}">
