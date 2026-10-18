<?php

namespace Dengrweb\DAuth\Tests\Feature;

use Dengrweb\DAuth\DAuth;
use Dengrweb\DAuth\DAuthUser;
use Dengrweb\DAuth\Events\DAuthenticated;
use Dengrweb\DAuth\Tests\Fixtures\User;
use Dengrweb\DAuth\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class SignInTest extends TestCase
{
    private function fakeAccounts(array $profile = []): void
    {
        Http::fake([
            'accounts.test/oauth/token' => Http::response(['access_token' => 'access-abc', 'refresh_token' => 'refresh-def', 'expires_in' => 3600]),
            'accounts.test/api/user' => Http::response($profile + [
                'id' => '42',
                'name' => 'Rahim Uddin',
                'email' => 'rahim@example.com',
                'email_verified' => true,
                'avatar' => 'https://accounts.test/avatars/42.png',
            ]),
        ]);
    }

    /** Starts a sign-in and returns the state the callback must send back. */
    private function startSignIn(): string
    {
        $location = $this->get('/auth/dauth/redirect')->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        return $query['state'];
    }

    public function test_redirect_sends_the_user_to_accounts_with_pkce_and_state()
    {
        $response = $this->get('/auth/dauth/redirect');

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.test/oauth/authorize?', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('client-123', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('profile email', $query['scope']);
        $this->assertSame(url('auth/dauth/callback'), $query['redirect_uri']);
        $this->assertSame(session('dauth.state'), $query['state']);

        $verifier = session('dauth.verifier');
        $this->assertSame(strtr(rtrim(base64_encode(hash('sha256', $verifier, true)), '='), '+/', '-_'), $query['code_challenge']);
    }

    public function test_callback_creates_and_signs_in_a_new_user()
    {
        Event::fake([DAuthenticated::class]);
        $this->fakeAccounts();
        $state = $this->startSignIn();

        $this->get('/auth/dauth/callback?code=the-code&state='.$state)->assertRedirect('/dashboard');

        $user = User::where('dauth_id', '42')->firstOrFail();
        $this->assertSame('Rahim Uddin', $user->name);
        $this->assertSame('rahim@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        Http::assertSent(fn ($request) => $request->url() === 'https://accounts.test/oauth/token'
            && $request['code'] === 'the-code'
            && $request['client_secret'] === 'secret-456'
            && strlen($request['code_verifier']) === 96);
        Event::assertDispatched(DAuthenticated::class, fn ($event) => $event->remote->accessToken() === 'access-abc');
    }

    public function test_callback_updates_the_same_user_on_later_sign_ins()
    {
        $existing = User::create(['dauth_id' => '42', 'name' => 'Old Name', 'email' => 'old@example.com', 'password' => 'x']);
        $this->fakeAccounts();
        $state = $this->startSignIn();

        $this->get('/auth/dauth/callback?code=c&state='.$state);

        $this->assertSame(1, User::count());
        $this->assertSame('Rahim Uddin', $existing->refresh()->name);
        $this->assertSame('rahim@example.com', $existing->email);
    }

    public function test_an_existing_local_user_is_linked_by_verified_email()
    {
        $local = User::create(['name' => 'Rahim', 'email' => 'rahim@example.com', 'password' => 'x']);
        $this->fakeAccounts();
        $state = $this->startSignIn();

        $this->get('/auth/dauth/callback?code=c&state='.$state);

        $this->assertSame('42', $local->refresh()->dauth_id);
        $this->assertSame(1, User::count());
    }

    public function test_unverified_emails_are_not_linked_to_existing_users()
    {
        User::create(['name' => 'Someone', 'email' => 'rahim@example.com', 'password' => 'x']);
        $this->fakeAccounts(['email' => 'rahim@example.com', 'email_verified' => false]);
        $state = $this->startSignIn();

        $this->get('/auth/dauth/callback?code=c&state='.$state)->assertRedirect('/')->assertSessionHas('error');

        $this->assertGuest();
        $this->assertSame(1, User::count());
        $this->assertNull(User::where('email', 'rahim@example.com')->value('dauth_id'));
    }

    public function test_callback_rejects_a_wrong_state()
    {
        $this->fakeAccounts();
        $this->startSignIn();

        $this->get('/auth/dauth/callback?code=c&state=forged')->assertForbidden();
        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_cancelled_sign_in_goes_home_with_an_error()
    {
        $state = $this->startSignIn();

        $this->get('/auth/dauth/callback?error=access_denied&state='.$state)
            ->assertRedirect('/')
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_a_failing_accounts_server_does_not_sign_anyone_in()
    {
        Http::fake(['accounts.test/*' => Http::response(['error' => 'invalid_grant'], 400)]);
        $state = $this->startSignIn();

        $this->get('/auth/dauth/callback?code=c&state='.$state)->assertRedirect('/')->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_apps_can_resolve_users_their_own_way()
    {
        DAuth::resolveUsersUsing(fn (DAuthUser $remote) => User::create([
            'dauth_id' => $remote->id,
            'name' => strtoupper($remote->name),
            'email' => $remote->email,
            'password' => 'x',
        ]));
        $this->fakeAccounts();
        $state = $this->startSignIn();

        $this->get('/auth/dauth/callback?code=c&state='.$state);

        $this->assertSame('RAHIM UDDIN', User::first()->name);
    }

    public function test_logout_signs_out_of_this_app()
    {
        $user = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);

        $this->actingAs($user)->post('/auth/dauth/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_shared_props_include_services_and_sign_in_url()
    {
        $props = DAuth::sharedProps();

        $this->assertSame('https://accounts.test', $props['url']);
        $this->assertSame(url('auth/dauth/redirect'), $props['loginUrl']);
        $this->assertNotEmpty($props['services']);
        $this->assertSame(['key', 'name', 'description', 'url', 'icon', 'color'], array_keys($props['services'][0]));
    }
}
