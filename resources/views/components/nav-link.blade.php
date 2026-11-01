@props(['href', 'active' => false])
<a href="{{ $href }}"
   class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium transition
   {{ $active ? 'bg-neutral-900 text-white' : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900' }}">
    {{ $slot }}
</a>
