# dengrweb/dauth

"Sign in with Dengrweb Accounts" and the shared **D apps** switcher, for Laravel + Vue (Inertia) apps.

One package for every app, so they all sign in the same way and show the same list of services.

- **Laravel:** ready-made sign-in routes (OAuth 2.0 authorization code + PKCE), local user creation and linking, logout, and a `DAuthenticated` event.
- **Vue:** `<DAppSwitcher>` (grid button + slide-over of every D app), `<DSignInButton>`, and `useDAuth()`. They use their own scoped CSS, so no UI kit or Tailwind setup is needed.
- **Shared list:** the services live in this package's `config/dauth.php`. Update the package and every app shows the new list.

Requires PHP 8.2+, Laravel 11 or 12, Vue 3.4+ and `@inertiajs/vue3` 1 or 2.

## 1. Register the app on Accounts

On Dengrweb Accounts go to **Admin → OAuth apps → Register app** (or **Developers** for non-admins):

- Type: **Server-side web app**
- Redirect URL: `https://your-app.example.com/auth/dauth/callback`

Copy the client ID and secret.

## 2. Install

This package is installed from GitHub. Add the repository to the app's `composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/mdsazzad0002/deng_auth_d" }
]
```

During local development you can point `url` at the folder instead, e.g. `"/home/sazzad/Desktop/deng_auth_d"`. Composer reads it as a git repository and copies the committed code into `vendor/`.

```bash
composer require dengrweb/dauth:^0.1   # or dev-main to follow the main branch
php artisan vendor:publish --tag=dauth-migrations
php artisan migrate
```

The migration adds a nullable, unique `dauth_id` column to `users`.

Add to `.env`:

```dotenv
DAUTH_URL=https://accounts.example.com
DAUTH_CLIENT_ID=...
DAUTH_CLIENT_SECRET=...
# Optional, defaults to APP_URL/auth/dauth/callback
DAUTH_REDIRECT_URI=
```

To change anything else, publish the config: `php artisan vendor:publish --tag=dauth-config`.

## 3. Routes you get

| Method | URL                    | Name             |
| ------ | ---------------------- | ---------------- |
| GET    | `/auth/dauth/redirect` | `dauth.redirect` |
| GET    | `/auth/dauth/callback` | `dauth.callback` |
| POST   | `/auth/dauth/logout`   | `dauth.logout`   |

To make it the app's only sign-in, send guests there:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->redirectGuestsTo(fn () => route('dauth.redirect'));
})
```

After signing in, users go back to the page they asked for, or to `dauth.home` (`/dashboard`).

## Automatic sign-in

If someone is already signed in to Accounts, they're signed in to your app as soon as they open it, without clicking anything (like YouTube with a Google account).

On a guest's first full page load in a browser session, the app bounces once through Accounts with `prompt=none`. Accounts answers immediately without showing a page: with a sign-in, or with "not signed in". Either way the user lands back on the page they opened. It runs once per session, so guests who aren't signed in to Accounts just browse normally.

- **Requires a Trusted app on Accounts** (skip the "Allow access?" screen). Otherwise Accounts answers `consent_required` and the user stays a guest until they click Sign in.
- **Skipped for:** signed-in users, Inertia/XHR/JSON requests, non-HTML requests (video, images, `fetch`), prefetches, bots, and `silent_except` paths.
- **After signing out** (`dauth.logout`), that browser session isn't signed in automatically again.
- **If you log users out yourself** (for example, a "banned" check that invalidates the session), call `SilentSignIn::suppress($request)` afterwards. Otherwise they'd be signed straight back in:

```php
use Dengrweb\DAuth\Http\Middleware\SilentSignIn;

Auth::logout();
$request->session()->invalidate();
SilentSignIn::suppress($request);
```

To turn it off: `DAUTH_SILENT_SIGN_IN=false`. In tests, turn it off in `phpunit.xml` (`<env name="DAUTH_SILENT_SIGN_IN" value="false"/>`), because Laravel's test requests look like browser page loads.

## 4. Vue

Point an alias at the package's Vue code in `vite.config.ts`:

```ts
import path from 'node:path';

export default defineConfig({
    resolve: {
        alias: {
            '@dauth': path.resolve(__dirname, 'vendor/dengrweb/dauth/resources/js'),
        },
    },
    // ...
});
```

Add the same alias to `tsconfig.json` → `compilerOptions.paths`: `"@dauth": ["./vendor/dengrweb/dauth/resources/js"]`.

Then use it anywhere:

```vue
<script setup lang="ts">
import { DAppSwitcher, DSignInButton, useDAuth } from '@dauth';

const { logoutUrl } = useDAuth();
</script>

<template>
    <!-- In the top bar, next to the profile menu -->
    <DAppSwitcher />

    <!-- On a sign-in page -->
    <DSignInButton />
</template>
```

The Laravel side shares a `dauth` prop with every Inertia page. It holds `name`, `url`, `accountUrl`, `loginUrl`, `logoutUrl` and `services`, and the components read it automatically. You can also pass a list yourself: `<DAppSwitcher :services="[...]" />`.

Dark mode follows a `dark` class on `<html>`, like Tailwind's class strategy. Links open in the same tab.

## The services list

Edit `services` in `config/dauth.php` **in this package**, commit, tag a new version, push the commit and the tag (`git push origin main --tags`), then run `composer update dengrweb/dauth` in each app:

```php
[
    'key' => 'mail',
    'name' => 'Mail',
    'description' => 'Your mailbox',
    'url' => 'https://mail.example.com',
    'icon' => 'mail',      // accounts, panel, mail, video, chat, cloud, shop, docs, chart, code, calendar
    'logo' => null,        // or an image URL instead of an icon
    'color' => '#e11d48',  // any CSS colour
],
```

The tile for the app you're on is highlighted automatically.

## Local users

By default, the user is looked up by `dauth_id`. If none is found and Accounts says the email is verified, an existing local user with that email is linked (`link_by_email`). Otherwise a new user is created with `name`, `email`, `email_verified_at`, `avatar` (when those columns exist) and a random password.

If a different local user already has the email and it can't be linked, sign-in stops with an error message instead of creating a duplicate.

To map users your own way:

```php
use Dengrweb\DAuth\DAuth;
use Dengrweb\DAuth\DAuthUser;

// AppServiceProvider::boot()
DAuth::resolveUsersUsing(fn (DAuthUser $remote) => User::updateOrCreate(
    ['dauth_id' => $remote->id],
    ['name' => $remote->name, 'email' => $remote->email, 'phone' => $remote->get('phone_number')],
));
```

To keep the tokens (for example, to call the Accounts API later), listen for the event:

```php
use Dengrweb\DAuth\Events\DAuthenticated;

Event::listen(function (DAuthenticated $event) {
    $event->user->forceFill([
        'dauth_token' => $event->remote->accessToken(),
        'dauth_refresh_token' => $event->remote->refreshToken(),
    ])->save();
});
```

`DAuth::client()->refresh($refreshToken)` gets a new access token.

## On the Accounts server itself

Accounts is the server, not a client, so it only uses the switcher. Set `DAUTH_ROUTES=false` in its `.env`. The services list is still shared with its pages.

## Tests

```bash
composer install
vendor/bin/phpunit
```
