<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};

const inputBase =
    'block h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-900 shadow-sm outline-none transition-colors duration-150 placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20';
// Border normal vs error dipisah: dua kelas border-* sekaligus saling menimpa
const inputClass = inputBase + ' border-gray-300';
const inputErrorClass = inputBase + ' border-red-600';
const secondaryButtonClass =
    'flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/20';
</script>

<template>
    <LoginLayout>
        <Head title="Lupa kata sandi" />

        <h1 class="text-center text-xl font-semibold text-gray-900">Lupa kata sandi</h1>

        <div v-if="status" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-900">
                    Email<span class="text-red-600">*</span>
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@perusahaan.com"
                    :class="form.errors.email ? inputErrorClass : inputClass"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40"
            >
                {{ form.processing ? 'Memproses…' : 'Kirim link reset' }}
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-gray-400">
            <span class="h-px flex-1 bg-gray-200"></span>
            atau
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        <Link :href="route('login')" :class="secondaryButtonClass">
            <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m12 19-7-7 7-7M19 12H5" />
            </svg>
            Kembali ke halaman masuk
        </Link>
    </LoginLayout>
</template>
