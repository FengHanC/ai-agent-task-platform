<template>
    <div class="flex flex-col">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider">任务消息</h2>
            <!-- WebSocket 连接状态 -->
            <span
                v-if="!echoConnected"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200"
                title="实时连接未启用，消息可能延迟显示"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                离线模式
            </span>
        </div>

        <!-- 消息列表 -->
        <div ref="messageContainer" class="space-y-3 max-h-[400px] overflow-y-auto pr-1 mb-4">
            <div v-if="allMessages.length === 0" class="text-center py-8">
                <p class="text-sm text-gray-400">暂无消息</p>
            </div>

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

        <!-- 发送消息 -->
        <form @submit.prevent="sendMessage" class="flex items-end space-x-2 border-t border-gray-100 pt-3">
            <div class="flex-1">
                <textarea
                    ref="inputRef"
                    v-model="messageContent"
                    rows="2"
                    placeholder="输入消息..."
                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none',
                        sendError ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                    @keydown.enter.ctrl="sendMessage"
                    @keydown.enter.meta="sendMessage"
                    :disabled="sending"
                ></textarea>
                <p v-if="sendError" class="mt-1 text-xs text-red-600">{{ sendError }}</p>
            </div>
            <button
                type="submit"
                :disabled="sending || !messageContent.trim()"
                :class="['px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors flex-shrink-0',
                    sending || !messageContent.trim()
                        ? 'bg-indigo-400 cursor-not-allowed'
                        : 'bg-indigo-600 hover:bg-indigo-700'
                ]"
            >
                {{ sending ? '发送中...' : '发送' }}
            </button>
        </form>
        <p class="mt-1 text-xs text-gray-400">按 Ctrl+Enter / ⌘+Enter 快速发送</p>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useTaskChannel, echoConnected } from '@/composables/useTaskChannel'

const props = defineProps({
    messages: { type: Array, default: () => [] },
    taskId: { type: [Number, String], required: true },
})

const emit = defineEmits(['message-sent'])

// 实时消息：合并 props 消息 + WebSocket 推送的新消息
const liveIncoming = ref([])

const allMessages = computed(() => {
    return [...props.messages, ...liveIncoming.value]
})

const sortedMessages = computed(() => {
    return [...allMessages.value].sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at)
    })
})

const messageContent = ref('')
const sending = ref(false)
const sendError = ref('')
const messageContainer = ref(null)
const inputRef = ref(null)

// ── WebSocket 监听 ──
let channel

onMounted(() => {
    channel = useTaskChannel(props.taskId)

    channel.onMessageSent((data) => {
        // 避免自己发送的消息重复（fetch 返回后 emit message-sent → reload）
        // 如果消息不是当前用户刚发的，才追加
        const exists = props.messages.some(m => m.id === data.id) ||
                       liveIncoming.value.some(m => m.id === data.id)
        if (!exists) {
            liveIncoming.value.push(data)
        }
    })
})

onUnmounted(() => {
    if (channel) channel.leave()
})

// ── 发送消息 ──
async function sendMessage() {
    const content = messageContent.value.trim()
    if (!content || sending.value) return

    sending.value = true
    sendError.value = ''

    try {
        const res = await fetch(`/api/v1/tasks/${props.taskId}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ content, type: 'user' }),
        })

        const data = await res.json()

        if (!res.ok) {
            sendError.value = res.status === 422
                ? (data.errors?.content?.[0] || '数据校验失败')
                : (data.message || '发送失败')
            return
        }

        messageContent.value = ''
        emit('message-sent')

        nextTick(() => {
            inputRef.value?.focus()
        })
    } catch (e) {
        sendError.value = '网络错误，请重试'
    } finally {
        sending.value = false
    }
}

// ── 滚动 ──
watch(() => allMessages.value.length, () => {
    nextTick(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = 0
        }
    })
})

// ── 样式 helper ──
function messageBg(type) {
    const map = { system: 'bg-gray-50', agent: 'bg-blue-50', user: 'bg-green-50', error: 'bg-red-50' }
    return map[type] || 'bg-gray-50'
}

function messageTextColor(type) {
    const map = { system: 'text-gray-600', agent: 'text-blue-700', user: 'text-green-700', error: 'text-red-700' }
    return map[type] || 'text-gray-600'
}

function messageTypeLabel(type) {
    const map = { system: '🔧 系统', agent: '🤖 Agent', user: '👤 用户', error: '❌ 错误' }
    return map[type] || type
}

function formatTime(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    const pad = n => String(n).padStart(2, '0')
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}
</script>
