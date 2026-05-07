<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('ssh_port')->nullable();
            $table->string('ssh_user');
            $table->string('ssh_key_path')->nullable();
            $table->text('ssh_key_contents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
