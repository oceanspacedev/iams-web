<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const statusFilter = ref('');

const filteredStores = computed(() => {
    return props.stores.filter((s) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (s.name && s.name.toLowerCase().includes(query)) ||
            (s.code && s.code.toLowerCase().includes(query)) ||
            (s.area && s.area.toLowerCase().includes(query)) ||
            (s.regional && s.regional.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || s.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

const confirmModal = ref({
    show: false,
    title: '',
    message: '',
    confirmText: '',
    type: 'primary',
    action: null,
});

const openConfirm = (config) => {
    confirmModal.value = {
        show: true,
        title: config.title || 'Konfirmasi Tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, Lanjutkan',
        type: config.type || 'primary',
        action: config.action,
    };
};

const handleConfirm = () => {
    if (confirmModal.value.action) {
        confirmModal.value.action();
    }
    confirmModal.value.show = false;
};

const deleteStore = (store) => {
    openConfirm({
        title: 'Hapus Data Toko Retail',
        message: `Apakah Anda yakin ingin menghapus toko "${store.name}" (${store.code})?`,
        confirmText: 'Ya, Hapus Toko',
        type: 'danger',
        action: () => router.delete(route('admin.stores.destroy', store.id)),
    });
};
</script>

<template>
    <AppLayout title="Manajemen Toko">
        <Head title="Manajemen Toko — Admin" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Toko (Store)</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola data cabang retail, area, regional, dan penugasan PIC Auditee</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Total: {{ filteredStores.length }} Toko
                </span>
                <Link
                    :href="route('admin.stores.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    + Tambah Toko Baru
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-xs mb-6">
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari kode, nama toko, atau area..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-gray-50/50 focus:bg-white transition-colors"
                    />
                </div>
                <div class="w-full sm:w-56 shrink-0">
                    <select
                        v-model="statusFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3.5 whitespace-nowrap w-28">Kode</th>
                            <th class="px-4 py-3.5 min-w-[200px]">Nama Toko</th>
                            <th class="px-4 py-3.5 whitespace-nowrap w-44">Area / Regional</th>
                            <th class="px-4 py-3.5 min-w-[180px]">PIC Auditee</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Total Audit</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredStores.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-xs font-medium">Tidak ada toko ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="s in filteredStores"
                            :key="s.id"
                            class="hover:bg-slate-50/80 transition-colors"
                        >
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap font-mono font-semibold text-gray-900">
                                {{ s.code }}
                            </td>
                            <td class="px-4 py-3.5 align-middle font-medium text-gray-900">
                                {{ s.name }}
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-gray-600">
                                {{ s.area || '-' }} <span v-if="s.regional" class="text-gray-400">({{ s.regional }})</span>
                            </td>
                            <td class="px-4 py-3.5 align-middle text-gray-700">
                                <div class="truncate max-w-xs font-medium">{{ s.auditees }}</div>
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ s.audits_count }} audit
                                </span>
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-center">
                                <span
                                    class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border"
                                    :class="s.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-200'"
                                >
                                    {{ s.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-right space-x-1.5">
                                <Link
                                    :href="route('admin.stores.edit', s.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteStore(s)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-red-200 text-red-600 bg-white hover:bg-red-50 shadow-xs transition-colors"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modern Action Confirmation Modal -->
        <ConfirmModal
            :show="confirmModal.show"
            :title="confirmModal.title"
            :message="confirmModal.message"
            :confirm-text="confirmModal.confirmText"
            :type="confirmModal.type"
            @confirm="handleConfirm"
            @close="confirmModal.show = false"
        />
    </AppLayout>
</template>
