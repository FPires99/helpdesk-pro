@php
    $user = auth()->user();
    $field = 'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<x-app-layout :title="$ticket->reference()">
    <a href="{{ url()->previous() === url()->current() ? route('tickets.index') : url()->previous() }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm font-medium text-slate-500">{{ $ticket->reference() }}</span>
                <x-status-badge :status="$ticket->status" />
                <x-priority-badge :priority="$ticket->priority" />
                @if ($user->isStaff() && $ticket->isOverdue())
                    <span class="inline-flex items-center rounded-md bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Overdue</span>
                @endif
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ $ticket->subject }}</h1>
        </div>

        @can('update', $ticket)
            @if ($ticket->assignee_id !== $user->id && $ticket->status->isActive())
                <form method="POST" action="{{ route('tickets.assign-to-me', $ticket) }}">
                    @csrf
                    <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50">
                        <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/></svg>
                        Assign to me
                    </button>
                </form>
            @endif
        @endcan
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        {{-- Conversation --}}
        <div class="space-y-6 lg:col-span-2">
            <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$ticket->requester" class="h-9 w-9" />
                    <div class="text-sm">
                        <p class="font-semibold">{{ $ticket->requester->name }}</p>
                        <p class="text-slate-500">{{ $ticket->requester->department }} · <time datetime="{{ $ticket->created_at->toIso8601String() }}" title="{{ $ticket->created_at->format('j M Y, H:i') }}">{{ $ticket->created_at->diffForHumans() }}</time></p>
                    </div>
                </div>
                <p class="mt-4 whitespace-pre-line text-slate-700">{{ $ticket->description }}</p>
            </article>

            <section id="comments" class="space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Activity</h2>

                @forelse ($comments as $comment)
                    @php $fromStaff = $comment->author->isStaff(); @endphp
                    <div @class([
                        'flex gap-3 rounded-xl border p-4',
                        'border-amber-200 bg-amber-50' => $comment->is_internal,
                        'border-indigo-100 bg-indigo-50/40' => ! $comment->is_internal && $fromStaff,
                        'border-slate-200 bg-white' => ! $comment->is_internal && ! $fromStaff,
                    ])>
                        <x-avatar :user="$comment->author" />
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-x-2 text-sm">
                                <span class="font-semibold">{{ $comment->author->name }}</span>
                                @if ($fromStaff)
                                    <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-indigo-700">IT</span>
                                @endif
                                @if ($comment->is_internal)
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-200/70 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-amber-900">
                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/></svg>
                                        Internal note
                                    </span>
                                @endif
                                <time class="text-slate-500" datetime="{{ $comment->created_at->toIso8601String() }}" title="{{ $comment->created_at->format('j M Y, H:i') }}">{{ $comment->created_at->diffForHumans() }}</time>
                            </p>
                            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $comment->body }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No replies yet. The IT team will respond here.</p>
                @endforelse

                {{-- Reply box --}}
                @can('comment', $ticket)
                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" x-data="{ internal: {{ old('is_internal') ? 'true' : 'false' }} }"
                          class="rounded-xl border bg-white p-4 shadow-sm" :class="internal ? 'border-amber-300 ring-1 ring-amber-200' : 'border-slate-200'">
                        @csrf
                        <label for="body" class="sr-only">Reply</label>
                        <textarea id="body" name="body" rows="4" required maxlength="5000" class="block w-full resize-y rounded-lg border-0 p-0 text-sm focus:ring-0"
                                  :placeholder="internal ? 'Write an internal note (only IT can see this)…' : 'Write a reply…'">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-2" />
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                            @can('addInternalNote', $ticket)
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" name="is_internal" value="1" x-model="internal" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                                    Internal note
                                </label>
                            @else
                                <span class="text-xs text-slate-500">
                                    @if (! $ticket->status->isActive()) Replying will reopen this ticket. @endif
                                </span>
                            @endcan
                            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
                                    :class="internal ? 'bg-amber-500 hover:bg-amber-400' : 'bg-indigo-600 hover:bg-indigo-500'"
                                    x-text="internal ? 'Add note' : 'Send reply'">Send reply</button>
                        </div>
                    </form>
                @endcan
            </section>
        </div>

        {{-- Details sidebar --}}
        <aside class="space-y-6">
            @can('update', $ticket)
                <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Manage ticket</h2>

                    <div>
                        <label for="status" class="text-sm font-medium">Status</label>
                        <select id="status" name="status" class="{{ $field }}">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="priority" class="text-sm font-medium">Priority</label>
                        <select id="priority" name="priority" class="{{ $field }}">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="assignee_id" class="text-sm font-medium">Assignee</label>
                        <select id="assignee_id" name="assignee_id" class="{{ $field }}">
                            <option value="">Unassigned</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}" @selected($ticket->assignee_id === $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('assignee_id')" class="mt-2" />
                    </div>
                    <div>
                        <label for="category_id" class="text-sm font-medium">Category</label>
                        <select id="category_id" name="category_id" class="{{ $field }}">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($ticket->category_id === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save changes</button>
                </form>
            @endcan

            <dl class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white px-5 text-sm shadow-sm">
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-slate-500">Category</dt>
                    <dd class="font-medium">{{ $ticket->category->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-slate-500">Assignee</dt>
                    <dd class="font-medium">{{ $ticket->assignee?->name ?? 'Not assigned yet' }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-slate-500">Opened</dt>
                    <dd class="font-medium">{{ $ticket->created_at->format('j M Y, H:i') }}</dd>
                </div>
                @if ($ticket->status->isActive())
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate-500">Target</dt>
                        <dd @class(['font-medium', 'text-red-600' => $ticket->isOverdue()])>
                            {{ $ticket->dueAt()->format('j M, H:i') }}
                            <span class="block text-right text-xs font-normal text-slate-500">{{ $ticket->priority->slaHours() }}h for {{ strtolower($ticket->priority->label()) }} priority</span>
                        </dd>
                    </div>
                @elseif ($ticket->resolved_at)
                    <div class="flex justify-between gap-4 py-3">
                        <dt class="text-slate-500">Resolved</dt>
                        <dd class="text-right font-medium">
                            {{ $ticket->resolved_at->format('j M Y, H:i') }}
                            <span class="block text-xs font-normal text-slate-500">after {{ $ticket->created_at->diffForHumans($ticket->resolved_at, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }}</span>
                        </dd>
                    </div>
                @endif
            </dl>

            @can('delete', $ticket)
                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete {{ $ticket->reference() }}? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm font-medium text-red-600 hover:text-red-500">Delete ticket</button>
                </form>
            @endcan
        </aside>
    </div>
</x-app-layout>
