<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlayerCookie
{
    public const string COOKIE = 'pixelnime_player';

    public const string ATTRIBUTE = 'player_uuid';

    /**
     * Give every visitor an anonymous player identifier stored in a long lived cookie.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->cookie(self::COOKIE);

        if (! is_string($uuid) || ! Str::isUuid($uuid)) {
            $uuid = (string) Str::uuid();
            Cookie::queue(self::COOKIE, $uuid, 60 * 24 * 365 * 5);
        }

        $request->attributes->set(self::ATTRIBUTE, $uuid);

        return $next($request);
    }

    /**
     * Get the current player's identifier.
     */
    public static function playerUuid(?Request $request = null): string
    {
        $request ??= request();
        $uuid = $request->attributes->get(self::ATTRIBUTE) ?? $request->cookie(self::COOKIE);

        if (! is_string($uuid) || ! Str::isUuid($uuid)) {
            throw new LogicException('No player identifier: the EnsurePlayerCookie middleware did not run.');
        }

        return $uuid;
    }
}
