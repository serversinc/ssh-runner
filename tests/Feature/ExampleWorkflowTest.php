<?php

namespace Serversinc\SshRunner\Tests\Feature;

use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\Tests\Actions\ListDirectory;
use Serversinc\SshRunner\Tests\Actions\MakeDirectory;
use Serversinc\SshRunner\Tests\Actions\RemoveDirectory;
use Serversinc\SshRunner\Tests\Fixtures\IntegrationTestServer;
use Serversinc\SshRunner\Tests\TestCase;

/**
 * Example integration tests demonstrating SSH action workflow
 *
 * These tests run on the host machine and SSH into a Docker container
 *
 * Prerequisites:
 *   1. Start the SSH server: docker compose up -d ssh-server
 *   2. Wait for it to be healthy: docker compose ps
 *   3. Run tests: vendor/bin/phpunit tests/Feature/ExampleWorkflowTest.php
 *
 * To test manually:
 *   docker compose up -d ssh-server
 *   ssh -p 2222 -i docker/ssh-keys/id_rsa -o StrictHostKeyChecking=no testuser@localhost
 */
class ExampleWorkflowTest extends TestCase
{
    private ?SshServer $server = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure for host-based testing (connecting to Docker container on localhost:2222)
        // Note: SshConnection will automatically detect and use the Docker container IP
        putenv('SSH_TEST_HOST=localhost');
        putenv('SSH_TEST_PORT=2222');
        putenv('SSH_TEST_USER=testuser');
        putenv('SSH_TEST_KEY_PATH=docker/ssh-keys/id_rsa');

        $this->server = IntegrationTestServer::fromEnvironment();
    }

    /** @test */
    public function it_can_create_and_list_directories(): void
    {
        // Connect to the SSH server
        $connection = SshConnection::for($this->server);

        // Create a unique test directory name
        $testDir = '/tmp/test_dir_'.uniqid();

        try {
            // Step 1: Create the directory
            $mkdirResult = $connection->execute(new MakeDirectory($testDir));

            $this->assertTrue($mkdirResult->success, "Failed to create directory: {$mkdirResult->errorOutput}");

            // Step 2: List the parent directory and verify our directory exists
            $lsResult = $connection->execute(new ListDirectory('/tmp'));

            $this->assertTrue($lsResult->success, "Failed to list directory: {$lsResult->errorOutput}");
            $this->assertStringContainsString(basename($testDir), $lsResult->output);
        } finally {
            // Cleanup: Remove the test directory
            $connection->execute(new RemoveDirectory($testDir));
        }
    }

    /** @test */
    public function it_can_create_nested_directories(): void
    {
        $connection = SshConnection::for($this->server);

        // Create a nested directory structure
        $nestedDir = '/tmp/test_parent_'.uniqid();
        $fullPath = $nestedDir.'/test_child/grandchild';

        try {
            // This should work with recursive flag (default)
            $result = $connection->execute(new MakeDirectory($fullPath, true));

            $this->assertTrue($result->success, "Failed to create nested directories: {$result->errorOutput}");

            // Verify the deepest directory exists by listing it
            $lsResult = $connection->execute(new ListDirectory($nestedDir));

            $this->assertTrue($lsResult->success);
            $this->assertStringContainsString('test_child', $lsResult->output);
        } finally {
            // Cleanup: Remove the entire test tree
            $connection->execute(new RemoveDirectory($nestedDir));
        }
    }

    /** @test */
    public function it_fails_to_create_nested_directories_without_recursive_flag(): void
    {
        $connection = SshConnection::for($this->server);

        // Try to create a nested directory without recursive flag
        // This should fail because parent doesn't exist
        $nestedDir = '/tmp/nonexistent_parent_'.uniqid().'/child';

        $result = $connection->execute(new MakeDirectory($nestedDir, false));

        // Should fail because parent directory doesn't exist
        $this->assertFalse($result->success);
        $this->assertNotEquals(0, $result->exitCode);
    }

    /** @test */
    public function it_demonstrates_action_workflow(): void
    {
        // This test shows the typical workflow:
        // 1. Create an action (e.g., MakeDirectory)
        // 2. Connect to SSH server
        // 3. Execute the action
        // 4. Verify the result

        $connection = SshConnection::for($this->server);
        $testDir = '/tmp/demo_workflow_'.uniqid();

        try {
            // Create action
            $action = new MakeDirectory($testDir, true);

            // Execute
            $result = $connection->execute($action);

            // Assert
            $this->assertTrue($result->success);
            $this->assertEquals(0, $result->exitCode);

            // The output from mkdir is typically empty on success
            $this->assertEmpty($result->output);
        } finally {
            // Cleanup
            $connection->execute(new RemoveDirectory($testDir));
        }
    }

    /** @test */
    public function it_shows_complete_ssh_interaction(): void
    {
        // This demonstrates the full cycle:
        // - SSH connection
        // - Command execution
        // - Output verification

        $connection = SshConnection::for($this->server);
        $testPath = '/tmp/complete_test_'.uniqid();

        try {
            // Create a directory
            $mkdir = new MakeDirectory($testPath);
            $mkdirResult = $connection->execute($mkdir);

            $this->assertTrue($mkdirResult->success, 'Directory creation should succeed');

            // List it
            $ls = new ListDirectory(dirname($testPath));
            $lsResult = $connection->execute($ls);

            $this->assertTrue($lsResult->success, 'List should succeed');
            $this->assertStringContainsString(basename($testPath), $lsResult->output);
        } finally {
            // Cleanup
            $connection->execute(new RemoveDirectory($testPath));
        }
    }
}
