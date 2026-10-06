<footer class="page-footer">

    <a href="{{ route('portal.dashboard') }}" class="footer-brand brand-link" aria-label="{{ $portalBrand['title'] }} – go to My home">

        <div class="footer-logo logo-3d">
            <img src="{{ asset($portalBrand['logo']) }}" alt="">
        </div>

        <div>
            <div class="footer-name"><span>{{ $portalBrand['prefix'] }}</span> {{ $portalBrand['name'] }}</div>
            <div class="footer-tagline" data-i18n="{{ $portalBrand['tagline_key'] }}">{{ $portalBrand['tagline'] }}</div>
        </div>

    </a>

    <div class="footer-right">
        <span class="footer-secure">
            <span class="footer-dot">•</span>
            <span data-i18n="footer_secure">Secure</span>
        </span>
        <span>•</span>
        <span data-i18n="footer_reliable">Reliable</span>
        <span>•</span>
        <span data-i18n="footer_always">Always With You</span>
    </div>

</footer>
