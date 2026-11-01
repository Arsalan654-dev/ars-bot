<!doctype html>
<html class="has-no-js is-first-loading" lang="en" data-theme="lisa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Cyprus Digital Solutions — innovative digital solutions for your brand.')">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}?v=5">

    <script>
        document.documentElement.classList.remove('has-no-js');
    </script>
</head>

<body>
{{-- ==================== PRELOADER ==================== --}}
<div class="c-preloader" aria-hidden="true" id="preloader">
    <div class="c-preloader_outer">
        <div class="c-preloader_inner">
            <div class="c-preloader_content">
                <div></div>
                <div></div>
            </div>
            <div class="c-preloader_logo">
                <div class="c-preloader_wordmark">
                    <img src="{{ asset('assets/images/logo.png') }}?v=3" alt="" class="c-preloader_logo_img">
                    {{ config('app.name') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ==================== HEADER ==================== --}}
<div class="c-header_bg"></div>

<header>
    <div class="c-header" data-header="header">
        <a href="{{ route('home') }}" class="c-header_logo" aria-label="{{ config('app.name') }} — home">
            <img src="{{ asset('assets/images/logo.png') }}?v=3" alt="" class="c-header_logo_img">
            <span class="c-header_logo_inner" data-hover-shuffle>{{ config('app.name') }}</span>
        </a>

        <span aria-hidden="true" class="c-header_icon"></span>

        <nav class="c-header_menu" aria-label="Main">
            <ul class="c-header-menu_list">
                <li class="c-header-menu_item"><a href="{{ route('pages.work') }}" class="c-header-menu_link {{ request()->routeIs('pages.work') ? '-current' : '' }}" data-hover-shuffle>Work</a></li>
                <li class="c-header-menu_item"><a href="{{ route('pages.agency') }}" class="c-header-menu_link {{ request()->routeIs('pages.agency') ? '-current' : '' }}" data-hover-shuffle>Agency</a></li>
                <li class="c-header-menu_item"><a href="{{ route('pages.careers') }}" class="c-header-menu_link {{ request()->routeIs('pages.careers') ? '-current' : '' }}" data-hover-shuffle>Careers</a></li>
            </ul>
        </nav>

        <a href="{{ route('home') }}" class="c-header_cta" data-hover-shuffle>Let&rsquo;s talk</a>

        <button class="c-header_menu-toggler" data-header-open-label="Menu" data-header-close-label="Close"
                aria-expanded="false" aria-controls="mobile-menu" aria-label="Navigation mobile">
            Menu
        </button>
    </div>

    <nav class="c-menu" id="mobile-menu" aria-label="Mobile">
        <div class="c-menu_inner">
            <ul class="c-menu_nav">
                <li class="c-menu_nav_item"><a href="{{ route('pages.work') }}" class="c-menu_nav_link">Work</a></li>
                <li class="c-menu_nav_item"><a href="{{ route('pages.agency') }}" class="c-menu_nav_link">Agency</a></li>
                <li class="c-menu_nav_item"><a href="{{ route('pages.careers') }}" class="c-menu_nav_link">Careers</a></li>
                <li class="c-menu_nav_item"><a href="{{ route('home') }}" class="c-menu_nav_link">Let&rsquo;s talk</a></li>
            </ul>
            <div class="c-menu_footer">
                <a href="#" class="c-menu_langswitcher">Fran&ccedil;ais</a>
                <span class="c-menu_location">
                    <span class="u-screen-reader-text">Cyprus</span>
                    <span aria-hidden="true">&#127464;&#127486;</span>
                </span>
            </div>
        </div>
    </nav>
</header>

@yield('content')

{{-- ==================== FOOTER ==================== --}}
<footer class="c-page-footer">
    <h2 class="c-page-footer__heading u-display js-reveal">
        Let&rsquo;s make something<br>
        great together.
    </h2>

    <div class="c-page-footer__meta js-reveal">
        <div>
            <span class="c-page-footer__label">Email</span>
            <a href="mailto:cyprussolutionss@gmail.com">cyprussolutionss@gmail.com</a>
        </div>
        <div>
            <span class="c-page-footer__label">Phone</span>
            <a href="tel:+923303914554">+92 330 3914554</a>
        </div>
        <div>
            <span class="c-page-footer__label">Social</span>
            <span class="c-page-footer__socials">
                <a href="https://www.linkedin.com/company/cyprus-digital-solutions/" target="_blank" rel="noopener">LinkedIn</a>
                <a href="https://www.facebook.com/share/1GU6q9ASxn/" target="_blank" rel="noopener">Facebook</a>
                <a href="https://www.instagram.com/cyprusdigitalsolutions" target="_blank" rel="noopener">Instagram</a>
            </span>
        </div>
    </div>

    <div class="c-page-footer__bottom">
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
        <a href="{{ route('home') }}" class="c-page-footer__cta" data-cursor="hover">
            Talk to {{ $bot?->bot_name ?? 'L.I.S.A' }} &rarr;
        </a>
    </div>
</footer>

<button class="c-backtop" aria-label="Back to top">&uarr;</button>

<script src="{{ asset('assets/js/app.js') }}"></script>
<script src="{{ asset('assets/js/pages.js') }}"></script>
</body>
</html>
