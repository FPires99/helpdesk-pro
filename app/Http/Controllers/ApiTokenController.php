<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Personal access tokens for the REST API, managed from the profile page. */
class ApiTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validateWithBag('apiToken', ['token_name' => ['required', 'string', 'max:60']]);

        $token = $request->user()->createToken($request->input('token_name'));

        // The plain-text token is only ever shown once.
        return back()->with('new_api_token', $token->plainTextToken)->with('status', 'API token created. Copy it now; it won\'t be shown again.');
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $request->user()->tokens()->whereKey($tokenId)->delete();

        return back()->with('status', 'API token revoked.');
    }
}
