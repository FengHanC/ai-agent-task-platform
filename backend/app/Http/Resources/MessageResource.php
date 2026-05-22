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
            'type' => $this->type,
            'content' => $this->content,
            'created_at' => $this->created_at?->toISOString(),
        ];

        if ($this->relationLoaded('agent')) {
            $data['agent'] = [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
            ];
        }

        if ($this->metadata) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
