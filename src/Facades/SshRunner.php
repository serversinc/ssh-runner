<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Facades;

use Illuminate\Support\Facades\Facade;
use Serversinc\SshRunner\Contracts\SshActionInterface;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\SshPipeline;

/**
 * @method static SshConnection connect(SshServer $server)
 * @method static SshPipeline pipeline(SshServer $server)
 * @method static ActionResult run(SshServer $server, SshActionInterface $action)
 *
 * @see \Serversinc\SshRunner\SshRunner
 */
class SshRunner extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ssh-runner';
    }
}
