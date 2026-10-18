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

    'url' => rtrim((string) env('DAUTH_URL', 'http://accounts.localhost'), '/'),

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
            'key' => 'accounts',
            'name' => 'Accounts',
            'description' => 'Your profile and sign-in',
            'url' => 'http://accounts.localhost',
            'icon' => 'accounts',
            'color' => '#059669',
        ],
        [
            'key' => 'panel',
            'name' => 'dPanel',
            'description' => 'Hosting control panel',
            'url' => 'http://dpanel.localhost',
            'icon' => 'panel',
            'color' => '#2563eb',
        ],
        [
            'key' => 'banglatube',
            'name' => 'BanglaTube',
            'description' => 'Watch and share videos',
            'url' => 'http://local.youtube.localhost',
            'icon' => 'video',
            'color' => '#dc2626',
        ],
        [
            'key' => 'mail',
            'name' => 'Mail',
            'description' => 'Your mailbox',
            'url' => 'http://mail.localhost',
            'icon' => 'mail',
            'color' => '#e11d48',
        ],
    ],

    // Share `dauth` (services, sign-in URL, account URL) with every Inertia page.
    'share_inertia' => true,

];
