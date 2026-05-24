<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 页面标题 -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">任务管理</h1>
                        <p class="mt-1 text-sm text-gray-500">查看和管理所有任务</p>
                    </div>
                    <Link
                        href="/tasks/create"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors flex-shrink-0"
                    >
                        <span class="mr-1.5">＋</span>
                        <span class="hidden sm:inline">创建任务</span>
                        <span class="sm:hidden">创建</span>
                    </Link>
                </div>

                <!-- 搜索 -->
                <div class="mt-6 relative">
                    <input
                        v-model="search"
                        @input="onSearch"
                        type="text"
                        placeholder="搜索任务标题..."
                        class="w-full px-4 py-2.5 pl-10 text-sm border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                    />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- 统计横幅（移动端 2 列） -->
                <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <div
                        v-for="stat in stats"
                        :key="stat.key"
                        class="bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4 text-center"
                    >
                        <div class="text-xl sm:text-2xl font-bold" :class="stat.color">{{ stat.count }}</div>
                        <div class="text-xs sm:text-sm text-gray-500 mt-0.5 sm:mt-1">{{ stat.label }}</div>
                    </div>
                </div>

                <!-- 按状态分组列表 -->
                <div class="mt-6 space-y-4 sm:space-y-6">
                    <div
                        v-for="group in statusGroups"
                        :key="group.status"
                        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
                    >
                        <!-- 分组标题 -->
                        <div class="px-4 sm:px-6 py-3 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2" :class="group.headerBg">
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" :class="group.dotColor"></span>
                                <h2 class="text-sm sm:text-base font-semibold text-gray-900">{{ group.label }}</h2>
                                <span class="text-sm text-gray-400">({{ group.tasks.length }})</span>
                            </div>
                            <!-- 筛选下拉（移动端折叠显示） -->
                            <div v-if="group.tasks.length > 0" class="flex items-center space-x-2 overflow-x-auto">
                                <select
                                    v-model="group.typeFilter"
                                    @change="filterGroup(group)"
                                    class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                >
                                    <option value="">全部类型</option>
                                    <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <select
                                    v-model="group.priorityFilter"
                                    @change="filterGroup(group)"
                                    class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                >
                                    <option value="">全部优先级</option>
                                    <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- 任务卡片 -->
                        <div v-if="group.filteredTasks.length > 0">
                            <div
                                v-for="task in group.filteredTasks"
                                :key="task.id"
                                class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2 sm:space-x-3 min-w-0">
                                        <span class="text-base sm:text-lg flex-shrink-0">{{ typeIcon(task.type) }}</span>
                                        <div class="min-w-0">
                                            <Link
                                                :href="`/tasks/${task.id}`"
                                                class="text-sm font-medium text-gray-900 hover:text-indigo-600 block truncate"
                                            >
                                                {{ task.title }}
                                            </Link>
                                            <div class="flex items-center flex-wrap gap-1.5 mt-1">
                                                <span :class="typeBadge(task.type)">{{ typeLabel(task.type) }}</span>
                                                <span :class="priorityBadge(task.priority)">{{ priorityLabel(task.priority) }}</span>
                                                <span v-if="task.agent" class="text-xs text-gray-400">
                                                    🤖 {{ task.agent.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400 flex-shrink-0 ml-2 hidden sm:block">{{ formatDate(task.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-4 sm:px-6 py-6 sm:py-8 text-center">
                            <p class="text-sm text-gray-400">{{ group.emptyText }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    tasks: { type: Array, default: () => [] },
})

const search = ref('')

let searchTimer
function onSearch() {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        window.location.href = '/tasks?search=' + encodeURIComponent(search.value)
    }, 300)
}

const typeOptions = [
    { value: 'code', label: '代码' },
    { value: 'analysis', label: '分析' },
    { value: 'design', label: '设计' },
    { value: 'review', label: '审查' },
    { value: 'other', label: '其他' },
]

const priorityOptions = [
    { value: 'low', label: '低' },
    { value: 'medium', label: '中' },
    { value: 'high', label: '高' },
    { value: 'urgent', label: '紧急' },
]

