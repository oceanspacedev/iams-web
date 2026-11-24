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

const isClosed = computed(() => props.finding.status === 'CLOSED');
const canEdit = computed(() => !['CLOSED', 'VERIFIED'].includes(props.finding.status));
const canDelete = computed(() => ['OPEN', 'IN_PROGRESS'].includes(props.finding.status));

// State for editing action plan
const editingActionPlan = ref(!props.finding.action_plan?.action_plan && props.finding.status !== 'CLOSED');
const actionPlanForm = useForm({
    action_plan: props.finding.action_plan?.action_plan || '',
    pic: props.finding.action_plan?.pic || '',
    deadline: props.finding.action_plan?.deadline ? String(props.finding.action_plan.deadline).substring(0, 10) : '',
    notes: props.finding.action_plan?.response || '',
});

const submitActionPlan = () => {
    actionPlanForm.patch(route('auditor.findings.action-plan.update', props.finding.id), {
        onSuccess: () => {
            editingActionPlan.value = false;
        },
    });
};

// Confirm Modal state
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

const deleteFinding = () => {
    openConfirm({
        title: 'Hapus temuan',
        message: 'Apakah Anda yakin ingin menghapus temuan audit ini beserta data terkait?',
        confirmText: 'Ya, hapus temuan',
        type: 'danger',
        action: () => router.delete(route('auditor.findings.destroy', props.finding.id)),
    });
};
</script>

<template>
    <AppLayout :title="`Detail temuan - ${finding.audit.audit_number}`">
        <Head :title="`Detail temuan - ${finding.audit.audit_number}`" />

        <!-- Header: breadcrumb + judul + badge + aksi dalam satu baris -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <Link :href="route('auditor.audits.index')" class="text-2xl leading-8 text-gray-400 transition-colors duration-150 hover:text-blue-600">Audit saya</Link>
                <span class="text-2xl leading-8 text-gray-400" aria-hidden="true">/</span>
                <Link :href="route('auditor.audits.show', finding.audit.id)" class="text-2xl leading-8 text-gray-400 tabular-nums transition-colors duration-150 hover:text-blue-600">{{ finding.audit.audit_number }}</Link>
                <span class="text-2xl leading-8 text-gray-400" aria-hidden="true">/</span>
                <h1 class="text-2xl font-semibold leading-8 text-gray-900 tabular-nums">#{{ String(finding.id).padStart(4, '0') }}</h1>
                <SeverityBadge :severity="finding.severity" :show-timeline="true" />
                <StatusBadge :status="finding.status" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('auditor.finding-qualities.create', { finding_id: finding.id, audit_id: finding.audit.id })"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Lapor finding quality</span>
                </Link>

                <Link
                    v-if="canEdit"
                    :href="route('auditor.findings.edit', finding.id)"
                    title="Edit temuan"
                    aria-label="Edit temuan"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                </Link>

                <button
                    v-if="canDelete"
                    @click="deleteFinding"
                    title="Hapus temuan"
                    aria-label="Hapus temuan"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-300 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-600 hover:text-white"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
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
                    <dt class="text-xs text-gray-500">No. surat tugas</dt>
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
                <div>
                    <dt class="text-xs text-gray-500">LHP / BAP</dt>
                    <dd class="mt-1 text-sm">
                        <span v-if="finding.audit?.documents_count > 0" class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800 tabular-nums">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ finding.audit.documents_count }} dokumen
                        </span>
                        <span v-else class="text-gray-400">Belum ada</span>
                    </dd>
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
                                <div class="text-xs text-gray-500">Rekomendasi</div>
                                <p class="mt-1 leading-relaxed text-gray-800">{{ finding.recommendation }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tindak lanjut toko (input auditor) -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                        <h2 class="text-base font-semibold text-gray-900">Tindak lanjut toko</h2>
                        <button
                            v-if="!editingActionPlan && !isClosed && finding.action_plan?.action_plan"
                            @click="editingActionPlan = true"
                            title="Edit tindak lanjut"
                            aria-label="Edit tindak lanjut"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-blue-600 transition-colors duration-150 hover:text-blue-800"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                        </button>
                    </div>

                    <!-- Mode input / edit -->
                    <form v-if="editingActionPlan && !isClosed" @submit.prevent="submitActionPlan">
                        <div class="space-y-4 p-5">
                            <div>
                                <label for="ap-action-plan" class="mb-2 block text-sm font-medium text-gray-900">
                                    Komitmen perbaikan <span class="text-red-600">*</span>
                                </label>
                                <textarea
                                    id="ap-action-plan"
                                    v-model="actionPlanForm.action_plan"
                                    rows="3"
                                    required
                                    class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                ></textarea>
                                <div v-if="actionPlanForm.errors.action_plan" class="mt-1 text-xs text-red-600">{{ actionPlanForm.errors.action_plan }}</div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="ap-pic" class="mb-2 block text-sm font-medium text-gray-900">PIC</label>
                                    <input
                                        id="ap-pic"
                                        v-model="actionPlanForm.pic"
                                        type="text"
                                        placeholder="Kepala Toko"
                                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                    />
                                </div>

                                <div>
                                    <label for="ap-deadline" class="mb-2 block text-sm font-medium text-gray-900">Deadline</label>
                                    <input
                                        id="ap-deadline"
                                        v-model="actionPlanForm.deadline"
                                        type="date"
                                        class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="ap-notes" class="mb-2 block text-sm font-medium text-gray-900">Catatan</label>
                                <input
                                    id="ap-notes"
                                    v-model="actionPlanForm.notes"
                                    type="text"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-4">
                            <button
                                v-if="finding.action_plan?.action_plan"
                                type="button"
                                @click="editingActionPlan = false"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="actionPlanForm.processing"
                                class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                            >
                                {{ actionPlanForm.processing ? 'Menyimpan…' : 'Simpan tindak lanjut' }}
                            </button>
                        </div>
                    </form>

                    <!-- Mode tampilan -->
                    <div v-else-if="finding.action_plan?.action_plan" class="space-y-4 p-5">
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

            <!-- Alur status -->
            <div class="space-y-6">
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
