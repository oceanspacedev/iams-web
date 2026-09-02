<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    findings: {
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
const severityFilter = ref('');
const categoryFilter = ref('');
const currentPage = ref(1);

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const filteredFindings = computed(() => {
    return props.findings.filter((f) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (f.finding && f.finding.toLowerCase().includes(query)) ||
            (f.store && f.store.toLowerCase().includes(query)) ||
            (f.audit_number && f.audit_number.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || f.status === statusFilter.value;
        const matchesSeverity = !severityFilter.value || f.severity === severityFilter.value;
        const matchesCategory = !categoryFilter.value || f.category === categoryFilter.value;

        return matchesSearch && matchesStatus && matchesSeverity && matchesCategory;
    });
});

// Reset pagination when filter changes
watch([searchQuery, statusFilter, severityFilter, categoryFilter], () => {
    currentPage.value = 1;
});

const paginatedFindings = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredFindings.value.slice(start, start + 10);
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

const deleteFinding = (finding) => {
    openConfirm({
        title: 'Hapus Temuan Audit (Finding)',
        message: `Apakah Anda yakin ingin menghapus temuan "${finding.finding.substring(0, 50)}..."?`,
        confirmText: 'Ya, Hapus Temuan',
        type: 'danger',
        action: () => router.delete(route('admin.findings.destroy', finding.id)),
    });
};
</script>

<template>
    <AppLayout title="Semua Temuan (Findings)">
        <Head title="Daftar Temuan — Admin" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Daftar Seluruh Temuan Audit (Findings)</h1>
                <p class="text-xs text-gray-500 mt-1">Monitoring dan pengawasan temuan audit dari seluruh unit cabang toko retail</p>
            </div>
            <div>
                <span class="text-xs text-gray-500 font-mono">
                    {{ filteredFindings.length }} Temuan terdaftar
                </span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-2xs mb-5 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <div class="relative sm:col-span-2 md:col-span-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari temuan, toko, no. audit..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    />
                </div>

                <div>
                    <select
                        v-model="severityFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Severity</option>
                        <option value="MINOR">Minor</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="MAJOR">Major</option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="statusFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="OPEN">Open</option>
                        <option value="IN_PROGRESS">In Progress</option>
                        <option value="CLOSED">Closed</option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="categoryFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clean Enterprise Table with 10-Item Pagination -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap w-44">No. Audit & Toko</th>
                            <th class="px-4 py-3 min-w-[240px]">Kategori & Temuan</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Severity</th>
                            <th class="px-4 py-3 whitespace-nowrap w-36">Nominal Kerugian</th>
                            <th class="px-4 py-3 min-w-[220px]">Komitmen Tindak Lanjut</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data temuan yang sesuai filter.
                            </td>
                        </tr>
                        <tr
                            v-for="f in paginatedFindings"
                            :key="f.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                <Link
                                    :href="route('admin.audits.show', f.audit_id)"
                                    class="font-mono font-semibold text-slate-800 hover:text-blue-600 transition-colors"
                                >
                                    {{ f.audit_number }}
                                </Link>
                                <div class="text-xs text-gray-900 font-medium mt-0.5">{{ f.store }}</div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="text-[11px] font-medium text-slate-500 mb-0.5">
                                    {{ f.category }}
                                </div>
                                <div class="text-gray-900 leading-relaxed font-normal line-clamp-3">{{ f.finding }}</div>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <SeverityBadge :severity="f.severity" />
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap font-medium text-gray-900 font-mono text-[11px]">
                                {{ formatRupiah(f.loss_amount) }}
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div v-if="f.action_plan" class="space-y-0.5">
                                    <div class="text-gray-900 text-xs line-clamp-2 font-normal">{{ f.action_plan }}</div>
                                    <div class="text-[11px] text-gray-500 font-mono">PIC: {{ f.pic || '—' }} | Target: {{ f.deadline || '—' }}</div>
                                </div>
                                <div v-else class="text-gray-400 font-normal italic text-xs">Belum diisi</div>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-center">
                                <StatusBadge :status="f.status" />
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        :href="route('admin.findings.show', f.id)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Detail
                                    </Link>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteFinding(f)"
                                        class="text-red-600 hover:text-red-800 font-medium hover:underline text-xs"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredFindings.length"
                @update:current-page="currentPage = $event"
            />
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
