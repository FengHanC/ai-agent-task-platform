<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->timestamp('last_heartbeat_at')->nullable()->after('metadata')
                ->comment('最后一次心跳时间，用于检测 Agent 是否在线');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('last_heartbeat_at');
        });
    }
};
