<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('capabilities')->nullable()->comment('能力标签数组');
            $table->enum('status', ['online', 'offline', 'busy'])->default('offline');
            $table->unsignedInteger('max_capacity')->default(5)->comment('最大并发任务数');
            $table->unsignedInteger('current_tasks')->default(0)->comment('当前任务数');
            $table->json('metadata')->nullable()->comment('扩展字段');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
