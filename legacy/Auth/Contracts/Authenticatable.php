<?php

namespace Zxin\Think\Auth\Contracts;

interface Authenticatable
{
    /**
     * TODO 可能需要提供的接口
     * - 提供用户的唯一标识字段名称
     * - 用户唯一标识值
     * - 获取用户密码
     * - 提供 Remember 令牌字段名称
     * - 获取 Remember 令牌值
     * - 设置 Remember 令牌值
     */

    /**
     * @param int|string $id
     * @return static
     */
    public static function getSelfProvider($id);

    /**
     * @return int|string
     */
    public function getIdentity();

    public function isIgnoreAuthentication(): bool;

    public function allowPermission(string $permission): bool;

    public function permissions(): array;

    public function attachSessionInfo(): array;

    public function getRememberSecret(): string;

    public function getRememberToken(): string;

    public function updateRememberToken(string $token): void;
}
