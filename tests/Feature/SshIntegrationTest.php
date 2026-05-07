<?php

namespace Serversinc\SshRunner\Tests\Feature;

use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Enums\FailureStrategy;
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\Tests\Actions\CheckCommandExists;
use Serversinc\SshRunner\Tests\Actions\CheckOperatingSystem;
use Serversinc\SshRunner\Tests\Actions\CreateTempFile;
use Serversinc\SshRunner\Tests\Actions\FailingAction;
use Serversinc\SshRunner\Tests\Actions\GetCurrentUser;
use Serversinc\SshRunner\Tests\Actions\ListDirectory;
use Serversinc\SshRunner\Tests\Fixtures\IntegrationTestServer;
use Serversinc\SshRunner\Tests\TestCase;

/**
 * Integration tests that run against the Docker SSH container
 *
 * These tests require the Docker environment to be running:
 * docker-compose up -d ssh-server
 */
class SshIntegrationTest extends TestCase
{
    private ?SshServer $server = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure for host-based testing (connecting to Docker container on localhost:2222)
        // Note: SshConnection will automatically detect and use the Docker container IP
        if (! getenv('SSH_TEST_HOST')) {
            putenv('SSH_TEST_HOST=localhost');
            putenv('SSH_TEST_PORT=2222');
            putenv('SSH_TEST_USER=testuser');
            putenv('SSH_TEST_KEY_PATH=docker/ssh-keys/id_rsa');
        }

        $this->server = IntegrationTestServer::fromEnvironment();
    }

    /** @test */
    public function it_can_connect_to_ssh_server(): void
    {
        $connection = SshConnection::for($this->server);
        $result = $connection->execute(new GetCurrentUser);

        // Verify SSH connection works and returns expected username
        $this->assertTrue($result->success);
        $this->assertStringContainsString(getenv('SSH_TEST_USER'), $result->output);
    }

    /** @test */
    public function it_can_execute_a_simple_command(): void
    {
        $connection = SshConnection::for($this->server);
        $result = $connection->execute(new CheckOperatingSystem);

        // Just verify the command executed successfully - don't test exact output format
        // which may vary between OS versions
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->output);
    }

    /** @test */
    public function it_can_check_if_commands_exist(): void
    {
        $connection = SshConnection::for($this->server);

        // Test common commands that should exist
        // which returns the path to the command on success
        $commands = ['ssh', 'ls', 'cat', 'echo'];

        foreach ($commands as $command) {
            $result = $connection->execute(new CheckCommandExists($command));
            $this->assertTrue($result->success, "Command {$command} should exist");
            $this->assertEquals(0, $result->exitCode);
            // which returns a path like /bin/ls or /usr/bin/ssh
            $this->assertMatchesRegularExpression('#^/[\w/]+'.$command.'$#m', trim($result->output));
        }
    }

    /** @test */
    public function it_can_list_directory_contents(): void
    {
        $connection = SshConnection::for($this->server);
        $result = $connection->execute(new ListDirectory('/'));

        // Verify listing succeeded and contains expected root directory entries
        // Root should have directories like bin, etc, home, tmp, usr, var
        $this->assertTrue($result->success);
        $this->assertStringContainsString('bin', $result->output);
        $this->assertStringContainsString('etc', $result->output);
    }

    /** @test */
    public function it_can_run_a_pipeline_of_actions(): void
    {
        $connection = SshConnection::for($this->server);

        $result = $connection->pipeline()
            ->run(new GetCurrentUser)
            ->run(new CheckOperatingSystem)
            ->run(new ListDirectory)
            ->execute();

        $this->assertTrue($result->success);
        $this->assertCount(3, $result->results);
    }

    /** @test */
    public function it_handles_failed_commands(): void
    {
        $connection = SshConnection::for($this->server);
        $result = $connection->execute(new FailingAction);

        $this->assertFalse($result->success);
        $this->assertEquals(1, $result->exitCode);
    }

    /** @test */
    public function it_handles_nonexistent_commands(): void
    {
        $connection = SshConnection::for($this->server);

        // Try to execute a command that doesn't exist
        $result = $connection->execute(new CheckCommandExists('this_command_definitely_does_not_exist_12345'));

        $this->assertFalse($result->success);
        $this->assertNotEquals(0, $result->exitCode);
        // which returns exit code 1 when command not found
        $this->assertEquals(1, $result->exitCode);
    }

    /** @test */
    public function it_stops_on_failure_with_stop_strategy(): void
    {
        $connection = SshConnection::for($this->server);

        $result = $connection->pipeline()
            ->onFailure(FailureStrategy::STOP)
            ->run(new GetCurrentUser)
            ->run(new FailingAction)
            ->run(new CheckOperatingSystem) // Should not execute
            ->execute();

        $this->assertFalse($result->success);
        $this->assertCount(2, $result->results); // Only first two run
    }

    /** @test */
    public function it_continues_on_failure_with_continue_strategy(): void
    {
        $connection = SshConnection::for($this->server);

        $result = $connection->pipeline()
            ->onFailure(FailureStrategy::CONTINUE)
            ->run(new FailingAction)
            ->run(new GetCurrentUser)
            ->run(new CheckOperatingSystem)
            ->execute();

        $this->assertFalse($result->success);
        $this->assertCount(3, $result->results); // All three run
    }

    /** @test */
    public function it_can_rollback_completed_actions(): void
    {
        $connection = SshConnection::for($this->server);
        $createAction = new CreateTempFile('test content for rollback');

        $result = $connection->pipeline()
            ->onFailure(FailureStrategy::ROLLBACK)
            ->run($createAction)
            ->run(new FailingAction)
            ->execute();

        $this->assertFalse($result->success);

        // Verify the file was actually deleted by trying to list it
        $tempFile = $createAction->getTempFile();
        $checkResult = $connection->execute(new ListDirectory($tempFile));
        // ls should fail because file doesn't exist anymore
        $this->assertFalse($checkResult->success);
    }

    /** @test */
    public function it_can_check_docker_environment(): void
    {
        $connection = SshConnection::for($this->server);

        // Verify we're running in Alpine Linux (the ssh-server image)
        $result = $connection->execute(new CheckOperatingSystem);

        $this->assertTrue($result->success);
        $this->assertStringContainsString('Alpine', $result->output);
    }
}
