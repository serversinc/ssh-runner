<?php

namespace Serversinc\SshRunner;

use Illuminate\Support\Collection;
use Serversinc\SshRunner\Actions\ExecuteScriptAction;
use Serversinc\SshRunner\Contracts\SshActionInterface;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Enums\FailureStrategy;
use Serversinc\SshRunner\Scripts\BaseScript;
use Serversinc\SshRunner\Models\SshActionLog;
use Serversinc\SshRunner\Models\SshPipelineLog;
use Serversinc\SshRunner\Results\ActionResult;
use Serversinc\SshRunner\Results\PipelineResult;
use Spatie\Ssh\Ssh;

class SshPipeline
{
    private readonly Collection $actions;

    private FailureStrategy $failureStrategy = FailureStrategy::STOP;

    private function __construct(
        private readonly SshServer $server,
        private readonly Ssh $ssh,
    ) {
        $this->actions = collect();
    }

    public static function for(SshServer $server, Ssh $ssh): static
    {
        return new static($server, $ssh);
    }

    public function onFailure(FailureStrategy $strategy): static
    {
        $this->failureStrategy = $strategy;

        return $this;
    }

    public function run(SshActionInterface $action): static
    {
        $this->actions->push($action);

        return $this;
    }

    public function script(BaseScript $script): static
    {
        return $this->run(new ExecuteScriptAction($script));
    }

    public function execute(): PipelineResult
    {
        $startedAt = now();
        $results = collect();
        $stoppedEarly = false;
        $completed = collect();
        $success = true;

        // Only log to database if the server has a model key (i.e., it's an Eloquent model)
        $run = null;
        if (method_exists($this->server, 'getKey') && $this->server->getKey()) {
            $run = SshPipelineLog::create([
                'server_id' => $this->server->getKey(),
                'server_type' => $this->server->getMorphClass(),
                'success' => false,
                'started_at' => $startedAt,
            ]);
        }

        foreach ($this->actions as $action) {
            $result = $action->handle($this->server, $this->ssh);
            $results->push($result);

            if ($run) {
                SshActionLog::create([
                    'ssh_pipeline_log_id' => $run->id,
                    'action' => $result->action,
                    'success' => $result->success,
                    'output' => $result->output,
                    'error_output' => $result->errorOutput,
                    'exit_code' => $result->exitCode,
                    'executed_at' => $result->executedAt,
                ]);
            }

            if ($result->failed()) {
                if ($this->failureStrategy === FailureStrategy::ROLLBACK) {
                    $completed->reverse()->each(
                        fn (SshActionInterface $a) => $a->undo($this->server, $this->ssh)
                    );
                }

                if ($this->failureStrategy !== FailureStrategy::CONTINUE) {
                    $stoppedEarly = true;
                    break;
                }
            }

            $completed->push($action);
        }

        $success = ! $results->some(fn (ActionResult $r): bool => $r->failed());
        $completedAt = now();

        if ($run) {
            $run->update([
                'success' => $success,
                'stopped_early' => $stoppedEarly,
                'completed_at' => $completedAt,
            ]);
        }

        return new PipelineResult(
            success: $success,
            results: $results,
            startedAt: $startedAt->toImmutable(),
            completedAt: $completedAt->toImmutable(),
        );
    }
}
