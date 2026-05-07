<?php

namespace Serversinc\SshRunner\Results;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PipelineResult
{
    public function __construct(
        public readonly bool $success,
        public readonly Collection $results,
        public readonly CarbonImmutable $startedAt,
        public readonly CarbonImmutable $completedAt,
    ) {}

    public function failed(): bool
    {
        return ! $this->success;
    }

    public function failedActions(): Collection
    {
        return $this->results->filter(fn (ActionResult $r): bool => $r->failed());
    }

    public function duration(): float
    {
        return $this->startedAt->diffInSeconds($this->completedAt);
    }
}
