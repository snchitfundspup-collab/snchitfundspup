{{-- Site icons for every page's <head>: the Sri Lakshmi lotus, drawn for
     each size (favicon-16-sl.png, favicon-32-sl.png, *-sl files). Every
     business carries the Sri Lakshmi brand since Oct 2026. --}}

@php
    $sriLakshmiIcons = true;
    $iconSuffix = '-sl';
@endphp
@if ($sriLakshmiIcons)
    {{-- sharp images drawn for each tab size, so the browser does not shrink the big one (blurry) --}}
    <link rel="icon" href="{{ asset('favicon-32-sl.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon-16-sl.png') }}" type="image/png" sizes="16x16">
@endif
<link rel="icon" href="{{ asset('favicon'.$iconSuffix.'.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('icon-192'.$iconSuffix.'.png') }}" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon'.$iconSuffix.'.png') }}">
