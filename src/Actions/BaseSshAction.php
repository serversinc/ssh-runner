<?php

namespace Serversinc\SshRunner\Actions;

use Serversinc\SshRunner\Contracts\SshActionInterface;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

abstract class BaseSshAction implements SshActionInterface
{
    abstract public function handle(SshServer $server, Ssh $ssh): ActionResult;

    protected function run(Ssh $ssh, array $commands): ActionResult
    {
        $process = $ssh->execute($commands);

        return new ActionResult(
            success: $process->isSuccessful(),
            output: $process->getOutput(),
            errorOutput: $process->getErrorOutput(),
            exitCode: $process->getExitCode(),
            action: static::class,
            executedAt: now()->toImmutable(),
        );
    }

    public function undo(SshServer $server, Ssh $ssh): void
    {
        // no-op by default
    }
}
