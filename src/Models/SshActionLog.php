<?php

namespace Serversinc\SshRunner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SshActionLog extends Model
{
    protected $fillable = [
        'ssh_pipeline_log_id',
        'action',
        'success',
        'output',
        'error_output',
        'exit_code',
        'executed_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'exit_code' => 'integer',
        'executed_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(SshPipelineLog::class, 'ssh_pipeline_log_id');
    }

    public function failed(): bool
    {
        return ! $this->success;
    }
}
