<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action to create a directory on the remote server
 */
class MakeDirectory extends BaseSshAction
{
    public function __construct(
        private readonly string $path,
        private readonly bool $recursive = true
    ) {}

    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        $flags = $this->recursive ? '-p' : '';

        return $this->run($ssh, ["mkdir {$flags} {$this->path}"]);
    }

    #[\Override]
    public function undo(SshServer $server, Ssh $ssh): void
    {
        // Remove the directory on rollback
        $ssh->execute(["rm -rf {$this->path}"]);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
