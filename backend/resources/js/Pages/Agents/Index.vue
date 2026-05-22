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
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <span class="mr-2">＋</span>
                        添加 Agent
                    </Link>
                </div>
            </div>

            <!-- Flash 消息 -->
            <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ $page.props.flash.success }}
                </div>
            </div>

            <!-- 状态筛选 -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500 mr-2">状态筛选：</span>
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            @click="filterByStatus(option.value)"
                            :class="[
                                'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
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

            <!-- Agent 列表 -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
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
                                    <span :class="statusBadgeClass(agent.status)">
                                        {{ statusLabel(agent.status) }}
                                    </span>
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
                                        <span v-if="!agent.capabilities || agent.capabilities.length === 0" class="text-xs text-gray-400">
                                            无
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ agent.created_at }}
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

                <!-- 分页 -->
                <div v-if="agents.total > agents.per_page" class="mt-4 flex items-center justify-between">
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
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

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

function filterByStatus(status) {
    router.get('/agents', { status }, { preserveState: true, replace: true })
}
</script>
