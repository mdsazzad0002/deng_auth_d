<?php

namespace Dengrweb\DAuth\Http\Controllers;

use Dengrweb\DAuth\DAuth;
use Dengrweb\DAuth\DAuthClient;
use Dengrweb\DAuth\Events\DAuthenticated;
use Dengrweb\DAuth\Exceptions\EmailAlreadyUsed;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DAuthController extends Controller
{
    /**
     * Send the user to the Accounts server to sign in.
     */
    public function redirect(Request $request, DAuthClient $client): RedirectResponse
    {
        $pkce = DAuthClient::pkce();

        $request->session()->put('dauth.state', $state = Str::random(40));
        $request->session()->put('dauth.verifier', $pkce['verifier']);

        return redirect()->away($client->authorizeUrl($state, $pkce['challenge']));
    }

    /**
     * The Accounts server sends the user back here with a code.
     */
    public function callback(Request $request, DAuthClient $client): RedirectResponse
    {
        $state = $request->session()->pull('dauth.state');
        $verifier = $request->session()->pull('dauth.verifier');

        abort_unless(is_string($state) && hash_equals($state, (string) $request->query('state')), 403, 'Sign-in expired. Please try again.');

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect(config('dauth.logout_redirect', '/'))->with('error', 'Sign-in was cancelled.');
        }

        try {
            $remote = $client->user((string) $request->query('code'), (string) $verifier);
        } catch (RequestException) {
            return redirect(config('dauth.logout_redirect', '/'))->with('error', 'Could not sign you in. Please try again.');
        }

        try {
            $user = DAuth::resolveUser($remote);
        } catch (EmailAlreadyUsed) {
            return redirect(config('dauth.logout_redirect', '/'))
                ->with('error', 'An account with this email already exists here. Verify your email on '.config('dauth.name').' and try again.');
        }

        Auth::login($user, (bool) config('dauth.remember', true));
        $request->session()->regenerate();

        event(new DAuthenticated($user, $remote));

        return redirect()->intended(config('dauth.home', '/dashboard'));
    }

    /**
     * Sign out of this app. (The Accounts session stays signed in.)
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('dauth.logout_redirect', '/'));
    }
}
