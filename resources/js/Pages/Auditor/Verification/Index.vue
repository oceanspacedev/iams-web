<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    pending_evidences: {
        type: Array,
        default: () => [],
    },
});

const evidenceList = computed(() => props.pending_evidences || []);

// Pagination 10 baris per halaman; kembali ke halaman 1 saat filter berubah
const currentPage = ref(1);
const perPage = 10;
watch(() => evidenceList.value.length, (n) => {
    const last = Math.max(1, Math.ceil(n / perPage));
    if (currentPage.value > last) currentPage.value = last;
});
const paginatedEvidences = computed(() => evidenceList.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage));

const rejectingEvidenceId = ref(null);
const rejectForm = useForm({
    rejection_reason: '',
});

const openRejectModal = (evidenceId) => {
    rejectingEvidenceId.value = evidenceId;
    rejectForm.rejection_reason = '';
};

const closeRejectModal = () => {
    rejectingEvidenceId.value = null;
    rejectForm.reset();
};

const submitReject = () => {
    rejectForm.patch(route('auditor.evidences.reject', rejectingEvidenceId.value), {
        onSuccess: () => closeRejectModal(),
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

const approveEvidence = (evidenceId) => {
    openConfirm({
        title: 'Setujui evidence',
        message: 'Bukti perbaikan ini sudah sesuai standar?',
        confirmText: 'Ya, setujui',
        type: 'success',
        action: () => router.patch(route('auditor.evidences.approve', evidenceId)),
    });
};
</script>

<template>
    <AppLayout title="Antrean verifikasi">
        <Head title="Antrean verifikasi" />

        <!-- Header: judul + jumlah -->
        <div class="mb-6 flex items-baseline gap-3">
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Antrean verifikasi</h1>
            <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ evidenceList.length }}</span>
        </div>

        <!-- Mobile cards vs desktop table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="evidenceList.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada evidence
                </div>
                <div
                    v-for="e in paginatedEvidences"
                    :key="'m-ev-' + e.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ e.store }}</div>
                            <div class="mt-0.5 text-xs text-gray-500 tabular-nums">{{ e.audit }}</div>
                        </div>
                        <span class="shrink-0 text-xs text-gray-500 tabular-nums">{{ e.uploaded_at }}</span>
                    </div>

                    <Link
                        :href="route('auditor.findings.show', e.finding_id)"
                        class="line-clamp-2 text-sm font-medium text-gray-900 hover:text-blue-600 hover:underline"
                    >
                        {{ e.finding }}
                    </Link>

                    <div class="text-sm text-gray-700">
                        {{ e.description || 'Bukti perbaikan' }}
                        <span class="text-xs text-gray-500">· {{ e.uploaded_by }}</span>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <a
                            :href="e.file_url"
                            target="_blank"
                            title="Lihat file"
                            aria-label="Lihat file"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                        </a>
                        <button
                            @click="approveEvidence(e.id)"
                            class="inline-flex h-8 cursor-pointer items-center rounded-lg bg-emerald-600 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700"
                        >
                            Approve
                        </button>
                        <button
                            @click="openRejectModal(e.id)"
                            class="inline-flex h-8 cursor-pointer items-center rounded-lg bg-red-600 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="evidenceList.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
                        <tr>
                            <th class="h-10 px-5 font-medium">Audit</th>
                            <th class="h-10 px-5 font-medium">Temuan</th>
                            <th class="h-10 px-5 font-medium">Keterangan</th>
                            <th class="h-10 px-5 font-medium">Diunggah</th>
                            <th class="h-10 px-5"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200">
                        <tr v-if="evidenceList.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                Tidak ada evidence
                            </td>
                        </tr>
                        <tr
                            v-for="e in paginatedEvidences"
                            :key="e.id"
                            class="transition-colors duration-150 hover:bg-gray-50/70"
                        >
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-900 tabular-nums">{{ e.audit }}</div>
                                <div class="text-xs text-gray-500">{{ e.store }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <Link
                                    :href="route('auditor.findings.show', e.finding_id)"
                                    class="line-clamp-2 font-medium text-gray-900 hover:text-blue-600 hover:underline"
                                >
                                    {{ e.finding }}
                                </Link>
                            </td>
                            <td class="px-5 py-3 text-gray-700">
                                {{ e.description || 'Bukti perbaikan' }}
                            </td>
                            <td class="px-5 py-3 text-gray-600">
                                <div>{{ e.uploaded_by }}</div>
                                <div class="text-xs text-gray-400 tabular-nums">{{ e.uploaded_at }}</div>
                            </td>
                            <td class="space-x-2 whitespace-nowrap px-5 py-3 text-right">
                                <a
                                    :href="e.file_url"
                                    target="_blank"
                                    title="Lihat file"
                                    aria-label="Lihat file"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white align-middle text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                </a>
                                <button
                                    @click="approveEvidence(e.id)"
                                    class="inline-flex h-8 cursor-pointer items-center rounded-lg bg-emerald-600 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700"
                                >
                                    Approve
                                </button>
                                <button
                                    @click="openRejectModal(e.id)"
                                    class="inline-flex h-8 cursor-pointer items-center rounded-lg bg-red-600 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700"
                                >
                                    Reject
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :current-page="currentPage"
                :per-page="perPage"
                :total-items="evidenceList.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Reject Modal -->
        <div
            v-if="rejectingEvidenceId"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <h3 class="text-base font-semibold text-gray-900">Tolak evidence</h3>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Alasan penolakan <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            required
                            rows="4"
                            class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        ></textarea>
                        <div v-if="rejectForm.errors.rejection_reason" class="mt-1 text-xs text-red-600">{{ rejectForm.errors.rejection_reason }}</div>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            @click="closeRejectModal"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ rejectForm.processing ? 'Menolak…' : 'Konfirmasi tolak' }}
                        </button>
                    </div>
                </form>
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
