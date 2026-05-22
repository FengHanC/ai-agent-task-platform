<template>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">最近活动</h2>
        </div>

        <!-- 空状态 -->
        <div v-if="activities.length === 0" class="px-6 py-8 text-center">
            <div class="text-3xl mb-2">📭</div>
            <p class="text-sm text-gray-400">暂无活动记录</p>
        </div>

        <!-- 活动列表 -->
        <div v-else class="divide-y divide-gray-50">
            <div
                v-for="(activity, index) in displayedActivities"
                :key="activity.id || index"
                class="px-6 py-3.5 flex items-start space-x-3 hover:bg-gray-50 transition-colors"
            >
                <span class="text-lg flex-shrink-0 mt-0.5">{{ activityIcon(activity.type) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-900">{{ activity.description }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ formatTime(activity.created_at) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    activities: { type: Array, default: () => [] },
    maxItems: { type: Number, default: 10 },
})

const displayedActivities = computed(() => {
    return props.activities.slice(0, props.maxItems)
})

function activityIcon(type) {
    const map = {
        task_created: '📋',
        task_assigned: '📤',
        task_completed: '✅',
        task_failed: '❌',
        task_cancelled: '🚫',
        agent_created: '🤖',
        agent_status: '🔄',
        message_sent: '💬',
        system: '🔧',
    }
    return map[type] || '📌'
}

function formatTime(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    return `${pad(d.getMonth() + 1)}/${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
</script>
