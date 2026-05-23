<?php

namespace App\Console\Commands;

use App\Services\AgentWorkerService;
use Illuminate\Console\Command;

class AgentWorkCommand extends Command
{
    protected $signature = 'agents:work
                            {--max=3 : 本次最多处理多少个任务}
                            {--once : 只处理一次（默认），否则持续循环}
                            {--interval=5 : --once 未设置时，每次循环间隔秒数}';

    protected $description = 'Agent Worker：自动拉取待处理任务，调用 LLM 处理并写回消息';

    public function handle(AgentWorkerService $worker): int
    {
        $config = config('agent-worker.worker');
        $maxTasks = (int) $this->option('max') ?: $config['max_tasks_per_run'];

        // 单次模式：每次 schedule run 处理一批任务
        $count = $worker->run($maxTasks);
        $this->info("处理了 {$count} 个任务");

        if ($count > 0 && !$this->option('once')) {
            // --once 未设时，如果有剩余任务则立即再跑一轮
            $total = $count;
            while ($total < $maxTasks) {
                $remaining = $maxTasks - $total;
                $count = $worker->run($remaining);
                $total += $count;
                if ($count === 0) break;
            }
            if ($total > $count) {
                $this->info("累计处理了 {$total} 个任务");
            }
        }

        return Command::SUCCESS;
    }
}
