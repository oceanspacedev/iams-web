<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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

const getInitialDeadline = () => {
    if (!props.finding.action_plan?.deadline) return '';
    return typeof props.finding.action_plan.deadline === 'string'
        ? props.finding.action_plan.deadline.substring(0, 10)
        : '';
};

// Check if action plan has already been filled with content
const hasActionPlan = computed(() => Boolean(props.finding.action_plan && props.finding.action_plan.action_plan));

// Action plan hanya bisa diisi/diubah saat status OPEN atau IN_PROGRESS
const canEditActionPlan = computed(() =>
    Boolean(props.finding.can_edit_action_plan) && ['OPEN', 'IN_PROGRESS'].includes(props.finding.status)
);

// Setelah action plan ada, deadline dikunci (backend mempertahankan deadline tersimpan)
const isDeadlineLocked = computed(() => Boolean(props.finding.deadline_locked));

// Evidence tidak bisa diunggah lagi setelah VERIFIED / CLOSED
const canUploadEvidence = computed(() =>
    Boolean(props.finding.can_upload_evidence) && !['VERIFIED', 'CLOSED'].includes(props.finding.status)
);

// Action Plan State & Form
const isEditing = ref(!hasActionPlan.value && canEditActionPlan.value);

const actionPlanForm = useForm({
    action_plan: props.finding.action_plan?.action_plan || '',
    response: props.finding.action_plan?.response || '',
    pic: props.finding.action_plan?.pic || '',
    deadline: getInitialDeadline(),
});

const startEditing = () => {
    actionPlanForm.action_plan = props.finding.action_plan?.action_plan || '';
    actionPlanForm.response = props.finding.action_plan?.response || '';
    actionPlanForm.pic = props.finding.action_plan?.pic || '';
    actionPlanForm.deadline = getInitialDeadline();
    actionPlanForm.clearErrors();
    isEditing.value = true;
};

const cancelEditing = () => {
    actionPlanForm.action_plan = props.finding.action_plan?.action_plan || '';
    actionPlanForm.response = props.finding.action_plan?.response || '';
    actionPlanForm.pic = props.finding.action_plan?.pic || '';
    actionPlanForm.deadline = getInitialDeadline();
    actionPlanForm.clearErrors();
    if (hasActionPlan.value) {
        isEditing.value = false;
    }
};

const submitActionPlan = () => {
    if (props.finding.action_plan) {
        actionPlanForm.patch(route('auditee.action-plans.update', props.finding.id), {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
            },
        });
    } else {
        actionPlanForm.post(route('auditee.action-plans.store', props.finding.id), {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
            },
        });
    }
};

// Evidence Form
const evidenceForm = useForm({
    file: null,
    description: '',
});

const fileInputRef = ref(null);

const handleFileChange = (e) => {
    evidenceForm.file = e.target.files[0] || null;
};

const handleFileUpload = (e) => {
    evidenceForm.file = e.target.files[0] || null;
};

const submitEvidence = () => {
    evidenceForm.post(route('auditee.evidences.store', props.finding.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            evidenceForm.reset();
            if (fileInputRef.value) fileInputRef.value.value = '';
        },
    });
};

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

const deleteEvidence = (evidenceId) => {
    openConfirm({
        title: 'Hapus evidence',
        message: 'Hapus file bukti perbaikan ini?',
        confirmText: 'Ya, hapus',
        type: 'danger',
        action: () => router.delete(route('auditee.evidences.destroy', evidenceId), { preserveScroll: true }),
    });
};
</script>

