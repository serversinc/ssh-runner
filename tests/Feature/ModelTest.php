<?php

namespace Serversinc\SshRunner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Serversinc\SshRunner\Models\SshActionLog;
use Serversinc\SshRunner\Models\SshPipelineLog;
use Serversinc\SshRunner\Tests\Fixtures\FakeServer;
use Serversinc\SshRunner\Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_pipeline_log(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $log = SshPipelineLog::create([
            'server_id' => $server->id,
            'server_type' => FakeServer::class,
            'success' => true,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(1),
        ]);

        $this->assertDatabaseHas('ssh_pipeline_logs', [
            'id' => $log->id,
            'success' => true,
        ]);
    }

    /** @test */
    public function it_can_create_an_action_log(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $pipelineLog = SshPipelineLog::create([
            'server_id' => $server->id,
            'server_type' => FakeServer::class,
            'success' => true,
            'started_at' => now(),
        ]);

        $actionLog = SshActionLog::create([
            'ssh_pipeline_log_id' => $pipelineLog->id,
            'action' => 'TestAction',
            'success' => true,
            'output' => 'Test output',
            'error_output' => null,
            'exit_code' => 0,
            'executed_at' => now(),
        ]);

        $this->assertDatabaseHas('ssh_action_logs', [
            'id' => $actionLog->id,
            'action' => 'TestAction',
        ]);
    }

    /** @test */
    public function action_log_belongs_to_pipeline_log(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $pipelineLog = SshPipelineLog::create([
            'server_id' => $server->id,
            'server_type' => FakeServer::class,
            'success' => true,
            'started_at' => now(),
        ]);

        $actionLog = SshActionLog::create([
            'ssh_pipeline_log_id' => $pipelineLog->id,
            'action' => 'TestAction',
            'success' => true,
            'exit_code' => 0,
            'executed_at' => now(),
        ]);

        $this->assertInstanceOf(SshPipelineLog::class, $actionLog->run);
        $this->assertEquals($pipelineLog->id, $actionLog->run->id);
    }

    /** @test */
    public function pipeline_log_has_many_action_logs(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $pipelineLog = SshPipelineLog::create([
            'server_id' => $server->id,
            'server_type' => FakeServer::class,
            'success' => true,
            'started_at' => now(),
        ]);

        SshActionLog::create([
            'ssh_pipeline_log_id' => $pipelineLog->id,
            'action' => 'Action1',
            'success' => true,
            'exit_code' => 0,
            'executed_at' => now(),
        ]);

        SshActionLog::create([
            'ssh_pipeline_log_id' => $pipelineLog->id,
            'action' => 'Action2',
            'success' => false,
            'exit_code' => 1,
            'executed_at' => now(),
        ]);

        $this->assertCount(2, $pipelineLog->actionLogs);
    }

    /** @test */
    public function failed_method_returns_true_for_failed_log(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $pipelineLog = SshPipelineLog::create([
            'server_id' => $server->id,
            'server_type' => FakeServer::class,
            'success' => false,
            'started_at' => now(),
        ]);

        $actionLog = SshActionLog::create([
            'ssh_pipeline_log_id' => $pipelineLog->id,
            'action' => 'TestAction',
            'success' => false,
            'exit_code' => 1,
            'executed_at' => now(),
        ]);

        $this->assertTrue($pipelineLog->failed());
        $this->assertTrue($actionLog->failed());
    }

    /** @test */
    public function failed_method_returns_false_for_successful_log(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $pipelineLog = SshPipelineLog::create([
            'server_id' => $server->id,
            'server_type' => FakeServer::class,
            'success' => true,
            'started_at' => now(),
        ]);

        $actionLog = SshActionLog::create([
            'ssh_pipeline_log_id' => $pipelineLog->id,
            'action' => 'TestAction',
            'success' => true,
            'exit_code' => 0,
            'executed_at' => now(),
        ]);

        $this->assertFalse($pipelineLog->failed());
        $this->assertFalse($actionLog->failed());
    }
}
