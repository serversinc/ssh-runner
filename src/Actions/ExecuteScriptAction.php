<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Actions;

use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Serversinc\SshRunner\Scripts\BaseScript;
use Spatie\Ssh\Ssh;

class ExecuteScriptAction extends BaseSshAction
{
    private array $completedSteps = [];

    public function __construct(private readonly BaseScript $script) {}

    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        $this->script->validate();

        // Enable SSH multiplexing so each step reuses the same underlying connection
        $controlPath = sys_get_temp_dir().'/ssh_runner_mux_'.getmypid().'_'.uniqid();
        $ssh->useMultiplexing($controlPath, '10m');

        foreach ($this->script->steps() as $step) {
            $result = $this->run($ssh, [$step->command]);

            if (! $result->success) {
                if ($step->critical) {
                    $this->rollback($ssh);

                    return new ActionResult(
                        success: false,
                        output: '',
                        errorOutput: "Failed at step '{$step->name}': {$result->errorOutput}",
                        exitCode: 1,
                        action: self::class,
                        executedAt: now()->toImmutable(),
                    );
                }

                continue;
            }

            $this->completedSteps[] = $step;
        }

        return new ActionResult(
            success: true,
            output: 'Script completed '.count($this->completedSteps).' steps',
            errorOutput: '',
            exitCode: 0,
            action: self::class,
            executedAt: now()->toImmutable(),
        );
    }

    public function undo(SshServer $server, Ssh $ssh): void
    {
        $this->rollback($ssh);
    }

    private function rollback(Ssh $ssh): void
    {
        foreach (array_reverse($this->completedSteps) as $step) {
            if ($step->rollback) {
                $ssh->execute([$step->rollback]);
            }
        }
    }
}
