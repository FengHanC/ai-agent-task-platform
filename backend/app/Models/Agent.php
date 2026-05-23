<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'capabilities',
        'status',
        'max_capacity',
        'current_tasks',
        'metadata',
        'last_heartbeat_at',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'max_capacity' => 'integer',
        'current_tasks' => 'integer',
        'last_heartbeat_at' => 'datetime',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'online' && $this->current_tasks < $this->max_capacity;
    }

    public function incrementTaskCount(): void
    {
        $this->increment('current_tasks');
        if ($this->current_tasks >= $this->max_capacity) {
            $this->update(['status' => 'busy']);
        }
    }

    public function decrementTaskCount(): void
    {
        $this->decrement('current_tasks');
        // 确保不低于 0
        if ($this->current_tasks < 0) {
            $this->update(['current_tasks' => 0]);
            $this->current_tasks = 0;
        }
        if ($this->status === 'busy' && $this->current_tasks < $this->max_capacity) {
            $this->update(['status' => 'online']);
        }
    }

    /**
     * 记录心跳
     */
    public function heartbeat(): void
    {
        $this->update([
            'last_heartbeat_at' => now(),
            'status' => 'online',
        ]);
    }

    /**
     * 检查心跳是否超时（超过指定秒数未发送心跳则为超时）
     */
    public function isHeartbeatExpired(int $timeoutSeconds = 120): bool
    {
        if (!$this->last_heartbeat_at) {
            return true;
        }
        return $this->last_heartbeat_at->diffInSeconds(now()) > $timeoutSeconds;
    }

    /**
     * 标记为离线
     */
    public function markOffline(): void
    {
        $this->update([
            'status' => 'offline',
        ]);
    }
}
