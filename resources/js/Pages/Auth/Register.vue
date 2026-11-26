<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

const props = defineProps({
    availableRoles: {
        type: Array,
        default: () => [],
    },
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    requested_role: 'auditor',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputBase =
    'block h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-900 shadow-sm outline-none transition-colors duration-150 placeholder:text-gray-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20';
// Border normal vs error dipisah: dua kelas border-* sekaligus saling menimpa
const inputClass = inputBase + ' border-gray-300';
const inputErrorClass = inputBase + ' border-red-600';
const passwordWrapClass =
    'flex overflow-hidden rounded-lg border bg-white shadow-sm transition-colors duration-150 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/20';
const passwordInputClass =
    'h-10 min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:ring-0';
const eyeButtonClass =
    'flex w-10 shrink-0 items-center justify-center border-l border-gray-300 text-gray-500 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-900 focus-visible:outline-none';
const secondaryButtonClass =
    'flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/20';
</script>

<template>
    <LoginLayout>
        <Head title="Daftar akun" />

        <h1 class="text-center text-xl font-semibold text-gray-900">Daftar akun</h1>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-gray-900">
                    Nama lengkap<span class="text-red-600">*</span>
                </label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Nama sesuai identitas"
                    :class="form.errors.name ? inputErrorClass : inputClass"
                />
                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-900">
                    Email<span class="text-red-600">*</span>
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="nama@perusahaan.com"
                    :class="form.errors.email ? inputErrorClass : inputClass"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-3">
                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-gray-900">
                        Nomor WhatsApp<span class="text-red-600">*</span>
                    </label>
                    <input
                        id="phone"
                        type="text"
                        inputmode="tel"
                        v-model="form.phone"
                        required
                        autocomplete="tel"
                        placeholder="081234567890"
                        :class="[form.errors.phone ? inputErrorClass : inputClass, 'tabular-nums']"
                    />
                    <p v-if="form.errors.phone" class="mt-2 text-sm text-red-600">{{ form.errors.phone }}</p>
                </div>

                <div>
                    <label for="requested_role" class="mb-2 block text-sm font-medium text-gray-900">
                        Jabatan<span class="text-red-600">*</span>
                    </label>
                    <select
                        id="requested_role"
                        v-model="form.requested_role"
                        required
                        :class="[form.errors.requested_role ? inputErrorClass : inputClass, 'pr-10']"
                    >
                        <option v-for="r in availableRoles" :key="r.value" :value="r.value">
                            {{ r.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.requested_role" class="mt-2 text-sm text-red-600">{{ form.errors.requested_role }}</p>
                </div>
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-900">
                    Kata sandi<span class="text-red-600">*</span>
                </label>
                <!-- Tombol mata menempel di sisi kanan kolom -->
                <div :class="[passwordWrapClass, form.errors.password ? 'border-red-600' : 'border-gray-300']">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
                        :class="passwordInputClass"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :class="eyeButtonClass"
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

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-900">
                    Ulangi kata sandi<span class="text-red-600">*</span>
                </label>
                <div :class="[passwordWrapClass, form.errors.password_confirmation ? 'border-red-600' : 'border-gray-300']">
                    <input
                        id="password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Ketik ulang kata sandi"
                        :class="passwordInputClass"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                        :aria-label="showPasswordConfirmation ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :class="eyeButtonClass"
                    >
                        <svg v-if="!showPasswordConfirmation" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="flex gap-3 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 16v-4M12 8h.01" />
                </svg>
                <span>Akun aktif setelah disetujui admin</span>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40"
            >
                {{ form.processing ? 'Memproses…' : 'Daftar' }}
            </button>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-gray-400">
            <span class="h-px flex-1 bg-gray-200"></span>
            atau
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        <Link :href="route('login')" :class="[secondaryButtonClass, 'w-full']">
            Sudah punya akun? Masuk
        </Link>
    </LoginLayout>
</template>
