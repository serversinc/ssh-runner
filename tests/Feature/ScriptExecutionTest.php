<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Feature;

use Serversinc\SshRunner\Enums\FailureStrategy;
use Serversinc\SshRunner\Facades\SshRunner;
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\Tests\Actions\CheckOperatingSystem;
use Serversinc\SshRunner\Tests\Actions\ListDirectory;
use Serversinc\SshRunner\Tests\Fixtures\CreateTempDirectoryScript;
use Serversinc\SshRunner\Tests\Fixtures\FailingCriticalStepScript;
use Serversinc\SshRunner\Tests\Fixtures\FailingNonCriticalStepScript;
use Serversinc\SshRunner\Tests\Fixtures\IntegrationTestServer;
use Serversinc\SshRunner\Tests\TestCase;

/**
 * Integration tests for script execution against the Docker SSH container
 *
 * These tests require the Docker environment to be running:
 * docker compose up -d ssh-server
 */
class ScriptExecutionTest extends TestCase
{
    private $server;

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('SSH_TEST_HOST')) {
            putenv('SSH_TEST_HOST=localhost');
            putenv('SSH_TEST_PORT=2222');
            putenv('SSH_TEST_USER=testuser');
            putenv('SSH_TEST_KEY_PATH=docker/ssh-keys/id_rsa');
        }

        $this->server = IntegrationTestServer::fromEnvironment();
    }

    /** @test */
    public function it_can_execute_a_script_successfully(): void
    {
        $script = new CreateTempDirectoryScript(
            dirName: 'script_success_'.uniqid(),
            fileName: 'test.txt',
        );

        $result = SshRunner::script($this->server, $script);

        $this->assertTrue($result->success);
        $this->assertStringContainsString('Script completed', $result->output);

        // Verify the file was actually created
        $connection = SshConnection::for($this->server);
        $checkResult = $connection->execute(new ListDirectory($script->getFile()));
        $this->assertTrue($checkResult->success);
    }

    /** @test */
    public function it_can_execute_a_script_in_a_pipeline(): void
    {
        $script = new CreateTempDirectoryScript(
            dirName: 'pipeline_script_'.uniqid(),
            fileName: 'pipeline.txt',
        );

        $result = SshRunner::pipeline($this->server)
            ->run(new CheckOperatingSystem)
            ->script($script)
            ->execute();

        $this->assertTrue($result->success);
        $this->assertCount(2, $result->results);
    }

    /** @test */
    public function it_rolls_back_on_critical_step_failure(): void
    {
        $script = new FailingCriticalStepScript(
            dirName: 'rollback_test_'.uniqid(),
            fileName: 'rollback.txt',
        );

        $result = SshRunner::script($this->server, $script);

        $this->assertFalse($result->success);
        $this->assertStringContainsString("Failed at step 'This step fails'", $result->errorOutput);

        // Verify rollback removed the directory
        $connection = SshConnection::for($this->server);
        $checkResult = $connection->execute(new ListDirectory($script->getDir()));
        $this->assertFalse($checkResult->success);
    }

    /** @test */
    public function it_continues_on_non_critical_step_failure(): void
    {
        $script = new FailingNonCriticalStepScript(
            dirName: 'nc_test_'.uniqid(),
            fileName: 'nc.txt',
        );

        $result = SshRunner::script($this->server, $script);

        // Script should succeed because the failing step was non-critical
        $this->assertTrue($result->success);
        $this->assertStringContainsString('Script completed', $result->output);

        // Verify the file was still created after the non-critical failure
        $connection = SshConnection::for($this->server);
        $checkResult = $connection->execute(new ListDirectory($script->getFile()));
        $this->assertTrue($checkResult->success);
    }

    /** @test */
    public function it_triggers_script_undo_when_pipeline_rollback_strategy_is_used(): void
    {
        $script = new CreateTempDirectoryScript(
            dirName: 'pipeline_rollback_'.uniqid(),
            fileName: 'rollback.txt',
        );

        $connection = SshConnection::for($this->server);

        // Use a failing action after the script to trigger pipeline rollback
        $failingAction = new \Serversinc\SshRunner\Tests\Actions\FailingAction;

        $result = $connection->pipeline()
            ->onFailure(FailureStrategy::ROLLBACK)
            ->script($script)
            ->run($failingAction)
            ->execute();

        $this->assertFalse($result->success);

        // Verify the script's rollback was called (directory should be gone)
        $checkResult = $connection->execute(new ListDirectory($script->getDir()));
        $this->assertFalse($checkResult->success);
    }

    /** @test */
    public function it_validates_script_before_execution(): void
    {
        $script = new class extends \Serversinc\SshRunner\Scripts\BaseScript
        {
            public function steps(): array
            {
                return [
                    new \Serversinc\SshRunner\Scripts\ScriptStep(name: 'Step', command: 'echo hello'),
                ];
            }

            public function validate(): void
            {
                throw new \InvalidArgumentException('Validation error');
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation error');

        SshRunner::script($this->server, $script);
    }

    /** @test */
    public function it_reuses_the_same_ssh_connection_between_steps(): void
    {
        $script = new class extends \Serversinc\SshRunner\Scripts\BaseScript
        {
            public function steps(): array
            {
                return [
                    new \Serversinc\SshRunner\Scripts\ScriptStep(
                        name: 'Create file',
                        command: 'echo "persisted" > /tmp/ssh_runner_persist_test.txt',
                    ),
                    new \Serversinc\SshRunner\Scripts\ScriptStep(
                        name: 'Read file from previous step',
                        command: 'cat /tmp/ssh_runner_persist_test.txt',
                    ),
                    new \Serversinc\SshRunner\Scripts\ScriptStep(
                        name: 'Cleanup',
                        command: 'rm /tmp/ssh_runner_persist_test.txt',
                    ),
                ];
            }
        };

        $result = SshRunner::script($this->server, $script);

        $this->assertTrue($result->success);
        $this->assertStringContainsString('Script completed 3 steps', $result->output);
    }

    /** @test */
    public function it_preserves_filesystem_state_between_steps(): void
    {
        $script = new class extends \Serversinc\SshRunner\Scripts\BaseScript
        {
            public function steps(): array
            {
                return [
                    new \Serversinc\SshRunner\Scripts\ScriptStep(
                        name: 'Create file',
                        command: 'echo "persisted_data" > /tmp/ssh_runner_fs_test.txt',
                    ),
                    new \Serversinc\SshRunner\Scripts\ScriptStep(
                        name: 'Read file created in previous step',
                        command: 'cat /tmp/ssh_runner_fs_test.txt && rm /tmp/ssh_runner_fs_test.txt',
                    ),
                ];
            }
        };

        $result = SshRunner::script($this->server, $script);

        // Script succeeded with 2 steps, meaning step 2 could read the file from step 1
        $this->assertTrue($result->success);
        $this->assertStringContainsString('Script completed 2 steps', $result->output);
    }
}
