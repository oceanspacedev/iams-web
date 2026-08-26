<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
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
        <Head title="Manajemen Findings — Admin" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Daftar Seluruh Temuan Audit (Findings)</h1>
                <p class="text-xs text-gray-500 mt-1">Monitoring dan pengawasan temuan audit dari seluruh unit cabang toko retail</p>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Total: {{ filteredFindings.length }} Temuan
                </span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-xs mb-6">
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
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-gray-50/50 focus:bg-white transition-colors"
                    />
                </div>

                <div>
                    <select
                        v-model="severityFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Severity</option>
                        <option value="CRITICAL">CRITICAL</option>
                        <option value="MAJOR">MAJOR</option>
                        <option value="MINOR">MINOR</option>
                        <option value="OBSERVATION">OBSERVATION</option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="statusFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="OPEN">Open</option>
                        <option value="IN_PROGRESS">In Progress</option>
                        <option value="WAITING_VERIFICATION">Waiting Verification</option>
                        <option value="VERIFIED">Verified</option>
                        <option value="CLOSED">Closed</option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="categoryFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
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
                            <th class="px-4 py-3.5 whitespace-nowrap w-44">No. Audit & Toko</th>
                            <th class="px-4 py-3.5 min-w-[220px]">Kategori & Temuan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Severity</th>
                            <th class="px-4 py-3.5 whitespace-nowrap w-36">Nominal Kerugian</th>
                            <th class="px-4 py-3.5 min-w-[200px]">Action Plan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span class="text-xs font-medium">Tidak ada data finding yang sesuai.</span>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="f in filteredFindings"
                            :key="f.id"
                            class="hover:bg-slate-50/80 transition-colors"
                        >
                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                <Link
                                    :href="route('admin.audits.show', f.audit_id)"
                                    class="font-mono font-semibold text-blue-600 hover:underline"
                                >
                                    {{ f.audit_number }}
                                </Link>
                                <div class="text-[11px] text-gray-900 font-semibold mt-0.5">{{ f.store }}</div>
                            </td>

                            <td class="px-4 py-3.5 align-top">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-100 text-slate-700 mb-1 border border-slate-200">
                                    {{ f.category }}
                                </span>
                                <div class="text-gray-900 leading-relaxed font-medium line-clamp-3">{{ f.finding }}</div>
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-center">
                                <SeverityBadge :severity="f.severity" />
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap font-semibold text-gray-900">
                                {{ formatRupiah(f.loss_amount) }}
                            </td>

                            <td class="px-4 py-3.5 align-top">
                                <div v-if="f.action_plan" class="space-y-1">
                                    <div class="text-gray-900 text-[11px] line-clamp-2">{{ f.action_plan }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono">PIC: {{ f.pic || '-' }} | DL: {{ f.deadline || '-' }}</div>
                                </div>
                                <div v-else class="text-red-500 italic text-[11px]">Belum diisi</div>
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-center">
                                <StatusBadge :status="f.status" />
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-right space-x-1.5">
                                <Link
                                    :href="route('admin.findings.show', f.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                                >
                                    Detail
                                </Link>
                                <button
                                    @click="deleteFinding(f)"
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
