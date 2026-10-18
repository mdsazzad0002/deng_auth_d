<?php

namespace Dengrweb\DAuth\Tests\Feature;

use Dengrweb\DAuth\Tests\Fixtures\User;
use Dengrweb\DAuth\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class SilentSignInTest extends TestCase
{
    private const PAGE = ['Accept' => 'text/html,application/xhtml+xml', 'User-Agent' => 'Mozilla/5.0 Chrome/140.0'];

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->get('/videos', fn () => 'videos page');
        Route::middleware('web')->get('/media/clip.mp4', fn () => 'bytes');
    }

    /** Opens a page as a browser would and returns the authorize URL's query, following the silent bounce. */
    private function bounce(string $path = '/videos'): array
    {
        $this->get($path, self::PAGE)->assertRedirect(route('dauth.redirect', ['silent' => 1]));

        $location = $this->get(route('dauth.redirect', ['silent' => 1]))->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }

    public function test_guests_are_bounced_through_accounts_with_prompt_none()
    {
        $query = $this->bounce();

        $this->assertSame('none', $query['prompt']);
    }

    public function test_signed_in_on_accounts_means_signed_in_here_on_the_same_page()
    {
        Http::fake([
            'accounts.test/oauth/token' => Http::response(['access_token' => 'token']),
            'accounts.test/api/user' => Http::response(['id' => '42', 'name' => 'Rahim', 'email' => 'rahim@example.com', 'email_verified' => true]),
        ]);
        $query = $this->bounce('/videos?page=2');

        $this->get('/auth/dauth/callback?code=c&state='.$query['state'])->assertRedirect(url('/videos?page=2'));

        $this->assertAuthenticatedAs(User::where('dauth_id', '42')->first());
    }

    public function test_not_signed_in_on_accounts_returns_to_the_page_as_a_guest_without_an_error()
    {
        $query = $this->bounce();

        $this->get('/auth/dauth/callback?error=login_required&state='.$query['state'])
            ->assertRedirect(url('/videos'))
            ->assertSessionMissing('error');
        $this->assertGuest();

        // Tried once per session: the next page loads normally.
        $this->get('/videos', self::PAGE)->assertOk()->assertSee('videos page');
    }

    public function test_an_explicit_sign_in_does_not_use_prompt_none()
    {
        $location = $this->get('/auth/dauth/redirect')->headers->get('Location');

        $this->assertStringNotContainsString('prompt=', $location);
    }

    public function test_it_only_runs_on_real_page_loads()
    {
        $this->get('/videos', ['Accept' => 'application/json'] + self::PAGE)->assertOk();
        $this->get('/videos', ['X-Inertia' => 'true'] + self::PAGE)->assertOk();
        $this->get('/videos', ['X-Requested-With' => 'XMLHttpRequest'] + self::PAGE)->assertOk();
        $this->get('/videos', ['Sec-Purpose' => 'prefetch'] + self::PAGE)->assertOk();
        $this->get('/media/clip.mp4', ['Accept' => '*/*'] + self::PAGE)->assertOk();
        $this->post('/videos', [], self::PAGE)->assertStatus(405);
    }

    public function test_it_skips_bots()
    {
        $this->get('/videos', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'] + self::PAGE)->assertOk();
        $this->get('/videos', ['User-Agent' => 'curl/8.5.0'] + self::PAGE)->assertOk();
    }

    public function test_it_skips_signed_in_users()
    {
        $this->actingAs(User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']));

        $this->get('/videos', self::PAGE)->assertOk();
    }

    public function test_logging_out_does_not_sign_you_straight_back_in()
    {
        $this->actingAs(User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']));

        $this->post('/auth/dauth/logout')->assertRedirect('/');

        $this->get('/videos', self::PAGE)->assertOk();
        $this->assertGuest();
    }

    public function test_apps_can_suppress_it_after_logging_someone_out_themselves()
    {
        Route::middleware('web')->get('/kick', function (\Illuminate\Http\Request $request) {
            $request->session()->invalidate();
            \Dengrweb\DAuth\Http\Middleware\SilentSignIn::suppress($request);

            return 'kicked';
        });

        $this->get('/kick', ['Accept' => '*/*'])->assertOk();

        // The flag survives in the regenerated session, so the middleware won't bounce this browser.
        $this->assertTrue(session(\Dengrweb\DAuth\Http\Middleware\SilentSignIn::TRIED));
    }

    public function test_browsers_without_cookies_are_not_bounced_forever()
    {
        // The callback arrives with no session (cookies blocked) and an error from a silent attempt.
        $this->get('/auth/dauth/callback?error=login_required&state=whatever')->assertRedirect(url('/').'?dauth_silent=off');

        $this->get('/?dauth_silent=off', self::PAGE)->assertNotFound(); // no "/" route in tests: it got past the middleware
    }

    public function test_it_can_be_turned_off()
    {
        config(['dauth.silent_sign_in' => false]);

        $this->get('/videos', self::PAGE)->assertOk();
    }

    public function test_paths_can_be_excluded()
    {
        config(['dauth.silent_except' => ['videos']]);

        $this->get('/videos', self::PAGE)->assertOk();
    }
}
