<?php

declare(strict_types=1);

namespace Zxin\Think\Auth\Access;

use Zxin\Think\Auth\Exception\AuthorizationException;

class Response implements \Stringable
{
    /**
     * Indicates whether the response was allowed.
     *
     * @var bool
     */
    protected $allowed;

    /**
     * The response message.
     *
     * @var string|null
     */
    protected $message;

    /**
     * Create a new response.
     *
     * @param  mixed  $code
     * @return void
     */
    final public function __construct(bool $allowed, ?string $message = '', protected $code = null)
    {
        $this->allowed = $allowed;
        $this->message = $message;
    }

    /**
     * Create a new "allow" Response.
     *
     * @param mixed       $code
     * @return Response
     */
    public static function allow(?string $message = null, $code = null)
    {
        return new static(true, $message, $code);
    }

    /**
     * Create a new "deny" Response.
     *
     * @param mixed $code
     * @return Response
     */
    public static function deny(?string $message = null, $code = null)
    {
        return new static(false, $message, $code);
    }

    /**
     * Determine if the response was allowed.
     *
     * @return bool
     */
    public function allowed()
    {
        return $this->allowed;
    }

    /**
     * Determine if the response was denied.
     *
     * @return bool
     */
    public function denied()
    {
        return !$this->allowed();
    }

    /**
     * Get the response message.
     *
     * @return string|null
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Get the response code / reason.
     *
     * @return mixed
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Throw authorization exception if response was denied.
     *
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function authorize()
    {
        if ($this->denied()) {
            throw (new AuthorizationException($this->message(), $this->code()))
                ->setResponse($this);
        }

        return $this;
    }

    /**
     * Convert the response to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'allowed' => $this->allowed(),
            'message' => $this->message(),
            'code' => $this->code(),
        ];
    }

    /**
     * Get the string representation of the message.
     */
    public function __toString(): string
    {
        return (string) $this->message();
    }
}
