<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action to check if a command is available on the remote server
 */
class CheckCommandExists extends BaseSshAction
{
    public function __construct(private readonly string $command) {}

    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        return $this->run($ssh, ["which {$this->command}"]);
    }
}
