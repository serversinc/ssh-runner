<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Contracts;

use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

interface SshActionInterface
{
    public function handle(SshServer $server, Ssh $ssh): ActionResult;

    public function undo(SshServer $server, Ssh $ssh): void;
}
