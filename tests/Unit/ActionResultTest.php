<?php

namespace Serversinc\SshRunner\Tests\Unit;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Serversinc\SshRunner\Results\ActionResult;

class ActionResultTest extends TestCase
{
    /** @test */
    public function it_stores_result_data(): void
    {
        $executedAt = CarbonImmutable::now();

        $result = new ActionResult(
            success: true,
            output: 'Command output',
            errorOutput: '',
            exitCode: 0,
            action: 'TestAction',
            executedAt: $executedAt
        );

        $this->assertTrue($result->success);
        $this->assertEquals('Command output', $result->output);
        $this->assertEquals('', $result->errorOutput);
        $this->assertEquals(0, $result->exitCode);
        $this->assertEquals('TestAction', $result->action);
        $this->assertEquals($executedAt, $result->executedAt);
    }

    /** @test */
    public function failed_returns_false_for_successful_result(): void
    {
        $result = new ActionResult(
            success: true,
            output: '',
            errorOutput: '',
            exitCode: 0,
            action: 'TestAction',
            executedAt: CarbonImmutable::now()
        );

        $this->assertFalse($result->failed());
    }

    /** @test */
    public function failed_returns_true_for_failed_result(): void
    {
        $result = new ActionResult(
            success: false,
            output: '',
            errorOutput: 'Error occurred',
            exitCode: 1,
            action: 'TestAction',
            executedAt: CarbonImmutable::now()
        );

        $this->assertTrue($result->failed());
    }
}
