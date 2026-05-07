<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action to list directory contents on the remote server
 */
class ListDirectory extends BaseSshAction
{
    public function __construct(private readonly string $path = '~') {}

    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        return $this->run($ssh, ["ls -la {$this->path}"]);
    }
}
