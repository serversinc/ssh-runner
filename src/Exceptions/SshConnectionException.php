<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Exceptions;

use Exception;

class SshConnectionException extends Exception
{
    public function __construct(string $message = 'SSH connection failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
