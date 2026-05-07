<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Exceptions;

use Exception;

class SshActionException extends Exception
{
    public function __construct(string $message = 'SSH action failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
