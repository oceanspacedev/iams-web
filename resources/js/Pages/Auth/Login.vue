<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    showDemoAccounts: {
        type: Boolean,
        default: true,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillCredentials = (email) => {
    form.email = email;
    form.password = 'password';
};

const demoAccounts = [
    { role: 'Admin', email: 'admin@auditflow.com' },
    { role: 'Chief Auditor', email: 'chief@auditflow.com' },
    { role: 'Asmen', email: 'asmen@auditflow.com' },
    { role: 'Koordinator', email: 'kordinator@auditflow.com' },
    { role: 'Auditor', email: 'auditor@auditflow.com' },
    { role: 'Auditee', email: 'auditee@auditflow.com' },
];

const inputBase =
    'block h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-900 shadow-sm outline-none transition-colors duration-150 placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20';
// Border normal vs error dipisah: dua kelas border-* sekaligus saling menimpa
const inputClass = inputBase + ' border-gray-300';
const inputErrorClass = inputBase + ' border-red-600';
const secondaryButtonClass =
    'flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/20';
</script>

<template>
    <LoginLayout>
        <Head title="Masuk" />

        <h1 class="text-center text-xl font-semibold text-gray-900">Masuk ke akun Anda</h1>

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

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-900">
                    Kata sandi<span class="text-red-600">*</span>
                </label>
                <!-- Tombol mata menempel di sisi kanan kolom -->
                <div
                    class="flex overflow-hidden rounded-lg border bg-white shadow-sm transition-colors duration-150 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/20"
                    :class="form.errors.password ? 'border-red-600' : 'border-gray-300'"
                >
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="h-10 min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:ring-0"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        class="flex w-10 shrink-0 items-center justify-center border-l border-gray-300 text-gray-500 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus-visible:outline-none"
                    >
                        <svg v-if="!showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" />
                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
                            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" />
                            <path d="m2 2 20 20" />
                        </svg>
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex w-fit cursor-pointer select-none items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="form.remember" class="h-4 w-4 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-600" />
                    Ingat saya
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-blue-600 transition-colors duration-150 hover:text-blue-800"
                >
                    Lupa kata sandi?
                </Link>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40"
            >
                {{ form.processing ? 'Memproses…' : 'Masuk' }}
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-gray-400">
            <span class="h-px flex-1 bg-gray-200"></span>
            atau
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <Link :href="route('login.whatsapp')" :class="secondaryButtonClass">
                <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" />
                </svg>
                WhatsApp
            </Link>
            <Link :href="route('register')" :class="secondaryButtonClass">
                <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M19 8v6M22 11h-6" />
                </svg>
                Daftar akun
            </Link>
        </div>

        <template v-if="showDemoAccounts" #below>
            <div class="mt-6">
                <div class="text-center text-xs text-gray-500">Akun demo</div>
                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <button
                        v-for="account in demoAccounts"
                        :key="account.email"
                        type="button"
                        @click="fillCredentials(account.email)"
                        :title="account.email"
                        class="h-8 rounded-lg border px-3 text-xs shadow-sm transition-colors duration-150"
                        :class="form.email === account.email
                            ? 'border-blue-600 bg-blue-50 text-blue-700'
                            : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900'"
                    >
                        {{ account.role }}
                    </button>
                </div>
            </div>
        </template>
    </LoginLayout>
</template>
