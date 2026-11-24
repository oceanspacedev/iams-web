<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import WorkflowTracker from '@/Components/WorkflowTracker.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    finding: {
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

const SEVERITY_STATUS_LABELS = {
    PENDING_REVIEW: 'Menunggu review',
    APPROVED: 'Disetujui',
    ADJUSTED: 'Disesuaikan',
};
const severityStatusLabel = (status) => SEVERITY_STATUS_LABELS[status] || 'Menunggu review';

const SEVERITY_TILES = [
    {
        value: 'MINOR',
        label: 'Minor',
        timeline: '3 - 7 hr',
        ariaTimeline: '3 - 7 hari',
        active: 'bg-emerald-100 border-emerald-500 ring-1 ring-emerald-400 text-emerald-950 font-medium',
        idle: 'bg-emerald-50/70 border-emerald-200 text-emerald-800 hover:bg-emerald-100/60',
        sub: 'text-emerald-700/80',
    },
    {
        value: 'MEDIUM',
        label: 'Medium',
        timeline: '8 - 14 hr',
        ariaTimeline: '8 - 14 hari',
        active: 'bg-amber-100 border-amber-500 ring-1 ring-amber-400 text-amber-950 font-medium',
        idle: 'bg-amber-50/70 border-amber-200 text-amber-800 hover:bg-amber-100/60',
        sub: 'text-amber-700/80',
    },
    {
        value: 'MAJOR',
        label: 'Major',
        timeline: '15 - 30 hr',
        ariaTimeline: '15 - 30 hari',
        active: 'bg-rose-100 border-rose-500 ring-1 ring-rose-400 text-rose-950 font-medium',
        idle: 'bg-rose-50/70 border-rose-200 text-rose-800 hover:bg-rose-100/60',
        sub: 'text-rose-700/80',
    },
    {
        value: 'CONTINUOUS',
        label: 'Continuous',
        timeline: 'Monitoring',
        ariaTimeline: 'monitoring',
        active: 'bg-purple-100 border-purple-500 ring-1 ring-purple-400 text-purple-950 font-medium',
        idle: 'bg-purple-50/70 border-purple-200 text-purple-800 hover:bg-purple-100/60',
        sub: 'text-purple-700/80',
    },
];

const isClosed = computed(() => props.finding.status === 'CLOSED');
const canClose = computed(() => !!props.finding.can_close && props.finding.status === 'VERIFIED');

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
        title: config.title || 'Konfirmasi tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, lanjutkan',
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

const isEditingRecommendation = ref(false);
const recommendationForm = useForm({
    recommendation: props.finding.recommendation || '',
});

const promptEditRecommendation = () => {
    openConfirm({
        title: 'Konfirmasi edit rekomendasi',
        message: 'Edit rekomendasi perbaikan untuk temuan ini?',
        confirmText: 'Ya, edit rekomendasi',
        type: 'primary',
        action: () => {
            recommendationForm.clearErrors();
            recommendationForm.recommendation = props.finding.recommendation || '';
            isEditingRecommendation.value = true;
        },
    });
};

const promptSaveRecommendation = () => {
    openConfirm({
        title: 'Konfirmasi simpan rekomendasi',
        message: 'Simpan perubahan rekomendasi?',
        confirmText: 'Ya, simpan perubahan',
        type: 'primary',
        action: () => {
            recommendationForm.patch(route('coordinator.findings.recommendation.update', props.finding.id), {
                preserveScroll: true,
                onSuccess: () => {
                    isEditingRecommendation.value = false;
                },
            });
        },
    });
};

// Form review severity (koordinator). Rekomendasi tidak ikut dikirim agar
// perubahan rekomendasi tidak tertimpa salinan lama.
const severityForm = useForm({
    severity: props.finding.severity || 'MINOR',
    severity_notes: props.finding.severity_notes || '',
});

const submitSeverityReview = () => {
    severityForm.patch(route('coordinator.findings.review-severity', props.finding.id), {
        preserveScroll: true,
    });
};

const closeFinding = () => {
    openConfirm({
        title: 'Tutup temuan',
        message: 'Temuan ditutup dan action plan terkait ditandai selesai. Lanjutkan?',
        confirmText: 'Ya, tutup temuan',
        type: 'success',
        action: () => router.patch(route('coordinator.findings.close', props.finding.id)),
    });
};
</script>

