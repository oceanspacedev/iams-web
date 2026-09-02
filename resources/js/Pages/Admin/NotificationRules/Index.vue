<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    rules: {
        type: Array,
        default: () => [],
    },
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const currentRuleId = ref(null);

const form = ref({
    name: '',
    days_before: 0,
    send_time: '08:00',
    channel: 'whatsapp',
    recipient_type: 'all',
    is_active: true,
});

const resetForm = () => {
    form.value = {
        name: '',
        days_before: 0,
        send_time: '08:00',
        channel: 'whatsapp',
        recipient_type: 'all',
        is_active: true,
    };
    isEditing.value = false;
    currentRuleId.value = null;
};

const openCreateModal = () => {
    resetForm();
    isModalOpen.value = true;
};

const openEditModal = (rule) => {
    form.value = {
        name: rule.name,
        days_before: rule.days_before,
        send_time: rule.send_time,
        channel: rule.channel,
        recipient_type: rule.recipient_type || 'all',
        is_active: Boolean(rule.is_active),
    };
    currentRuleId.value = rule.id;
    isEditing.value = true;
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        router.put(route('admin.notification-rules.update', currentRuleId.value), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                resetForm();
            },
        });
    } else {
        router.post(route('admin.notification-rules.store'), form.value, {
            preserveScroll: true,
            onSuccess: () => {
                isModalOpen.value = false;
                resetForm();
            },
        });
    }
};

const toggleActive = (rule) => {
    router.patch(route('admin.notification-rules.toggle-active', rule.id), {}, {
        preserveScroll: true,
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

const currentPage = ref(1);

const paginatedRules = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return props.rules.slice(start, start + 10);
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

const deleteRule = (rule) => {
    openConfirm({
        title: 'Hapus Aturan Notifikasi',
        message: `Apakah Anda yakin ingin menghapus aturan "${rule.name}"?`,
        confirmText: 'Hapus Aturan',
        type: 'danger',
        action: () => router.delete(route('admin.notification-rules.destroy', rule.id)),
    });
};
</script>

<template>
    <AppLayout title="Jadwal Notifikasi Audit">
        <Head title="Jadwal Notifikasi Audit — Admin" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Jadwal Notifikasi Audit (Notification Rules)</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola aturan pengingat otomatis jadwal audit (H-7, H-3, H-1, Hari H) yang dihitung berdasarkan Tanggal Audit</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-mono">
                    {{ rules.length }} Aturan terdaftar
                </span>
                <button
                    @click="openCreateModal"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs"
                >
                    + Tambah Aturan Notifikasi
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">Nama Aturan</th>
                            <th class="px-4 py-3 whitespace-nowrap">Waktu Pengiriman</th>
                            <th class="px-4 py-3 whitespace-nowrap">Jam Kirim</th>
                            <th class="px-4 py-3 whitespace-nowrap">Channel</th>
                            <th class="px-4 py-3 whitespace-nowrap">Penerima</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="rules.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Belum ada aturan notifikasi yang dibuat.
                            </td>
                        </tr>
                        <tr v-for="rule in paginatedRules" :key="rule.id" class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 font-semibold text-gray-900">
                                {{ rule.name }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                <span v-if="rule.days_before === 0" class="font-medium text-blue-700">Hari H (0 Hari)</span>
                                <span v-else>{{ rule.days_before }} hari sebelum audit</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-800">
                                {{ rule.send_time }} WIB
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-medium uppercase border"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': rule.channel === 'whatsapp',
                                        'bg-blue-50 text-blue-700 border-blue-200': rule.channel === 'email',
                                        'bg-slate-100 text-slate-700 border-slate-200': rule.channel === 'internal',
                                    }"
                                >
                                    {{ rule.channel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 capitalize">
                                {{ rule.recipient_type || 'Semua' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    @click="toggleActive(rule)"
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium transition-colors border"
                                    :class="rule.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100' : 'text-gray-500 bg-gray-50 border-gray-200 hover:bg-gray-100'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="rule.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        @click="openEditModal(rule)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Edit
                                    </button>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteRule(rule)"
                                        class="text-red-600 hover:text-red-800 font-medium hover:underline text-xs"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="rules.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Modal Form Create / Edit Rule -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
        >
            <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl border border-gray-200">
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <h3 class="text-sm font-semibold text-gray-900">
                        {{ isEditing ? 'Edit Aturan Notifikasi' : 'Tambah Aturan Notifikasi Baru' }}
                    </h3>
                    <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg leading-none">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nama Aturan</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: H-7, H-3, H-1, Hari H"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Hari Sebelum (Days Before)</label>
                            <input
                                v-model.number="form.days_before"
                                type="number"
                                min="0"
                                max="90"
                                required
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                            <span class="text-[10px] text-gray-400 mt-0.5 block">0 = Hari H pelaksanaan</span>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Jam Pengiriman</label>
                            <input
                                v-model="form.send_time"
                                type="text"
                                placeholder="08:00"
                                required
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                            />
                            <span class="text-[10px] text-gray-400 mt-0.5 block">Format: JJ:MM (24 Jam)</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Channel Notifikasi</label>
                            <select
                                v-model="form.channel"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="whatsapp">WhatsApp</option>
                                <option value="email">Email</option>
                                <option value="dashboard">Dashboard</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Target Penerima</label>
                            <select
                                v-model="form.recipient_type"
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="all">Semua (Toko & Auditor)</option>
                                <option value="auditee">Auditee Toko</option>
                                <option value="auditor">Auditor Lapangan</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="text-xs text-gray-700 font-medium">Status Aturan Aktif</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                        <button
                            @click="isModalOpen = false"
                            type="button"
                            class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-3.5 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-medium"
                        >
                            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Aturan' }}
                        </button>
                    </div>
                </form>
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
