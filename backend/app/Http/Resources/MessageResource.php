<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'agent_id' => $this->agent_id,
            'agent_name' => $this->whenLoaded('agent', function () {
                return $this->agent->name;
            }),
            'type' => $this->type,
            'content' => $this->content,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];

        if ($this->relationLoaded('agent')) {
            $data['agent'] = [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
            ];
        }

        return $data;
    }
}
