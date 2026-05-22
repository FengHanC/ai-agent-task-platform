<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:agents,name',
            'description' => 'nullable|string|max:1000',
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string|max:100',
            'max_capacity' => 'nullable|integer|min:1|max:100',
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
            'name.required' => 'Agent 名称不能为空',
            'name.unique' => '该 Agent 名称已被使用',
            'max_capacity.integer' => '最大并发数必须为整数',
            'max_capacity.min' => '最大并发数不能小于 1',
            'max_capacity.max' => '最大并发数不能超过 100',
        ];
    }
}
