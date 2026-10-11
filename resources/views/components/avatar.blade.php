@props(['user', 'size' => 'h-8 w-8 text-xs'])
@php
    $palette = ['bg-indigo-100 text-indigo-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-800', 'bg-rose-100 text-rose-700', 'bg-sky-100 text-sky-700', 'bg-violet-100 text-violet-700'];
@endphp
<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-full font-semibold', $size, $palette[$user->id % count($palette)]]) }} title="{{ $user->name }}">{{ $user->initials() }}</span>
