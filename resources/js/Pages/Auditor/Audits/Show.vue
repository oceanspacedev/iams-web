<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
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
    <AppLayout :title="`Audit ${audit.audit_number}`">
        <Head :title="`Detail Audit ${audit.audit_number}`" />

        <!-- Top Breadcrumb & Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('auditor.audits.index')" class="hover:text-blue-600">My Audits</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">{{ audit.audit_number }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                    {{ audit.audit_number }}
                    <StatusBadge :status="audit.status" />
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <Link
                    :href="route('auditor.findings.create', audit.id)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Finding Baru
                </Link>
            </div>
        </div>

        <!-- Audit Information (Clean Corporate Summary Table / Key-Value Grid) -->
        <div class="bg-white rounded border border-gray-200 p-5 mb-8">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100">
                Audit Information
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-xs">
                <div>
                    <div class="text-gray-500 font-medium">Toko</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.store.name }}</div>
                    <div class="text-[11px] text-gray-500 font-mono">{{ audit.store.code }} ({{ audit.store.area || '-' }})</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Tanggal Audit</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.audit_date }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Auditor</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.auditor.name }}</div>
                    <div class="text-[11px] text-gray-500">{{ audit.auditor.email }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Total Temuan</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.findings.length }} Finding(s)</div>
                </div>
            </div>

            <div v-if="audit.notes" class="mt-4 pt-3 border-t border-gray-100 text-xs">
                <span class="text-gray-500 font-medium">Catatan Audit: </span>
                <span class="text-gray-700">{{ audit.notes }}</span>
            </div>
        </div>

        <!-- Findings List Section -->
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                Daftar Finding / Temuan Audit ({{ audit.findings.length }})
            </h2>
        </div>

        <div v-if="audit.findings.length === 0" class="bg-white rounded border border-gray-200 p-8 text-center text-xs text-gray-500">
            Belum ada finding untuk audit ini. Klik tombol "Tambah Finding Baru" di atas untuk mencatat temuan.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(finding, index) in audit.findings"
                :key="finding.id"
                class="bg-white rounded border border-gray-200 p-5 shadow-xs transition-colors hover:border-gray-300"
            >
                <!-- Finding Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono font-bold text-gray-400">#{{ String(index + 1).padStart(3, '0') }}</span>
                        <span class="text-xs font-semibold text-gray-900">{{ finding.category }}</span>
                        <SeverityBadge :severity="finding.severity" />
                        <StatusBadge :status="finding.status" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('auditor.findings.show', finding.id)"
                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Detail & Verifikasi →
                        </Link>
                    </div>
                </div>

                <!-- Finding Content Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                    <div class="md:col-span-2 space-y-3">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Temuan (Finding):</div>
                            <p class="text-gray-900 leading-relaxed font-normal bg-gray-50/50 p-2.5 rounded border border-gray-100">{{ finding.finding }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="finding.opinion">
                                <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                                <p class="text-gray-700 leading-relaxed">{{ finding.opinion }}</p>
                            </div>
                            <div v-if="finding.recommendation">
                                <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                                <p class="text-gray-700 leading-relaxed">{{ finding.recommendation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Meta, Loss, SOP, Action Plan Status -->
                    <div class="border-t md:border-t-0 md:border-l md:border-gray-100 md:pl-6 space-y-3">
                        <div>
                            <div class="text-gray-500 font-medium">Nominal Kerugian</div>
                            <div class="text-sm font-semibold text-gray-900 mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium">SOP / SE Terkait</div>
                            <div class="text-gray-800 mt-0.5">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</div>
                        </div>

                        <div class="pt-2 border-t border-gray-100">
                            <div class="text-gray-500 font-medium mb-1">Tindak Lanjut Toko</div>
                            <div v-if="finding.action_plan?.action_plan" class="space-y-1">
                                <div class="text-gray-900 font-medium">{{ finding.action_plan.action_plan }}</div>
                                <div class="text-[11px] text-gray-500">PIC: <span class="font-medium text-gray-700">{{ finding.action_plan.pic || '-' }}</span> | Deadline: <span class="font-medium text-gray-700">{{ finding.action_plan.deadline || '-' }}</span></div>
                            </div>
                            <div v-else class="text-gray-400 italic">Belum diisi oleh toko</div>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium">Evidence</div>
                            <div class="text-gray-800 mt-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700">
                                    {{ finding.evidences?.length || 0 }} file diunggah
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
