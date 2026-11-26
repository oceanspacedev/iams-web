<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

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

const deleteAudit = () => {
    openConfirm({
        title: 'Hapus penugasan audit',
        message: `Apakah Anda yakin ingin menghapus audit ${props.audit.audit_number}? Seluruh temuan terkait akan ikut terhapus.`,
        confirmText: 'Ya, hapus audit',
        type: 'danger',
        action: () => router.delete(route('coordinator.audits.destroy', props.audit.id)),
    });
};

const sendNotificationNow = (notif) => {
    openConfirm({
        title: `Kirim notifikasi ${notif.rule_name}`,
        message: `Kirim notifikasi WhatsApp sekarang untuk aturan "${notif.rule_name}" ke penerima terkait?`,
        confirmText: 'Kirim sekarang',
        type: 'primary',
        action: () => router.post(route('coordinator.audits.notifications.send-now', notif.id), {}, { preserveScroll: true }),
    });
};
</script>

<template>
    <AppLayout :title="`Audit ${audit.audit_number}`">
        <Head :title="`Audit ${audit.audit_number}`" />

        <!-- Header: breadcrumb + judul + status + aksi dalam satu baris -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <Link :href="route('coordinator.audits.index')" class="text-2xl leading-8 text-gray-400 transition-colors duration-150 hover:text-blue-600">Audit</Link>
                <span class="text-2xl leading-8 text-gray-400" aria-hidden="true">/</span>
                <h1 class="text-2xl font-semibold leading-8 text-gray-900 tabular-nums">{{ audit.audit_number }}</h1>
                <span
                    v-if="audit.category"
                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium"
                    :class="{
                        'bg-emerald-50 text-emerald-800 border-emerald-200': audit.category.includes('Retail'),
                        'bg-amber-50 text-amber-800 border-amber-200': audit.category.includes('Finance'),
                        'bg-indigo-50 text-indigo-800 border-indigo-200': audit.category.includes('Distribusi'),
                        'bg-slate-100 text-slate-700 border-slate-200': !audit.category
                    }"
                >
                    {{ audit.category }}
                </span>
                <StatusBadge :status="audit.status" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('coordinator.finding-qualities.create', { audit_id: audit.id })"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Lapor finding quality
                </Link>
                <Link
                    :href="route('coordinator.audits.edit', audit.id)"
                    title="Edit audit"
                    aria-label="Edit audit"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                </Link>
                <button
                    @click="deleteAudit"
                    title="Hapus audit"
                    aria-label="Hapus audit"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-600 transition-colors duration-150 hover:bg-red-50"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                </button>
            </div>
        </div>

        <!-- Fakta audit -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 md:grid-cols-4">
                <div>
                    <dt class="text-xs text-gray-500">Kategori</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ audit.category || 'Belum ditentukan' }}</dd>
                    <dd class="text-xs text-gray-500">{{ audit.title || 'Audit lapangan' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Toko</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ audit.store.name }}</dd>
                    <dd class="text-xs text-gray-500 tabular-nums">{{ audit.store.code }} · {{ audit.store.area || '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Tanggal</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900 tabular-nums">{{ audit.audit_date }}</dd>
                    <dd class="text-xs text-gray-500 tabular-nums">{{ audit.audit_time }} WIB</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Lead Auditor</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ audit.auditor.name }}</dd>
                    <dd class="truncate text-xs text-gray-500">{{ audit.auditor.email }}</dd>
                </div>
                <div v-if="audit.location">
                    <dt class="text-xs text-gray-500">Lokasi</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-800">{{ audit.location }}</dd>
                </div>
                <div v-if="audit.notes" class="col-span-2 md:col-span-4">
                    <dt class="text-xs text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ audit.notes }}</dd>
                </div>
            </dl>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Temuan & review severity -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white lg:col-span-2 shadow-sm">
                <div class="flex h-14 items-center gap-3 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Temuan</h2>
                    <span class="text-base text-gray-400 tabular-nums">{{ audit.findings.length }}</span>
                </div>

                <div v-if="audit.findings.length === 0" class="p-8 text-center text-sm text-gray-500">
                    Belum ada temuan
                </div>

                <div v-else class="divide-y">
                    <Link
                        v-for="(finding, index) in audit.findings"
                        :key="finding.id"
                        :href="route('coordinator.findings.show', finding.id)"
                        title="Review severity & detail"
                        class="group block px-5 py-4 transition-colors duration-150 hover:bg-gray-50"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-400 tabular-nums">#{{ String(index + 1).padStart(3, '0') }}</span>
                            <span class="min-w-0 truncate text-sm font-medium text-gray-900">{{ finding.category }}</span>
                            <SeverityBadge :severity="finding.severity" />
                            <StatusBadge :status="finding.status" />
                            <svg class="ml-auto h-4 w-4 shrink-0 text-gray-400 transition-colors duration-150 group-hover:text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                        </div>

                        <p class="mt-2 text-sm leading-relaxed text-gray-900">{{ finding.finding }}</p>
                        <p v-if="finding.recommendation" class="mt-1 text-sm leading-relaxed text-gray-700">
                            <span class="text-gray-500">Rekomendasi · </span>{{ finding.recommendation }}
                        </p>

                        <div
                            v-if="finding.sop || finding.loss_amount > 0 || finding.surplus_amount > 0"
                            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs tabular-nums"
                        >
                            <span v-if="finding.sop" class="text-gray-500">{{ finding.sop.code }} - {{ finding.sop.title }}</span>
                            <span v-if="finding.loss_amount > 0" class="text-sm font-medium text-red-600">NBH {{ formatRupiah(finding.loss_amount) }}</span>
                            <span v-if="finding.surplus_amount > 0" class="text-sm font-medium text-blue-700">NBL {{ formatRupiah(finding.surplus_amount) }}</span>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Jadwal notifikasi -->
            <section class="self-start overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Notifikasi</h2>
                    <span class="text-xs text-gray-400">Otomatis (H-N)</span>
                </div>

                <div v-if="!audit.notifications || audit.notifications.length === 0" class="p-6 text-center text-sm text-gray-400">
                    Belum ada jadwal
                </div>

                <div v-else class="divide-y">
                    <div
                        v-for="notif in audit.notifications"
                        :key="notif.id"
                        class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 font-medium text-gray-900">
                                <span>{{ notif.rule_name }}</span>
                                <span class="text-xs font-normal capitalize text-gray-400">{{ notif.channel }}</span>
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500 tabular-nums">{{ notif.scheduled_at }}</div>
                            <div v-if="notif.sent_at" class="mt-0.5 text-xs text-emerald-600 tabular-nums">Terkirim {{ notif.sent_at }}</div>
                            <div v-if="notif.error_message" class="mt-0.5 max-w-[180px] truncate text-xs text-red-500" :title="notif.error_message">
                                {{ notif.error_message }}
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <span
                                class="inline-block rounded-md border px-2 py-0.5 text-xs font-medium"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-200': notif.status === 'SENT',
                                    'bg-amber-50 text-amber-700 border-amber-200': notif.status === 'PENDING',
                                    'bg-red-50 text-red-700 border-red-200': notif.status === 'FAILED',
                                    'bg-gray-100 text-gray-500 border-gray-200': notif.status === 'INACTIVE',
                                }"
                            >
                                <span v-if="notif.status === 'SENT'">Terkirim</span>
                                <span v-else-if="notif.status === 'PENDING'">Menunggu</span>
                                <span v-else-if="notif.status === 'FAILED'">Gagal</span>
                                <span v-else-if="notif.status === 'INACTIVE'">Nonaktif</span>
                                <span v-else>{{ notif.status }}</span>
                            </span>

                            <button
                                v-if="notif.status !== 'SENT' && notif.status !== 'INACTIVE'"
                                @click="sendNotificationNow(notif)"
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition-colors duration-150 hover:bg-blue-50 hover:text-blue-600"
                                title="Kirim sekarang"
                                aria-label="Kirim notifikasi sekarang"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Konfirmasi -->
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
