<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serversinc\SshRunner\Exceptions\SshActionException;
use Serversinc\SshRunner\Exceptions\SshConnectionException;

class ExceptionTest extends TestCase
{
    /** @test */
    public function ssh_action_exception_can_be_thrown(): void
    {
        $this->expectException(SshActionException::class);
        $this->expectExceptionMessage('SSH action failed');

        throw new SshActionException;
    }

    /** @test */
    public function ssh_action_exception_accepts_custom_message(): void
    {
        $this->expectException(SshActionException::class);
        $this->expectExceptionMessage('Custom error message');

        throw new SshActionException('Custom error message');
    }

    /** @test */
    public function ssh_connection_exception_can_be_thrown(): void
    {
        $this->expectException(SshConnectionException::class);
        $this->expectExceptionMessage('SSH connection failed');

        throw new SshConnectionException;
    }

    /** @test */
    public function ssh_connection_exception_accepts_custom_message(): void
    {
        $this->expectException(SshConnectionException::class);
        $this->expectExceptionMessage('Connection timeout');

        throw new SshConnectionException('Connection timeout');
    }
}
