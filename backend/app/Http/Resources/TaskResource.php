<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $this->status,
            'assigned_agent_id' => $this->assigned_agent_id,
            'agent' => $this->whenLoaded('agent', function () {
                return [
                    'id' => $this->agent->id,
                    'name' => $this->agent->name,
                    'status' => $this->agent->status,
                    'current_tasks' => $this->agent->current_tasks,
                    'max_capacity' => $this->agent->max_capacity,
                    'is_available' => $this->agent->isAvailable(),
                ];
            }),
            'messages' => $this->whenLoaded('messages', function () {
                return $this->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'type' => $message->type,
                        'content' => $message->content,
                        'agent_id' => $message->agent_id,
                        'created_at' => $message->created_at?->toISOString(),
                    ];
                });
            }),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
