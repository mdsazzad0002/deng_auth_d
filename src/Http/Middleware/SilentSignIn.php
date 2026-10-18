<?php

namespace Dengrweb\DAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs guests in automatically when they're already signed in to Accounts.
 *
 * On a guest's first full page load in a session, it bounces once through
 * Accounts with prompt=none. Accounts answers straight away, without showing
 * a page: with a sign-in if someone is signed in there (and the app needs no
 * consent screen), or with "login_required" otherwise. Either way the user
 * ends up back on the page they opened. It's tried once per session.
 */
class SilentSignIn
{
    public const TRIED = 'dauth.silent_tried';

    // Crawlers don't keep cookies, so bouncing them would cost a redirect on every page.
    protected const BOTS = '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|embedly|quora link preview|whatsapp|telegram|discord|preview|curl|wget|python-requests|httpclient|go-http-client/i';

    /**
     * Don't sign this browser session in automatically. Call it after logging a user out yourself
     * (e.g. a "banned" check that invalidates the session), or they'd be signed straight back in.
     */
    public static function suppress(Request $request): void
    {
        $request->session()->put(self::TRIED, true);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldTry($request)) {
            $request->session()->put(self::TRIED, true);
            redirect()->setIntendedUrl($request->fullUrl());

            return redirect()->route('dauth.redirect', ['silent' => 1]);
        }

        return $next($request);
    }

    protected function shouldTry(Request $request): bool
    {
        return config('dauth.silent_sign_in', true)
            && $request->isMethod('GET')
            && $request->hasSession()
            && Auth::guest()
            && ! $request->session()->get(self::TRIED)
            && $this->isPageNavigation($request)
            && ! $request->query('dauth_silent') // set after a failed attempt without cookies, see DAuthController
            && ! preg_match(self::BOTS, (string) $request->userAgent())
            && Route::has('dauth.redirect')
            && ! $request->routeIs('dauth.*', 'login', 'logout')
            && ! $request->is(...config('dauth.silent_except', []));
    }

    /**
     * Only real page loads: not Inertia or fetch/XHR calls, prefetches, or media/image requests.
     */
    protected function isPageNavigation(Request $request): bool
    {
        if ($request->header('X-Inertia') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        if (str_contains((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose')), 'prefetch')) {
            return false;
        }

        // Browsers send text/html for navigations; <video>, <img> and fetch() don't.
        return str_contains((string) $request->header('Accept'), 'text/html');
    }
}
