<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
});

const messages = {
    403: { title: 'Akses ditolak', text: 'Anda tidak punya akses ke halaman ini.' },
    404: { title: 'Halaman tidak ditemukan', text: 'Halaman yang Anda cari tidak ada atau sudah dipindahkan.' },
    500: { title: 'Terjadi kesalahan', text: 'Ada masalah di server. Coba lagi beberapa saat lagi.' },
    503: { title: 'Sedang dalam pemeliharaan', text: 'Sistem sedang diperbarui. Coba lagi nanti.' },
};

const message = computed(() => messages[props.status] ?? messages[500]);

const goBack = () => {
    if (window.history.length > 1) window.history.back();
    else window.location.href = '/';
};
</script>

<template>
    <LoginLayout>
        <Head :title="message.title" />

        <div class="text-center">
            <div class="text-sm font-medium text-gray-400 tabular-nums">{{ status }}</div>
            <h1 class="mt-2 text-xl font-semibold text-gray-900">{{ message.title }}</h1>
            <p class="mt-2 text-sm text-gray-500">{{ message.text }}</p>
        </div>

        <div class="mt-8 grid grid-cols-2 gap-3">
            <button
                type="button"
                @click="goBack"
                class="flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/20"
            >
                <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                Kembali
            </button>
            <!-- "/" mengarahkan ke dashboard sesuai role, atau ke halaman masuk bila belum login -->
            <a
                href="/"
                class="flex h-10 items-center justify-center rounded-lg bg-blue-600 px-3 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40"
            >
                Ke dashboard
            </a>
        </div>
    </LoginLayout>
</template>
