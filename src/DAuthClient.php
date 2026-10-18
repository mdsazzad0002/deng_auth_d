<?php

namespace Dengrweb\DAuth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Talks to the Accounts server: builds the authorize URL (with PKCE) and
 * exchanges the code for a token and the user's profile.
 */
class DAuthClient
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        protected string $url,
        protected ?string $clientId,
        protected ?string $clientSecret,
        protected string $redirect,
        protected array $scopes = ['profile', 'email'],
    ) {}

    /**
     * A random PKCE verifier and its S256 challenge.
     *
     * @return array{verifier: string, challenge: string}
     */
    public static function pkce(): array
    {
        $verifier = Str::random(96);

        return ['verifier' => $verifier, 'challenge' => strtr(rtrim(base64_encode(hash('sha256', $verifier, true)), '='), '+/', '-_')];
    }

    public function authorizeUrl(string $state, string $challenge): string
    {
        return $this->url.'/oauth/authorize?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirect,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * @return array<string, mixed> access_token, refresh_token, expires_in, token_type
     */
    public function exchangeCode(string $code, string $verifier): array
    {
        return Http::asForm()->acceptJson()->post($this->url.'/oauth/token', array_filter([
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirect,
            'code_verifier' => $verifier,
            'code' => $code,
        ], fn ($value) => $value !== null))->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(string $refreshToken): array
    {
        return Http::asForm()->acceptJson()->post($this->url.'/oauth/token', array_filter([
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', $this->scopes),
        ], fn ($value) => $value !== null))->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(string $accessToken): array
    {
        return Http::withToken($accessToken)->acceptJson()->get($this->url.'/api/user')->throw()->json();
    }

    public function user(string $code, string $verifier): DAuthUser
    {
        $token = $this->exchangeCode($code, $verifier);

        return DAuthUser::fromProfile($this->profile($token['access_token']), $token);
    }
}
