<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('camera_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedInteger('duration')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
