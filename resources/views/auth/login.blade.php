<x-guest-layout>
    <h1 class="text-2xl font-bold tracking-tight">Sign in</h1>
    <p class="mt-1 text-sm text-slate-500">Welcome back to the service desk.</p>

    @if (config('helpdesk.demo'))
        <div class="mt-8">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Try the demo: sign in as</p>
            <div class="mt-3 grid gap-2">
                @foreach (config('helpdesk.demo_accounts') as $account)
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $account['email'] }}">
                        <input type="hidden" name="password" value="password">
                        <button type="submit" class="flex w-full items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-left transition hover:border-indigo-400 hover:bg-indigo-50/50">
                            <span>
                                <span class="block text-sm font-semibold">{{ $account['role'] }}</span>
                                <span class="block text-xs text-slate-500">{{ $account['description'] }}</span>
                            </span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </button>
                    </form>
                @endforeach
            </div>
            <div class="my-8 flex items-center gap-3 text-xs text-slate-400">
                <span class="h-px flex-1 bg-slate-200"></span>or use your account<span class="h-px flex-1 bg-slate-200"></span>
            </div>
        </div>
    @endif

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" @class(['mt-8' => ! config('helpdesk.demo')])>
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    @if (Route::has('register'))
        <p class="mt-8 text-center text-sm text-slate-500">
            New here? <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Create an employee account</a>
        </p>
    @endif
</x-guest-layout>
