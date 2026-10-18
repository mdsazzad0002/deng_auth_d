<?php

namespace Dengrweb\DAuth;

/**
 * The signed-in person as the Accounts server describes them (GET /api/user),
 * plus the tokens from the sign-in.
 */
final class DAuthUser
{
    /**
     * @param  array<string, mixed>  $raw  Every field /api/user returned.
     * @param  array<string, mixed>  $token  The /oauth/token response.
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly bool $emailVerified,
        public readonly ?string $avatar,
        public readonly array $raw,
        public readonly array $token = [],
    ) {}

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $token
     */
    public static function fromProfile(array $profile, array $token = []): self
    {
        return new self(
            id: (string) ($profile['id'] ?? $profile['sub'] ?? ''),
            name: $profile['name'] ?? null,
            email: $profile['email'] ?? null,
            emailVerified: (bool) ($profile['email_verified'] ?? false),
            avatar: $profile['avatar'] ?? $profile['picture'] ?? null,
            raw: $profile,
            token: $token,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    public function accessToken(): ?string
    {
        return $this->token['access_token'] ?? null;
    }

    public function refreshToken(): ?string
    {
        return $this->token['refresh_token'] ?? null;
    }
}
