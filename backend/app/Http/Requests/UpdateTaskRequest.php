<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'type' => 'sometimes|string|in:code,analysis,design,review,other',
            'priority' => 'sometimes|string|in:low,medium,high,urgent',
            'status' => 'sometimes|string|in:pending,processing,completed,failed,cancelled',
            'assigned_agent_id' => [
                'nullable',
                'integer',
                Rule::exists('agents', 'id')->whereNull('deleted_at'),
            ],
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '任务标题不能为空',
            'type.in' => '任务类型无效，可选值: code, analysis, design, review, other',
            'priority.in' => '优先级无效，可选值: low, medium, high, urgent',
            'status.in' => '状态无效，可选值: pending, processing, completed, failed, cancelled',
            'assigned_agent_id.exists' => '指定的 Agent 不存在或已被删除',
        ];
    }
}
