@php
    /* customers see Sri Lakshmi Micro Finance while it is the only business open to them */
    $portalBrand = \App\Http\Middleware\EnsurePortalSectionOpen::financeOnly()
        ? ['logo' => 'images/sri-lakshmi-logo.png', 'prefix' => 'Sri Lakshmi', 'name' => 'Micro Finance', 'tagline' => 'Small Loans · Easy Repayment', 'tagline_key' => 'finance_tagline', 'title' => 'Sri Lakshmi Micro Finance']
        : ['logo' => 'images/sn-chit-funds-logo.png', 'prefix' => 'SN', 'name' => 'Chit Funds · Traders', 'tagline' => 'Trust · Growth · Together', 'tagline_key' => 'brand_tagline', 'title' => 'SN'];
@endphp
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('components.partials.site-icons')

    <title>@yield('title', 'My Account') | {{ $portalBrand['title'] }}</title>

    {{-- GLOBAL CSS + JS, then the customer pages' own styles --}}

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/create.css',
        'resources/css/dashboard.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/portal.css'
    ])

    @stack('styles')

</head>


<body class="portal-body">

    {{-- ANIMATED BACKGROUND (shared on every page — see app.css) --}}

    <div class="background-shape shape-orange"></div>
    <div class="background-shape shape-blue"></div>
    <div class="background-shape shape-purple"></div>
    <div class="background-shape shape-bottom"></div>


    @include('portal.partials.header')

    @include('portal.partials.menu')


    <main class="animate-page portal-main">

        @if (session('success'))
            <div class="portal-flash glass" role="status">
                <span class="portal-flash-icon icon-3d icon-3d-green"><x-icon name="check" /></span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @yield('content')

    </main>


    @include('portal.partials.footer')

    @stack('scripts')

</body>

</html>
