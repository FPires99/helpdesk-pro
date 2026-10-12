{{-- Multiple file picker that lists the chosen files before upload. --}}
<div x-data="{ files: [] }" {{ $attributes }}>
    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-600 hover:border-indigo-400 hover:text-indigo-600">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
        <span x-text="files.length ? files.length + (files.length === 1 ? ' file' : ' files') + ' selected' : 'Attach files'">Attach files</span>
        <input type="file" name="attachments[]" multiple class="sr-only"
               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log,.csv,.docx,.xlsx,.zip"
               @change="files = Array.from($event.target.files).map(f => f.name)">
    </label>
    <ul x-show="files.length" x-cloak class="mt-2 space-y-0.5 text-xs text-slate-500">
        <template x-for="name in files" :key="name"><li x-text="name"></li></template>
    </ul>
    <x-input-error :messages="array_merge($errors->get('attachments'), $errors->get('attachments.*') ? array_merge(...array_values($errors->get('attachments.*'))) : [])" class="mt-2" />
</div>
