<?php

namespace Serversinc\SshRunner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SshPipelineLog extends Model
{
    protected $table = 'ssh_pipeline_logs';

    protected $fillable = [
        'server_id',
        'server_type',
        'success',
        'stopped_early',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'stopped_early' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function server(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(SshActionLog::class);
    }

    public function failed(): bool
    {
        return ! $this->success;
    }
}
