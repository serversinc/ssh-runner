<?php

namespace Serversinc\SshRunner\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery as m;
use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Enums\FailureStrategy;
use Serversinc\SshRunner\Results\ActionResult;
use Serversinc\SshRunner\SshPipeline;
use Serversinc\SshRunner\Tests\Fixtures\FakeServer;
use Serversinc\SshRunner\Tests\TestCase;
use Spatie\Ssh\Ssh;

class SshPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    private function mockSsh(): Ssh
    {
        return m::mock(Ssh::class);
    }

    /** @test */
    public function it_creates_a_pipeline_log_on_execution(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $ssh = $this->mockSsh();

        $action = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: true,
                    output: 'Success',
                    errorOutput: '',
                    exitCode: 0,
                    action: self::class,
                    executedAt: now()->toImmutable()
                );
            }
        };

        SshPipeline::for($server, $ssh)
            ->run($action)
            ->execute();

        $this->assertDatabaseHas('ssh_pipeline_logs', [
            'server_id' => $server->id,
            'server_type' => FakeServer::class,
            'success' => true,
        ]);
    }

    /** @test */
    public function it_logs_action_results(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $ssh = $this->mockSsh();

        $action = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: true,
                    output: 'Command output',
                    errorOutput: '',
                    exitCode: 0,
                    action: self::class,
                    executedAt: now()->toImmutable()
                );
            }
        };

        SshPipeline::for($server, $ssh)
            ->run($action)
            ->execute();

        $this->assertDatabaseHas('ssh_action_logs', [
            'action' => $action::class,
            'success' => true,
            'exit_code' => 0,
            'output' => 'Command output',
        ]);
    }

    /** @test */
    public function it_stops_execution_on_failure_with_stop_strategy(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $ssh = $this->mockSsh();

        $successAction = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: true,
                    output: '',
                    errorOutput: '',
                    exitCode: 0,
                    action: 'SuccessAction',
                    executedAt: now()->toImmutable()
                );
            }
        };

        $failAction = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: false,
                    output: '',
                    errorOutput: 'Command failed',
                    exitCode: 1,
                    action: 'FailAction',
                    executedAt: now()->toImmutable()
                );
            }
        };

        $result = SshPipeline::for($server, $ssh)
            ->run($successAction)
            ->run($failAction)
            ->run($successAction) // This should not execute
            ->execute();

        $this->assertFalse($result->success);
        $this->assertCount(2, $result->results); // Only 2 actions executed
    }

    /** @test */
    public function it_continues_execution_on_failure_with_continue_strategy(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $ssh = $this->mockSsh();

        $failAction = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: false,
                    output: '',
                    errorOutput: 'Error',
                    exitCode: 1,
                    action: 'FailAction',
                    executedAt: now()->toImmutable()
                );
            }
        };

        $result = SshPipeline::for($server, $ssh)
            ->onFailure(FailureStrategy::CONTINUE)
            ->run($failAction)
            ->run($failAction)
            ->execute();

        $this->assertCount(2, $result->results);
        $this->assertDatabaseHas('ssh_pipeline_logs', [
            'success' => false,
            'stopped_early' => false,
        ]);
    }

    /** @test */
    public function it_calls_undo_on_rollback_strategy(): void
    {
        $server = FakeServer::create([
            'name' => 'Test Server',
            'ip_address' => '192.168.1.1',
            'ssh_user' => 'root',
        ]);

        $ssh = $this->mockSsh();
        $undoCalled = false;

        $rollbackAction = new class($undoCalled) extends BaseSshAction
        {
            private bool $undoCalled;

            public function __construct(bool &$undoCalled)
            {
                $this->undoCalled = &$undoCalled;
            }

            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: true,
                    output: '',
                    errorOutput: '',
                    exitCode: 0,
                    action: 'RollbackAction',
                    executedAt: now()->toImmutable()
                );
            }

            public function undo(SshServer $server, Ssh $ssh): void
            {
                $this->undoCalled = true;
            }
        };

        $failAction = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: false,
                    output: '',
                    errorOutput: 'Failed',
                    exitCode: 1,
                    action: 'FailAction',
                    executedAt: now()->toImmutable()
                );
            }
        };

        SshPipeline::for($server, $ssh)
            ->onFailure(FailureStrategy::ROLLBACK)
            ->run($rollbackAction)
            ->run($failAction)
            ->execute();

        $this->assertTrue($undoCalled);
    }
}
