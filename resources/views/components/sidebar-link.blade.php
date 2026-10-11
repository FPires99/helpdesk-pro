@props(['href', 'active' => false, 'count' => null])
<a href="{{ $href }}" @class([
    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-indigo-50 text-indigo-700' => $active,
    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
])>
    <span @class(['h-5 w-5 shrink-0', 'text-indigo-600' => $active, 'text-slate-400 group-hover:text-slate-500' => ! $active])>{{ $icon }}</span>
    <span class="flex-1">{{ $slot }}</span>
    @if ($count)
        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $count }}</span>
    @endif
</a>
