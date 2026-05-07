<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ssh_action_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ssh_pipeline_log_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->boolean('success');
            $table->longText('output')->nullable();
            $table->longText('error_output')->nullable();
            $table->integer('exit_code');
            $table->timestamp('executed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('ssh_action_logs');
    }
};
