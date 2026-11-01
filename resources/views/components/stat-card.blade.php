@props(['label' => null])
<div class="rounded-xl border border-neutral-200 bg-white p-5">
    @if ($label)
        <div class="mb-1 text-xs font-medium uppercase tracking-wide text-neutral-500">{{ $label }}</div>
    @endif
    <div class="text-2xl font-semibold">{{ $slot }}</div>
</div>
