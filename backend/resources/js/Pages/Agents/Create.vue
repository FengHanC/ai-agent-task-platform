<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 页面标题 -->
                <div class="mb-6">
                    <Link href="/agents" class="text-sm text-indigo-600 hover:text-indigo-800 mb-2 inline-block">
                        ← 返回 Agent 列表
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">创建 Agent</h1>
                    <p class="mt-1 text-sm text-gray-500">注册一个新的 AI Agent 到平台</p>
                </div>

                <!-- 提示消息 -->
                <div v-if="$page.props.flash?.success" class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ $page.props.flash.success }}
                </div>

                <!-- 表单 -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 space-y-6">

                        <!-- Agent 名称 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Agent 名称 <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="例如：代码助手 Alpha"
                                :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.name ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- 描述 -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">描述</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="描述这个 Agent 的职责和特点..."
                                :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- 能力标签 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">能力标签</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <label
                                    v-for="option in capabilityOptions"
                                    :key="option.value"
                                    :class="['flex items-center p-3 border rounded-lg cursor-pointer transition-colors',
                                        isCapabilitySelected(option.value)
                                            ? 'bg-indigo-50 border-indigo-300 ring-1 ring-indigo-200'
                                            : 'bg-white border-gray-200 hover:bg-gray-50'
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="option.value"
                                        :checked="isCapabilitySelected(option.value)"
                                        @change="toggleCapability(option.value)"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">{{ option.label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.capabilities" class="mt-1 text-xs text-red-600">{{ form.errors.capabilities }}</p>
                        </div>

                        <!-- 最大并发数 -->
                        <div>
                            <label for="max_capacity" class="block text-sm font-medium text-gray-700 mb-1">
                                最大并发任务数 <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="max_capacity"
                                v-model="form.max_capacity"
                                type="number"
                                min="1"
                                max="100"
                                :class="['w-32 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.max_capacity ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <p v-if="form.errors.max_capacity" class="mt-1 text-xs text-red-600">{{ form.errors.max_capacity }}</p>
                            <p class="mt-1 text-xs text-gray-400">Agent 最多同时处理的任务数量（1-100）</p>
                        </div>
                    </div>

                    <!-- 提交按钮 -->
                    <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-200 flex items-center justify-end space-x-3">
                        <Link
                            href="/agents"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            取消
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            :class="['px-6 py-2 text-sm font-medium text-white rounded-lg transition-colors',
                                form.processing
                                    ? 'bg-indigo-400 cursor-not-allowed'
                                    : 'bg-indigo-600 hover:bg-indigo-700'
                            ]"
                        >
                            <span v-if="form.processing">创建中...</span>
                            <span v-else>创建 Agent</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    capabilityOptions: { type: Array, required: true },
})

const form = useForm({
    name: '',
    description: '',
    capabilities: [],
    max_capacity: 5,
})

function isCapabilitySelected(value) {
    return form.capabilities.includes(value)
}

function toggleCapability(value) {
    if (form.capabilities.includes(value)) {
        form.capabilities = form.capabilities.filter(v => v !== value)
    } else {
        form.capabilities.push(value)
    }
}

function submit() {
    form.post('/agents')
}
</script>
