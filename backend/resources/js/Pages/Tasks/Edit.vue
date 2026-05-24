<template>
    <AppLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="`/tasks/${task.id}`" class="text-sm text-indigo-600 hover:text-indigo-800 mb-2 inline-block">
                        ← 返回任务详情
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">编辑任务</h1>
                    <p class="mt-1 text-sm text-gray-500">修改 #{{ task.id }} {{ task.title }}</p>
                </div>

                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-4 sm:p-6 space-y-5 sm:space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                任务标题 <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    errors.title ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <p v-if="errors.title" class="mt-1 text-xs text-red-600">{{ errors.title }}</p>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">任务描述</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            ></textarea>
                            <p v-if="errors.description" class="mt-1 text-xs text-red-600">{{ errors.description }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                                    任务类型 <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white',
                                        errors.type ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                >
                                    <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <p v-if="errors.type" class="mt-1 text-xs text-red-600">{{ errors.type }}</p>
                            </div>

                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                                    优先级 <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="priority"
                                    v-model="form.priority"
                                    :class="['w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none bg-white',
                                        errors.priority ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                                >
                                    <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <p v-if="errors.priority" class="mt-1 text-xs text-red-600">{{ errors.priority }}</p>
                            </div>
                        </div>

                        <div v-if="errors._general" class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                            {{ errors._general }}
                        </div>
                    </div>

                    <div class="px-4 sm:px-6 py-4 bg-gray-50 rounded-b-xl border-t border-gray-200 flex flex-col-reverse sm:flex-row items-stretch sm:items-center sm:justify-end gap-2 sm:space-x-3 sm:gap-0">
                        <Link
                            :href="`/tasks/${task.id}`"
                            class="text-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            取消
                        </Link>
                        <button
                            type="submit"
                            :disabled="sending"
                            :class="['px-6 py-2 text-sm font-medium text-white rounded-lg transition-colors',
                                sending ? 'bg-indigo-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'
                            ]"
                        >
                            <span v-if="sending">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                保存中...
                            </span>
                            <span v-else>保存修改</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useToast } from '@/composables/useToast'

const { success: toastSuccess } = useToast()

const props = defineProps({
    task: { type: Object, required: true },
})

const form = reactive({
    title: props.task.title || '',
    description: props.task.description || '',
    type: props.task.type || 'other',
    priority: props.task.priority || 'medium',
})

const errors = reactive({})
const sending = ref(false)

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

async function submit() {
    if (sending.value) return
    sending.value = true
    Object.keys(errors).forEach(k => delete errors[k])

    try {
        const res = await fetch(`/api/v1/tasks/${props.task.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(Object.assign({}, form)),
        })

        const data = await res.json()

        if (!res.ok) {
            if (res.status === 422 && data.errors) {
                Object.entries(data.errors).forEach(([field, msgs]) => {
                    errors[field] = Array.isArray(msgs) ? msgs[0] : msgs
                })
            } else {
                errors._general = data.message || '保存失败，请重试'
            }
            return
        }

        toastSuccess('任务已更新')
        router.visit(`/tasks/${props.task.id}`)
    } catch (e) {
        errors._general = '网络错误，请重试'
    } finally {
        sending.value = false
    }
}
</script>
