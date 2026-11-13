@props(['href', 'active' => false])
<a href="{{ $href }}"
   class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium transition
   {{ $active ? 'bg-[#22C55E] text-[#0F172A]' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
    {{ $slot }}
</a>