const statusGroups = reactive([
    {
        status: 'pending',
        label: '待处理',
        dotColor: 'bg-yellow-400',
        headerBg: '',
        emptyText: '暂无待处理任务',
        tasks: computed(() => props.tasks.filter(t => t.status === 'pending')),
        filteredTasks: computed(() => {
            const g = statusGroups[0]
            return g.tasks.filter(t => {
                if (g.typeFilter && t.type !== g.typeFilter) return false
                if (g.priorityFilter && t.priority !== g.priorityFilter) return false
                return true
            })
        }),
        typeFilter: '',
        priorityFilter: '',
    },
    {
        status: 'processing',
        label: '进行中',
        dotColor: 'bg-blue-400',
        headerBg: '',
        emptyText: '暂无进行中的任务',
        tasks: computed(() => props.tasks.filter(t => t.status === 'processing')),
        filteredTasks: computed(() => {
            const g = statusGroups[1]
            return g.tasks.filter(t => {
                if (g.typeFilter && t.type !== g.typeFilter) return false
                if (g.priorityFilter && t.priority !== g.priorityFilter) return false
                return true
            })
        }),
        typeFilter: '',
        priorityFilter: '',
    },
    {
        status: 'completed',
        label: '已完成',
        dotColor: 'bg-green-400',
        headerBg: '',
        emptyText: '暂无已完成任务',
        tasks: computed(() => props.tasks.filter(t => t.status === 'completed')),
        filteredTasks: computed(() => {
            const g = statusGroups[2]
            return g.tasks.filter(t => {
                if (g.typeFilter && t.type !== g.typeFilter) return false
                if (g.priorityFilter && t.priority !== g.priorityFilter) return false
                return true
            })
        }),
        typeFilter: '',
        priorityFilter: '',
    },
    {
        status: 'failed',
        label: '失败',
        dotColor: 'bg-red-400',
        headerBg: '',
        emptyText: '暂无失败任务',
        tasks: computed(() => props.tasks.filter(t => t.status === 'failed')),
        filteredTasks: computed(() => {
            const g = statusGroups[3]
            return g.tasks.filter(t => {
                if (g.typeFilter && t.type !== g.typeFilter) return false
                if (g.priorityFilter && t.priority !== g.priorityFilter) return false
                return true
            })
        }),
        typeFilter: '',
        priorityFilter: '',
    },
])

const stats = computed(() => [
    { key: 'pending', label: '待处理', count: statusGroups[0].tasks.length, color: 'text-yellow-500' },
    { key: 'processing', label: '进行中', count: statusGroups[1].tasks.length, color: 'text-blue-500' },
    { key: 'completed', label: '已完成', count: statusGroups[2].tasks.length, color: 'text-green-500' },
    { key: 'failed', label: '失败', count: statusGroups[3].tasks.length, color: 'text-red-500' },
])

function filterGroup(group) {
    // reactivity is handled by computed
}

function typeIcon(type) {
    const map = { code: '💻', analysis: '📊', design: '🎨', review: '👀', other: '📋' }
    return map[type] || '📋'
}

function typeLabel(type) {
    const map = { code: '代码', analysis: '分析', design: '设计', review: '审查', other: '其他' }
    return map[type] || type
}

function typeBadge(type) {
    const map = {
        code: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 ring-1 ring-purple-200',
        analysis: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200',
        design: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-50 text-pink-700 ring-1 ring-pink-200',
        review: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-teal-50 text-teal-700 ring-1 ring-teal-200',
        other: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-50 text-gray-600 ring-1 ring-gray-200',
    }
    return map[type] || map.other
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

async function deleteTask(task) {
    const forbidden = ['processing', 'completed']
    if (forbidden.includes(task.status)) {
        alert('进行中或已完成的任务不能删除')
        return
    }
    if (!confirm('确定删除任务“' + task.title + '”？')) return

    try {
        const res = await fetch('/api/v1/tasks/' + task.id, { method: 'DELETE' })
        if (!res.ok) {
            const data = await res.json()
            alert(data.message || '删除失败')
            return
        }
        window.location.reload()
    } catch (e) {
        alert('网络错误，请重试')
    }
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    return `${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
</script>
