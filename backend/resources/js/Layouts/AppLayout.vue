<template>
    <div class="min-h-screen bg-gray-50">
        <!-- ===== 顶部导航栏 ===== -->
        <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- 左侧 -->
                    <div class="flex items-center">
                        <!-- 汉堡菜单（移动端） -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="md:hidden mr-3 p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            aria-label="菜单"
                        >
                            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Logo -->
                        <Link href="/dashboard" class="flex items-center space-x-2 flex-shrink-0">
                            <span class="text-2xl">🤖</span>
                            <span class="text-base sm:text-lg font-bold text-gray-900 hidden sm:inline">AI Agent 任务平台</span>
                        </Link>

                        <!-- 桌面端导航 -->
                        <div class="hidden md:flex items-center ml-10 space-x-1">
                            <NavLink href="/dashboard" :active="isActive('/dashboard')">
                                📊 仪表盘
                            </NavLink>
                            <NavLink href="/agents" :active="isActive('/agents')">
                                🤖 Agent 管理
                            </NavLink>
                            <NavLink href="/tasks" :active="isActive('/tasks')">
                                📋 任务管理
                            </NavLink>
                        </div>
                    </div>

                    <!-- 右侧 -->
                    <div class="flex items-center space-x-4">
                        <span class="hidden sm:inline text-sm text-gray-400">MVP v0.1</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ===== 移动端侧栏 ===== -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="mobileMenuOpen"
                    class="fixed inset-0 bg-black/40 z-40 md:hidden"
                    @click="mobileMenuOpen = false"
                ></div>
            </Transition>

            <Transition name="slide-left">
                <div
                    v-if="mobileMenuOpen"
                    class="fixed top-0 left-0 bottom-0 w-72 bg-white shadow-2xl z-50 md:hidden flex flex-col"
                >
                    <!-- 侧栏头部 -->
                    <div class="flex items-center justify-between px-5 h-16 border-b border-gray-100">
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl">🤖</span>
                            <span class="text-base font-bold text-gray-900">AI Agent 平台</span>
                        </div>
                        <button
                            @click="mobileMenuOpen = false"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            aria-label="关闭菜单"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- 导航 -->
                    <div class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                        <SidebarLink href="/dashboard" :active="isActive('/dashboard')" @click="mobileMenuOpen = false">
                            <span class="text-lg">📊</span>
                            仪表盘
                        </SidebarLink>
                        <SidebarLink href="/agents" :active="isActive('/agents')" @click="mobileMenuOpen = false">
                            <span class="text-lg">🤖</span>
                            Agent 管理
                        </SidebarLink>
                        <SidebarLink href="/tasks" :active="isActive('/tasks')" @click="mobileMenuOpen = false">
                            <span class="text-lg">📋</span>
                            任务管理
                        </SidebarLink>
                    </div>

                    <!-- 底部 -->
                    <div class="px-5 py-3 border-t border-gray-100">
                        <p class="text-xs text-gray-400">MVP v0.1</p>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ===== Toast 全局通知 ===== -->
        <Toast />

        <!-- ===== 页面内容 ===== -->
        <main>
            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import Toast from '@/Components/Toast.vue'
import NavLink from '@/Components/NavLink.vue'
import SidebarLink from '@/Components/SidebarLink.vue'
import { useToast } from '@/composables/useToast'

const { success: toastSuccess, error: toastError } = useToast()
const mobileMenuOpen = ref(false)

function isActive(path) {
    if (typeof window === 'undefined') return false
    return window.location.pathname.startsWith(path)
}

// 监听 Inertia flash 消息，自动弹 Toast
const page = usePage()
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return
        if (flash.success) {
            toastSuccess(flash.success)
        }
        if (flash.error) {
            toastError(flash.error)
        }
    },
    { deep: true, immediate: true }
)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-left-leave-active {
    transition: transform 0.2s ease-in;
}
.slide-left-enter-from {
    transform: translateX(-100%);
}
.slide-left-leave-to {
    transform: translateX(-100%);
}
</style>
