<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Unit;

use Serversinc\SshRunner\Scripts\BaseScript;
use Serversinc\SshRunner\Scripts\ScriptStep;
use Serversinc\SshRunner\Tests\TestCase;

class ScriptTest extends TestCase
{
    /** @test */
    public function script_step_has_expected_properties(): void
    {
        $step = new ScriptStep(
            name: 'Test step',
            command: 'echo hello',
            rollback: 'echo goodbye',
            critical: false,
        );

        $this->assertEquals('Test step', $step->name);
        $this->assertEquals('echo hello', $step->command);
        $this->assertEquals('echo goodbye', $step->rollback);
        $this->assertFalse($step->critical);
    }

    /** @test */
    public function script_step_defaults_to_critical_with_no_rollback(): void
    {
        $step = new ScriptStep(
            name: 'Simple step',
            command: 'echo hello',
        );

        $this->assertTrue($step->critical);
        $this->assertNull($step->rollback);
    }

    /** @test */
    public function base_script_can_be_extended_and_validated(): void
    {
        $script = new class extends BaseScript
        {
            public function steps(): array
            {
                return [
                    new ScriptStep(name: 'Step 1', command: 'echo 1'),
                    new ScriptStep(name: 'Step 2', command: 'echo 2'),
                ];
            }
        };

        $this->assertCount(2, $script->steps());
        $this->assertInstanceOf(ScriptStep::class, $script->steps()[0]);

        // validate() should be a no-op by default
        $this->assertNull($script->validate());
    }

    /** @test */
    public function base_script_validate_can_throw_exceptions(): void
    {
        $script = new class extends BaseScript
        {
            public function steps(): array
            {
                return [];
            }

            public function validate(): void
            {
                throw new \InvalidArgumentException('Validation failed');
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed');

        $script->validate();
    }
}
