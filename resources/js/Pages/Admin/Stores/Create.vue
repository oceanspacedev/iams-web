<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    auditees: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    code: '',
    name: '',
    area: '',
    regional: '',
    status: 'active',
    auditees: [],
});

const submit = () => {
    form.post(route('admin.stores.store'));
};
</script>

<template>
    <AppLayout title="Tambah Toko Baru">
        <Head title="Tambah Toko Baru" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('admin.stores.index')" class="hover:text-blue-600">Stores</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Tambah Toko</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Tambah Toko Baru</h1>
            <p class="text-xs text-gray-500 mt-1">Daftarkan data unit toko ritel baru ke sistem</p>
        </div>

        <div class="bg-white rounded border border-gray-200 p-6 shadow-xs max-w-2xl text-xs">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Kode Toko <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.code"
                            type="text"
                            required
                            placeholder="Contoh: STR-003"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 uppercase font-mono"
                        />
                        <div v-if="form.errors.code" class="text-red-600 text-[11px] mt-1">{{ form.errors.code }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nama Toko <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Contoh: Toko Jakarta Barat"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.name" class="text-red-600 text-[11px] mt-1">{{ form.errors.name }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Area</label>
                        <input
                            v-model="form.area"
                            type="text"
                            placeholder="DKI Jakarta"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Regional</label>
                        <input
                            v-model="form.regional"
                            type="text"
                            placeholder="Jabodetabek"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <!-- Auditee PIC Assignment -->
                <div class="pt-2 border-t border-gray-100">
                    <label class="block font-medium text-gray-700 mb-1.5">Hubungkan Pengguna Auditee (PIC Toko)</label>
                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-3 bg-gray-50 rounded border border-gray-200">
                        <label v-for="a in auditees" :key="a.id" class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                :value="a.id"
                                v-model="form.auditees"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="text-gray-800">{{ a.name }}</span>
                        </label>
                    </div>
                    <div v-if="form.errors.auditees" class="text-red-600 text-[11px] mt-1">{{ form.errors.auditees }}</div>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.stores.index')"
                        class="px-3.5 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Toko' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
