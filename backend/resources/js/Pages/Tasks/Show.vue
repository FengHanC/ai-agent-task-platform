<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 返回链接 -->
                <Link href="/tasks" class="text-sm text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
                    ← 返回任务列表
                </Link>

                <!-- 任务信息卡片 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- 头部 -->
                    <div class="p-4 sm:p-6 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex items-start space-x-3">
                                <span class="text-2xl sm:text-3xl flex-shrink-0">{{ typeIcon }}</span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 break-words">{{ task.title }}</h1>
                                        <span :class="priorityBadge(task.priority)">{{ priorityLabel(task.priority) }}</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span :class="statusBadge(task.status)">{{ statusLabel(task.status) }}</span>
                                        <span class="text-xs sm:text-sm text-gray-400">创建于 {{ formatDate(task.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 描述 -->
                    <div v-if="task.description" class="px-4 sm:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">任务描述</h2>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ task.description }}</p>
                    </div>

                    <!-- 基本信息 -->
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">基本信息</h2>
                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            <div>
                                <dt class="text-xs text-gray-500">任务类型</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ typeLabel }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">优先级</dt>
                                <dd><span :class="priorityBadge(task.priority)">{{ priorityLabel(task.priority) }}</span></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">状态</dt>
                                <dd><span :class="statusBadge(task.status)">{{ statusLabel(task.status) }}</span></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">创建时间</dt>
                                <dd class="text-sm text-gray-900">{{ formatDate(task.created_at) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- 指派区域 -->
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider">指派的 Agent</h2>
                        </div>

                        <!-- 已指派 -->
                        <div v-if="task.agent" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm flex-shrink-0">
                                    {{ task.agent.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ task.agent.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        <span :class="agentStatusBadge(task.agent.status)">{{ agentStatusLabel(task.agent.status) }}</span>
                                    </p>
                                </div>
                            </div>
                            <span v-if="task.started_at" class="text-xs text-gray-400">
                                开始于 {{ formatDate(task.started_at) }}
                            </span>
                        </div>

                        <!-- 未指派 -->
                        <div v-else>
                            <p v-if="!showAssignForm" class="text-sm text-gray-400 mb-3">尚未指派给任何 Agent</p>
                            <button
                                v-if="!showAssignForm"
                                @click="showAssignForm = true"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-100 transition-colors ring-1 ring-indigo-200"
                            >
                                📤 指派给 Agent
                            </button>
                            <div v-if="showAssignForm" class="mt-2">
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mb-3">
                                    <select
                                        v-model="assignAgentId"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white"
                                    >
                                        <option value="" disabled>选择一个 Agent...</option>
                                        <option
                                            v-for="agent in availableAgents"
                                            :key="agent.id"
                                            :value="agent.id"
                                        >
                                            {{ agent.name }}（{{ agent.current_tasks }}/{{ agent.max_capacity }}）
                                        </option>
                                    </select>
                                    <div class="flex space-x-2">
                                        <button
                                            @click="submitAssign"
                                            :disabled="assigning || !assignAgentId"
                                            :class="['flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors',
                                                assigning || !assignAgentId
                                                    ? 'bg-indigo-400 cursor-not-allowed'
                                                    : 'bg-indigo-600 hover:bg-indigo-700'
                                            ]"
                                        >
                                            {{ assigning ? '指派中...' : '指派' }}
                                        </button>
                                        <button
                                            @click="showAssignForm = false"
                                            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700"
                                        >
                                            取消
                                        </button>
                                    </div>
                                </div>
                            <!-- 错误通过 Toast 显示 -->
                                <p v-if="availableAgents.length === 0" class="text-xs text-amber-600">
                                    ⚠️ 当前没有可用的 Agent
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 状态操作 -->
                    <div v-if="showStatusActions" class="px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            <template v-if="task.status === 'pending'">
                                <button
                                    @click="updateStatus('processing')"
                                    :disabled="statusChanging"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 ring-1 ring-blue-200 transition-colors"
                                >
                                    {{ statusChanging ? '处理中...' : '▶ 开始任务' }}
                                </button>
                                <button
                                    @click="updateStatus('cancelled')"
                                    :disabled="statusChanging"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 ring-1 ring-gray-200 transition-colors"
                                >
                                    取消任务
                                </button>
                            </template>
                            <template v-if="task.status === 'processing'">
                                <button
                                    @click="updateStatus('completed')"
                                    :disabled="statusChanging"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-green-50 text-green-700 hover:bg-green-100 ring-1 ring-green-200 transition-colors"
                                >
                                    {{ statusChanging ? '处理中...' : '✅ 标记完成' }}
                                </button>
                                <button
                                    @click="updateStatus('failed')"
                                    :disabled="statusChanging"
                                    class="px-4 py-2 text-sm font-medium rounded-lg bg-red-50 text-red-700 hover:bg-red-100 ring-1 ring-red-200 transition-colors"
                                >
                                    ❌ 标记失败
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- 消息聚合面板 -->
                    <div class="px-4 sm:px-6 py-4">
                        <MessagePanel
                            :messages="task.messages || []"
                            :task-id="task.id"
                            @message-sent="refreshMessages"
                        />
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
import MessagePanel from '@/Components/MessagePanel.vue'
import { useToast } from '@/composables/useToast'

const { success: toastSuccess, error: toastError } = useToast()

const props = defineProps({
    task: { type: Object, default: () => ({ title: '', status: '', type: '', priority: '', description: '', created_at: '', started_at: '', agent: null, messages: [] }) },
    availableAgents: { type: Array, default: () => [] },
})

const showAssignForm = ref(false)
const assignAgentId = ref('')
const assigning = ref(false)
const statusChanging = ref(false)

async function submitAssign() {
    if (!assignAgentId.value || assigning.value) return
    assigning.value = true

    try {
        const res = await fetch(`/api/v1/tasks/${props.task.id}/assign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ agent_id: assignAgentId.value }),
        })

        const data = await res.json()

        if (!res.ok) {
            toastError(data.message || '指派失败')
            return
        }

        toastSuccess(data.message || '指派成功')
        showAssignForm.value = false
        router.visit(`/tasks/${props.task.id}`, { preserveState: true })
    } catch (e) {
        toastError('网络错误，请重试')
    } finally {
        assigning.value = false
    }
}

async function updateStatus(status) {
    if (statusChanging.value) return
    statusChanging.value = true

    try {
        const res = await fetch(`/api/v1/tasks/${props.task.id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status }),
        })

        const data = await res.json()

        if (!res.ok) {
            toastError(data.message || '操作失败')
            return
        }

        toastSuccess(data.message || '状态已更新')
        router.visit(`/tasks/${props.task.id}`, { preserveState: true })
    } catch (e) {
        toastError('网络错误，请重试')
    } finally {
        statusChanging.value = false
    }
}

const showStatusActions = computed(() => {
    return ['pending', 'processing'].includes(props.task.status)
})

const typeIcon = computed(() => {
    const map = { code: '💻', analysis: '📊', design: '🎨', review: '👀', other: '📋' }
    return map[props.task.type] || '📋'
})

const typeLabel = computed(() => {
    const map = { code: '代码', analysis: '分析', design: '设计', review: '审查', other: '其他' }
    return map[props.task.type] || props.task.type
})

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    return `${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
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

function statusLabel(status) {
    const map = { pending: '待处理', processing: '进行中', completed: '已完成', failed: '失败', cancelled: '已取消' }
    return map[status] || status
}

function statusBadge(status) {
    const map = {
        pending: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200',
        processing: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ring-1 ring-blue-200',
        completed: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ring-1 ring-green-200',
        failed: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ring-1 ring-red-200',
        cancelled: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 ring-1 ring-gray-200',
    }
    return map[status] || map.pending
}

function agentStatusLabel(status) {
    const map = { online: '在线', offline: '离线', busy: '忙碌' }
    return map[status] || status
}

function agentStatusBadge(status) {
    const map = {
        online: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 ring-1 ring-green-200',
        offline: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-gray-200',
        busy: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200',
    }
    return map[status] || map.offline
}

function refreshMessages() {
    router.reload({ preserveScroll: true, preserveState: true })
}
</script>
