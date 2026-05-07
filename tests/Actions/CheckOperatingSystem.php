<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action to check the operating system on the remote server
 */
class CheckOperatingSystem extends BaseSshAction
{
    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        return $this->run($ssh, ['cat /etc/os-release | head -1']);
    }
}
