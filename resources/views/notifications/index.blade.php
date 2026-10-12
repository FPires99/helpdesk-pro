<x-app-layout title="Notifications">
    <div class="mx-auto max-w-3xl">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-2xl font-bold tracking-tight">Notifications</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Mark all as read</button>
                </form>
            @endif
        </div>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @forelse ($notifications as $notification)
                <a href="{{ route('notifications.open', $notification->id) }}" @class([
                    'flex items-start gap-3 border-b border-slate-100 px-5 py-4 transition last:border-0 hover:bg-slate-50',
                    'bg-indigo-50/40' => $notification->unread(),
                ])>
                    <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-indigo-500' => $notification->unread(), 'bg-transparent' => $notification->read()])></span>
                    <div class="min-w-0 flex-1">
                        <p @class(['text-sm', 'font-semibold' => $notification->unread()])>{{ $notification->data['message'] }}</p>
                        <p class="mt-0.5 truncate text-sm text-slate-500">
                            <span class="font-mono text-xs">{{ $notification->data['reference'] }}</span> · {{ $notification->data['subject'] }}
                        </p>
                    </div>
                    <time class="shrink-0 text-xs text-slate-400" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                </a>
            @empty
                <div class="px-6 py-16 text-center">
                    <p class="font-semibold">You're all caught up</p>
                    <p class="mt-1 text-sm text-slate-500">Updates about your tickets will appear here and in your email.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-app-layout>
