<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-lg border border-gray-300 bg-white h-9 px-4 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:opacity-25 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800"
    >
        <slot />
    </button>
</template>
