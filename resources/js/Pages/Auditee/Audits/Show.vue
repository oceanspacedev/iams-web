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

const formatDate = (dateString, fallback = '—') => {
    if (!dateString) return fallback;
    if (typeof dateString === 'string') {
        const match = dateString.trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            const [, year, month, day] = match;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const mIdx = parseInt(month, 10) - 1;
            return `${parseInt(day, 10)} ${months[mIdx] || month} ${year}`;
        }
    }
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
};
</script>

<template>
    <AppLayout :title="`Audit ${audit.audit_number}`">
        <Head :title="`Audit ${audit.audit_number}`" />

        <!-- Breadcrumb + judul + status -->
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('auditee.audits.index')" class="transition-colors duration-150 hover:text-blue-600">Audit toko</Link>
                <span>/</span>
                <span class="font-medium text-gray-900 tabular-nums">{{ audit.audit_number }}</span>
            </div>
            <h1 class="flex flex-wrap items-center gap-3 text-2xl font-semibold leading-8 text-gray-900">
                <span class="tabular-nums">{{ audit.audit_number }}</span>
                <StatusBadge :status="audit.status" />
            </h1>
        </div>

        <!-- Fakta utama -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 md:grid-cols-4">
                <div>
                    <dt class="text-xs text-gray-500">Toko</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ audit.store.name }}</dd>
                    <dd class="mt-0.5 text-xs text-gray-500 tabular-nums">{{ audit.store.code }} ({{ audit.store.area || '-' }})</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Tanggal</dt>
                    <dd class="mt-1 text-sm text-gray-900 tabular-nums">{{ audit.audit_date }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Auditor</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ audit.auditor.name }}</dd>
                    <dd class="mt-0.5 text-xs text-gray-500">{{ audit.auditor.email }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Temuan</dt>
                    <dd class="mt-1 text-sm text-gray-900 tabular-nums">{{ audit.findings.length }}</dd>
                </div>
                <div v-if="audit.notes" class="col-span-2 md:col-span-4">
                    <dt class="text-xs text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ audit.notes }}</dd>
                </div>
            </dl>
        </div>

        <!-- Daftar temuan: setiap baris membuka halaman tindak lanjut -->
        <section class="mt-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex h-14 items-center gap-3 border-b border-gray-200 px-5">
                <h2 class="text-base font-semibold text-gray-900">Temuan</h2>
                <span class="text-base text-gray-400 tabular-nums">{{ audit.findings.length }}</span>
            </div>

            <div v-if="audit.findings.length === 0" class="p-8 text-center text-sm text-gray-500">
                Tidak ada temuan
            </div>

            <div v-else>
                <Link
                    v-for="(finding, index) in audit.findings"
                    :key="finding.id"
                    :href="route('auditee.findings.show', finding.id)"
                    class="group block border-b border-gray-100 px-5 py-4 text-sm transition-colors duration-150 last:border-b-0"
                >
                    <div class="flex items-start gap-4">
                        <span class="hidden w-10 shrink-0 font-medium text-gray-400 tabular-nums sm:block">#{{ String(index + 1).padStart(3, '0') }}</span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium text-gray-400 tabular-nums sm:hidden">#{{ String(index + 1).padStart(3, '0') }}</span>
                                <span class="font-medium text-gray-900">{{ finding.category }}</span>
                                <SeverityBadge :severity="finding.severity" />
                                <StatusBadge :status="finding.status" />
                            </div>

                            <p class="mt-2 line-clamp-2 leading-relaxed text-gray-900">{{ finding.finding }}</p>
                            <p v-if="finding.recommendation" class="mt-1 line-clamp-2 leading-relaxed text-blue-900" title="Rekomendasi">{{ finding.recommendation }}</p>

                            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                                <span title="Nilai barang hilang">NBH <span class="font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.loss_amount) }}</span></span>
                                <span title="Nilai barang lebih">NBL <span class="font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.surplus_amount) }}</span></span>
                                <span>SOP <span class="text-gray-800">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</span></span>
                            </div>

                            <!-- Action plan (mobile) -->
                            <div class="mt-3 text-xs sm:hidden">
                                <template v-if="finding.action_plan?.action_plan">
                                    <span class="font-medium text-emerald-700">Action plan sudah diisi</span>
                                    <span class="text-gray-500 tabular-nums"> · PIC {{ finding.action_plan.pic || '-' }} · {{ formatDate(finding.action_plan.deadline, finding.severity === 'CONTINUOUS' ? 'Dalam monitoring' : '—') }}</span>
                                </template>
                                <span v-else class="font-medium text-amber-700">Action plan belum diisi</span>
                            </div>
                        </div>

                        <!-- Action plan (desktop) -->
                        <div class="hidden w-44 shrink-0 text-right sm:block">
                            <div class="text-xs text-gray-500">Action plan</div>
                            <div v-if="finding.action_plan?.action_plan" class="mt-1 text-gray-800">
                                <span class="font-medium text-emerald-700">Sudah diisi</span>
                                <div class="mt-0.5 text-xs text-gray-500 tabular-nums">PIC {{ finding.action_plan.pic || '-' }}</div>
                                <div class="text-xs text-gray-500 tabular-nums">{{ formatDate(finding.action_plan.deadline, finding.severity === 'CONTINUOUS' ? 'Dalam monitoring' : '—') }}</div>
                            </div>
                            <div v-else class="mt-1 font-medium text-amber-700">
                                Belum diisi
                            </div>
                        </div>

                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition-colors duration-150 group-hover:text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                    </div>
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
