<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

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
        const matchesSearch =
            f.finding.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.store.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            f.audit_number.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesStatus = !statusFilter.value || f.status === statusFilter.value;
        const matchesSeverity = !severityFilter.value || f.severity === severityFilter.value;
        const matchesCategory = !categoryFilter.value || f.category === categoryFilter.value;

        return matchesSearch && matchesStatus && matchesSeverity && matchesCategory;
    });
});

const deleteFinding = (finding) => {
    if (confirm('Hapus temuan audit ini?')) {
        router.delete(route('admin.findings.destroy', finding.id));
    }
};
</script>

<template>
    <AppLayout title="Semua Temuan (Findings)">
        <Head title="Manajemen Findings" />

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Daftar Seluruh Temuan Audit (Findings)</h1>
            <p class="text-xs text-gray-500 mt-1">Monitoring dan pengawasan temuan audit dari seluruh unit cabang toko retail</p>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col md:flex-row gap-3 items-center justify-between">
            <div class="w-full md:w-72">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari temuan, toko, atau no. audit..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div class="w-full md:w-auto flex flex-wrap gap-2.5">
                <select
                    v-model="severityFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Severity</option>
                    <option value="CRITICAL">CRITICAL</option>
                    <option value="MAJOR">MAJOR</option>
                    <option value="MINOR">MINOR</option>
                    <option value="OBSERVATION">OBSERVATION</option>
                </select>

                <select
                    v-model="statusFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Status</option>
                    <option value="OPEN">Open</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="WAITING_VERIFICATION">Waiting Verification</option>
                    <option value="VERIFIED">Verified</option>
                    <option value="CLOSED">Closed</option>
                </select>

                <select
                    v-model="categoryFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Kategori</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.name">{{ cat.name }}</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3.5">No. Audit & Toko</th>
                            <th class="px-4 py-3.5">Kategori & Temuan</th>
                            <th class="px-4 py-3.5">Severity</th>
                            <th class="px-4 py-3.5">Nominal Kerugian</th>
                            <th class="px-4 py-3.5">Action Plan</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredFindings.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada data finding yang sesuai kriteria.</td>
                        </tr>
                        <tr v-for="f in filteredFindings" :key="f.id" class="hover:bg-gray-50/70">
                            <td class="px-4 py-3.5">
                                <div class="font-mono font-medium text-blue-600 hover:underline">
                                    <Link :href="route('admin.audits.show', f.audit_id)">{{ f.audit_number }}</Link>
                                </div>
                                <div class="text-[11px] text-gray-900 font-semibold">{{ f.store }}</div>
                            </td>
                            <td class="px-4 py-3.5 max-w-sm">
                                <div class="text-[11px] font-semibold text-gray-500 mb-0.5">{{ f.category }}</div>
                                <div class="text-gray-900 line-clamp-2 leading-relaxed">{{ f.finding }}</div>
                            </td>
                            <td class="px-4 py-3.5"><SeverityBadge :severity="f.severity" /></td>
                            <td class="px-4 py-3.5 font-medium text-gray-900">{{ formatRupiah(f.loss_amount) }}</td>
                            <td class="px-4 py-3.5">
                                <div v-if="f.action_plan" class="space-y-0.5">
                                    <div class="text-gray-900 truncate max-w-xs font-medium">{{ f.action_plan }}</div>
                                    <div class="text-[10px] text-gray-500">PIC: {{ f.pic || '-' }} | DL: {{ f.deadline || '-' }}</div>
                                </div>
                                <div v-else class="text-red-500 italic text-[11px]">Belum diisi</div>
                            </td>
                            <td class="px-4 py-3.5"><StatusBadge :status="f.status" /></td>
                            <td class="px-4 py-3.5 text-right space-x-2">
                                <Link
                                    :href="route('admin.findings.show', f.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Detail
                                </Link>
                                <button
                                    @click="deleteFinding(f)"
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