<template>
    <AppLayout :title="`Tindak lanjut temuan #${finding.id}`">
        <Head :title="`Tindak lanjut temuan #${finding.id}`" />

        <!-- Breadcrumb + judul + badge -->
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('auditee.audits.index')" class="transition-colors duration-150 hover:text-blue-600">Audit toko</Link>
                <span>/</span>
                <Link :href="route('auditee.audits.show', finding.audit.id)" class="tabular-nums transition-colors duration-150 hover:text-blue-600">{{ finding.audit.audit_number }}</Link>
                <span>/</span>
                <span class="font-medium text-gray-900">Tindak lanjut</span>
            </div>
            <h1 class="flex flex-wrap items-center gap-3 text-2xl font-semibold leading-8 text-gray-900">
                <span class="tabular-nums">Temuan #{{ String(finding.id).padStart(4, '0') }}</span>
                <SeverityBadge :severity="finding.severity" />
                <StatusBadge :status="finding.status" />
            </h1>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Temuan (read-only) -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Temuan</h2>
                    </div>

                    <div class="space-y-4 p-5">
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-xs text-gray-500">Kategori</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ finding.category }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">SOP / SE</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}
                                </dd>
                            </div>
                        </dl>

                        <div>
                            <div class="mb-1 text-xs text-gray-500">Uraian</div>
                            <p class="leading-relaxed text-gray-900">{{ finding.finding }}</p>
                        </div>

                        <div v-if="finding.auditor_opinion">
                            <div class="mb-1 text-xs text-gray-500">Opini auditor</div>
                            <p class="leading-relaxed text-gray-800">{{ finding.auditor_opinion }}</p>
                        </div>

                        <div>
                            <div class="mb-1 text-xs text-gray-500">Rekomendasi</div>
                            <p class="rounded-lg border border-blue-100 bg-blue-50/40 p-3 font-medium leading-relaxed text-blue-900">{{ finding.recommendation }}</p>
                        </div>
                    </div>
                </section>

                <!-- Action plan -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Action plan</h2>
                        <div class="flex items-center gap-2">
                            <StatusBadge v-if="finding.action_plan" :status="finding.action_plan.status" />
                            <button
                                v-if="hasActionPlan && !isEditing && canEditActionPlan"
                                type="button"
                                @click="startEditing"
                                title="Edit action plan"
                                aria-label="Edit action plan"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 text-blue-600 transition-colors duration-150 hover:bg-blue-50 hover:text-blue-700"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- View mode (read-only setelah disimpan) -->
                    <div v-if="hasActionPlan && !isEditing" class="space-y-4 p-5">
                        <div>
                            <div class="mb-1 text-xs text-gray-500">Rencana perbaikan</div>
                            <p class="whitespace-pre-line font-medium leading-relaxed text-gray-900">{{ finding.action_plan.action_plan }}</p>
                        </div>

                        <div>
                            <div class="mb-1 text-xs text-gray-500">Tanggapan toko</div>
                            <p class="whitespace-pre-line leading-relaxed text-gray-900">{{ finding.action_plan.response }}</p>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 border-t border-gray-100 pt-4">
                            <div>
                                <dt class="text-xs text-gray-500">PIC</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ finding.action_plan.pic }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Deadline</dt>
                                <dd class="mt-1 text-sm text-gray-900 tabular-nums">{{ formatDate(finding.action_plan.deadline, finding.severity === 'CONTINUOUS' ? 'Dalam monitoring' : '—') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Edit / input mode -->
                    <form v-else-if="canEditActionPlan" @submit.prevent="submitActionPlan">
                        <div class="space-y-4 p-5">
                            <div>
                                <label for="ap-action-plan" class="mb-2 block text-sm font-medium text-gray-900">
                                    Rencana perbaikan <span class="text-red-600">*</span>
                                </label>
                                <textarea
                                    id="ap-action-plan"
                                    v-model="actionPlanForm.action_plan"
                                    rows="3"
                                    required
                                    class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 disabled:bg-gray-100 shadow-sm"
                                ></textarea>
                                <div v-if="actionPlanForm.errors.action_plan" class="mt-1 text-xs text-red-600">{{ actionPlanForm.errors.action_plan }}</div>
                            </div>

                            <div>
                                <label for="ap-response" class="mb-2 block text-sm font-medium text-gray-900">
                                    Tanggapan toko <span class="text-red-600">*</span>
                                </label>
                                <textarea
                                    id="ap-response"
                                    v-model="actionPlanForm.response"
                                    rows="3"
                                    required
                                    class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 disabled:bg-gray-100 shadow-sm"
                                ></textarea>
                                <div v-if="actionPlanForm.errors.response" class="mt-1 text-xs text-red-600">{{ actionPlanForm.errors.response }}</div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="ap-pic" class="mb-2 block text-sm font-medium text-gray-900">
                                        PIC <span class="text-red-600">*</span>
                                    </label>
                                    <input
                                        id="ap-pic"
                                        v-model="actionPlanForm.pic"
                                        type="text"
                                        required
                                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 disabled:bg-gray-100 shadow-sm"
                                    />
                                    <div v-if="actionPlanForm.errors.pic" class="mt-1 text-xs text-red-600">{{ actionPlanForm.errors.pic }}</div>
                                </div>

                                <div v-if="isDeadlineLocked">
                                    <div class="mb-2 block text-sm font-medium text-gray-900">Deadline</div>
                                    <div class="flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-900 tabular-nums">
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                        <span>{{ formatDate(finding.action_plan?.deadline, finding.severity === 'CONTINUOUS' ? 'Dalam monitoring' : '—') }}</span>
                                    </div>
                                </div>

                                <div v-else>
                                    <label for="ap-deadline" class="mb-2 block text-sm font-medium text-gray-900">
                                        Deadline <span v-if="finding.severity !== 'CONTINUOUS'" class="text-red-600">*</span>
                                        <span v-else class="text-xs font-normal text-purple-600">(opsional, dalam monitoring)</span>
                                    </label>
                                    <input
                                        id="ap-deadline"
                                        v-model="actionPlanForm.deadline"
                                        type="date"
                                        :required="finding.severity !== 'CONTINUOUS'"
                                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 disabled:bg-gray-100 shadow-sm"
                                    />
                                    <div v-if="actionPlanForm.errors.deadline" class="mt-1 text-xs text-red-600">{{ actionPlanForm.errors.deadline }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-4">
                            <button
                                v-if="hasActionPlan"
                                type="button"
                                @click="cancelEditing"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="actionPlanForm.processing"
                                class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                            >
                                {{ actionPlanForm.processing ? 'Menyimpan…' : (hasActionPlan ? 'Simpan perubahan' : 'Simpan action plan') }}
                            </button>
                        </div>
                    </form>

                    <!-- Kosong: tidak bisa edit dan belum pernah diisi -->
                    <div v-else class="p-8 text-center text-sm text-gray-500">
                        Belum ada action plan
                    </div>
                </section>

                <!-- Evidence -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Evidence</h2>
                        <span v-if="canUploadEvidence" class="text-xs text-gray-500">JPG, PNG, PDF, XLSX, DOCX · maks 10 MB</span>
                    </div>

                    <!-- Form unggah (jika masih terbuka) -->
                    <div v-if="canUploadEvidence" class="space-y-4 border-b border-gray-200 bg-gray-50/70 p-5">
                        <!-- Ada evidence yang ditolak -->
                        <div
                            v-if="finding.evidences.some(e => e.verification_status === 'REJECTED')"
                            class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900"
                        >
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="text-sm">
                                <span class="font-medium">Bukti sebelumnya ditolak.</span> Cek catatan penolakan di bawah, lalu unggah bukti baru.
                            </div>
                        </div>

                        <form @submit.prevent="submitEvidence" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <label for="evidence-file" class="mb-2 block text-sm font-medium text-gray-900">File <span class="text-red-600">*</span></label>
                                <input
                                    id="evidence-file"
                                    ref="fileInputRef"
                                    type="file"
                                    required
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                                    @change="handleFileChange"
                                    class="block w-full cursor-pointer text-sm text-gray-500 file:mr-3 file:rounded-lg file:border file:border-gray-300 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-50"
                                />
                                <div v-if="evidenceForm.errors.file" class="mt-1 text-xs text-red-600">{{ evidenceForm.errors.file }}</div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <label for="evidence-description" class="mb-2 block text-sm font-medium text-gray-900">Keterangan</label>
                                <input
                                    id="evidence-description"
                                    v-model="evidenceForm.description"
                                    type="text"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                                <div v-if="evidenceForm.errors.description" class="mt-1 text-xs text-red-600">{{ evidenceForm.errors.description }}</div>
                            </div>

                            <button
                                type="submit"
                                :disabled="evidenceForm.processing"
                                class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700 disabled:opacity-50"
                            >
                                <svg v-if="evidenceForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ evidenceForm.processing ? 'Mengunggah…' : 'Unggah' }}</span>
                            </button>
                        </form>
                    </div>

                    <!-- Daftar evidence -->
                    <div v-if="finding.evidences.length === 0" class="p-8 text-center text-gray-400">
                        Belum ada evidence
                    </div>

                    <div v-else>
                        <div
                            v-for="e in finding.evidences"
                            :key="e.id"
                            class="flex items-start gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0"
                        >
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-gray-900">{{ e.description || 'Bukti perbaikan' }}</span>
                                    <StatusBadge :status="e.verification_status" />
                                </div>
                                <div class="text-xs text-gray-500 tabular-nums">
                                    {{ e.uploaded_at }}<template v-if="e.verified_by"> · Diverifikasi {{ e.verified_by }}</template>
                                </div>
                                <div v-if="e.rejection_reason" class="text-xs font-medium text-red-600">
                                    Catatan penolakan: {{ e.rejection_reason }}
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <a
                                    :href="e.file_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    title="Lihat file"
                                    aria-label="Lihat file"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                </a>
                                <button
                                    v-if="e.can_delete"
                                    @click="deleteEvidence(e.id)"
                                    title="Hapus evidence"
                                    aria-label="Hapus evidence"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 text-red-600 transition-colors duration-150 hover:bg-red-50"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Kolom kanan: info audit + alur -->
            <div class="space-y-6">
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white text-sm shadow-sm">
                    <div class="flex h-14 items-center border-b border-gray-200 px-5">
                        <h3 class="text-base font-semibold text-gray-900">Audit</h3>
                    </div>

                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 p-5">
                        <div class="col-span-2">
                            <dt class="text-xs text-gray-500">No. audit</dt>
                            <dd class="mt-1 text-sm text-gray-900 tabular-nums">{{ finding.audit.audit_number }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs text-gray-500">Toko</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ finding.store.name }} ({{ finding.store.code }})</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500" title="Nilai barang hilang">NBH</dt>
                            <dd class="mt-1 text-base font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.loss_amount) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500" title="Nilai barang lebih">NBL</dt>
                            <dd class="mt-1 text-base font-medium text-gray-900 tabular-nums">{{ formatRupiah(finding.surplus_amount) }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs text-gray-500">Status</dt>
                            <dd class="mt-1"><StatusBadge :status="finding.status" /></dd>
                        </div>
                    </dl>
                </section>

                <!-- Workflow Step Tracker -->
                <WorkflowTracker
                    :status="finding.status"
                    :has-documents="finding.has_documents || (finding.audit && finding.audit.documents_count > 0)"
                    :documents-count="finding.documents_count || (finding.audit ? finding.audit.documents_count : 0)"
                    :has-action-plan="finding.has_action_plan || !!finding.action_plan?.action_plan"
                />
            </div>
        </div>

        <!-- Action Confirmation Modal -->
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
