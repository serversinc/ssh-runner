<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serversinc\SshRunner\Enums\FailureStrategy;

class FailureStrategyTest extends TestCase
{
    /** @test */
    public function it_has_stop_strategy(): void
    {
        $strategy = FailureStrategy::STOP;

        $this->assertInstanceOf(FailureStrategy::class, $strategy);
    }

    /** @test */
    public function it_has_continue_strategy(): void
    {
        $strategy = FailureStrategy::CONTINUE;

        $this->assertInstanceOf(FailureStrategy::class, $strategy);
    }

    /** @test */
    public function it_has_rollback_strategy(): void
    {
        $strategy = FailureStrategy::ROLLBACK;

        $this->assertInstanceOf(FailureStrategy::class, $strategy);
    }

    /** @test */
    public function strategies_are_distinct(): void
    {
        $stop = FailureStrategy::STOP;
        $continue = FailureStrategy::CONTINUE;
        $rollback = FailureStrategy::ROLLBACK;

        $this->assertNotSame($stop, $continue);
        $this->assertNotSame($stop, $rollback);
        $this->assertNotSame($continue, $rollback);
    }
}
