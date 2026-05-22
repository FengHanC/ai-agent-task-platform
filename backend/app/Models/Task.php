<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'type',
        'priority',
        'status',
        'assigned_agent_id',
        'created_by',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function assignTo(Agent $agent): void
    {
        $this->update([
            'assigned_agent_id' => $agent->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);
        $agent->incrementTaskCount();
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        if ($this->agent) {
            $this->agent->decrementTaskCount();
        }
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
        if ($this->agent) {
            $this->agent->decrementTaskCount();
        }
    }

    public function markCancelled(): void
    {
        if ($this->status === 'processing' && $this->agent) {
            $this->agent->decrementTaskCount();
        }
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }
}
