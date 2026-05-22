<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Flash 消息 -->
                <div v-if="$page.props.flash?.success" class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ $page.props.flash.success }}
                </div>

                <!-- 统计卡片 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">⏳</span>
                        </div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.pending }}</div>
                        <div class="text-sm text-gray-500">待处理</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">🔄</span>
                        </div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.processing }}</div>
                        <div class="text-sm text-gray-500">进行中</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">✅</span>
                        </div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.completed }}</div>
                        <div class="text-sm text-gray-500">已完成</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">❌</span>
                        </div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.failed }}</div>
                        <div class="text-sm text-gray-500">失败</div>
                    </div>
                </div>

                <!-- 快捷入口 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Link href="/agents" class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow group">
                        <div class="flex items-center space-x-4">
                            <span class="text-4xl group-hover:scale-110 transition-transform">🤖</span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Agent 管理</h3>
                                <p class="text-sm text-gray-500">注册、配置和监控 AI Agent</p>
                            </div>
                        </div>
                    </Link>
                    <Link href="/tasks" class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow group">
                        <div class="flex items-center space-x-4">
                            <span class="text-4xl group-hover:scale-110 transition-transform">📋</span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">任务管理</h3>
                                <p class="text-sm text-gray-500">创建、管理和指派 AI 任务</p>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- 最近活动 -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">最近活动</h2>
                    </div>
                    <div class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-400">暂无活动记录</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const stats = reactive({
    pending: 0,
    processing: 0,
    completed: 0,
    failed: 0,
})
</script>
