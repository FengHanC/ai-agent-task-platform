<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">AI Agent 任务平台</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">MVP v0.1</span>
                    </div>
                </div>
            </div>
        </nav>
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <div class="text-3xl font-bold text-yellow-500">⏳</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.pending }}</div>
                    <div class="text-sm text-gray-500">待处理</div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <div class="text-3xl font-bold text-blue-500">🔄</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.processing }}</div>
                    <div class="text-sm text-gray-500">进行中</div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <div class="text-3xl font-bold text-green-500">✅</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.completed }}</div>
                    <div class="text-sm text-gray-500">已完成</div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg p-6">
                    <div class="text-3xl font-bold text-red-500">❌</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.failed }}</div>
                    <div class="text-sm text-gray-500">失败</div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">最近任务</h2>
                    <p class="text-gray-500 text-sm">平台初始化完成，等待功能开发...</p>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { reactive } from 'vue'

const stats = reactive({
    pending: 0,
    processing: 0,
    completed: 0,
    failed: 0,
})
</script>
