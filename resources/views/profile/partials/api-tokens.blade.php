<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">API tokens</h2>
        <p class="mt-1 text-sm text-slate-600">
            Let scripts and other tools use the HelpDesk Pro API on your behalf, e.g. a monitoring system that raises tickets automatically.
            Send the token as <code class="rounded bg-slate-100 px-1">Authorization: Bearer &lt;token&gt;</code>.
        </p>
    </header>

    @if (session('new_api_token'))
        <div x-data="{ copied: false }" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-semibold text-emerald-900">Your new token. Copy it now; it won't be shown again:</p>
            <div class="mt-2 flex gap-2">
                <code x-ref="token" class="flex-1 break-all rounded bg-white px-3 py-2 font-mono text-xs ring-1 ring-emerald-200">{{ session('new_api_token') }}</code>
                <button type="button" @click="navigator.clipboard.writeText($refs.token.textContent); copied = true"
                        class="shrink-0 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-500" x-text="copied ? 'Copied' : 'Copy'">Copy</button>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('api-tokens.store') }}" class="mt-6 flex gap-3">
        @csrf
        <div class="flex-1">
            <x-input-label for="token_name" value="Token name" class="sr-only" />
            <x-text-input id="token_name" name="token_name" class="block w-full" placeholder="e.g. Server monitoring" required maxlength="60" />
            <x-input-error :messages="$errors->apiToken->get('token_name')" class="mt-2" />
        </div>
        <x-primary-button>Create token</x-primary-button>
    </form>

    @if ($tokens->isNotEmpty())
        <ul class="mt-6 divide-y divide-slate-100 rounded-lg border border-slate-200">
            @foreach ($tokens as $token)
                <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                    <div>
                        <p class="font-medium">{{ $token->name }}</p>
                        <p class="text-xs text-slate-500">Created {{ $token->created_at->diffForHumans() }} · {{ $token->last_used_at ? 'last used '.$token->last_used_at->diffForHumans() : 'never used' }}</p>
                    </div>
                    <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="font-medium text-red-600 hover:text-red-500">Revoke</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</section>
