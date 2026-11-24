<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    action_plans: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const statusFilter = ref('');

const filteredPlans = computed(() => {
    return props.action_plans.filter((ap) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (ap.action_plan && ap.action_plan.toLowerCase().includes(query)) ||
            (ap.store && ap.store.toLowerCase().includes(query)) ||
            (ap.audit_number && ap.audit_number.toLowerCase().includes(query)) ||
            (ap.pic && ap.pic.toLowerCase().includes(query)) ||
            (ap.finding && ap.finding.toLowerCase().includes(query));

        // Overdue memakai flag is_overdue (sama dengan badge), bukan status tersimpan
        const matchesStatus =
            !statusFilter.value ||
            (statusFilter.value === 'OVERDUE'
                ? ap.is_overdue
                : !ap.is_overdue && ap.status === statusFilter.value);

        return matchesSearch && matchesStatus;
    });
});

// Pagination 10 baris per halaman; kembali ke halaman 1 saat filter berubah
const currentPage = ref(1);
const perPage = 10;
watch([searchQuery, statusFilter], () => {
    currentPage.value = 1;
});
watch(() => filteredPlans.value.length, (n) => {
    const last = Math.max(1, Math.ceil(n / perPage));
    if (currentPage.value > last) currentPage.value = last;
});
const paginatedPlans = computed(() => filteredPlans.value.slice((currentPage.value - 1) * perPage, currentPage.value * perPage));

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
        title: config.title || 'Konfirmasi',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Lanjutkan',
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

const sendSingleReminder = (ap) => {
    openConfirm({
        title: 'Kirim pengingat WhatsApp',
        message: `Kirim notifikasi pengingat ke toko ${ap.store} untuk audit ${ap.audit_number}?`,
        confirmText: 'Kirim pengingat',
        type: 'primary',
        action: () => router.post(route('admin.action-plans.send-reminder', ap.id), {}, { preserveScroll: true }),
    });
};

const broadcastAllReminders = () => {
    openConfirm({
        title: 'Jalankan pengingat otomatis',
        message: 'Sistem akan memproses seluruh Action Plan yang mendekati batas waktu dan mengirimkan pengingat ke toko terkait.',
        confirmText: 'Jalankan pengingat',
        type: 'primary',
        action: () => router.post(route('admin.action-plans.broadcast-reminders'), {}, { preserveScroll: true }),
    });
};
</script>

<template>
    <AppLayout title="Action plan">
        <Head title="Action plan" />

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Action plan</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ filteredPlans.length }}</span>
            </div>
            <button
                @click="broadcastAllReminders"
                type="button"
                class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
            >
                Jalankan pengingat WA
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Filter di dalam kartu tabel -->
            <div class="flex flex-col gap-3 border-b border-gray-200 p-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari"
                        aria-label="Cari rencana, toko, no. audit, PIC, atau temuan"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                </div>
                <div class="flex w-full gap-3 sm:ml-auto sm:w-auto">
                    <select
                        v-model="statusFilter"
                        aria-label="Status plan"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua status</option>
                        <option value="OPEN">Terbuka</option>
                        <option value="IN_PROGRESS">Dalam proses</option>
                        <option value="COMPLETED">Selesai</option>
                        <option value="OVERDUE">Terlambat</option>
                    </select>
                </div>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="filteredPlans.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada action plan
                </div>
                <div
                    v-for="ap in paginatedPlans"
                    :key="'m-ap-' + ap.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ ap.store }}</div>
                            <div class="mt-0.5 text-xs text-gray-400 tabular-nums">{{ ap.audit_number }}</div>
                        </div>
                        <StatusBadge :status="ap.is_overdue ? 'OVERDUE' : ap.status" />
                    </div>

                    <div class="line-clamp-2 text-sm text-gray-800">{{ ap.finding }}</div>

                    <div class="rounded-lg border border-gray-100 bg-slate-50 p-3 text-xs">
                        <div v-if="ap.action_plan" class="line-clamp-2 text-gray-700">{{ ap.action_plan }}</div>
                        <div v-else class="text-gray-400">Belum diisi toko</div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3">
                        <div class="min-w-0 text-xs text-gray-600">
                            <div class="truncate font-medium text-gray-800">{{ ap.pic || '—' }}</div>
                            <div class="tabular-nums" :class="ap.is_overdue ? 'text-red-600 font-semibold' : 'text-gray-500 tabular-nums'">
                                {{ ap.deadline || '—' }}
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                v-if="ap.status !== 'COMPLETED'"
                                @click="sendSingleReminder(ap)"
                                type="button"
                                class="inline-flex h-8 items-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                            >
                                Ingatkan WA
                            </button>
                            <Link
                                :href="route('admin.findings.show', ap.finding_id)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition-colors duration-150 hover:bg-slate-200"
                                title="Detail"
                                aria-label="Detail"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="filteredPlans.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
                        <tr>
                            <th class="h-10 whitespace-nowrap px-4 font-medium">Audit</th>
                            <th class="h-10 px-4 font-medium">Temuan</th>
                            <th class="h-10 px-4 font-medium">Rencana</th>
                            <th class="h-10 whitespace-nowrap px-4 font-medium">PIC & deadline</th>
                            <th class="h-10 whitespace-nowrap px-4 font-medium">Plan</th>
                            <th class="h-10 whitespace-nowrap px-4 font-medium">Finding</th>
                            <th class="h-10 whitespace-nowrap px-4"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200">
                        <tr v-if="filteredPlans.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada action plan
                            </td>
                        </tr>
                        <tr
                            v-for="ap in paginatedPlans"
                            :key="ap.id"
                            class="transition-colors duration-150 hover:bg-gray-50/70"
                        >
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <div class="font-medium text-gray-900 tabular-nums">
                                    {{ ap.audit_number }}
                                </div>
                                <div class="mt-0.5 text-xs text-gray-600">{{ ap.store }}</div>
                            </td>

                            <td class="max-w-xs px-4 py-3 align-top">
                                <div class="line-clamp-2 text-gray-900">
                                    {{ ap.finding }}
                                </div>
                            </td>

                            <td class="max-w-sm px-4 py-3 align-top">
                                <div v-if="ap.action_plan" class="line-clamp-2 text-gray-800">
                                    {{ ap.action_plan }}
                                </div>
                                <div v-else class="text-gray-400">
                                    Belum diisi toko
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <div class="font-medium text-gray-900">{{ ap.pic || '—' }}</div>
                                <div
                                    class="mt-0.5 text-xs tabular-nums"
                                    :class="ap.is_overdue ? 'text-red-600 font-medium' : 'text-gray-500'"
                                >
                                    {{ ap.deadline || '—' }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <StatusBadge :status="ap.is_overdue ? 'OVERDUE' : ap.status" />
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <StatusBadge :status="ap.finding_status" />
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="ap.status !== 'COMPLETED'"
                                        @click="sendSingleReminder(ap)"
                                        type="button"
                                        class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                                    >
                                        Ingatkan WA
                                    </button>
                                    <Link
                                        :href="route('admin.findings.show', ap.finding_id)"
                                        class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                                        title="Detail"
                                        aria-label="Detail"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination
                :current-page="currentPage"
                :per-page="perPage"
                :total-items="filteredPlans.length"
                @update:current-page="currentPage = $event"
            />
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
