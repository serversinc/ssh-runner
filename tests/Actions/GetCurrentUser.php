<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action to get the current user on the remote server
 */
class GetCurrentUser extends BaseSshAction
{
    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        return $this->run($ssh, ['whoami']);
    }
}
