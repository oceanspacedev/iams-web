<script setup>
defineProps({
    size: {
        type: String,
        default: 'h-10 w-10',
    },
});
</script>

<template>
    <div :class="['relative shrink-0 flex items-center justify-center', size]">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full" aria-hidden="true">
            <!-- Perisai flat dengan tanda verifikasi -->
            <path
                d="M24 4L8 10V22C8 32.5 14.8 42.2 24 44C33.2 42.2 40 32.5 40 22V10L24 4Z"
                fill="#2563EB"
            />
            <path
                d="M17.5 23.5L22 28L31 18.5"
                stroke="#FFFFFF"
                stroke-width="3.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </div>
</template>
