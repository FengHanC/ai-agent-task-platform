<template>
    <div class="min-h-screen bg-gray-50">
        <!-- 顶部导航栏 -->
        <nav class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/dashboard" class="flex items-center space-x-2">
                            <span class="text-2xl">🤖</span>
                            <span class="text-lg font-bold text-gray-900">AI Agent 任务平台</span>
                        </Link>
                        <div class="ml-10 flex items-center space-x-1">
                            <Link
                                href="/dashboard"
                                :class="['px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/dashboard')
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                ]"
                            >
                                📊 仪表盘
                            </Link>
                            <Link
                                href="/agents"
                                :class="['px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/agents')
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                ]"
                            >
                                🤖 Agent 管理
                            </Link>
                            <Link
                                href="/tasks"
                                :class="['px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/tasks')
                                        ? 'bg-indigo-50 text-indigo-700'
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                ]"
                            >
                                📋 任务管理
                            </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-400">MVP v0.1</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- 页面内容 -->
        <main>
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

function isActive(path) {
    if (typeof window === 'undefined') return false
    return window.location.pathname.startsWith(path)
}
</script>
