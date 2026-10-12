<?php

namespace App\Http\Controllers;

use App\Models\CannedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Saved replies are shared by the whole IT team (routes are limited to staff). */
class CannedResponseController extends Controller
{
    public function index(): View
    {
        return view('canned-responses.index', [
            'responses' => CannedResponse::with('author')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CannedResponse::create($this->validated($request) + ['user_id' => $request->user()->id]);

        return back()->with('status', 'Saved reply added.');
    }

    public function update(Request $request, CannedResponse $cannedResponse): RedirectResponse
    {
        $cannedResponse->update($this->validated($request));

        return back()->with('status', 'Saved reply updated.');
    }

    public function destroy(CannedResponse $cannedResponse): RedirectResponse
    {
        $cannedResponse->delete();

        return back()->with('status', 'Saved reply deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
    }
}
