<!-- =====================================================
  Toast 通知弹框
  - 从顶部滑入，3秒后自动消失
  - 支持 success / error / info 三种类型
  - 可手动关闭
  ===================================================== -->
<template>
    <Teleport to="body">
        <div class="fixed top-0 left-0 right-0 z-[9999] pointer-events-none flex flex-col items-center pt-4 px-4 space-y-2">
            <TransitionGroup name="toast" tag="div" class="w-full max-w-sm space-y-2">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'pointer-events-auto w-full rounded-xl shadow-lg border px-4 py-3.5',
                        'flex items-start space-x-3 transition-all duration-300',
                        toastBg(toast.type),
                        toastBorder(toast.type),
                        toast.leaving ? 'toast-leave' : '',
                    ]"
                    role="alert"
                >
                    <!-- 图标 -->
                    <span class="flex-shrink-0 text-lg mt-0.5">{{ toastIcon(toast.type) }}</span>

                    <!-- 消息 -->
                    <p class="flex-1 text-sm font-medium leading-5" :class="toastText(toast.type)">
                        {{ toast.message }}
                    </p>

                    <!-- 关闭按钮 -->
                    <button
                        @click="removeToast(toast.id)"
                        class="flex-shrink-0 ml-2 p-0.5 rounded hover:bg-black/5 transition-colors"
                        aria-label="关闭"
                    >
                        <svg class="w-4 h-4" :class="toastCloseColor(toast.type)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast'

const { toasts, removeToast } = useToast()

function toastIcon(type) {
    const map = {
        success: '✅',
        error: '❌',
        info: '💡',
    }
    return map[type] || '💡'
}

function toastBg(type) {
    const map = {
        success: 'bg-green-50',
        error: 'bg-red-50',
        info: 'bg-blue-50',
    }
    return map[type] || 'bg-blue-50'
}

function toastBorder(type) {
    const map = {
        success: 'border-green-200',
        error: 'border-red-200',
        info: 'border-blue-200',
    }
    return map[type] || 'border-blue-200'
}

function toastText(type) {
    const map = {
        success: 'text-green-800',
        error: 'text-red-800',
        info: 'text-blue-800',
    }
    return map[type] || 'text-blue-800'
}

function toastCloseColor(type) {
    const map = {
        success: 'text-green-400 hover:text-green-600',
        error: 'text-red-400 hover:text-red-600',
        info: 'text-blue-400 hover:text-blue-600',
    }
    return map[type] || 'text-blue-400 hover:text-blue-600'
}
</script>

<style scoped>
/* 进入动画：从顶部滑入 + 淡入 */
.toast-enter-active {
    animation: toast-in 0.35s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
}

/* 离开动画：向上滑出 + 淡出 */
.toast-leave-active {
    animation: toast-out 0.3s ease-in forwards;
}

/* 列表移动过渡 */
.toast-move {
    transition: transform 0.3s ease;
}

@keyframes toast-in {
    0% {
        opacity: 0;
        transform: translateY(-24px) scale(0.95);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes toast-out {
    0% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateY(-16px) scale(0.95);
    }
}
</style>
