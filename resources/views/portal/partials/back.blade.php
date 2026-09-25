{{-- Back link at the top of a customer page (default: My home). --}}
<a href="{{ route($backRoute ?? 'portal.dashboard') }}" class="group-back-link">
    <x-icon name="arrow-left" />
    <span data-i18n="{{ $backKey ?? 'menu_home' }}">{{ $backLabel ?? 'Home' }}</span>
</a>
