<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Results;

use Carbon\CarbonImmutable;

class ActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $output,
        public readonly string $errorOutput,
        public readonly int $exitCode,
        public readonly string $action,
        public readonly CarbonImmutable $executedAt
    ) {}

    public function failed(): bool
    {
        return ! $this->success;
    }
}
