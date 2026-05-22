<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 返回链接 -->
                <Link href="/agents" class="text-sm text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
                    ← 返回 Agent 列表
                </Link>

                <!-- Agent 信息卡片 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- 头部 -->
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                                    {{ agent.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900">{{ agent.name }}</h1>
                                    <p v-if="agent.description" class="mt-1 text-sm text-gray-500">{{ agent.description }}</p>
                                </div>
                            </div>
                            <span :class="statusBadgeClass(agent.status)">
                                {{ statusLabel(agent.status) }}
                            </span>
                        </div>
                    </div>

                    <!-- 信息详情 -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 基本信息 -->
                        <div>
                            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">基本信息</h2>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">ID</dt>
                                    <dd class="text-sm text-gray-900 font-mono">#{{ agent.id }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">状态</dt>
                                    <dd>
                                        <span :class="statusBadgeClass(agent.status)">{{ statusLabel(agent.status) }}</span>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">创建时间</dt>
                                    <dd class="text-sm text-gray-900">{{ agent.created_at }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">更新时间</dt>
                                    <dd class="text-sm text-gray-900">{{ agent.updated_at }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- 任务容量 -->
                        <div>
                            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">任务容量</h2>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">当前任务数</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ agent.current_tasks }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">最大并发数</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ agent.max_capacity }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">负载率</dt>
                                    <dd class="text-sm">
                                        <span :class="loadTextClass" class="font-medium">{{ loadPercent }}%</span>
                                    </dd>
                                </div>
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            :class="loadBarClass"
                                            class="h-2 rounded-full transition-all"
                                            :style="{ width: loadPercent + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </dl>
                        </div>

                        <!-- 能力标签 -->
                        <div class="md:col-span-2">
                            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">能力标签</h2>
                            <div v-if="agent.capabilities && agent.capabilities.length > 0" class="flex flex-wrap gap-2">
                                <span
                                    v-for="cap in agent.capabilities"
                                    :key="cap"
                                    class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200"
                                >
                                    {{ capabilityLabel(cap) }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-400">暂无能力标签</p>
                        </div>
                    </div>
                </div>

                <!-- 关联任务 -->
                <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">最近任务</h2>
                    </div>
                    <div v-if="agent.tasks && agent.tasks.length > 0" class="divide-y divide-gray-100">
                        <div v-for="task in agent.tasks" :key="task.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg">{{ typeIcon(task.type) }}</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ task.title }}</p>
                                    <p class="text-xs text-gray-500">{{ task.created_at }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span :class="priorityBadge(task.priority)" class="text-xs">{{ task.priority }}</span>
                                <span :class="taskStatusBadge(task.status)" class="text-xs">{{ taskStatusLabel(task.status) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-400">暂无关联任务</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    agent: { type: Object, required: true },
})

const capabilityMap = {
    code_gen: '代码生成',
    code_review: '代码审查',
    analysis: '分析',
    design: '设计',
    testing: '测试',
    documentation: '文档',
    debugging: '调试',
    data_processing: '数据处理',
}

function capabilityLabel(value) {
    return capabilityMap[value] || value
}

function statusLabel(status) {
    const map = { online: '在线', offline: '离线', busy: '忙碌' }
    return map[status] || status
}

function statusBadgeClass(status) {
    const map = {
        online: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ring-1 ring-green-200',
        offline: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 ring-1 ring-gray-200',
        busy: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200',
    }
    return map[status] || map.offline
}

function typeIcon(type) {
    const map = { code: '💻', analysis: '📊', design: '🎨', review: '👀', other: '📋' }
    return map[type] || '📋'
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

function taskStatusLabel(status) {
    const map = { pending: '待处理', processing: '进行中', completed: '已完成', failed: '失败', cancelled: '已取消' }
    return map[status] || status
}

function taskStatusBadge(status) {
    const map = {
        pending: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
        processing: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200',
        completed: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 ring-1 ring-green-200',
        failed: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 ring-1 ring-red-200',
        cancelled: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-gray-200',
    }
    return map[status] || map.pending
}

const loadPercent = computed(() => {
    if (props.agent.max_capacity === 0) return 0
    return Math.round((props.agent.current_tasks / props.agent.max_capacity) * 100)
})

const loadTextClass = computed(() => {
    const p = loadPercent.value
    if (p >= 80) return 'text-red-600'
    if (p >= 50) return 'text-yellow-600'
    return 'text-green-600'
})

const loadBarClass = computed(() => {
    const p = loadPercent.value
    if (p >= 80) return 'bg-red-500'
    if (p >= 50) return 'bg-yellow-500'
    return 'bg-green-500'
})
</script>
