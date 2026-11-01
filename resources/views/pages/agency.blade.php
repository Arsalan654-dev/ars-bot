@extends('layouts.site')

@section('title', 'Agency')
@section('description', 'About Cyprus Digital Solutions — a creative digital agency crafting impactful digital experiences.')

@section('content')
<main class="c-page">
    <section class="c-page-hero">
        <div class="c-page-hero__bg"></div>
        <div>
            <span class="c-page-hero__label">The Agency</span>
            <h1 class="c-page-hero__heading u-display">Agency</h1>
            <p class="c-page-hero__lead">
                At our Creative Digital Agency, we bring your ideas to life by crafting
                engaging, impactful digital experiences that captivate audiences and
                drive results.
            </p>
            <div class="c-page-hero__meta">
                <span>Creative Digital Agency</span>
                <span>Cyprus &middot; Worldwide</span>
            </div>
        </div>
    </section>

    {{-- Marquee band --}}
    <div class="c-marquee" aria-hidden="true">
        <div class="c-marquee__track">
            @for ($i = 0; $i < 2; $i++)
                <span class="c-marquee__item">
                    <span>Visual Identity</span><span>&bull;</span>
                    <span>Social Growth</span><span>&bull;</span>
                    <span>Digital Excellence</span><span>&bull;</span>
                    <span>Art &amp; Design</span><span>&bull;</span>
                    <span>Digital World</span><span>&bull;</span>
                </span>
            @endfor
        </div>
    </div>

    {{-- Dynamic sections: title, subtitle, text, image, video --}}
    <section class="c-section c-section--blocks">
        @forelse ($sections as $index => $section)
            <article class="c-page-block js-reveal {{ $index % 2 === 1 ? '-reverse' : '' }}">
                @if ($section->image_path || $section->video_url)
                    <div class="c-page-block__media">
                        @if ($section->video_url && ! $section->image_path)
                            @if (str_contains($section->video_url, '.mp4') || str_contains($section->video_url, '.webm'))
                                <video src="{{ $section->video_url }}" controls playsinline preload="metadata"></video>
                            @else
                                <iframe src="{{ $section->video_url }}" loading="lazy" allowfullscreen title="{{ $section->title }}"></iframe>
                            @endif
                        @else
                            <img src="{{ asset('storage/'.$section->image_path) }}" alt="{{ $section->title }}" loading="lazy">
                        @endif
                    </div>
                @endif

                <div class="c-page-block__body">
                    @if ($section->subtitle)
                        <span class="c-section__head">{{ $section->subtitle }}</span>
                    @endif
                    <h2 class="c-section__title">{{ $section->title }}</h2>
                    @if ($section->content)
                        <p class="c-section__body">{!! nl2br(e($section->content)) !!}</p>
                    @endif

                    @if ($section->video_url && $section->image_path)
                        <a href="{{ $section->video_url }}" target="_blank" rel="noopener" class="c-page-block__video-link">
                            Watch the video &rarr;
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="c-page-empty js-reveal">
                <p>No agency content published yet. Add sections from the admin panel.</p>
            </div>
        @endforelse
    </section>

    {{-- Why choose us strip --}}
    <section class="c-section">
        <span class="c-section__head js-reveal">Why choose us</span>
        <h2 class="c-section__title js-reveal">
            Expertise for your<br>
            digital growth journey.
        </h2>

        <div class="c-cap-grid">
            <div class="c-cap-card js-reveal">
                <h3>Data-driven approach</h3>
                <p>We leverage data and insights to make informed decisions that lead to more effective and efficient solutions.</p>
            </div>
            <div class="c-cap-card js-reveal">
                <h3>Competitive pricing</h3>
                <p>We offer our top-quality services at competitive prices, providing you with great value for your investment.</p>
            </div>
            <div class="c-cap-card js-reveal">
                <h3>Ethical business practices</h3>
                <p>We maintain the highest level of professionalism and ethical standards in all our business dealings.</p>
            </div>
        </div>
    </section>
</main>
@endsection
