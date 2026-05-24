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
                    <div class="p-4 sm:p-6 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex items-center space-x-3 sm:space-x-4">
                                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg sm:text-xl flex-shrink-0">
                                    {{ agent.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 break-words">{{ agent.name }}</h1>
                                    <p v-if="agent.description" class="mt-1 text-sm text-gray-500 break-words">{{ agent.description }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 self-start flex-shrink-0">
                                <span :class="statusBadgeClass(agent.status)">
                                    {{ statusLabel(agent.status) }}
                                </span>
                                <button
                                    @click="toggleStatus"
                                    :disabled="toggling"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 disabled:text-gray-300 bg-indigo-50 px-2.5 py-1 rounded-lg"
                                >
                                    {{ toggling ? '...' : (agent.status === 'online' ? '下线' : '上线') }}
                                </button>
                                <Link
                                    :href="`/agents/${agent.id}/edit`"
                                    class="text-xs font-medium text-gray-600 hover:text-gray-800 bg-gray-100 px-2.5 py-1 rounded-lg hover:bg-gray-200"
                                >
                                    编辑
                                </Link>
                                <button
                                    @click="deleteAgent"
                                    class="text-xs font-medium text-red-600 hover:text-red-800 bg-red-50 px-2.5 py-1 rounded-lg hover:bg-red-100"
                                >
                                    删除
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 信息详情 -->
                    <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
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
                                    <dd class="text-sm text-gray-900">{{ formatDate(agent.created_at) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">更新时间</dt>
                                    <dd class="text-sm text-gray-900">{{ formatDate(agent.updated_at) }}</dd>
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
                                    class="inline-flex items-center px-2 sm:px-3 py-1 rounded-lg text-xs sm:text-sm font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200"
                                >
                                    {{ capabilityLabel(cap) }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-400">暂无能力标签</p>
                        </div>
                    </div>
                </div>

                <!-- 关联任务 -->
                <div class="mt-6 sm:mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">最近任务</h2>
                    </div>
                    <div v-if="agent.tasks && agent.tasks.length > 0" class="divide-y divide-gray-100">
                        <div v-for="task in agent.tasks" :key="task.id" class="px-4 sm:px-6 py-3.5 sm:py-4 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                                <span class="text-base sm:text-lg flex-shrink-0">{{ typeIcon(task.type) }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ task.title }}</p>
                                    <p class="text-xs text-gray-500">{{ formatDate(task.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1.5 sm:space-x-2 flex-shrink-0">
                                <span :class="priorityBadge(task.priority)" class="text-xs">{{ task.priority }}</span>
                                <span :class="taskStatusBadge(task.status)" class="text-xs">{{ taskStatusLabel(task.status) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-4 sm:px-6 py-6 sm:py-8 text-center">
                        <p class="text-sm text-gray-400">暂无关联任务</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

const { success: toastSuccess, error: toastError } = useToast()
const toggling = ref(false)

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

async function toggleStatus() {
    const newStatus = props.agent.status === 'online' ? 'offline' : 'online'
    if (toggling.value) return
    toggling.value = true

    try {
        const res = await fetch(`/api/v1/agents/${props.agent.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status: newStatus }),
        })

        const data = await res.json()

        if (!res.ok) {
            toastError(data.message || '操作失败')
            return
        }

        const label = newStatus === 'online' ? '上线' : '下线'
        toastSuccess(`${props.agent.name} 已${label}`)
        router.reload({ preserveState: true })
    } catch (e) {
        toastError('网络错误，请重试')
    } finally {
        toggling.value = false
    }
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

async function deleteAgent() {
    const name = props.agent.name
    if (!confirm(`确定删除 Agent "${name}"？${props.agent.current_tasks > 0 ? ' 当前有任务进行中。' : ''}`)) return

    try {
        const res = await fetch(`/api/v1/agents/${props.agent.id}`, { method: 'DELETE' })
        if (!res.ok) {
            const data = await res.json()
            alert(data.message || '删除失败')
            return
        }
        window.location.href = '/agents'
    } catch (e) {
        alert('网络错误，请重试')
    }
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

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    return `${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
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
