@props(['attachments'])
@if ($attachments->isNotEmpty())
    <div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
        @foreach ($attachments as $file)
            @if ($file->isImage())
                <a href="{{ route('attachments.show', $file) }}" target="_blank" class="group block overflow-hidden rounded-lg border border-slate-200 bg-white" title="{{ $file->original_name }}">
                    <img src="{{ route('attachments.show', $file) }}" alt="{{ $file->original_name }}" loading="lazy" class="h-28 w-48 bg-slate-50 object-contain transition group-hover:opacity-90">
                </a>
            @else
                <a href="{{ route('attachments.show', $file) }}" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:border-indigo-300 hover:bg-indigo-50/40">
                    <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    <span class="max-w-[12rem] truncate font-medium">{{ $file->original_name }}</span>
                    <span class="text-xs text-slate-400">{{ $file->humanSize() }}</span>
                </a>
            @endif
        @endforeach
    </div>
@endif
