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
    ];

    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'max_capacity' => 'integer',
        'current_tasks' => 'integer',
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
        if ($this->status === 'busy' && $this->current_tasks < $this->max_capacity) {
            $this->update(['status' => 'online']);
        }
    }
}
