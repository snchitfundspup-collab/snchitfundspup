<footer class="page-footer">

    <a
        href="{{ route($business['home'] ?? 'dashboard') }}"
        class="footer-brand brand-link"
        aria-label="{{ $business['full_name'] ?? 'SN Chit Funds' }} – go to Home"
    >

        <div class="footer-logo logo-3d">
            <img
                src="{{ asset('images/sn-chit-funds-logo.png') }}"
                alt=""
            >
        </div>

        <div>

            <div class="footer-name">
                <span>SN</span> {{ $business['name'] ?? 'Chit Funds' }}
            </div>

            <div class="footer-tagline" data-i18n="{{ $business['tagline_key'] ?? 'brand_tagline' }}">
                {{ ($business['key'] ?? 'chit') === 'traders' ? 'Quality Rice · Fair Price' : 'Trust · Growth · Together' }}
            </div>

        </div>

    </a>


    <div class="footer-right">

        <span class="footer-secure">
            <span class="footer-dot">•</span>
            <span data-i18n="footer_secure">Secure</span>
        </span>

        <span>•</span>

        <span data-i18n="footer_reliable">
            Reliable
        </span>

        <span>•</span>

        <span data-i18n="footer_always">
            Always With You
        </span>

    </div>

</footer>