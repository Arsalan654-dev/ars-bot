@extends('layouts.site')

@section('title', 'Work')
@section('description', 'Selected projects by Cyprus Digital Solutions — branding, web and digital experiences.')

@section('content')
<main class="c-page">
    <section class="c-page-hero">
        <div class="c-page-hero__bg"></div>
        <div>
            <span class="c-page-hero__label">Selected Work</span>
            <h1 class="c-page-hero__heading u-display">Work</h1>
            <p class="c-page-hero__lead">
                Brands with cutting-edge digital solutions &amp; design. Empowering brands
                through innovative digital strategies, immersive design, and tailored
                solutions that drive growth and engagement.
            </p>
            <div class="c-page-hero__meta">
                <span>{{ $sections->count() }} projects</span>
                <span>Branding &middot; Web &middot; Marketing</span>
            </div>
        </div>
    </section>

    {{-- Marquee band --}}
    <div class="c-marquee" aria-hidden="true">
        <div class="c-marquee__track">
            @for ($i = 0; $i < 2; $i++)
                <span class="c-marquee__item">
                    <span>Custom Branding</span><span>&bull;</span>
                    <span>Website Design</span><span>&bull;</span>
                    <span>Digital Marketing</span><span>&bull;</span>
                    <span>Strategy Consulting</span><span>&bull;</span>
                    <span>Analytics &amp; Reporting</span><span>&bull;</span>
                </span>
            @endfor
        </div>
    </div>

    <section class="c-section">
        <span class="c-section__head js-reveal">Portfolio</span>
        <h2 class="c-section__title js-reveal">
            Projects that went live<br>
            and made an impact.
        </h2>

        <div class="c-project-grid">
            @forelse ($sections as $section)
                <article class="c-project-card js-reveal" data-cursor="hover">
                    @if ($section->image_path)
                        <img src="{{ asset('storage/'.$section->image_path) }}"
                             alt="{{ $section->title }}"
                             class="c-project-card__image" loading="lazy">
                    @else
                        <div class="c-project-card__bg"></div>
                    @endif

                    @if ($section->subtitle)
                        <span class="c-project-card__tag">{{ $section->subtitle }}</span>
                    @endif
                    <span class="c-project-card__label">{{ $section->title }}</span>

                    @if ($section->content)
                        <div class="c-project-card__overlay">
                            <p>{{ $section->content }}</p>
                        </div>
                    @endif

                    @if ($section->video_url)
                        <a href="{{ $section->video_url }}" target="_blank" rel="noopener"
                           class="c-project-card__video" aria-label="Watch video">&#9658;</a>
                    @endif
                </article>
            @empty
                <div class="c-page-empty js-reveal">
                    <p>No projects published yet. Add them from the admin panel.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="c-section">
        <span class="c-section__head js-reveal">How we work</span>
        <h2 class="c-section__title js-reveal">
            Strategy first.<br>
            Craft always.
        </h2>
        <p class="c-section__body js-reveal">
            Every engagement starts with understanding your brand, your audience and your goals.
            We prototype early, iterate fast, and polish until it feels inevitable — then we ship
            something you are proud to put your name on.
        </p>
    </section>
</main>
@endsection
