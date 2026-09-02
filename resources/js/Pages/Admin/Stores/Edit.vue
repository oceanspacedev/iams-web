<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    store: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    code: props.store.code,
    name: props.store.name,
    business_entity: props.store.business_entity || '',
    type: props.store.type || 'TOKO',
    area: props.store.area || '',
    regional: props.store.regional || '',
    address: props.store.address || '',
    status: props.store.status || 'active',
});

const submit = () => {
    form.put(route('admin.stores.update', props.store.id));
};
</script>

<template>
    <AppLayout :title="`Edit Toko - ${store.name}`">
        <Head :title="`Edit Toko - ${store.name} — Admin`" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('admin.stores.index')" class="hover:text-blue-600">Stores</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">{{ store.code }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Edit Toko / CSA</h1>
            <p class="text-xs text-gray-500 mt-1">Perbarui data unit cabang retail, gudang, atau badan usaha finance</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-xs max-w-2xl text-xs">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Kode Unit / Toko <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.code"
                            type="text"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 uppercase font-mono"
                        />
                        <div v-if="form.errors.code" class="text-red-600 text-[11px] mt-1">{{ form.errors.code }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nama Toko / Unit <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.name" class="text-red-600 text-[11px] mt-1">{{ form.errors.name }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Badan Usaha (Finance / PT / CV)</label>
                        <input
                            v-model="form.business_entity"
                            type="text"
                            placeholder="Contoh: PT Sumber Ritel Utama / CV Maju"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.business_entity" class="text-red-600 text-[11px] mt-1">{{ form.errors.business_entity }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Tipe Unit <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.type"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                        >
                            <option value="TOKO">TOKO (Cabang Retail)</option>
                            <option value="GUDANG">GUDANG (Warehouse / DC)</option>
                            <option value="FINANCE">FINANCE / Kantor Pusat (HO)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Area</label>
                        <input
                            v-model="form.area"
                            type="text"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Regional</label>
                        <input
                            v-model="form.regional"
                            type="text"
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
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                    <textarea
                        v-model="form.address"
                        rows="2"
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.stores.index')"
                        class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Toko / CSA' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
