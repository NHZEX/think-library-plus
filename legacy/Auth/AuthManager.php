<?php

namespace Zxin\Think\Auth;

use think\Container;
use Zxin\Think\Auth\Contracts\Authenticatable;

class AuthManager
{
    public static function instance(): AuthGuard
    {
        return Container::getInstance()->make(AuthGuard::class);
    }

    /**
     * @return int|string|null
     */
    public static function id()
    {
        return self::instance()->id();
    }

    /**
     */
    public static function user(): ?Authenticatable
    {
        return self::instance()->user();
    }

    public static function check(): bool
    {
        return self::instance()->check();
    }

    public static function context(): ?AuthContext
    {
        return AuthContext::get();
    }

    public static function getPermissions(): ?array
    {
        $user = self::user();
        if (empty($user)) {
            return null;
        }
        return $user->permissions();
    }

    public static function allowPermission(string $permission): bool
    {
        $permissions = self::getPermissions();
        if (empty($permissions)) {
            return false;
        }
        return isset($permissions[$permission]);
    }
}
