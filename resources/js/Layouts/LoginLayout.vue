<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const year = new Date().getFullYear();
</script>

<template>
    <div class="login-shell min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        <main class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6">
            <div class="w-full max-w-[420px]">
                <div class="flex justify-center">
                    <ApplicationLogo size="h-11 w-11" />
                </div>

                <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <slot />
                </div>

                <slot name="below" />

                <p class="mt-6 text-center text-xs text-gray-500">© {{ year }} IAMS</p>
            </div>
        </main>
    </div>
</template>
