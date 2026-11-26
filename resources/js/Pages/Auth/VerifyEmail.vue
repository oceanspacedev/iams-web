<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <LoginLayout>
        <Head title="Verifikasi email" />

        <h1 class="text-center text-xl font-semibold text-gray-900">Verifikasi email</h1>

        <p class="mt-4 text-center text-sm text-gray-600">
            Klik link verifikasi yang kami kirim ke email Anda
        </p>

        <div v-if="verificationLinkSent" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Link verifikasi baru sudah dikirim
        </div>

        <form @submit.prevent="submit" class="mt-6">
            <button
                type="submit"
                :disabled="form.processing"
                class="flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40"
            >
                {{ form.processing ? 'Memproses…' : 'Kirim ulang email verifikasi' }}
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-gray-400">
            <span class="h-px flex-1 bg-gray-200"></span>
            atau
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/20"
        >
            <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
            </svg>
            Keluar
        </Link>
    </LoginLayout>
</template>
