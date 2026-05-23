<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <Link href="/" class="inline-flex items-center space-x-2">
                    <span class="text-4xl">🤖</span>
                    <span class="text-2xl font-bold text-gray-900">AI Agent 平台</span>
                </Link>
                <p class="mt-2 text-sm text-gray-500">登录你的账户</p>
            </div>

            <!-- 登录卡片 -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- 邮箱 -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            邮箱
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            placeholder="your@email.com"
                            :class="['w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors',
                                form.errors.email ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <!-- 密码 -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            密码
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="输入密码"
                                :class="['w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors pr-10',
                                    form.errors.password ? 'border-red-300 bg-red-50' : 'border-gray-300']"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                            >
                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <!-- 记住我 -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label for="remember" class="ml-2 text-sm text-gray-600">记住我</label>
                    </div>

                    <!-- 错误提示 -->
                    <div v-if="form.errors.error" class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                        {{ form.errors.error }}
                    </div>

                    <!-- 登录按钮 -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="['w-full py-2.5 text-sm font-medium text-white rounded-lg transition-all',
                            form.processing
                                ? 'bg-indigo-400 cursor-not-allowed'
                                : 'bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98]'
                        ]"
                    >
                        <span v-if="form.processing" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            登录中...
                        </span>
                        <span v-else>登录</span>
                    </button>
                </form>

                <!-- 注册入口 -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        还没有账户？
                        <Link href="/register" class="text-indigo-600 hover:text-indigo-800 font-medium">
                            注册
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post('/login', {
        onError: () => {
            form.reset('password')
        },
    })
}
</script>
