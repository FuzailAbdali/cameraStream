<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('ip_address')->nullable();
            $table->string('external_ip')->nullable();
            $table->unsignedInteger('port')->default(554);
            $table->unsignedInteger('forwarded_port')->nullable();
            $table->string('username');
            $table->text('password');
            $table->text('rtsp_url')->nullable();
            $table->string('stream_path')->nullable();
            $table->string('stream_status')->default('idle');
            $table->timestamp('last_stream_started_at')->nullable();
            $table->timestamp('last_stream_heartbeat_at')->nullable();
            $table->timestamp('last_stream_failed_at')->nullable();
            $table->text('last_stream_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
