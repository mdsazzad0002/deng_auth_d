<?php

namespace Dengrweb\DAuth\Events;

use Dengrweb\DAuth\DAuthUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after a user signs in through Accounts. Listen to it to store tokens
 * ($remote->accessToken(), $remote->refreshToken()) or sync extra fields.
 */
class DAuthenticated
{
    use Dispatchable;

    public function __construct(
        public readonly Authenticatable $user,
        public readonly DAuthUser $remote,
    ) {}
}
