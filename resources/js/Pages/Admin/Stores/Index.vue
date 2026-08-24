<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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
        const matchesSearch =
            s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            s.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (s.area && s.area.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (s.regional && s.regional.toLowerCase().includes(searchQuery.value.toLowerCase()));

        const matchesStatus = !statusFilter.value || s.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

const deleteStore = (store) => {
    if (confirm(`Hapus toko ${store.name} (${store.code})?`)) {
        router.delete(route('admin.stores.destroy', store.id));
    }
};
</script>

<template>
    <AppLayout title="Manajemen Toko">
        <Head title="Manajemen Toko" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Toko (Store)</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola data cabang retail, area, regional, dan penugasan PIC Auditee</p>
            </div>

            <Link
                :href="route('admin.stores.create')"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
            >
                + Tambah Toko Baru
            </Link>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari kode, nama toko, atau area..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <div class="w-full sm:w-auto">
                <select
                    v-model="statusFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">Kode</th>
                            <th class="px-5 py-3.5">Nama Toko</th>
                            <th class="px-5 py-3.5">Area / Regional</th>
                            <th class="px-5 py-3.5">PIC Auditee</th>
                            <th class="px-5 py-3.5">Total Audit</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredStores.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">Tidak ada toko ditemukan.</td>
                        </tr>
                        <tr v-for="s in filteredStores" :key="s.id" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-mono font-medium text-gray-900">{{ s.code }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-900">{{ s.name }}</td>
                            <td class="px-5 py-3.5 text-gray-600">
                                {{ s.area || '-' }} <span v-if="s.regional" class="text-gray-400">({{ s.regional }})</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700 max-w-xs truncate">{{ s.auditees }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-800">{{ s.audits_count }} audit</td>
                            <td class="px-5 py-3.5">
                                <span
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-medium border"
                                    :class="s.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-600 border-gray-200'"
                                >
                                    {{ s.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                <Link
                                    :href="route('admin.stores.edit', s.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteStore(s)"
                                    class="px-2.5 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
