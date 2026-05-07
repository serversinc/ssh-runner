<?php

declare(strict_types=1);

namespace Serversinc\SshRunner;

use Serversinc\SshRunner\Contracts\SshActionInterface;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

class SshRunner
{
    /**
     * Create a new SSH connection for a server.
     */
    public static function connect(SshServer $server): SshConnection
    {
        return SshConnection::for($server);
    }

    /**
     * Create a new SSH pipeline for a server.
     * Shortcut for: SshRunner::connect($server)->pipeline()
     */
    public static function pipeline(SshServer $server): SshPipeline
    {
        return SshConnection::for($server)->pipeline();
    }

    /**
     * Execute a single action on a server.
     * Shortcut for: SshRunner::connect($server)->execute($action)
     */
    public static function run(SshServer $server, SshActionInterface $action): ActionResult
    {
        return SshConnection::for($server)->execute($action);
    }
}
