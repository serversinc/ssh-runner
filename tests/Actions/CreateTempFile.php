<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Actions;

use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

/**
 * Action to create a temporary file on the remote server
 */
class CreateTempFile extends BaseSshAction
{
    private string $tempFile;

    public function __construct(private readonly string $content = 'test content') {}

    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        $this->tempFile = '/tmp/ssh_test_'.uniqid().'.txt';

        return $this->run($ssh, ["echo '{$this->content}' > {$this->tempFile}"]);
    }

    #[\Override]
    public function undo(SshServer $server, Ssh $ssh): void
    {
        if (isset($this->tempFile)) {
            $ssh->execute(["rm -f {$this->tempFile}"]);
        }
    }

    public function getTempFile(): string
    {
        return $this->tempFile;
    }
}
