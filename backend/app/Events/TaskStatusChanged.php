<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * The task instance.
     */
    public Task $task;

    /**
     * The previous status.
     */
    public string $oldStatus;

    /**
     * The new status.
     */
    public string $newStatus;

    /**
     * The system message describing the change.
     */
    public string $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, string $oldStatus, string $newStatus, string $message = '')
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->message = $message ?: "任务状态从 {$oldStatus} 变更为 {$newStatus}";
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tasks.{$this->task->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'TaskStatusChanged';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
        ];
    }
}
