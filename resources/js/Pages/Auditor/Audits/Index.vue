<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    audits: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const statusFilter = ref('');
const categoryFilter = ref('');
const currentPage = ref(1);

const filteredAudits = computed(() => {
    return props.audits.filter((audit) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (audit.audit_number && audit.audit_number.toLowerCase().includes(query)) ||
            (audit.store && audit.store.toLowerCase().includes(query)) ||
            (audit.store_area && audit.store_area.toLowerCase().includes(query)) ||
            (audit.category && audit.category.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || audit.status === statusFilter.value;
        const matchesCategory = !categoryFilter.value || audit.category_id == categoryFilter.value || audit.category === categoryFilter.value;

        return matchesSearch && matchesStatus && matchesCategory;
    });
});

watch([searchQuery, statusFilter, categoryFilter], () => {
    currentPage.value = 1;
});

const paginatedAudits = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredAudits.value.slice(start, start + 10);
});
</script>

<template>
    <AppLayout title="Penugasan Audit">
        <Head title="My Audits" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Daftar Penugasan Audit</h1>
                <p class="text-xs text-gray-500 mt-1">Seluruh jadwal dan riwayat audit yang ditugaskan kepada Anda</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 font-mono">
                    {{ filteredAudits.length }} Penugasan terdaftar
                </span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-2xs mb-5 text-xs flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-72">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nomor audit, nama toko, kategori..."
                    class="w-full text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 py-1.5 bg-white"
                />
            </div>
            <div class="w-full sm:w-auto flex items-center gap-3">
                <select
                    v-model="categoryFilter"
                    class="text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 py-1.5 bg-white font-medium"
                >
                    <option value="">Semua Kategori Audit</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
                <select
                    v-model="statusFilter"
                    class="text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 py-1.5 bg-white"
                >
                    <option value="">Semua Status</option>
                    <option value="PLANNED">Planned</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CLOSED">Closed</option>
                </select>
            </div>
        </div>

        <!-- Clean Enterprise Table with 10-Item Pagination -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap w-44">No. Surat Tugas</th>
                            <th class="px-4 py-3 whitespace-nowrap w-44">Kategori Audit</th>
                            <th class="px-4 py-3 min-w-[200px]">Toko & Lokasi</th>
                            <th class="px-4 py-3 whitespace-nowrap w-36">Tanggal Audit</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Temuan</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredAudits.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada penugasan audit yang ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="audit in paginatedAudits"
                            :key="audit.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle whitespace-nowrap">
                                <Link
                                    :href="route('auditor.audits.show', audit.id)"
                                    class="font-mono font-semibold text-slate-800 hover:text-blue-600 transition-colors"
                                >
                                    {{ audit.audit_number }}
                                </Link>
                                <div v-if="audit.title" class="text-[11px] text-gray-500 truncate max-w-[180px]">{{ audit.title }}</div>
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold tracking-wide border"
                                    :class="{
                                        'bg-emerald-50 text-emerald-800 border-emerald-200': audit.category && audit.category.includes('Retail'),
                                        'bg-amber-50 text-amber-800 border-amber-200': audit.category && audit.category.includes('Finance'),
                                        'bg-indigo-50 text-indigo-800 border-indigo-200': audit.category && audit.category.includes('Distribusi'),
                                        'bg-slate-100 text-slate-700 border-slate-200': !audit.category || audit.category === '—'
                                    }"
                                >
                                    {{ audit.category || '—' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 align-middle">
                                <div class="font-medium text-gray-900">{{ audit.store }}</div>
                                <div class="text-[11px] text-gray-500 font-mono">{{ audit.store_area || audit.store_code }}</div>
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-gray-600 font-mono text-[11px]">
                                {{ audit.audit_date }}
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ audit.findings_count }} temuan
                                </span>
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <StatusBadge :status="audit.status" />
                            </td>

                            <td class="px-4 py-3 align-middle whitespace-nowrap text-right">
                                <Link
                                    :href="route('auditor.audits.show', audit.id)"
                                    class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                >
                                    Buka Audit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredAudits.length"
                @update:current-page="currentPage = $event"
            />
        </div>
    </AppLayout>
</template>
