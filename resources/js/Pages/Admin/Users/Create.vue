<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    stores: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: 'auditor',
    is_active: true,
    stores: [],
});

const roleLabels = {
    admin: 'Admin',
    chief: 'Chief Auditor',
    asmen: 'Asisten Manager',
    coordinator: 'Koordinator Audit',
    auditor: 'Staff Audit',
    auditee: 'Auditee toko',
};

const storeSearch = ref('');
const filteredStores = computed(() => {
    const q = storeSearch.value.toLowerCase().trim();
    if (!q) return props.stores;
    return props.stores.filter((s) =>
        (s.name && s.name.toLowerCase().includes(q)) || (s.code && s.code.toLowerCase().includes(q)),
    );
});

const storesError = computed(() => {
    if (form.errors.stores) return form.errors.stores;
    const key = Object.keys(form.errors).find((k) => k.startsWith('stores.'));
    return key ? form.errors[key] : '';
});

const submit = () => {
    form.post(route('admin.users.store'));
};
</script>

<template>
    <AppLayout title="Tambah pengguna">
        <Head title="Tambah pengguna" />

        <!-- Header -->
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('admin.users.index')" class="transition-colors duration-150 hover:text-blue-600">Pengguna</Link>
                <span>/</span>
                <span class="font-medium text-gray-900">Baru</span>
            </div>
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Tambah pengguna</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6 text-sm">
            <!-- Profil -->
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Profil</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-900">Nama lengkap <span class="text-red-600">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">Email <span class="text-red-600">*</span></label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="user@auditflow.com"
                            class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        />
                        <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">No. WhatsApp</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            placeholder="081234567890"
                            class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        />
                        <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</div>
                    </div>
                </div>
            </section>

            <!-- Akses -->
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Akses</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">Role <span class="text-red-600">*</span></label>
                        <select
                            v-model="form.role"
                            required
                            class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        >
                            <option v-for="r in roles" :key="r" :value="r">{{ roleLabels[r] || r }}</option>
                        </select>
                        <div v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">Status</label>
                        <select
                            v-model="form.is_active"
                            class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        >
                            <option :value="true">Aktif</option>
                            <option :value="false">Nonaktif</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-900">Password <span class="text-red-600">*</span></label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            required
                            placeholder="Min. 6 karakter"
                            class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        />
                        <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</div>
                    </div>

                    <div v-if="form.role === 'auditee'" class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Toko <span class="text-red-600">*</span>
                            <span class="ml-1 font-normal text-gray-500 tabular-nums">{{ form.stores.length }} dipilih</span>
                        </label>
                        <div class="overflow-hidden rounded-lg border border-gray-300 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-2">
                                <input
                                    v-model="storeSearch"
                                    type="text"
                                    placeholder="Cari"
                                    aria-label="Cari toko"
                                    class="h-9 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                            </div>
                            <div class="max-h-60 overflow-y-auto py-1">
                                <label
                                    v-for="s in filteredStores"
                                    :key="s.id"
                                    class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm transition-colors duration-150 hover:bg-gray-50"
                                >
                                    <input
                                        v-model="form.stores"
                                        type="checkbox"
                                        :value="s.id"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600/20"
                                    />
                                    <span class="w-20 shrink-0 text-gray-500 tabular-nums">{{ s.code }}</span>
                                    <span class="min-w-0 truncate text-gray-900">{{ s.name }}</span>
                                </label>
                                <div v-if="!filteredStores.length" class="px-3 py-2 text-sm text-gray-500">Tidak ada toko</div>
                            </div>
                        </div>
                        <div v-if="storesError" class="mt-1 text-sm text-red-600">{{ storesError }}</div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                    <Link
                        :href="route('admin.users.index')"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                    >
                        {{ form.processing ? 'Menyimpan…' : 'Simpan user' }}
                    </button>
                </div>
            </section>
        </form>
    </AppLayout>
</template>
