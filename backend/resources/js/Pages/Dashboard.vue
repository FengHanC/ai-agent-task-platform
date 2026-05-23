<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 页面标题 -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">仪表盘</h1>
                    <p class="mt-1 text-sm text-gray-500">AI Agent 任务平台运行概览</p>
                </div>

                <!-- 统计卡片 -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-8">
                    <!-- 待处理 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-500">待处理</span>
                            <span class="text-xl">⏳</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-gray-900">{{ stats.pending }}</div>
                    </div>

                    <!-- 进行中 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-500">进行中</span>
                            <span class="text-xl">🔄</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-blue-600">{{ stats.processing }}</div>
                    </div>

                    <!-- 在线 Agent -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-500">在线 Agent</span>
                            <span class="text-xl">🤖</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-green-600">
                            {{ stats.online_agents }}
                            <span class="text-sm text-gray-400 font-normal">/ {{ stats.total_agents }}</span>
                        </div>
                    </div>

                    <!-- 今日完成 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-500">今日完成</span>
                            <span class="text-xl">✅</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-green-600">{{ stats.today_completed }}</div>
                    </div>

                    <!-- 失败 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-500">失败</span>
                            <span class="text-xl">❌</span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-red-600">{{ stats.failed }}</div>
                    </div>
                </div>

                <!-- 主内容区：待处理任务 + 最近活动 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- 待处理任务 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-900">📋 待处理任务</h2>
                            <Link
                                href="/tasks/create"
                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                新建
                            </Link>
                        </div>

                        <div v-if="pendingTasks.length > 0" class="divide-y divide-gray-50">
                            <div
                                v-for="task in pendingTasks"
                                :key="task.id"
                                class="px-5 py-3.5 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 min-w-0">
                                        <span class="text-base flex-shrink-0">{{ typeIcon(task.type) }}</span>
                                        <div class="min-w-0">
                                            <Link
                                                :href="`/tasks/${task.id}`"
                                                class="text-sm font-medium text-gray-900 hover:text-indigo-600 block truncate"
                                            >
                                                {{ task.title }}
                                            </Link>
                                            <div class="flex items-center space-x-2 mt-0.5">
                                                <span :class="priorityBadge(task.priority)">{{ priorityLabel(task.priority) }}</span>
                                                <span class="text-xs text-gray-400">{{ typeLabel(task.type) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <Link
                                        :href="`/tasks/${task.id}`"
                                        class="flex-shrink-0 text-xs text-indigo-600 font-medium bg-indigo-50 px-2.5 py-1 rounded-lg hover:bg-indigo-100"
                                    >
                                        处理
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-5 py-8 text-center">
                            <div class="text-3xl mb-2">🎉</div>
                            <p class="text-sm text-gray-400">没有待处理的任务</p>
                        </div>
                    </div>

                    <!-- 最近活动 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h2 class="text-base font-semibold text-gray-900">📊 最近动态</h2>
                        </div>

                        <div v-if="recentActivities.length > 0" class="divide-y divide-gray-50">
                            <div
                                v-for="(activity, i) in recentActivities"
                                :key="i"
                                class="px-5 py-3 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start space-x-3">
                                    <span class="text-base flex-shrink-0 mt-0.5">{{ activityIcon(activity.type) }}</span>
                                    <div class="min-w-0 flex-1">
                                        <Link
                                            :href="`/tasks/${activity.task_id}`"
                                            class="text-sm text-gray-900 hover:text-indigo-600"
                                        >
                                            {{ activity.description }}
                                        </Link>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ formatTime(activity.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-5 py-8 text-center">
                            <div class="text-3xl mb-2">📭</div>
                            <p class="text-sm text-gray-400">暂无活动记录</p>
                        </div>
                    </div>
                </div>

                <!-- 快捷入口 -->
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link href="/agents" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow group flex items-center space-x-3">
                        <span class="text-2xl group-hover:scale-110 transition-transform">🤖</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Agent 管理</p>
                            <p class="text-xs text-gray-500">注册和监控 AI Agent</p>
                        </div>
                    </Link>
                    <Link href="/tasks" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow group flex items-center space-x-3">
                        <span class="text-2xl group-hover:scale-110 transition-transform">📋</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">任务管理</p>
                            <p class="text-xs text-gray-500">查看和管理所有任务</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    stats: { type: Object, default: () => ({ pending: 0, processing: 0, completed: 0, failed: 0, online_agents: 0, total_agents: 0, today_completed: 0 }) },
    pendingTasks: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
})

function typeIcon(type) {
    const map = { code: '💻', analysis: '📊', design: '🎨', review: '👀', other: '📋' }
    return map[type] || '📋'
}

function typeLabel(type) {
    const map = { code: '代码', analysis: '分析', design: '设计', review: '审查', other: '其他' }
    return map[type] || type
}

function priorityLabel(priority) {
    const map = { low: '低', medium: '中', high: '高', urgent: '紧急' }
    return map[priority] || priority
}

function priorityBadge(priority) {
    const map = {
        low: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-gray-200',
        medium: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200',
        high: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-50 text-orange-700 ring-1 ring-orange-200',
        urgent: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 ring-1 ring-red-200',
    }
    return map[priority] || map.medium
}

function activityIcon(type) {
    const map = {
        task_processing: '🔄',
        task_completed: '✅',
        task_failed: '❌',
        task_cancelled: '🚫',
        task_created: '📋',
    }
    return map[type] || '📌'
}

function formatTime(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    const h = pad(d.getHours())
    const m = pad(d.getMinutes())
    return `${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${h}:${m}`
}
</script>
