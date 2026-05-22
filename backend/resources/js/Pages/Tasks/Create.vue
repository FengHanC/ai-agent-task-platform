<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 页面标题 -->
                <div class="mb-6">
                    <Link href="/tasks" class="text-sm text-indigo-600 hover:text-indigo-800 mb-2 inline-block">
                        ← 返回任务列表
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">创建任务</h1>
                    <p class="mt-1 text-sm text-gray-500">创建一个新任务分发给 Agent</p>
                </div>

                <!-- Flash 消息 -->
                <div v-if="$page.props.flash?.success" class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ $page.props.flash.success }}
                </div>

                <!-- 表单 -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 space-y-6">
                        <!-- 标题 -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                任务标题 <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="例如：重构用户登录模块"
                                :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.title ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <!-- 描述 -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">任务描述</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="详细描述任务内容、预期结果和注意事项..."
                                :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- 类型和优先级 -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                    任务类型 <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white',
                                        form.errors.type ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                >
                                    <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                            </div>

                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                                    优先级 <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="priority"
                                    v-model="form.priority"
                                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white',
                                        form.errors.priority ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                >
                                    <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.priority" class="mt-1 text-xs text-red-600">{{ form.errors.priority }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-200 flex items-center justify-end space-x-3">
                        <Link
                            href="/tasks"
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
                            <span v-else>创建任务</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    // 未使用 props，所有选项硬编码
})

const typeOptions = [
    { value: 'code', label: '💻 代码' },
    { value: 'analysis', label: '📊 分析' },
    { value: 'design', label: '🎨 设计' },
    { value: 'review', label: '👀 审查' },
    { value: 'other', label: '📋 其他' },
]

const priorityOptions = [
    { value: 'low', label: '🟢 低' },
    { value: 'medium', label: '🔵 中' },
    { value: 'high', label: '🟠 高' },
    { value: 'urgent', label: '🔴 紧急' },
]

const form = useForm({
    title: '',
    description: '',
    type: 'other',
    priority: 'medium',
})

function submit() {
    form.post('/api/v1/tasks', {
        onSuccess: () => {
            router.visit('/tasks')
        },
    })
}
</script>
