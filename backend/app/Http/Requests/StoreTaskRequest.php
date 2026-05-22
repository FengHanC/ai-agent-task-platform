<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'type' => 'required|string|in:code,analysis,design,review,other',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'assigned_agent_id' => 'nullable|integer|exists:agents,id',
            'created_by' => 'nullable|integer|exists:users,id',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => '任务标题不能为空',
            'type.in' => '任务类型无效，可选值: code, analysis, design, review, other',
            'priority.in' => '优先级无效，可选值: low, medium, high, urgent',
            'assigned_agent_id.exists' => '指定的 Agent 不存在',
            'created_by.exists' => '指定的用户不存在',
        ];
    }
}
