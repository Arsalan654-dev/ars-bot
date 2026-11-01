<!doctype html>
<html class="has-no-js {{ $embedded ? '' : 'is-first-loading' }}" lang="en" data-theme="{{ $bot->bot_theme === 'dark' ? 'dark' : 'lisa' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $bot->bot_name }} — {{ $bot->company ?: $bot->name }}</title>
    <meta name="description" content="Chat with {{ $bot->bot_name }}, the AI assistant for {{ $bot->company ?: $bot->name }}.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}?v=5">

    <style>
        :root {
            {{ $bot->bot_color ? '--bot-accent: '.e($bot->bot_color).';' : '' }}
        }
        .c-live_send, .c-live_msg.-user .c-live_msg_bubble { background-color: {{ e($bot->bot_color ?: '#000000') }}; }
        .c-live_avatar { background-color: {{ e($bot->bot_color ?: '#000000') }}; }
    </style>

    <script>
        document.documentElement.classList.remove('has-no-js');
        window.LISA_CONFIG = {
            apiKey: @json($bot->api_key),
            chatUrl: @json(url('/api/bot/chat')),
            name: @json($bot->bot_name),
            color: @json($bot->bot_color),
            embedded: @json($embedded),
            welcome: @json($serving ? ($welcome ?? null) : null),
        };
    </script>
</head>

<body class="{{ $embedded ? 'is-embedded' : 'is-bot-page' }}">

