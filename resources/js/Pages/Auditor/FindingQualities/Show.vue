<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    qualityFinding: {
        type: Object,
        required: true,
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    routePrefix: {
        type: String,
        default: '',
    },
});

const page = usePage();
const currentPrefix = computed(() => {
    if (props.routePrefix) return props.routePrefix;
    if (typeof route === 'function' && route().current('coordinator.*')) return 'coordinator.';
    if (typeof route === 'function' && route().current('auditor.*')) return 'auditor.';
    return (page.props.auth?.user?.roles || []).includes('auditor') ? 'auditor.' : 'coordinator.';
});

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const printReport = () => {
    window.print();
};

const deleteQualityFinding = () => {
    if (confirm('Hapus laporan finding quality ini?')) {
        router.delete(route(`${currentPrefix.value}finding-qualities.destroy`, props.qualityFinding.id));
    }
};
</script>

<template>
    <AppLayout title="Kualitas temuan">
        <Head :title="`Kualitas temuan - ${qualityFinding.title}`" />

        <!-- Breadcrumb, judul, aksi -->
        <div class="mx-auto mb-6 flex max-w-4xl flex-wrap items-end justify-between gap-4 print:hidden">
            <div>
                <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                    <Link :href="route(`${currentPrefix}finding-qualities.index`)" class="transition-colors duration-150 hover:text-blue-600">Kualitas temuan</Link>
                    <span>/</span>
                    <span class="font-medium text-gray-900 tabular-nums">#FQ-{{ String(qualityFinding.id).padStart(4, '0') }}</span>
                </div>
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Lembar laporan</h1>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="printReport"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak
                </button>

                <button
                    @click="deleteQualityFinding"
                    title="Hapus"
                    aria-label="Hapus"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-600 hover:text-white"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                </button>
            </div>
        </div>

        <!-- Lembar laporan (ikut tercetak) -->
        <div class="mx-auto max-w-4xl overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
            <!-- Kepala lembar -->
            <div class="border-b border-gray-200 bg-slate-50/70 px-6 py-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <template v-if="qualityFinding.quality_categories_info && qualityFinding.quality_categories_info.length">
                                <span
                                    v-for="cat in qualityFinding.quality_categories_info"
                                    :key="cat.id"
                                    class="rounded-md border border-slate-300 bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-800"
                                >
                                    {{ cat.label }}
                                </span>
                            </template>
                            <span v-else class="rounded-md border border-slate-300 bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-800">
                                {{ qualityFinding.categories_info?.label || qualityFinding.quality_category }}
                            </span>
                            <span class="text-xs text-gray-500 tabular-nums">#FQ-{{ String(qualityFinding.id).padStart(4, '0') }}</span>
                        </div>
                        <h2 class="text-2xl font-semibold leading-8 text-gray-900">
                            {{ qualityFinding.title }}
                        </h2>
                    </div>

                    <div class="shrink-0 space-y-1 text-left text-xs text-gray-500 tabular-nums sm:text-right">
                        <div>{{ qualityFinding.created_at }}</div>
                        <div>Pelapor <span class="font-medium text-gray-800">{{ qualityFinding.reported_by }}</span></div>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-gray-200 pt-4 md:grid-cols-4">
                    <div>
                        <div class="text-xs text-gray-500">No. surat tugas</div>
                        <div class="mt-1 text-sm text-gray-900 tabular-nums">{{ qualityFinding.audit.audit_number }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Toko</div>
                        <div class="mt-1 text-sm text-gray-900">{{ qualityFinding.audit.store_name }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Nilai dampak</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 tabular-nums">
                            {{ formatRupiah(qualityFinding.impact_amount || qualityFinding.finding.loss_amount) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Severity & status</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <SeverityBadge :severity="qualityFinding.finding.severity" />
                            <StatusBadge :status="qualityFinding.finding.status" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Isi laporan: label kiri, isi kanan -->
            <div>
                <div class="grid grid-cols-1 gap-2 border-b border-gray-200 px-6 py-5 md:grid-cols-[160px_1fr] md:gap-6">
                    <h3 class="text-sm text-gray-500">Uraian temuan</h3>
                    <div class="leading-relaxed text-gray-800">{{ qualityFinding.finding.finding }}</div>
                </div>

                <div class="grid grid-cols-1 gap-2 border-b border-gray-200 px-6 py-5 md:grid-cols-[160px_1fr] md:gap-6">
                    <h3 class="text-sm text-gray-500">Root cause</h3>
                    <div class="whitespace-pre-line leading-relaxed text-gray-800">{{ qualityFinding.root_cause }}</div>
                </div>

                <div class="grid grid-cols-1 gap-2 border-b border-gray-200 px-6 py-5 md:grid-cols-[160px_1fr] md:gap-6">
                    <h3 class="text-sm text-gray-500">Kelemahan sistemik</h3>
                    <div class="whitespace-pre-line leading-relaxed text-gray-800">
                        {{ qualityFinding.systemic_issue || 'Kelemahan kontrol internal di tingkat operasional unit yang memerlukan revisi SOP atau supervisi terpusat.' }}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 border-b border-gray-200 px-6 py-5 md:grid-cols-[160px_1fr] md:gap-6">
                    <h3 class="text-sm text-gray-500">Rekomendasi</h3>
                    <div class="whitespace-pre-line leading-relaxed text-gray-800">{{ qualityFinding.recommendation }}</div>
                </div>

                <div v-if="qualityFinding.auditor_notes" class="grid grid-cols-1 gap-2 border-b border-gray-200 px-6 py-5 md:grid-cols-[160px_1fr] md:gap-6">
                    <h3 class="text-sm text-gray-500">Catatan auditor</h3>
                    <div class="leading-relaxed text-gray-700">{{ qualityFinding.auditor_notes }}</div>
                </div>
            </div>

            <!-- Tanda tangan (untuk cetak) -->
            <div class="grid grid-cols-3 gap-6 px-6 pb-6 pt-8 text-center text-sm">
                <div>
                    <div class="mb-12 text-gray-500">Auditor pelapor</div>
                    <div class="border-t border-gray-300 pt-2 font-medium text-gray-900">{{ qualityFinding.reported_by }}</div>
                    <div class="text-xs text-gray-400">Staff Audit</div>
                </div>
                <div>
                    <div class="mb-12 text-gray-500">Koordinator / Asmen</div>
                    <div class="border-t border-gray-300 pt-2 font-medium text-gray-900">( ........................................ )</div>
                    <div class="text-xs text-gray-400">Koordinator Audit / Asmen</div>
                </div>
                <div>
                    <div class="mb-12 text-gray-500">Chief Auditor</div>
                    <div class="border-t border-gray-300 pt-2 font-medium text-gray-900">( ........................................ )</div>
                    <div class="text-xs text-gray-400">Chief Internal Audit</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
