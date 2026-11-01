@extends('layouts.app')

@section('title', 'Page content')
@section('heading', 'Page content')

@section('content')
    {{-- Page tabs --}}
    <div class="mb-6 flex flex-wrap items-center gap-2">
        @foreach (['work' => 'Work', 'agency' => 'Agency', 'careers' => 'Careers'] as $key => $label)
            <a href="{{ route('admin.pages.index', ['page' => $key]) }}"
               class="rounded-lg px-3 py-2 text-sm font-medium transition sm:px-4
               {{ $page === $key ? 'bg-neutral-900 text-white' : 'border border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-50' }}">
                {{ $label }}
                <span class="ml-1 text-xs opacity-60">({{ $counts[$key] ?? 0 }})</span>
            </a>
        @endforeach
        <a href="{{ route('pages.'.$page) }}" target="_blank"
           class="hidden rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50 sm:ml-auto sm:inline-block">
            View page &rarr;
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Existing sections --}}
        <div class="min-w-0 space-y-4 lg:col-span-2">
            @forelse ($sections as $section)
                <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5">
                    <form method="POST" action="{{ route('admin.pages.update', $section) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="page" value="{{ $page }}">

                        <div class="flex flex-col gap-4 sm:flex-row sm:gap-5">
                            <div class="w-full sm:w-32 sm:shrink-0">
                                @if ($section->image_path)
                                    <img src="{{ asset('storage/'.$section->image_path) }}"
                                         class="h-36 w-full rounded-lg object-cover sm:h-24 sm:w-32" alt="">
                                @else
                                    <div class="flex h-36 w-full items-center justify-center rounded-lg bg-neutral-100 text-xs text-neutral-400 sm:h-24 sm:w-32">
                                        No image
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <input name="title" value="{{ $section->title }}" placeholder="Title" required
                                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium">
                                    <input name="subtitle" value="{{ $section->subtitle }}" placeholder="Subtitle / tag"
                                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                                </div>
                                <textarea name="content" rows="3" placeholder="Description"
                                          class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ $section->content }}</textarea>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <input name="video_url" value="{{ $section->video_url }}" placeholder="Video URL (YouTube/Vimeo/MP4)"
                                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                                    <input name="image" type="file" accept="image/*"
                                           class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-xs">
                                </div>
                                <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                                    <input name="sort_order" type="number" value="{{ $section->sort_order }}"
                                           class="w-20 rounded-lg border border-neutral-300 px-2 py-1 text-sm" title="Sort order">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($section->is_active) class="rounded border-neutral-300">
                                        Active
                                    </label>
                                    <button class="ml-auto rounded-lg bg-neutral-900 px-4 py-1.5 text-xs font-semibold text-white hover:bg-neutral-700">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="mt-3 flex justify-end border-t border-neutral-100 pt-3">
                        <form method="POST" action="{{ route('admin.pages.destroy', $section) }}"
                              onsubmit="return confirm('Delete this section?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs font-medium text-red-500 hover:text-red-700">Delete section</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-neutral-200 bg-white p-10 text-center text-sm text-neutral-400">
                    No sections on this page yet — add one below.
                </div>
            @endforelse
        </div>

        {{-- Create section --}}
        <div class="min-w-0">
            <form method="POST" action="{{ route('admin.pages.store') }}" enctype="multipart/form-data"
                  class="rounded-xl border border-neutral-200 bg-white p-5 lg:sticky lg:top-24">
                @csrf
                <h3 class="mb-1 text-sm font-semibold">New section — {{ ucfirst($page) }}</h3>
                <p class="mb-4 text-xs text-neutral-500">
                    Har section mein title, subtitle, description, image aur video — jo chaho daal do.
                </p>
                <input type="hidden" name="page" value="{{ $page }}">
                <div class="space-y-3">
                    <input name="title" placeholder="Title (e.g. Meer Official)" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <input name="subtitle" placeholder="Subtitle / tag (e.g. E-commerce)" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <textarea name="content" rows="3" placeholder="Description…" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"></textarea>
                    <input name="video_url" placeholder="Video URL (optional)" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <input name="image" type="file" accept="image/*" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-xs">
                    <input type="hidden" name="is_active" value="1">
                    <button class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">Add section</button>
                </div>
            </form>
        </div>
    </div>
@endsection
