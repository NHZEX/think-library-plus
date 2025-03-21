<?php

declare(strict_types=1);

namespace Zxin\Think\Auth;

use think\App;
use think\Config;
use think\Container;
use think\Cookie as CookieJar;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\helper\Str;
use think\Session;
use Zxin\Think\Auth\Access\Gate;
use Zxin\Think\Auth\Contracts\Authenticatable;
use Zxin\Think\Auth\Contracts\Guard;
use Zxin\Think\Auth\Contracts\ProviderlSelfCheck;
use Zxin\Think\Auth\Exception\AuthException;
use Zxin\Think\Auth\Traits\EventHelpers;
use Zxin\Think\Auth\Traits\GuardHelpers;

use function Zxin\Crypto\decrypt_data;
use function Zxin\Crypto\encrypt_data;

class AuthGuard implements Guard
{
    use GuardHelpers;
    use EventHelpers;

    /**
     * @var Container|App
     */
    protected $container;

    /**
     * The session used by the guard.
     *
     * @var Session
     */
    protected $session;

    /**
     * The Illuminate cookie creator service.
     *
     * @var CookieJar
     */
    protected $cookie;

    /**
     * Indicates if the logout method has been called.
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * Indicates if the user was authenticated via a recaller cookie.
     *
     * @var bool
     */
    protected $viaRemember = false;

    /**
     * @var Authenticatable|null
     */
    protected $user;

    /**
     * @var array
     */
    protected $config = [
        'remember' => [
            'name'   => 'remember',
            'expire' => 604800,
        ],
    ];

    /**
     * AuthGuard constructor.
     */
    public function __construct(Container $container, Config $config, Session $session, CookieJar $cookie)
    {
        $this->container = $container;
        $this->session = $session;
        $this->cookie  = $cookie;
        $this->config = array_merge($this->config, $config->get('auth', []));
    }

    public function getAuthorization(): ParseAuthorization
    {
        return $this->container->make(ParseAuthorization::class);
    }

    public function getSecuritySalt(): string
    {
        return env('DEPLOY_SECURITY_SALT');
    }

    public function validate(array $credentials = [])
    {
        throw new \LogicException('method not implemented');
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return null !== $this->user();
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->loggedOut) {
            return null;
        }

        if (null !== $this->user) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        if (null !== $id && $this->user = $this->retrieveById($id)) {
            $this->triggerAuthenticatedEvent($this->user);
        }

        if (null === $this->user && null !== ($this->user = $this->validRememberToken())) {
            $this->createRememberToken($this->user);
            $this->updateSession($this->user->getIdentity());
            $this->attachUserInfo($this->user);

            $this->triggerLoginEvent($this->user, true);
        }

