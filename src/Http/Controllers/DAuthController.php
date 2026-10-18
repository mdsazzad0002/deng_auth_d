<?php

namespace Dengrweb\DAuth\Http\Controllers;

use Dengrweb\DAuth\DAuth;
use Dengrweb\DAuth\DAuthClient;
use Dengrweb\DAuth\Events\DAuthenticated;
use Dengrweb\DAuth\Exceptions\EmailAlreadyUsed;
use Dengrweb\DAuth\Http\Middleware\SilentSignIn;
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
     * With ?silent=1 (from SilentSignIn) Accounts answers without showing a page.
     */
    public function redirect(Request $request, DAuthClient $client): RedirectResponse
    {
        $pkce = DAuthClient::pkce();
        $silent = $request->boolean('silent');

        $request->session()->put('dauth.state', $state = Str::random(40));
        $request->session()->put('dauth.verifier', $pkce['verifier']);
        $request->session()->put('dauth.silent', $silent);

        return redirect()->away($client->authorizeUrl($state, $pkce['challenge'], $silent ? 'none' : null));
    }

    /**
     * The Accounts server sends the user back here with a code.
     */
    public function callback(Request $request, DAuthClient $client): RedirectResponse
    {
        $state = $request->session()->pull('dauth.state');
        $verifier = $request->session()->pull('dauth.verifier');
        $silent = (bool) $request->session()->pull('dauth.silent', false);

        if (! is_string($state) || ! hash_equals($state, (string) $request->query('state'))) {
            // No session came back (cookies blocked) from what was almost certainly a silent attempt:
            // go home without trying again, instead of bouncing to Accounts on every page.
            if (! is_string($state) && $request->filled('error')) {
                return redirect(url(config('dauth.logout_redirect', '/')).'?dauth_silent=off');
            }

            abort(403, 'Sign-in expired. Please try again.');
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            // Not signed in to Accounts (login_required) or the app needs consent: carry on as a guest.
            if ($silent) {
                return redirect()->intended(config('dauth.logout_redirect', '/'));
            }

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
     * Sign out of this app. (The Accounts session stays signed in, and this browser
     * session won't be signed in automatically again.)
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Otherwise SilentSignIn would sign them straight back in, since Accounts is still signed in.
        SilentSignIn::suppress($request);

        return redirect(config('dauth.logout_redirect', '/'));
    }
}
