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
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full drop-shadow-xs">
            <!-- Background Shield/Hexagon with Gradient -->
            <defs>
                <linearGradient id="iamsGradPrimary" x1="4" y1="4" x2="44" y2="44" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#3B82F6" />
                    <stop offset="1" stop-color="#1D4ED8" />
                </linearGradient>
                <linearGradient id="iamsGradAccent" x1="16" y1="8" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#60A5FA" stop-opacity="0.9" />
                    <stop offset="1" stop-color="#2563EB" stop-opacity="0.3" />
                </linearGradient>
                <linearGradient id="iamsGradCheck" x1="16" y1="18" x2="32" y2="34" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#FFFFFF" />
                    <stop offset="1" stop-color="#E2E8F0" />
                </linearGradient>
            </defs>

            <!-- Outer Shield Base -->
            <path
                d="M24 4L8 10V22C8 32.5 14.8 42.2 24 44C33.2 42.2 40 32.5 40 22V10L24 4Z"
                fill="url(#iamsGradPrimary)"
            />

            <!-- Inner Facet Highlight -->
            <path
                d="M24 6.2L10.5 11.4V21.5C10.5 30.8 16.3 39.2 24 41.5V6.2Z"
                fill="white"
                fill-opacity="0.12"
            />

            <!-- Geometric Audit Layer (Clipboard & Verification) -->
            <path
                d="M24 13C18.5 13 14 17.5 14 23C14 28.5 18.5 33 24 33C29.5 33 34 28.5 34 23C34 17.5 29.5 13 24 13Z"
                fill="url(#iamsGradAccent)"
                fill-opacity="0.3"
            />

            <!-- Sharp Verified Audit Checkmark -->
            <path
                d="M17.5 23.5L22 28L31 18.5"
                stroke="url(#iamsGradCheck)"
                stroke-width="3.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

            <!-- Mini Audit Node Pulse -->
            <circle cx="33" cy="15" r="2.5" fill="#38BDF8" />
        </svg>
    </div>
</template>
