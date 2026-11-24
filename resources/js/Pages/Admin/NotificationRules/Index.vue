<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
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

const form = useForm({
    name: '',
    days_before: 0,
    send_time: '08:00',
    channel: 'whatsapp',
    recipient_type: 'all',
    is_active: true,
});

const resetForm = () => {
    form.reset();
    form.clearErrors();
    isEditing.value = false;
    currentRuleId.value = null;
};

const openCreateModal = () => {
    resetForm();
    isModalOpen.value = true;
};

const openEditModal = (rule) => {
    form.clearErrors();
    form.name = rule.name;
    form.days_before = rule.days_before;
    form.send_time = rule.send_time;
    form.channel = rule.channel;
    form.recipient_type = rule.recipient_type || 'all';
    form.is_active = Boolean(rule.is_active);
    currentRuleId.value = rule.id;
    isEditing.value = true;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    resetForm();
};

const submitForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (isEditing.value) {
        form.put(route('admin.notification-rules.update', currentRuleId.value), options);
    } else {
        form.post(route('admin.notification-rules.store'), options);
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
        title: 'Hapus aturan notifikasi',
        message: `Apakah Anda yakin ingin menghapus aturan "${rule.name}"?`,
        confirmText: 'Hapus aturan',
        type: 'danger',
        action: () => router.delete(route('admin.notification-rules.destroy', rule.id)),
    });
};
</script>

