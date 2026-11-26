<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-12 antialiased sm:px-6 dark:bg-gray-900"
    >
        <div
            class="w-full max-w-[400px] overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_8px_24px_rgba(15,23,42,0.06)] sm:p-10 dark:bg-gray-800"
        >
            <Link href="/" class="flex w-fit items-center gap-3">
                <ApplicationLogo size="h-9 w-9" />
                <div>
                    <div class="text-base font-semibold leading-5 text-gray-900">IAMS</div>
                    <div class="text-xs leading-4 text-gray-500">Sistem Audit Internal</div>
                </div>
            </Link>

            <div class="mt-8">
                <slot />
            </div>
        </div>
    </div>
</template>
