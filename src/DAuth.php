<?php

namespace Dengrweb\DAuth;

use Closure;
use Dengrweb\DAuth\Exceptions\EmailAlreadyUsed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DAuth
{
    /** @var (Closure(DAuthUser): Authenticatable)|null */
    protected static ?Closure $resolver = null;

    /**
     * Replace the default "find or create the local user" step.
     *
     * @param  Closure(DAuthUser): Authenticatable  $resolver
     */
    public static function resolveUsersUsing(Closure $resolver): void
    {
        static::$resolver = $resolver;
    }

    public static function forgetResolver(): void
    {
        static::$resolver = null;
    }

    public static function client(): DAuthClient
    {
        return app(DAuthClient::class);
    }

    /**
     * Find the local user for this Accounts user, creating or linking one if needed.
     *
     * @throws EmailAlreadyUsed
     */
    public static function resolveUser(DAuthUser $remote): Authenticatable
    {
        if (static::$resolver) {
            return (static::$resolver)($remote);
        }

        /** @var class-string<Model&Authenticatable> $model */
        $model = config('dauth.user_model');
        $column = config('dauth.id_column', 'dauth_id');

        $user = $model::query()->where($column, $remote->id)->first();

        if (! $user && config('dauth.link_by_email') && $remote->email && $remote->emailVerified) {
            $user = $model::query()->where('email', $remote->email)->whereNull($column)->first();
        }

        if (! $user && $remote->email && $model::query()->where('email', $remote->email)->exists()) {
            throw new EmailAlreadyUsed("A local account already uses {$remote->email}.");
        }

        $user ??= new $model;

        $attributes = [$column => $remote->id, 'name' => $remote->name ?? $remote->email, 'email' => $remote->email];

        $table = $user->getTable();
        if (Schema::hasColumn($table, 'avatar') && $remote->avatar) {
            $attributes['avatar'] = $remote->avatar;
        }
        if (! $user->exists && Schema::hasColumn($table, 'password')) {
            // They sign in through Accounts; the local password is never used.
            $attributes['password'] = bcrypt(Str::random(48));
        }
        if ($remote->emailVerified && Schema::hasColumn($table, 'email_verified_at') && ! $user->getAttribute('email_verified_at')) {
            $attributes['email_verified_at'] = now();
        }

        $user->forceFill($attributes)->save();

        return $user;
    }

    /**
     * What Vue pages need: the services list and where to sign in.
     *
     * @return array<string, mixed>
     */
    public static function sharedProps(): array
    {
        $routes = config('dauth.routes') && Route::has('dauth.redirect');

        return [
            'name' => config('dauth.name'),
            'url' => config('dauth.url'),
            'accountUrl' => config('dauth.url').'/settings/profile',
            'loginUrl' => $routes ? route('dauth.redirect') : null,
            'logoutUrl' => $routes ? route('dauth.logout') : null,
            'services' => array_values(config('dauth.services', [])),
        ];
    }
}