<template>
    <AppLayout title="Jadwal notifikasi">
        <Head title="Jadwal notifikasi" />

        <!-- Header: judul + jumlah, aksi di kanan -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Jadwal notifikasi</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ rules.length }}</span>
            </div>
            <button
                @click="openCreateModal"
                type="button"
                class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah aturan
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="rules.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Belum ada aturan
                </div>
                <div
                    v-for="rule in paginatedRules"
                    :key="'m-rule-' + rule.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ rule.name }}</div>
                            <div class="mt-0.5 text-xs text-gray-500 tabular-nums">
                                <span class="font-medium text-blue-700">{{ rule.days_before === 0 ? 'Hari H' : 'H-' + rule.days_before }}</span>
                                · {{ rule.send_time }} WIB
                            </div>
                        </div>
                        <span
                            class="inline-flex shrink-0 items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium"
                            :class="rule.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="rule.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                            {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2 text-xs text-gray-500">
                            <span
                                class="inline-block rounded-md border px-2 py-0.5 text-xs font-medium capitalize"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-200': rule.channel === 'whatsapp',
                                    'bg-blue-50 text-blue-700 border-blue-200': rule.channel === 'email',
                                    'bg-slate-100 text-slate-700 border-slate-200': rule.channel === 'internal',
                                }"
                            >
                                {{ rule.channel }}
                            </span>
                            <span class="truncate capitalize" title="Penerima">{{ rule.recipient_type || 'Semua' }}</span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                @click="openEditModal(rule)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition-colors duration-150 hover:bg-slate-200"
                                title="Edit"
                                aria-label="Edit"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                            </button>
                            <button
                                @click="toggleActive(rule)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-slate-700 transition-colors duration-150 hover:bg-gray-50"
                                :title="rule.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                :aria-label="rule.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v9" /><path d="M6.4 6.4a8 8 0 1 0 11.2 0" /></svg>
                            </button>
                            <button
                                @click="deleteRule(rule)"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-100"
                                title="Hapus"
                                aria-label="Hapus"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="rules.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Nama</th>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Jadwal</th>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Jam</th>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Channel</th>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Penerima</th>
                            <th class="h-10 whitespace-nowrap px-5 text-center font-medium">Status</th>
                            <th class="h-10 whitespace-nowrap px-5"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="rules.length === 0">
                            <td colspan="7" class="px-5 py-10 text-center text-gray-500">
                                Belum ada aturan
                            </td>
                        </tr>
                        <tr v-for="rule in paginatedRules" :key="rule.id" class="transition-colors duration-150 hover:bg-slate-50/70">
                            <td class="h-14 px-5 py-3 font-medium text-gray-900">
                                {{ rule.name }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 tabular-nums">
                                <span v-if="rule.days_before === 0" class="font-medium text-blue-700">Hari H</span>
                                <span v-else :title="rule.days_before + ' hari sebelum audit'">H-{{ rule.days_before }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-800 tabular-nums">
                                {{ rule.send_time }} WIB
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-block rounded-md border px-2 py-0.5 text-xs font-medium capitalize"
                                    :class="{
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': rule.channel === 'whatsapp',
                                        'bg-blue-50 text-blue-700 border-blue-200': rule.channel === 'email',
                                        'bg-slate-100 text-slate-700 border-slate-200': rule.channel === 'internal',
                                    }"
                                >
                                    {{ rule.channel }}
                                </span>
                            </td>
                            <td class="px-5 py-3 capitalize text-gray-700">
                                {{ rule.recipient_type || 'Semua' }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button
                                    @click="toggleActive(rule)"
                                    class="inline-flex items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium transition-colors duration-150"
                                    :class="rule.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100' : 'text-gray-500 bg-gray-50 border-gray-200 hover:bg-gray-100'"
                                    title="Ubah status aktif"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="rule.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        @click="openEditModal(rule)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                        title="Edit"
                                        aria-label="Edit"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                    </button>
                                    <button
                                        @click="deleteRule(rule)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors duration-150 hover:text-red-800"
                                        title="Hapus"
                                        aria-label="Hapus"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
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
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/40 p-4 backdrop-blur-xs"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h3 class="text-base font-semibold text-gray-900">
                        {{ isEditing ? 'Edit aturan' : 'Tambah aturan' }}
                    </h3>
                    <button @click="closeModal" aria-label="Tutup" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 hover:text-gray-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="text-sm">
                    <div class="space-y-4 p-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-900">Nama</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="H-7, H-3, H-1, Hari H"
                                required
                                class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Hari sebelum</label>
                                <input
                                    v-model.number="form.days_before"
                                    type="number"
                                    min="0"
                                    max="90"
                                    required
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                                <span class="mt-1 block text-xs text-gray-400">0 = Hari H</span>
                                <div v-if="form.errors.days_before" class="mt-1 text-sm text-red-600">{{ form.errors.days_before }}</div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Jam kirim</label>
                                <input
                                    v-model="form.send_time"
                                    type="text"
                                    placeholder="08:00"
                                    required
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                                <span class="mt-1 block text-xs text-gray-400">JJ:MM, 24 jam</span>
                                <div v-if="form.errors.send_time" class="mt-1 text-sm text-red-600">{{ form.errors.send_time }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Channel</label>
                                <select
                                    v-model="form.channel"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Email</option>
                                    <option value="dashboard">Dashboard</option>
                                </select>
                                <div v-if="form.errors.channel" class="mt-1 text-sm text-red-600">{{ form.errors.channel }}</div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-900">Penerima</label>
                                <select
                                    v-model="form.recipient_type"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                >
                                    <option value="all">Semua</option>
                                    <option value="auditee">Auditee Toko</option>
                                    <option value="auditor">Staff Audit</option>
                                </select>
                                <div v-if="form.errors.recipient_type" class="mt-1 text-sm text-red-600">{{ form.errors.recipient_type }}</div>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600 shadow-sm"
                            />
                            <span class="text-sm font-medium text-gray-700">Aktif</span>
                        </label>
                        <div v-if="form.errors.is_active" class="mt-1 text-sm text-red-600">{{ form.errors.is_active }}</div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                        <button
                            @click="closeModal"
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                        >
                            {{ form.processing ? 'Menyimpan…' : (isEditing ? 'Simpan perubahan' : 'Tambah aturan') }}
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
