<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

const showPassword = ref(false);

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <LoginLayout>
        <Head title="Konfirmasi kata sandi" />

        <h1 class="text-center text-xl font-semibold text-gray-900">Konfirmasi kata sandi</h1>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
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
                        autofocus
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

            <button
                type="submit"
                :disabled="form.processing"
                class="flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40"
            >
                {{ form.processing ? 'Memproses…' : 'Konfirmasi' }}
            </button>
        </form>
    </LoginLayout>
</template>
