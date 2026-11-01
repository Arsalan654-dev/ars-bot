@props(['status' => 'new'])
@php
    $classes = match ($status) {
        'won' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'contacted' => 'bg-amber-50 text-amber-700 border-amber-200',
        'lost' => 'bg-neutral-100 text-neutral-500 border-neutral-200',
        default => 'bg-blue-50 text-blue-700 border-blue-200',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium capitalize $classes"]) }}>
    {{ $status }}
</span>
