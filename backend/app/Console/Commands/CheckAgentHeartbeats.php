<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;

class CheckAgentHeartbeats extends Command
{
    protected $signature = 'agents:check-heartbeats
                            {--timeout=120 : 心跳超时秒数，超过此时间未收到心跳则标记离线}';

    protected $description = '检查 Agent 心跳超时，下线超时的 Agent';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');

        $staleAgents = Agent::whereIn('status', ['online', 'busy'])
            ->get()
            ->filter(fn (Agent $agent) => $agent->isHeartbeatExpired($timeout));

        $count = 0;
        foreach ($staleAgents as $agent) {
            $this->line("  下线 Agent #{$agent->id}: {$agent->name}");
            $agent->markOffline();
            $count++;
        }

        $this->info("检查完成。下线了 {$count} 个超时 Agent（超时阈值: {$timeout}s）");

        return Command::SUCCESS;
    }
}
