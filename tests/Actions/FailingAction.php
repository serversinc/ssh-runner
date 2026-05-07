<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action that intentionally fails - useful for testing failure strategies
 */
class FailingAction extends BaseSshAction
{
    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        return $this->run($ssh, ['exit 1']);
    }
}
