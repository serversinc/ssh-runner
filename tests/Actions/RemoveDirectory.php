<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action to remove a directory on the remote server
 */
class RemoveDirectory extends BaseSshAction
{
    public function __construct(
        private readonly string $path,
        private readonly bool $recursive = true
    ) {}

    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        $flags = $this->recursive ? '-r' : '';

        return $this->run($ssh, ["rm {$flags}f {$this->path}"]);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
