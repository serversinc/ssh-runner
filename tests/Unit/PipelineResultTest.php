<?php

namespace Serversinc\SshRunner\Tests\Unit;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Serversinc\SshRunner\Results\ActionResult;
use Serversinc\SshRunner\Results\PipelineResult;

class PipelineResultTest extends TestCase
{
    /** @test */
    public function it_calculates_duration(): void
    {
        $startedAt = CarbonImmutable::now();
        $completedAt = $startedAt->addSeconds(5);

        $result = new PipelineResult(
            success: true,
            results: collect(),
            startedAt: $startedAt,
            completedAt: $completedAt
        );

        $this->assertEquals(5, $result->duration());
    }

    /** @test */
    public function failed_returns_false_for_successful_pipeline(): void
    {
        $result = new PipelineResult(
            success: true,
            results: collect(),
            startedAt: CarbonImmutable::now(),
            completedAt: CarbonImmutable::now()
        );

        $this->assertFalse($result->failed());
    }

    /** @test */
    public function failed_returns_true_for_failed_pipeline(): void
    {
        $result = new PipelineResult(
            success: false,
            results: collect(),
            startedAt: CarbonImmutable::now(),
            completedAt: CarbonImmutable::now()
        );

        $this->assertTrue($result->failed());
    }

    /** @test */
    public function it_filters_failed_actions(): void
    {
        $successfulAction = new ActionResult(
            success: true,
            output: '',
            errorOutput: '',
            exitCode: 0,
            action: 'SuccessAction',
            executedAt: CarbonImmutable::now()
        );

        $failedAction = new ActionResult(
            success: false,
            output: '',
            errorOutput: 'Error',
            exitCode: 1,
            action: 'FailedAction',
            executedAt: CarbonImmutable::now()
        );

        $result = new PipelineResult(
            success: false,
            results: collect([$successfulAction, $failedAction]),
            startedAt: CarbonImmutable::now(),
            completedAt: CarbonImmutable::now()
        );

        $failed = $result->failedActions();

        $this->assertCount(1, $failed);
        $this->assertEquals('FailedAction', $failed->first()->action);
    }
}
