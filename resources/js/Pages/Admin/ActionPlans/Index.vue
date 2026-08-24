<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

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
        const matchesSearch =
            (ap.action_plan && ap.action_plan.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            ap.store.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            ap.audit_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (ap.pic && ap.pic.toLowerCase().includes(searchQuery.value.toLowerCase()));

        const matchesStatus = !statusFilter.value || ap.status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });
});
</script>

<template>
    <AppLayout title="Action Plans & Follow-ups">
        <Head title="Manajemen Action Plans" />

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Action Plans (Tindak Lanjut)</h1>
            <p class="text-xs text-gray-500 mt-1">Pemantauan progres pelaksanaan perbaikan dan kepatuhan deadline seluruh toko</p>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari rencana perbaikan, toko, PIC..."
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

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">No. Audit & Toko</th>
                            <th class="px-5 py-3.5">Temuan Finding</th>
                            <th class="px-5 py-3.5">Rencana Perbaikan</th>
                            <th class="px-5 py-3.5">PIC & Deadline</th>
                            <th class="px-5 py-3.5">Status Action Plan</th>
                            <th class="px-5 py-3.5">Status Finding</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredPlans.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">Tidak ada action plan ditemukan.</td>
                        </tr>
                        <tr v-for="ap in filteredPlans" :key="ap.id" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5">
                                <div class="font-mono font-medium text-gray-900">{{ ap.audit_number }}</div>
                                <div class="text-[11px] text-gray-500">{{ ap.store }}</div>
                            </td>
                            <td class="px-5 py-3.5 max-w-xs truncate text-gray-900 font-medium">
                                {{ ap.finding }}
                            </td>
                            <td class="px-5 py-3.5 max-w-xs">
                                <div v-if="ap.action_plan" class="text-gray-900 line-clamp-2">{{ ap.action_plan }}</div>
                                <div v-else class="text-red-500 italic">Belum diisi oleh toko</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-gray-900">{{ ap.pic || '—' }}</div>
                                <div class="text-[11px]" :class="ap.is_overdue ? 'text-red-600 font-bold' : 'text-gray-500'">
                                    DL: {{ ap.deadline || '—' }}
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <StatusBadge :status="ap.is_overdue ? 'OVERDUE' : ap.status" />
                            </td>
                            <td class="px-5 py-3.5">
                                <StatusBadge :status="ap.finding_status" />
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <Link
                                    :href="route('admin.findings.show', ap.finding_id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Detail Finding
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
