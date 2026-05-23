<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 页面标题 -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">仪表盘</h1>
                    <p class="mt-1 text-sm text-gray-500">AI Agent 任务平台概览</p>
                </div>

                <!-- 统计卡片 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-3xl">⏳</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.pending }}</div>
                        <div class="text-sm text-gray-500 mt-0.5">待处理</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-3xl">🔄</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.processing }}</div>
                        <div class="text-sm text-gray-500 mt-0.5">进行中</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-3xl">✅</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.completed }}</div>
                        <div class="text-sm text-gray-500 mt-0.5">已完成</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-3xl">❌</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.failed }}</div>
                        <div class="text-sm text-gray-500 mt-0.5">失败</div>
                    </div>
                </div>

                <!-- 快捷入口 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <Link href="/agents" class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-5 sm:p-6 hover:shadow-md transition-shadow group">
                        <div class="flex items-center space-x-4">
                            <span class="text-3xl sm:text-4xl group-hover:scale-110 transition-transform">🤖</span>
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Agent 管理</h3>
                                <p class="text-sm text-gray-500">注册、配置和监控 AI Agent</p>
                            </div>
                        </div>
                    </Link>
                    <Link href="/tasks" class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200 p-5 sm:p-6 hover:shadow-md transition-shadow group">
                        <div class="flex items-center space-x-4">
                            <span class="text-3xl sm:text-4xl group-hover:scale-110 transition-transform">📋</span>
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900">任务管理</h3>
                                <p class="text-sm text-gray-500">创建、管理和指派 AI 任务</p>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- 活动日志 -->
                <div class="mt-6 sm:mt-8">
                    <ActivityLog :activities="activities" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ActivityLog from '@/Components/ActivityLog.vue'

const props = defineProps({
    activities: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ pending: 0, processing: 0, completed: 0, failed: 0 }) },
})
</script>
