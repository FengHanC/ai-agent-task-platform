<template>
    <Link
        :href="href"
        :class="[
            'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
            active
                ? 'bg-indigo-50 text-indigo-700'
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
