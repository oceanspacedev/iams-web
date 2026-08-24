<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    pending_findings: {
        type: Array,
        default: () => [],
    },
});

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};
</script>

<template>
    <AppLayout title="Dashboard Koordinator">
        <Head title="Koordinator Audit Dashboard" />

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Dashboard Koordinator Audit</h1>
            <p class="text-xs text-gray-500 mt-1">Review dan standarisasi severity temuan, monitoring pelaksanaan audit seluruh cabang</p>
        </div>

        <!-- Metrics Overview Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded border border-amber-200 shadow-xs">
                <div class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider">Perlu Review Severity</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.pending_reviews }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5">Temuan belum dikunci koordinator</div>
            </div>

            <div class="bg-white p-4 rounded border border-gray-200 shadow-xs">
                <div class="text-[11px] font-semibold text-gray-700 uppercase tracking-wider">Severity Terkunci</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.reviewed_locked }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5">Sudah disetujui dan dikunci</div>
            </div>

            <div class="bg-white p-4 rounded border border-gray-200 shadow-xs">
                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Total Seluruh Temuan</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_findings }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5">Dari {{ stats.total_audits }} penugasan audit</div>
            </div>

            <div class="bg-white p-4 rounded border border-gray-200 shadow-xs">
                <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Estimasi Kerugian (Loss)</div>
                <div class="text-lg font-bold text-gray-900 mt-1.5 truncate">{{ formatRupiah(stats.total_loss) }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5">Akumulasi kerugian retail</div>
            </div>
        </div>

        <!-- Section: Pending Severity Reviews -->
        <div class="bg-white rounded border border-gray-200 shadow-xs text-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Daftar Temuan Menunggu Review Severity ({{ stats.pending_reviews }})</h2>
                    <p class="text-[11px] text-gray-500">Tinjau dan tentukan tingkat severity final untuk temuan berikut</p>
                </div>
                <Link
                    :href="route('coordinator.findings.index')"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800"
                >
                    Lihat Semua Temuan →
                </Link>
            </div>

            <div v-if="pending_findings.length === 0" class="p-8 text-center text-gray-500 italic">
                Tidak ada temuan yang menunggu review severity saat ini.
            </div>

            <div v-else class="divide-y divide-gray-100">
                <div
                    v-for="item in pending_findings"
                    :key="item.id"
                    class="p-4 hover:bg-gray-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors"
                >
                    <div class="space-y-1 max-w-2xl">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-semibold text-gray-900">{{ item.audit_number }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="font-semibold text-gray-900">{{ item.store }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-500">Auditor: {{ item.auditor }}</span>
                        </div>
                        <p class="text-gray-800 line-clamp-1 font-medium">{{ item.finding }}</p>
                        <div class="flex items-center gap-2 text-[11px] text-gray-500">
                            <span>Kategori: {{ item.category }}</span>
                            <span>•</span>
                            <span>Kerugian: {{ formatRupiah(item.loss_amount) }}</span>
                            <span>•</span>
                            <span>{{ item.created_at }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500">Usulan Auditor:</div>
                            <SeverityBadge :severity="item.severity" />
                        </div>

                        <Link
                            :href="route('coordinator.findings.show', item.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium text-xs shadow-xs transition-colors"
                        >
                            Review & Kunci
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
