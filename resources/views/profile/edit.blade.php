<x-app-layout title="Profile">
    <div class="mx-auto max-w-3xl space-y-6">
        <h1 class="text-2xl font-bold tracking-tight">Profile</h1>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @include('profile.partials.api-tokens', ['tokens' => auth()->user()->tokens()->latest()->get()])
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
