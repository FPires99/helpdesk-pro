<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-slate-900 antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-full">
            {{-- Mobile backdrop --}}
            <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false" x-cloak></div>

            {{-- Sidebar --}}
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
                <a href="{{ route('dashboard') }}" class="flex h-16 items-center gap-2.5 px-6">
                    <x-application-logo class="h-8 w-8" />
                    <span class="text-lg font-bold tracking-tight">HelpDesk<span class="text-indigo-600"> Pro</span></span>
                </a>

                <nav class="flex-1 space-y-1 px-3 py-4">
                    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg></x-slot:icon>
                        Dashboard
                    </x-sidebar-link>

                    @if (auth()->user()->isStaff())
                        <x-sidebar-link :href="route('tickets.index', ['status' => 'active'])" :active="request()->routeIs('tickets.index') && ! request('view')" :count="$navCounts['active']">
                            <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg></x-slot:icon>
                            All tickets
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('tickets.index', ['view' => 'mine', 'status' => 'active'])" :active="request('view') === 'mine'" :count="$navCounts['mine']">
                            <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg></x-slot:icon>
                            My queue
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('tickets.index', ['view' => 'unassigned'])" :active="request('view') === 'unassigned'" :count="$navCounts['unassigned']">
                            <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/></svg></x-slot:icon>
                            Unassigned
                        </x-sidebar-link>
                    @else
                        <x-sidebar-link :href="route('tickets.index')" :active="request()->routeIs('tickets.index')" :count="$navCounts['active']">
                            <x-slot:icon><svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg></x-slot:icon>
                            My tickets
                        </x-sidebar-link>
                    @endif

                    <div class="pt-4">
                        <a href="{{ route('tickets.create') }}" class="flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            New ticket
                        </a>
                    </div>
                </nav>

                {{-- Current user --}}
                <div x-data="{ open: false }" class="relative border-t border-slate-200 p-3">
                    <button @click="open = !open" class="flex w-full items-center gap-3 rounded-lg p-2 text-left hover:bg-slate-100">
                        <x-avatar :user="auth()->user()" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ auth()->user()->role->label() }} · {{ auth()->user()->department }}</span>
                        </span>
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .55.24l3.25 3.5a.75.75 0 1 1-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 0 1-1.1-1.02l3.25-3.5A.75.75 0 0 1 10 3Zm-3.76 9.2a.75.75 0 0 1 1.06.04l2.7 2.908 2.7-2.908a.75.75 0 1 1 1.1 1.02l-3.25 3.5a.75.75 0 0 1-1.1 0l-3.25-3.5a.75.75 0 0 1 .04-1.06Z" clip-rule="evenodd"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition x-cloak class="absolute bottom-full left-3 right-3 mb-2 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Log out</button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="lg:pl-64">
                {{-- Mobile top bar --}}
                <div class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:hidden">
                    <button @click="sidebarOpen = true" class="-ml-1 rounded-md p-1.5 text-slate-600 hover:bg-slate-100" aria-label="Open menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <x-application-logo class="h-7 w-7" />
                    <span class="font-bold">HelpDesk<span class="text-indigo-600"> Pro</span></span>
                </div>

                @isset($header)
                    <header class="border-b border-slate-200 bg-white">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    @if (session('status') && ! in_array(session('status'), ['profile-updated', 'password-updated']))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
                             class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            <svg class="h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
