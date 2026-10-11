@props(['priority'])
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset whitespace-nowrap', $priority->badge()]) }}>
    @if ($priority === \App\Enums\TicketPriority::Urgent)
        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.5 3.5a1.5 1.5 0 0 1 3 0v7a1.5 1.5 0 0 1-3 0v-7ZM10 14.5a1.75 1.75 0 1 0 0 3.5 1.75 1.75 0 0 0 0-3.5Z" clip-rule="evenodd"/></svg>
    @endif
    {{ $priority->label() }}
</span>
