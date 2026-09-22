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

    {{-- HEADER --}}
    @include('components.header')


    {{-- MENU --}}
    @include('components.menu')


    {{-- PAGE CONTENT --}}

    <main>
        @yield('content')
    </main>


    {{-- FOOTER --}}

    @include('components.footer')


    {{-- PAGE-SPECIFIC JS --}}

    @stack('scripts')

</body>

</html>