<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class CheckTaskTimeouts extends Command
{
    protected $signature = 'tasks:check-timeouts
                            {--timeout=30 : 处理超时分钟数，超过此时间仍为 processing 则标记失败}
                            {--timeout-at-column=started_at : 用于判断超时的时间戳字段名}';

    protected $description = '检查处理中超时的任务，自动标记为失败';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeoutMinutes = (int) $this->option('timeout');
        $timeoutColumn = $this->option('timeout-at-column');

        $cutoff = now()->subMinutes($timeoutMinutes);

        $staleTasks = Task::where('status', 'processing')
            ->where($timeoutColumn, '<=', $cutoff)
            ->get();

        $count = 0;
        foreach ($staleTasks as $task) {
            $this->line("  超时任务 #{$task->id}: {$task->title}");
            $task->markTimedOut();
            $count++;
        }

        $this->info("检查完成。标记了 {$count} 个超时任务（超时阈值: {$timeoutMinutes}min）");

        return Command::SUCCESS;
    }
}
