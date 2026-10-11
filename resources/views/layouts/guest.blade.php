<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-slate-900 antialiased">
        <div class="flex min-h-full">
            {{-- Brand panel --}}
            <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-slate-900 p-12 text-white lg:flex">
                <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-indigo-600/30 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl"></div>

                <div class="relative flex items-center gap-3">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-xl font-bold">HelpDesk<span class="text-indigo-400"> Pro</span></span>
                </div>

                <div class="relative max-w-md">
                    <h2 class="text-4xl font-bold leading-tight tracking-tight">IT support, without the chaos.</h2>
                    <p class="mt-4 text-lg text-slate-300">Raise, track and resolve IT tickets in one place, with priorities, target times and a clear view of the queue.</p>
                    <ul class="mt-8 space-y-3 text-slate-300">
                        @foreach (['Role-based access for employees, technicians and admins', 'Priority-based target times with overdue alerts', 'Internal notes the requester never sees', 'Live dashboard of the support queue'] as $feature)
                            <li class="flex gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-indigo-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-sm text-slate-500">A portfolio project by Flávio Pires · Laravel · Tailwind CSS</p>
            </div>

            {{-- Form panel --}}
            <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12">
                <div class="mx-auto w-full max-w-sm">
                    <div class="flex items-center gap-3 lg:hidden">
                        <x-application-logo class="h-9 w-9" />
                        <span class="text-lg font-bold">HelpDesk<span class="text-indigo-600"> Pro</span></span>
                    </div>
                    <div class="mt-8 lg:mt-0">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
