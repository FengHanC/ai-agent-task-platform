<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 预置用户
        User::factory()->create([
            'name' => '管理员',
            'email' => 'admin@example.com',
        ]);

        // 预置 AI Agent（online 状态，可直接接任务）
        $agents = [
            [
                'name' => '代码助手',
                'description' => '擅长代码生成、代码审查和调试',
                'capabilities' => ['code', 'review'],
                'status' => 'online',
                'max_capacity' => 3,
            ],
            [
                'name' => '分析专家',
                'description' => '擅长数据分析、报告生成和问题分析',
                'capabilities' => ['analysis', 'documentation'],
                'status' => 'online',
                'max_capacity' => 3,
            ],
            [
                'name' => '测试工程师',
                'description' => '擅长测试用例编写、自动化测试和调试',
                'capabilities' => ['testing', 'debugging'],
                'status' => 'online',
                'max_capacity' => 3,
            ],
            [
                'name' => '设计顾问',
                'description' => '擅长 UI/UX 设计、原型制作和设计评审',
                'capabilities' => ['design', 'review'],
                'status' => 'online',
                'max_capacity' => 2,
            ],
            [
                'name' => '数据处理员',
                'description' => '擅长数据清洗、ETL 流程和技术文档',
                'capabilities' => ['data_processing', 'documentation'],
                'status' => 'online',
                'max_capacity' => 2,
            ],
        ];

        foreach ($agents as $data) {
            Agent::create($data);
        }
    }
}
