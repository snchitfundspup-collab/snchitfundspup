<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('components.partials.site-icons')

    <title>@yield('title', 'My Account') | SN</title>

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
