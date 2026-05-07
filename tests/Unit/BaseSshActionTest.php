<?php

namespace Serversinc\SshRunner\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

class BaseSshActionTest extends TestCase
{
    /** @test */
    public function it_implements_ssh_action_interface(): void
    {
        $action = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: true,
                    output: '',
                    errorOutput: '',
                    exitCode: 0,
                    action: self::class,
                    executedAt: now()->toImmutable()
                );
            }
        };

        $this->assertInstanceOf(BaseSshAction::class, $action);
    }

    /** @test */
    public function undo_is_noop_by_default(): void
    {
        $action = new class extends BaseSshAction
        {
            public function handle(SshServer $server, Ssh $ssh): ActionResult
            {
                return new ActionResult(
                    success: true,
                    output: '',
                    errorOutput: '',
                    exitCode: 0,
                    action: self::class,
                    executedAt: now()->toImmutable()
                );
            }
        };

        // Should not throw
        $action->undo(
            $this->createMock(SshServer::class),
            $this->createMock(Ssh::class)
        );

        $this->assertTrue(true); // If we get here, no exception was thrown
    }
}