<template>
    <AppLayout :title="`Review temuan #${finding.id}`">
        <Head :title="`Review temuan #${finding.id}`" />

        <!-- Header: breadcrumb + judul + badge + aksi dalam satu baris -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <Link :href="route('coordinator.findings.index')" class="text-2xl leading-8 text-gray-400 transition-colors duration-150 hover:text-blue-600">Review severity</Link>
                <span class="text-2xl leading-8 text-gray-400" aria-hidden="true">/</span>
                <Link :href="route('coordinator.audits.show', finding.audit.id)" class="text-2xl leading-8 text-gray-400 tabular-nums transition-colors duration-150 hover:text-blue-600">{{ finding.audit.audit_number }}</Link>
                <span class="text-2xl leading-8 text-gray-400" aria-hidden="true">/</span>
                <h1 class="text-2xl font-semibold leading-8 text-gray-900 tabular-nums">#{{ String(finding.id).padStart(4, '0') }}</h1>
                <SeverityBadge :severity="finding.severity" :show-timeline="true" />
                <StatusBadge :status="finding.status" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('coordinator.finding-qualities.create', { finding_id: finding.id, audit_id: finding.audit.id })"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Lapor finding quality</span>
                </Link>

                <button
                    v-if="canClose"
                    @click="closeFinding"
                    class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Tutup temuan</span>
                </button>
            </div>
        </div>

        <!-- Fakta temuan -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 md:grid-cols-4">
                <div>
                    <dt class="text-xs text-gray-500">Kategori</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ finding.category }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">SOP / SE</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Toko</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ finding.store.name }}</dd>
                    <dd class="text-xs text-gray-500 tabular-nums">{{ finding.store.code }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Auditor</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ finding.auditor.name }}</dd>
                    <dd v-if="finding.auditor.phone" class="text-xs text-gray-500 tabular-nums">{{ finding.auditor.phone }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">No. audit</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 tabular-nums">{{ finding.audit.audit_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500" title="Nilai barang hilang">NBH</dt>
                    <dd class="mt-1 text-base font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.loss_amount) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500" title="Nilai barang lebih">NBL</dt>
                    <dd class="mt-1 text-base font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.surplus_amount) }}</dd>
                </div>
            </dl>
        </div>

        <div class="grid grid-cols-1 gap-6 text-sm lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Temuan -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Temuan</h2>
                    </div>

                    <div class="space-y-5 p-5">
                        <p class="whitespace-pre-line text-base leading-relaxed text-gray-900">{{ finding.finding }}</p>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div v-if="finding.auditor_opinion">
                                <div class="text-xs text-gray-500">Opini auditor</div>
                                <p class="mt-1 leading-relaxed text-gray-800">{{ finding.auditor_opinion }}</p>
                            </div>

                            <div>
                                <div class="flex h-8 items-center justify-between gap-2">
                                    <label v-if="isEditingRecommendation && !isClosed" for="recommendation-input" class="text-xs text-gray-500">Rekomendasi</label>
                                    <div v-else class="text-xs text-gray-500">Rekomendasi</div>
                                    <button
                                        v-if="!isEditingRecommendation && !isClosed"
                                        type="button"
                                        @click="promptEditRecommendation"
                                        title="Edit rekomendasi"
                                        aria-label="Edit rekomendasi"
                                        class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-blue-600 transition-colors duration-150 hover:text-blue-800"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                    </button>
                                </div>

                                <form v-if="isEditingRecommendation && !isClosed" @submit.prevent="promptSaveRecommendation" class="space-y-2">
                                    <textarea
                                        id="recommendation-input"
                                        v-model="recommendationForm.recommendation"
                                        rows="3"
                                        required
                                        class="w-full rounded-lg border-blue-400 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                    ></textarea>
                                    <div v-if="recommendationForm.errors.recommendation" class="text-xs text-red-600">{{ recommendationForm.errors.recommendation }}</div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="isEditingRecommendation = false"
                                            class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                                        >
                                            Batal
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="recommendationForm.processing"
                                            class="inline-flex h-8 cursor-pointer items-center rounded-lg bg-blue-600 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                                        >
                                            {{ recommendationForm.processing ? 'Menyimpan…' : 'Simpan' }}
                                        </button>
                                    </div>
                                </form>
                                <p v-else class="leading-relaxed text-gray-800">
                                    {{ finding.recommendation || '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tindak lanjut toko -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Tindak lanjut toko</h2>
                        <StatusBadge v-if="finding.action_plan" :status="finding.action_plan.status" />
                    </div>

                    <div v-if="finding.action_plan?.action_plan" class="space-y-4 p-5">
                        <p class="whitespace-pre-line font-medium leading-relaxed text-gray-900">{{ finding.action_plan.action_plan }}</p>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 md:grid-cols-4">
                            <div>
                                <dt class="text-xs text-gray-500">PIC</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ finding.action_plan.pic || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Deadline</dt>
                                <dd class="mt-1 font-medium text-gray-900 tabular-nums">{{ formatDate(finding.action_plan.deadline, finding.severity === 'CONTINUOUS' ? 'Dalam monitoring' : '—') }}</dd>
                            </div>
                            <div v-if="finding.action_plan.response" class="col-span-2">
                                <dt class="text-xs text-gray-500">Catatan</dt>
                                <dd class="mt-1 text-gray-600">{{ finding.action_plan.response }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div v-else class="p-6 text-center text-gray-400">
                        Belum ada tindak lanjut
                    </div>
                </section>

                <!-- Evidence -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center gap-2 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Evidence</h2>
                        <span v-if="finding.evidences?.length" class="text-sm text-gray-500 tabular-nums">{{ finding.evidences.length }}</span>
                    </div>

                    <ul v-if="finding.evidences?.length" class="divide-y divide-gray-100">
                        <li v-for="ev in finding.evidences" :key="ev.id" class="flex items-start gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-gray-900">{{ ev.description || '—' }}</span>
                                    <StatusBadge :status="ev.verification_status" />
                                </div>
                                <div class="mt-0.5 text-xs text-gray-500 tabular-nums">{{ ev.uploaded_by }} · {{ ev.uploaded_at }}</div>
                                <div v-if="ev.rejection_reason" class="mt-1 text-xs text-red-700">{{ ev.rejection_reason }}</div>
                            </div>
                            <a
                                v-if="ev.file_url"
                                :href="ev.file_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Lihat file"
                                aria-label="Lihat file"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-blue-600 transition-colors duration-150 hover:text-blue-800"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </a>
                        </li>
                    </ul>

                    <div v-else class="p-6 text-center text-gray-400">
                        Belum ada evidence
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <!-- Review severity -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h3 class="text-base font-semibold text-gray-900">Review severity</h3>
                        <span
                            v-if="finding.is_severity_locked"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800"
                        >
                            Terkunci
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800"
                        >
                            Menunggu review
                        </span>
                    </div>

                    <div class="space-y-4 p-5">
                        <div v-if="finding.is_severity_locked || isClosed" class="space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <SeverityBadge :severity="finding.severity" />
                                <span class="font-medium text-gray-900">{{ severityStatusLabel(finding.severity_status) }}</span>
                            </div>
                            <div v-if="finding.severity_reviewed_by || finding.severity_reviewed_at" class="text-xs text-gray-600">
                                Direview <span class="font-medium text-gray-900">{{ finding.severity_reviewed_by || 'Koordinator' }}</span>
                                <span v-if="finding.severity_reviewed_at" class="tabular-nums"> · {{ finding.severity_reviewed_at }}</span>
                            </div>
                            <div v-if="finding.severity_notes" class="rounded-lg border border-gray-200 bg-white p-2 text-xs text-gray-700">
                                "{{ finding.severity_notes }}"
                            </div>
                        </div>

                        <form v-if="!isClosed" @submit.prevent="submitSeverityReview" class="space-y-4">
                            <div>
                                <div id="severity-review-label" class="mb-2 block text-sm font-medium text-gray-900">
                                    {{ finding.is_severity_locked ? 'Ubah severity' : 'Tentukan severity' }} <span class="text-red-600">*</span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-center text-xs sm:grid-cols-4" role="group" aria-labelledby="severity-review-label">
                                    <button
                                        v-for="tile in SEVERITY_TILES"
                                        :key="tile.value"
                                        type="button"
                                        @click="severityForm.severity = tile.value"
                                        :aria-pressed="severityForm.severity === tile.value"
                                        :aria-label="`Severity ${tile.label.toLowerCase()}, ${tile.ariaTimeline}`"
                                        class="cursor-pointer select-none rounded-lg border p-2 text-center transition-colors duration-150"
                                        :class="severityForm.severity === tile.value ? tile.active : tile.idle"
                                    >
                                        <div class="text-xs font-medium">{{ tile.label }}</div>
                                        <div class="text-xs tabular-nums" :class="tile.sub">{{ tile.timeline }}</div>
                                    </button>
                                </div>
                                <div v-if="severityForm.errors.severity" class="mt-1 text-xs text-red-600">{{ severityForm.errors.severity }}</div>
                            </div>

                            <div>
                                <label for="severity-notes" class="mb-2 block text-sm font-medium text-gray-900">Catatan (opsional)</label>
                                <textarea
                                    id="severity-notes"
                                    v-model="severityForm.severity_notes"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                ></textarea>
                                <div v-if="severityForm.errors.severity_notes" class="mt-1 text-xs text-red-600">{{ severityForm.errors.severity_notes }}</div>
                            </div>

                            <button
                                type="submit"
                                :disabled="severityForm.processing"
                                class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                            >
                                <span>{{ severityForm.processing ? 'Menyimpan…' : 'Simpan & kunci severity' }}</span>
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Alur status -->
                <WorkflowTracker
                    :status="finding.status"
                    :has-documents="finding.has_documents || (finding.audit && finding.audit.documents_count > 0)"
                    :documents-count="finding.documents_count || (finding.audit ? finding.audit.documents_count : 0)"
                    :has-action-plan="finding.has_action_plan || !!finding.action_plan?.action_plan"
                />
            </div>
        </div>

        <!-- Confirm Modal -->
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
