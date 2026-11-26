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
    type: (props.store.type || 'toko').toLowerCase(),
    area: props.store.area || '',
    regional: props.store.regional || '',
    address: props.store.address || '',
    status: props.store.status || 'active',
});

const submit = () => {
    form.put(route('coordinator.stores.update', props.store.id));
};
</script>

<template>
    <AppLayout :title="`Edit unit ${store.name}`">
        <Head :title="`Edit unit ${store.name}`" />

        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('coordinator.stores.index')" class="transition-colors duration-150 hover:text-blue-600">Toko & gudang</Link>
                <span>/</span>
                <span class="font-medium text-gray-900 tabular-nums">{{ store.code }}</span>
            </div>
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Edit unit</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
            <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                <h2 class="text-base font-semibold text-gray-900">Identitas</h2>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-900">Kode <span class="text-red-600">*</span></label>
                    <input
                        v-model="form.code"
                        type="text"
                        required
                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm uppercase tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                    <div v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-900">Tipe <span class="text-red-600">*</span></label>
                    <select
                        v-model="form.type"
                        required
                        class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    >
                        <option value="toko">Toko (cabang retail)</option>
                        <option value="gudang">Gudang (warehouse / DC)</option>
                        <option value="head_office">Head office</option>
                        <option value="hub">Hub</option>
                    </select>
                    <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-900">Status</label>
                    <select
                        v-model="form.status"
                        class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    >
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <div v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</div>
                </div>

                <div class="md:col-span-3">
                    <label class="mb-2 block text-sm font-medium text-gray-900">Nama <span class="text-red-600">*</span></label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                    <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                </div>

                <div class="md:col-span-3">
                    <label class="mb-2 block text-sm font-medium text-gray-900">Badan usaha</label>
                    <input
                        v-model="form.business_entity"
                        type="text"
                        placeholder="PT / CV"
                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                    <div v-if="form.errors.business_entity" class="mt-1 text-sm text-red-600">{{ form.errors.business_entity }}</div>
                </div>
            </div>

            <div class="flex h-14 items-center justify-between gap-4 border-y border-gray-200 px-5">
                <h2 class="text-base font-semibold text-gray-900">Lokasi</h2>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-900">Area</label>
                    <input
                        v-model="form.area"
                        type="text"
                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                    <div v-if="form.errors.area" class="mt-1 text-sm text-red-600">{{ form.errors.area }}</div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-900">Regional</label>
                    <input
                        v-model="form.regional"
                        type="text"
                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                    <div v-if="form.errors.regional" class="mt-1 text-sm text-red-600">{{ form.errors.regional }}</div>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-900">Alamat</label>
                    <textarea
                        v-model="form.address"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    ></textarea>
                    <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                <Link
                    :href="route('coordinator.stores.index')"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    Batal
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                >
                    {{ form.processing ? 'Menyimpan…' : 'Simpan perubahan' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
