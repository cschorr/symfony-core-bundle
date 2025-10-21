<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Exception;

class PasswordReusedException extends \RuntimeException
{
    public function __construct(string $message = 'This password was used recently. Please choose a different password.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
