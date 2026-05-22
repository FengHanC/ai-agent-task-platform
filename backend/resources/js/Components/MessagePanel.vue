<template>
    <div class="flex flex-col">
        <!-- 标题 -->
        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">任务消息</h2>

        <!-- 消息列表容器（带固定高度滚动） -->
        <div ref="messageContainer" class="space-y-3 max-h-[400px] overflow-y-auto pr-1 mb-4">
            <!-- 空状态 -->
            <div v-if="messages.length === 0" class="text-center py-8">
                <p class="text-sm text-gray-400">暂无消息</p>
            </div>

            <!-- 消息条目 -->
            <div
                v-for="msg in sortedMessages"
                :key="msg.id"
                :class="['p-3 rounded-lg text-sm transition-colors', messageBg(msg.type)]"
            >
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="font-medium" :class="messageTextColor(msg.type)">{{ messageTypeLabel(msg.type) }}</span>
                        <span v-if="msg.agent_name" class="text-xs text-gray-400">· {{ msg.agent_name }}</span>
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0 ml-2">{{ formatTime(msg.created_at) }}</span>
                </div>
                <p class="mt-1 text-gray-700 whitespace-pre-wrap">{{ msg.content }}</p>
            </div>
        </div>

        <!-- 发送消息表单 -->
        <form @submit.prevent="sendMessage" class="flex items-end space-x-2 border-t border-gray-100 pt-3">
            <div class="flex-1">
                <textarea
                    ref="inputRef"
                    v-model="form.content"
                    rows="2"
                    placeholder="输入消息..."
                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none',
                        form.errors.content ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    @keydown.enter.ctrl="sendMessage"
                    @keydown.enter.meta="sendMessage"
                ></textarea>
                <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
            </div>
            <button
                type="submit"
                :disabled="form.processing || !form.content.trim()"
                :class="['px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors flex-shrink-0',
                    form.processing || !form.content.trim()
                        ? 'bg-indigo-400 cursor-not-allowed'
                        : 'bg-indigo-600 hover:bg-indigo-700'
                ]"
            >
                {{ form.processing ? '发送中...' : '发送' }}
            </button>
        </form>
        <p class="mt-1 text-xs text-gray-400">按 Ctrl+Enter / ⌘+Enter 快速发送</p>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    messages: { type: Array, default: () => [] },
    taskId: { type: [Number, String], required: true },
})

const emit = defineEmits(['message-sent'])

const messageContainer = ref(null)
const inputRef = ref(null)

// 按时间倒序排列（最新在最上面）
const sortedMessages = computed(() => {
    return [...props.messages].sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at)
    })
})

const form = useForm({
    content: '',
    type: 'user',
})

function sendMessage() {
    if (!form.content.trim()) return

    form.post(`/api/v1/tasks/${props.taskId}/messages`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('message-sent')
            form.reset('content')
            nextTick(() => {
                inputRef.value?.focus()
            })
        },
    })
}

// 新消息到达时滚动到底部（最上面的最新消息）
watch(() => props.messages.length, () => {
    nextTick(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = 0
        }
    })
})

function messageBg(type) {
    const map = {
        system: 'bg-gray-50',
        agent: 'bg-blue-50',
        user: 'bg-green-50',
        error: 'bg-red-50',
    }
    return map[type] || 'bg-gray-50'
}

function messageTextColor(type) {
    const map = {
        system: 'text-gray-600',
        agent: 'text-blue-700',
        user: 'text-green-700',
        error: 'text-red-700',
    }
    return map[type] || 'text-gray-600'
}

function messageTypeLabel(type) {
    const map = {
        system: '🔧 系统',
        agent: '🤖 Agent',
        user: '👤 用户',
        error: '❌ 错误',
    }
    return map[type] || type
}

function formatTime(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}
</script>
