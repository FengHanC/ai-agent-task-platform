<template>
    <AppLayout>
        <div class="py-6">
            <!-- 页面标题 -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Agent 管理</h1>
                        <p class="mt-1 text-sm text-gray-500">管理所有 AI Agent 的注册与状态</p>
                    </div>
                    <Link
                        href="/agents/create"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors flex-shrink-0"
                    >
                        <span class="mr-1.5">＋</span>
                        <span class="hidden sm:inline">添加 Agent</span>
                        <span class="sm:hidden">添加</span>
                    </Link>
                </div>
            </div>

            <!-- 状态筛选（移动端可横向滚动） -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4">
                    <div class="flex items-center space-x-2 overflow-x-auto scrollbar-hide">
                        <span class="text-sm text-gray-500 mr-2 flex-shrink-0">状态：</span>
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            @click="filterByStatus(option.value)"
                            :class="[
                                'px-3 sm:px-4 py-1.5 rounded-lg text-sm font-medium transition-colors flex-shrink-0',
                                filters.status === option.value
                                    ? 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-300'
                                    : 'text-gray-600 hover:bg-gray-100'
                            ]"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== 桌面端表格 ===== -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名称</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">任务</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">能力标签</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="agent in agents.data" :key="agent.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                                            {{ agent.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="ml-3">
                                            <Link :href="`/agents/${agent.id}`" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                                {{ agent.name }}
                                            </Link>
                                            <p v-if="agent.description" class="text-xs text-gray-500 truncate max-w-[200px]">
                                                {{ agent.description }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span :class="statusBadgeClass(agent.status)">
                                            {{ statusLabel(agent.status) }}
                                        </span>
                                        <button
                                            @click="toggleStatus(agent)"
                                            :disabled="toggling[agent.id]"
                                            class="ml-2 text-xs font-medium text-indigo-600 hover:text-indigo-800 disabled:text-gray-300 flex-shrink-0"
                                        >
                                            {{ toggling[agent.id] ? '...' : (agent.status === 'online' ? '下线' : '上线') }}
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span class="font-medium">{{ agent.current_tasks }}</span>
                                        <span class="text-gray-500"> / {{ agent.max_capacity }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="cap in agent.capabilities"
                                            :key="cap"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200"
                                        >
                                            {{ capabilityLabel(cap) }}
                                        </span>
                                        <span v-if="!agent.capabilities || agent.capabilities.length === 0" class="text-xs text-gray-400">无</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(agent.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <Link :href="`/agents/${agent.id}`" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                        详情
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="agents.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-4xl mb-3">🤖</div>
                                    <p class="text-gray-500 text-sm">还没有 Agent，点击上方"添加 Agent"创建一个吧！</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ===== 移动端卡片列表 ===== -->
                <div class="md:hidden space-y-3">
                    <div
                        v-for="agent in agents.data"
                        :key="agent.id"
                        class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow"
                    >
                        <!-- 顶部：头像 + 名称 + 状态 -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                                    {{ agent.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <Link :href="`/agents/${agent.id}`" class="text-sm font-semibold text-gray-900 hover:text-indigo-600 block truncate">
                                        {{ agent.name }}
                                    </Link>
                                    <span :class="statusBadgeClass(agent.status)" class="inline-block mt-1">
                                        {{ statusLabel(agent.status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <button
                                    @click="toggleStatus(agent)"
                                    :disabled="toggling[agent.id]"
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 disabled:text-gray-300 px-2 py-1 rounded-lg hover:bg-indigo-50"
                                >
                                    {{ toggling[agent.id] ? '...' : (agent.status === 'online' ? '下线' : '上线') }}
                                </button>
                                <Link
                                    :href="`/agents/${agent.id}`"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium bg-indigo-50 px-2.5 py-1 rounded-lg"
                                >
                                    详情
                                </Link>
                            </div>
                        </div>

                        <!-- 描述 -->
                        <p v-if="agent.description" class="mt-2 text-xs text-gray-500 line-clamp-2">{{ agent.description }}</p>

                        <!-- 底部：任务数 + 能力标签 -->
                        <div class="mt-3 flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                任务：<span class="font-medium text-gray-700">{{ agent.current_tasks }}</span> / {{ agent.max_capacity }}
                            </div>
                            <div class="flex flex-wrap gap-1 justify-end">
                                <span
                                    v-for="cap in (agent.capabilities || []).slice(0, 2)"
                                    :key="cap"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700"
                                >
                                    {{ capabilityLabel(cap) }}
                                </span>
                                <span v-if="(agent.capabilities || []).length > 2" class="text-xs text-gray-400">
                                    +{{ agent.capabilities.length - 2 }}
                                </span>
                                <span v-if="!agent.capabilities || agent.capabilities.length === 0" class="text-xs text-gray-400">无标签</span>
                            </div>
                        </div>

                        <!-- 创建时间 -->
                        <div class="mt-2 text-xs text-gray-400">{{ formatDate(agent.created_at) }}</div>
                    </div>

                    <!-- 空状态 -->
                    <div v-if="agents.data.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                        <div class="text-4xl mb-3">🤖</div>
                        <p class="text-gray-500 text-sm">还没有 Agent，点击上方"添加"创建一个吧！</p>
                    </div>
                </div>

                <!-- 分页 -->
                <div v-if="agents.total > agents.per_page" class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p class="text-sm text-gray-500">
                        共 {{ agents.total }} 条，第 {{ agents.current_page }} / {{ agents.last_page }} 页
                    </p>
                    <div class="flex space-x-2">
                        <Link
                            v-if="agents.prev_page_url"
                            :href="agents.prev_page_url"
                            class="px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            上一页
                        </Link>
                        <Link
                            v-if="agents.next_page_url"
                            :href="agents.next_page_url"
                            class="px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            下一页
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

const { success: toastSuccess, error: toastError } = useToast()
const toggling = ref({})  // { [agentId]: true/false }

const props = defineProps({
    agents: { type: Object, required: true },
    filters: { type: Object, default: () => ({ status: 'all' }) },
})

const statusOptions = [
    { value: 'all', label: '全部' },
    { value: 'online', label: '在线' },
    { value: 'offline', label: '离线' },
    { value: 'busy', label: '忙碌' },
]

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

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    return `${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function filterByStatus(status) {
    router.get('/agents', { status }, { preserveState: true, replace: true })
}

async function toggleStatus(agent) {
    const newStatus = agent.status === 'online' ? 'offline' : 'online'
    if (toggling.value[agent.id]) return
    toggling.value[agent.id] = true

    try {
        const res = await fetch(`/api/v1/agents/${agent.id}`, {
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
        toastSuccess(`${agent.name} 已${label}`)
        router.reload({ preserveState: true, preserveScroll: true })
    } catch (e) {
        toastError('网络错误，请重试')
    } finally {
        toggling.value[agent.id] = false
    }
}
</script>
