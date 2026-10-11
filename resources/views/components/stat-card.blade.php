@props(['label', 'value', 'hint' => null, 'tone' => 'slate', 'href' => null])
@php
    $tones = [
        'slate' => 'text-slate-900',
        'indigo' => 'text-indigo-600',
        'amber' => 'text-amber-600',
        'red' => 'text-red-600',
        'emerald' => 'text-emerald-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['block rounded-xl border border-slate-200 bg-white p-5 shadow-sm', 'transition hover:border-indigo-300 hover:shadow' => $href]) }}>
    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold tracking-tight {{ $tones[$tone] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</{{ $tag }}>