        return $this->user;
    }

    /**
     * 获取当前经过身份验证的用户的ID
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id()
    {
        if ($this->loggedOut) {
            return null;
        }

        return $this->session->get($this->getName());
    }

    /**
     * @return string
     */
    public function getHashId()
    {
        return hash_hmac(
            'sha1',
            (string) $this->id(),
            $this->getSecuritySalt() . $this->getAuthorization()->getMachine()
        );
    }

    /**
     * @return Gate
     */
    public function gate()
    {
        return $this->container->make(Gate::class);
    }

    /**
     * @param int|string $id
     */
    public function getProvider($id): ?Authenticatable
    {
        /** @var class-string<Authenticatable> $class */
        $class = $this->config['provider'];
        if (!class_exists($class)) {
            throw new AuthException("auth provider({$class}) does not exist");
        }
        $implements = class_implements($class);
        if ($implements === false) {
            throw new AuthException("auth provider({$class}) load fail");
        }
        if (!isset($implements[Authenticatable::class])) {
            throw new AuthException("auth provider({$class}) not implement " . Authenticatable::class);
        }
        return $class::getSelfProvider($id);
    }

    /**
     * @param int|string $id
     */
    protected function retrieveById($id): ?Authenticatable
    {
        try {
            $result = $this->getProvider($id);
            if ($result
                && $result instanceof ProviderlSelfCheck
                && !$result->valid($message)
            ) {
                $this->logout();
                $this->setMessage($message);
                return null;
            }
            return $result;
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (DataNotFoundException | ModelNotFoundException | DbException) {
            return null;
        }
    }

    public function login(Authenticatable $user, bool $rememberme = false)
    {
        $this->updateSession($user->getIdentity());
        $this->attachUserInfo($user);

        if ($rememberme) {
            $this->ensureRememberTokenIsSet($user);
            $this->createRememberToken($user);
        } else {
            $this->clearupRememberToken();
        }

        $this->triggerLoginEvent($user, $rememberme);

        $this->setUser($user);
    }

    /**
     * Update the session with the given ID.
     *
     * @param  string|int  $id
     * @return void
     */
    protected function updateSession($id)
    {
        $this->session->set($this->getName(), $id);
        $this->session->regenerate();
    }

    protected function attachUserInfo(Authenticatable $user)
    {
        foreach ($user->attachSessionInfo() as $key => $value) {
            $this->session->set($this->getName($key), $value);
        }
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    public function logout()
    {
        $this->session->delete($this->getName());
        $this->clearupRememberToken();

        $user = $this->user();

        if (null !== $user && !empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }

        $this->user      = null;
        $this->loggedOut = true;
    }

    protected function ensureRememberTokenIsSet(Authenticatable $user)
    {
        if (empty($user->getRememberToken())) {
            $this->cycleRememberToken($user);
        }
    }

    /**
     * @return void
     */
    protected function createRememberToken(Authenticatable $user)
    {
        $machineCode = $this->getAuthorization()->getMachine();
        if (empty($machineCode)) {
            return;
        }
        $salt  = $this->getSecuritySalt();
        $expired = $this->config['remember']['expire'];
        $timeout = time() + $expired;
        $token = "{$user->getIdentity()}|{$user->getRememberToken()}|{$user->getRememberSecret()}|{$timeout}";
        $secret = encrypt_data($token, $salt, 'aes-128-ctr');
        $sign = hash_hmac('sha256', $secret, $machineCode . $salt, true);
        $secret = base64_encode($secret . $sign);
        $this->cookie->set($this->getRecallerName(), $secret, [
         'expire' => $expired,
         'httponly' => true,
        ]);
    }

    protected function clearupRememberToken()
    {
        $this->cookie->delete($this->getRecallerName());
    }

    protected function validRememberToken(): ?Authenticatable
    {
        $machineCode = $this->getAuthorization()->getMachine();
        if (empty($machineCode)) {
            return null;
        }
        $secret = $this->cookie->get($this->getRecallerName());
        if (empty($secret)) {
            return null;
        }
        $secretBytes = base64_decode($secret);
        if (empty($secretBytes)) {
            return null;
        }
        $salt  = $this->getSecuritySalt();
        $secretSign = substr($secretBytes, -32);
        $secretCiphertext = substr($secretBytes, 0, -32);
        if ($secretSign !== hash_hmac('sha256', $secretCiphertext, $machineCode . $salt, true)) {
            return null;
        }
        $token = decrypt_data($secretCiphertext, $salt, 'aes-128-ctr');
        if (empty($token) || 4 > \count($remember = explode('|', $token))) {
            return null;
        }
        [$userId, $rememberToken, $pass, $timeout] = $remember;
        if (time() > $timeout) {
            return null;
        }
        $user = $this->retrieveById((int) $userId);
        if (empty($user)
            || $rememberToken !== $user->getRememberToken()
            || $pass !== $user->getRememberSecret()
        ) {
            return null;
        }
        $this->viaRemember = true;
        return $user;
    }

    protected function cycleRememberToken(Authenticatable $user)
    {
        $user->updateRememberToken(Str::random(16));
    }

    /**
     * @return AuthGuard
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        $this->loggedOut = false;

        $this->triggerAuthenticatedEvent($user);

        return $this;
    }

    /**
     * Get a unique identifier for the auth session value.
     *
     * @param string|null $append
     * @return string
     */
    public function getName(string $append = null, string $join = '_')
    {
        return 'login_sess_' . sha1(static::class) . ($append ? ($join . $append) : $append);
    }

    /**
     * Get the name of the cookie used to store the "recaller".
     *
     * @return string
     */
    public function getRecallerName()
    {
        return $this->config['remember']['name'];
    }

    public function __get($name)
    {
        if ($this->loggedOut) {
            return null;
        }

        $name = $this->getName(Str::snake($name));
        return $this->session->get($name);
    }

    public function __isset($name)
    {
        if ($this->loggedOut) {
            return false;
        }

        $name = $this->getName(Str::snake($name));
        return $this->session->has($name);
    }
}
