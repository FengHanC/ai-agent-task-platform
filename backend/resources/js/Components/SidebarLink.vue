<template>
    <Link
        :href="href"
        :class="[
            'flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-medium transition-all',
            active
                ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
        ]"
    >
        <slot />
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    href: { type: String, required: true },
    active: { type: Boolean, default: false },
})
</script>
