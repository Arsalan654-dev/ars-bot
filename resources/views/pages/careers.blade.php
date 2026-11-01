@extends('layouts.site')

@section('title', 'Careers')
@section('description', 'Join the Cyprus Digital Solutions team — creative innovators wanted.')

@section('content')
<main class="c-page">
    <section class="c-page-hero">
        <div class="c-page-hero__bg"></div>
        <div>
            <span class="c-page-hero__label">Careers</span>
            <h1 class="c-page-hero__heading u-display">Careers</h1>
            <p class="c-page-hero__lead">
                Join our creative community to collaborate, innovate, and thrive together.
                We welcome passionate individuals eager to make an impact.
            </p>
            <div class="c-page-hero__meta">
                <span>Remote &middot; Hybrid</span>
                <span>Full-time &middot; Freelance</span>
            </div>
        </div>
    </section>

    {{-- Marquee band --}}
    <div class="c-marquee" aria-hidden="true">
        <div class="c-marquee__track">
            @for ($i = 0; $i < 2; $i++)
                <span class="c-marquee__item">
                    <span>Designers</span><span>&bull;</span>
                    <span>Developers</span><span>&bull;</span>
                    <span>Marketers</span><span>&bull;</span>
                    <span>Strategists</span><span>&bull;</span>
                    <span>Creators</span><span>&bull;</span>
                </span>
            @endfor
        </div>
    </div>

    {{-- Dynamic sections --}}
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
                <p>No open roles published yet. Add sections from the admin panel.</p>
            </div>
        @endforelse
    </section>

    <section class="c-section">
        <span class="c-section__head js-reveal">Why here</span>
        <h2 class="c-section__title js-reveal">
            Grow loud. Ego<br>
            stays at the door.
        </h2>

        <div class="c-cap-grid">
            <div class="c-cap-card js-reveal">
                <h3>Real ownership</h3>
                <p>Your name on the work. Your ideas in the room. No busywork, no shadow projects.</p>
            </div>
            <div class="c-cap-card js-reveal">
                <h3>Learning culture</h3>
                <p>Courses, tools, experiments — if it sharpens you, we support it.</p>
            </div>
            <div class="c-cap-card js-reveal">
                <h3>Life beyond pixels</h3>
                <p>Team retreats, flexible hours, and friendships that outlast deadlines.</p>
            </div>
        </div>
    </section>
</main>
@endsection
