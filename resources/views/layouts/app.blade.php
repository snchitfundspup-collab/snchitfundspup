<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    {{-- Laravel CSRF Token --}}
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    @include('components.partials.site-icons')

    <title>
        @yield('title', 'SN Chit Funds')
    </title>


    {{-- GLOBAL CSS + JS --}}

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])


    {{-- PAGE-SPECIFIC CSS --}}

    @stack('styles')

</head>


<body>

    {{-- ANIMATED BACKGROUND (shared on every page — see app.css) --}}

    <div class="background-shape shape-orange"></div>
    <div class="background-shape shape-blue"></div>
    <div class="background-shape shape-purple"></div>
    <div class="background-shape shape-bottom"></div>


    {{-- HEADER --}}
    @include('components.header')


    {{-- MENU --}}
    @include('components.menu')


    {{-- PAGE CONTENT --}}

    <main class="animate-page">
        @yield('content')
    </main>


    {{-- FOOTER --}}

    @include('components.footer')


    {{-- PAGE-SPECIFIC JS --}}

    @stack('scripts')

</body>

</html>