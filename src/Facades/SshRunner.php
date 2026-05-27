<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Facades;

use Illuminate\Support\Facades\Facade;
use Serversinc\SshRunner\Contracts\SshActionInterface;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Serversinc\SshRunner\Scripts\BaseScript;
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\SshPipeline;

/**
 * @method static SshConnection connect(SshServer $server)
 * @method static SshPipeline pipeline(SshServer $server)
 * @method static ActionResult run(SshServer $server, SshActionInterface $action)
 * @method static ActionResult script(SshServer $server, BaseScript $script)
 *
 * @see \Serversinc\SshRunner\SshRunner
 */
class SshRunner extends Facade
{
    public static function script(SshServer $server, BaseScript $script): ActionResult
    {
        return static::run($server, new \Serversinc\SshRunner\Actions\ExecuteScriptAction($script));
    }

    protected static function getFacadeAccessor(): string
    {
        return 'ssh-runner';
    }
}
