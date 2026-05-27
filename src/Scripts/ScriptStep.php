<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Scripts;

class ScriptStep
{
    public function __construct(
        public string $name,
        public string $command,
        public ?string $rollback = null,
        public bool $critical = true,
    ) {}
}
