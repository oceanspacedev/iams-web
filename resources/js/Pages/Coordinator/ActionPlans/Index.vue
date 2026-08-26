<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
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

        const matchesStatus = !statusFilter.value || ap.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});

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
        title: 'Kirim Pengingat WhatsApp',
        message: `Kirim notifikasi pengingat ke toko ${ap.store} untuk audit ${ap.audit_number}?`,
        confirmText: 'Kirim Pengingat',
        type: 'primary',
        action: () => router.post(route('coordinator.action-plans.send-reminder', ap.id), {}, { preserveScroll: true }),
    });
};

const broadcastAllReminders = () => {
    openConfirm({
        title: 'Jalankan Pengingat Otomatis',
        message: 'Sistem akan memproses seluruh Action Plan yang mendekati batas waktu dan mengirimkan pengingat ke toko terkait.',
        confirmText: 'Jalankan Pengingat',
        type: 'primary',
        action: () => router.post(route('coordinator.action-plans.broadcast-reminders'), {}, { preserveScroll: true }),
    });
};
</script>

<template>
    <AppLayout title="Action Plans">
        <Head title="Action Plans — Koordinator" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Action Plans</h1>
                <p class="text-xs text-gray-500 mt-1">Daftar tindak lanjut perbaikan temuan audit dan status penyelesaian toko</p>
            </div>
            <div>
                <button
                    @click="broadcastAllReminders"
                    type="button"
                    class="px-3.5 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs"
                >
                    Jalankan Pengingat WA
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari rencana, toko, no. audit..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <div class="w-full sm:w-auto">
                <select
                    v-model="statusFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Status Plan</option>
                    <option value="OPEN">Open</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="OVERDUE">Overdue</option>
                </select>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3.5 whitespace-nowrap">No. Audit & Toko</th>
                            <th class="px-4 py-3.5">Temuan</th>
                            <th class="px-4 py-3.5">Rencana Perbaikan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">PIC & Deadline</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Status Plan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Status Finding</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredPlans.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                Tidak ada data action plan.
                            </td>
                        </tr>
                        <tr
                            v-for="ap in filteredPlans"
                            :key="ap.id"
                            class="hover:bg-gray-50/70"
                        >
                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                <div class="font-mono font-medium text-gray-900">
                                    {{ ap.audit_number }}
                                </div>
                                <div class="text-[11px] text-gray-600 mt-0.5">{{ ap.store }}</div>
                            </td>

                            <td class="px-4 py-3.5 align-top max-w-xs">
                                <div class="text-gray-900 line-clamp-2">
                                    {{ ap.finding }}
                                </div>
                            </td>

                            <td class="px-4 py-3.5 align-top max-w-sm">
                                <div v-if="ap.action_plan" class="text-gray-800 line-clamp-2">
                                    {{ ap.action_plan }}
                                </div>
                                <div v-else class="text-gray-400 italic text-[11px]">
                                    Belum diisi oleh toko
                                </div>
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                <div class="text-gray-900 font-medium">{{ ap.pic || '—' }}</div>
                                <div
                                    class="text-[11px] mt-0.5"
                                    :class="ap.is_overdue ? 'text-red-600 font-medium' : 'text-gray-500'"
                                >
                                    DL: {{ ap.deadline || '—' }}
                                </div>
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                <StatusBadge :status="ap.is_overdue ? 'OVERDUE' : ap.status" />
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                <StatusBadge :status="ap.finding_status" />
                            </td>

                            <td class="px-4 py-3.5 align-top whitespace-nowrap text-right space-x-1.5">
                                <button
                                    v-if="ap.status !== 'COMPLETED'"
                                    @click="sendSingleReminder(ap)"
                                    type="button"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Ingatkan WA
                                </button>
                                <Link
                                    :href="route('coordinator.findings.show', ap.finding_id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Detail
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