@if (! $embedded)
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
                        {{ $bot->company ?: config('app.name') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== HEADER (minimal: logo + Let's talk) ==================== --}}
    <div class="c-header_bg"></div>

    <header>
        <div class="c-header -bot">
            <a href="{{ route('home') }}" class="c-header_logo" aria-label="Home">
                <img src="{{ asset('assets/images/logo.png') }}?v=3" alt="" class="c-header_logo_img">
                <span class="c-header_logo_inner" data-hover-shuffle>{{ $bot->company ?: config('app.name') }}</span>
            </a>

            <a href="#chat" class="c-header_cta" data-hover-shuffle>Let&rsquo;s talk</a>
        </div>
    </header>
@endif

{{-- ==================== VISUALIZER ==================== --}}
<div class="c-lisa_visualizer">
    <canvas id="visualizer-canvas"></canvas>
</div>

{{-- ==================== BOT PAGE (bot on top, chatbox below) ==================== --}}
@if (! $embedded)
    <div class="c-bot-page">
        {{-- ==================== BOT AVATAR (original CSS 3D character) ==================== --}}
        <section class="c-bot-page_hero">
            <div class="c-bot" aria-hidden="true">
                <div class="c-bot_holder">
                    <div class="c-bot_stage">
                        <div class="c-bot_glow"></div>
                        <div class="c-bot_ring"></div>
                        <div class="c-bot_shadow"></div>
                        <div class="c-bot_frame">
                            <div class="c-bot_character" role="img" aria-label="{{ $bot->bot_name }} avatar">
                                <div class="c-bot_head">
                                    <span class="c-bot_antenna"></span>
                                    <span class="c-bot_ear -left"></span>
                                    <span class="c-bot_ear -right"></span>
                                    <div class="c-bot_face">
                                        <span class="c-bot_eye -left"></span>
                                        <span class="c-bot_eye -right"></span>
                                        <span class="c-bot_mouth"></span>
                                    </div>
                                    <span class="c-bot_button"></span>
                                </div>
                                <div class="c-bot_body">
                                    <span class="c-bot_core"></span>
                                    <span class="c-bot_badge">AI</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
@endif

{{-- ==================== CHAT ==================== --}}
<main class="c-live {{ $embedded ? '-embedded' : '-page' }}" id="chat">
    <div class="c-live_head">
        <div class="c-live_avatar">
            @if ($bot->avatar_path)
                <img src="{{ asset('storage/'.$bot->avatar_path) }}" alt="{{ $bot->bot_name }}">
            @else
                {{ strtoupper(mb_substr($bot->bot_name, 0, 1)) }}
            @endif
            <span class="c-live_status {{ $serving ? '' : '-off' }}"></span>
        </div>
        <div>
            <div class="c-live_name">{{ $bot->bot_name }}</div>
            <div class="c-live_sub">
                {{ $serving ? 'Online — typically replies instantly' : 'Currently offline' }}
            </div>
        </div>
    </div>

    @if ($serving)
        <div class="c-live_feed"></div>

        <div class="c-live_chips">
            <button class="c-live_chip" type="button">What do you offer?</button>
            <button class="c-live_chip" type="button">Pricing?</button>
            <button class="c-live_chip" type="button">I want to order</button>
        </div>

        <form class="c-live_input">
            <input type="text" placeholder="Type your message…" aria-label="Message" autocomplete="off">
            <button class="c-live_send" type="submit" aria-label="Send">
                <svg viewBox="0 0 24 24"><path d="M3 11.5 21 3l-8.5 18-2.2-7.3L3 11.5z"/></svg>
            </button>
        </form>
    @else
        <div class="c-live_paused">
            <h2>Bot is offline</h2>
            <p>This assistant is currently unavailable. Please check back later or contact the team directly.</p>
        </div>
    @endif
</main>

@if (! $embedded)
        {{-- ==================== FOOTER (links moved out of the navbar) ==================== --}}
        <footer class="c-bot-page_footer">
            <ul aria-label="Site">
                <li><a href="{{ route('pages.work') }}" data-hover-shuffle>Work</a></li>
                <li><a href="{{ route('pages.agency') }}" data-hover-shuffle>Agency</a></li>
                <li><a href="{{ route('pages.careers') }}" data-hover-shuffle>Careers</a></li>
            </ul>

            <span class="c-bot-page_copy">&copy; {{ date('Y') }} {{ $bot->company ?: config('app.name') }}</span>
        </footer>
    </div>
@endif

{{-- ==================== SOUND TOGGLE ==================== --}}
<button class="c-lisa_sound -muted" type="button" aria-label="Toggle sound">
    <span class="u-screen-reader-text">Toggle sound</span>
    <div class="c-lisa_sound-icon -on" aria-hidden="true">
        <svg role="img" viewBox="0 0 43.77 35.29">
            <path d="M35.16,27.83a2.25,2.25,0,0,1-.48-4.45,5.87,5.87,0,0,0-.09-11.47,2.25,2.25,0,0,1,.9-4.41,10.37,10.37,0,0,1,.16,20.28A2.81,2.81,0,0,1,35.16,27.83Z"/>
            <polygon points="23.17 35.29 23.17 0 9.27 10.8 0 10.8 0 24.49 9.27 24.49 23.17 35.29"/>
        </svg>
    </div>
    <div class="c-lisa_sound-icon -off" aria-hidden="true">
        <svg role="img" viewBox="0 0 49.4 35.29">
            <path d="M33.9,26.38A2,2,0,0,1,32.48,23L46,9.46a2,2,0,0,1,2.83,2.83l-13.5,13.5A2,2,0,0,1,33.9,26.38Z"/>
            <path d="M47.4,26.38A2,2,0,0,1,46,25.79l-13.5-13.5a2,2,0,1,1,2.83-2.83L48.81,23a2,2,0,0,1-1.41,3.42Z"/>
            <polygon points="23.17 35.29 23.17 0 9.27 10.8 0 10.8 0 24.49 9.27 24.49 23.17 35.29"/>
        </svg>
    </div>
</button>

{{-- ==================== ABOUT PANEL (agency content) ==================== --}}
@if (! $embedded && $agencySections->isNotEmpty())
    <button class="c-live_about-toggle" type="button">About us</button>

    <div class="c-live_about" role="dialog" aria-label="About">
        <div class="c-live_about_panel">
            <button class="c-live_about_close" type="button" aria-label="Close">&#10005;</button>
            <h2>About {{ $bot->company ?: $bot->name }}</h2>

            @foreach ($agencySections as $section)
                <div class="c-live_about_section">
                    <h3>{{ $section->title }}</h3>
                    @if ($section->content)
                        <p>{!! nl2br(e($section->content)) !!}</p>
                    @endif
                    @if ($section->image_path)
                        <img src="{{ asset('storage/'.$section->image_path) }}" alt="{{ $section->title }}" loading="lazy">
                    @endif
                    @if ($section->video_url && ! str_contains($section->video_url, '.mp4'))
                        <iframe src="{{ $section->video_url }}" allowfullscreen loading="lazy" title="{{ $section->title }}"></iframe>
                    @endif
                </div>
            @endforeach

            <a href="{{ route('pages.agency') }}" class="c-live_about_more">Read the full story &rarr;</a>
        </div>
    </div>
@endif

{{-- ==================== SCRIPTS ==================== --}}
<script src="{{ asset('assets/js/lisa-live.js') }}?v=4"></script>

@if (! $embedded)
    <script src="{{ asset('assets/js/bot3d.js') }}?v=4"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
@else
    <script>
        // Embedded mode: skip preloader/header chrome, boot immediately
        document.addEventListener('DOMContentLoaded', function () {
            document.dispatchEvent(new CustomEvent('lisa:boot'));
        });
    </script>
@endif
</body>
</html>
