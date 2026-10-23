<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dengrweb Accounts server
    |--------------------------------------------------------------------------
    |
    | Where users sign in, and the OAuth client you registered for this app
    | under Admin → OAuth apps (or /developers).
    |
    */

    'name' => env('DAUTH_NAME', 'Dengrweb Accounts'),

    'url' => rtrim((string) env('DAUTH_URL', 'https://accounts.dengrweb.com'), '/'),

    'client_id' => env('DAUTH_CLIENT_ID'),

    'client_secret' => env('DAUTH_CLIENT_SECRET'),

    // Defaults to this app's /auth/dauth/callback route.
    'redirect' => env('DAUTH_REDIRECT_URI'),

    'scopes' => ['profile', 'email'],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Registers GET {prefix}/redirect, GET {prefix}/callback and
    | POST {prefix}/logout. Turn off on the Accounts server itself.
    |
    */

    'routes' => env('DAUTH_ROUTES', true),

    'route_prefix' => 'auth/dauth',

    'middleware' => ['web'],

    // Sign guests in automatically when they're already signed in to Accounts (prompt=none),
    // once per browser session, on full page loads only. Needs the app to skip the consent
    // screen (mark it Trusted on Accounts); otherwise guests just stay guests.
    'silent_sign_in' => env('DAUTH_SILENT_SIGN_IN', true),

    // Paths the automatic sign-in never runs on, e.g. ['webhooks/*', 'embed/*'].
    'silent_except' => [],

    // Where to send the user after signing in (unless they were heading somewhere) and after signing out.
    'home' => '/dashboard',

    'logout_redirect' => '/',

    /*
    |--------------------------------------------------------------------------
    | Local users
    |--------------------------------------------------------------------------
    |
    | Users are keyed on the Accounts id in `id_column` (publish the migration
    | to add it). `link_by_email` attaches an existing local user with the same
    | verified email the first time they sign in this way.
    |
    | For full control, call DAuth::resolveUsersUsing(fn (DAuthUser $u) => ...)
    | in a service provider.
    |
    */

    'user_model' => env('DAUTH_USER_MODEL', 'App\\Models\\User'),

    'id_column' => 'dauth_id',

    'link_by_email' => true,

    'remember' => true,

    /*
    |--------------------------------------------------------------------------
    | D apps
    |--------------------------------------------------------------------------
    |
    | The services shown in every app's grid switcher. Keep this list here so
    | updating the package updates it everywhere. Links open in the same tab.
    |
    | icon: one of accounts, panel, mail, video, chat, cloud, shop, docs, chart,
    |       code, calendar, or leave null and set `logo` to an image URL.
    | color: any CSS colour; used for the icon tile.
    |
    */

    'services' => [
        [
            'key' => 'search',
            'name' => 'Search',
            'description' => 'Find what matters',
            'url' => 'https://dengrweb.com',
            'icon' => null,
            'logo' => 'https://dengrweb.com/pwa/icon-192.png',
            'color' => '#0c6650',
        ],
        [
            'key' => 'accounts',
            'name' => 'Accounts',
            'description' => 'Your profile and sign-in',
            'url' => 'https://accounts.dengrweb.com',
            'icon' => 'accounts',
            'logo' => null,
            'color' => '#059669',
        ],
        [
            'key' => 'webmaster',
            'name' => 'Webmaster',
            'description' => 'Get your site indexed',
            'url' => 'https://webmaster.dengrweb.com',
            'icon' => 'chart',
            'logo' => null,
            'color' => '#0d9488',
        ],
        [
            'key' => 'panel',
            'name' => 'dPanel',
            'description' => 'Hosting control panel',
            'url' => 'https://dpanel.dengrweb.com',
            'icon' => 'panel',
            'logo' => null,
            'color' => '#2563eb',
        ],
        [
            'key' => 'lazytime',
            'name' => 'LazyTime',
            'description' => 'Watch and share videos',
            'url' => 'https://lazytime.dengrweb.com',
            'icon' => 'video',
            'logo' => null,
            'color' => '#dc2626',
        ],
        [
            'key' => 'about',
            'name' => 'About',
            'description' => 'Software solutions',
            'url' => 'https://about.dengrweb.com',
            'icon' => 'code',
            'logo' => null,
            'color' => '#7c3aed',
        ],
    ],

    // Share `dauth` (services, sign-in URL, account URL) with every Inertia page.
    'share_inertia' => true,

];
