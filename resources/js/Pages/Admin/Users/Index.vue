<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    currentStatus: {
        type: String,
        default: 'all',
    },
    stats: {
        type: Object,
        default: () => ({
            all: 0,
            active: 0,
            pending: 0,
            inactive: 0,
        }),
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const roleFilter = ref('');
const currentPage = ref(1);

const roleLabels = {
    admin: 'Admin',
    chief: 'Chief Auditor',
    asmen: 'Asisten Manager',
    coordinator: 'Koordinator',
    auditor: 'Staff Audit',
    auditee: 'Auditee Toko',
};

const getRoleLabel = (role) => roleLabels[role] || role;

const filteredUsers = computed(() => {
    return props.users.filter((u) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (u.name && u.name.toLowerCase().includes(query)) ||
            (u.email && u.email.toLowerCase().includes(query)) ||
            (u.phone && u.phone.toLowerCase().includes(query));

        const matchesRole = !roleFilter.value || u.role === roleFilter.value;

        return matchesSearch && matchesRole;
    });
});

watch([searchQuery, roleFilter], () => {
    currentPage.value = 1;
});

// Jaga halaman tetap valid saat jumlah data berkurang (hapus / ganti tab)
watch(() => filteredUsers.value.length, (total) => {
    const lastPage = Math.max(1, Math.ceil(total / 10));
    if (currentPage.value > lastPage) currentPage.value = lastPage;
});

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredUsers.value.slice(start, start + 10);
});

const approveRoleOrder = ['auditor', 'coordinator', 'auditee', 'asmen', 'chief', 'admin'];
const approveRoles = computed(() => approveRoleOrder.filter((r) => props.roles.includes(r)));

const setStatusFilter = (status) => {
    currentPage.value = 1;
    router.get(route('admin.users.index'), { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const toggleActive = (user) => {
    router.patch(route('admin.users.toggle-active', user.id), {}, {
        preserveScroll: true,
    });
};

// --- Approval & Rejection Logic ---
const approveModal = ref({
    show: false,
    user: null,
    selectedRole: 'auditor',
    error: '',
});

const openApproveModal = (user) => {
    approveModal.value = {
        show: true,
        user,
        selectedRole: user.requested_role || 'auditor',
        error: '',
    };
};

const submitApprove = () => {
    if (!approveModal.value.user) return;
    approveModal.value.error = '';
    router.patch(route('admin.users.approve', approveModal.value.user.id), {
        role: approveModal.value.selectedRole,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            approveModal.value.show = false;
        },
        onError: (errors) => {
            approveModal.value.error = errors.role || '';
        },
    });
};

const rejectModal = ref({
    show: false,
    user: null,
    reason: '',
});

const openRejectModal = (user) => {
    rejectModal.value = {
        show: true,
        user,
        reason: '',
    };
};

const submitReject = () => {
    if (!rejectModal.value.user) return;
    router.patch(route('admin.users.reject', rejectModal.value.user.id), {
        reason: rejectModal.value.reason,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            rejectModal.value.show = false;
        },
    });
};

// --- Delete Modal ---
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

// Chief tidak boleh mengelola akun admin (server juga menolak), jadi tombolnya disembunyikan
const page = usePage();
const isAdmin = computed(() => (page.props.auth?.user?.roles || []).some((r) => (r.name || r) === 'admin'));
const canManage = (u) => isAdmin.value || u.role !== 'admin';

const deleteUser = (user) => {
    openConfirm({
        title: 'Hapus akun pengguna',
        message: `Apakah Anda yakin ingin menghapus user ${user.name} (${user.email})?`,
        confirmText: 'Ya, hapus pengguna',
        type: 'danger',
        action: () => router.delete(route('admin.users.destroy', user.id)),
    });
};
</script>

<template>
    <AppLayout title="Pengguna">
        <Head title="Pengguna" />

        <!-- Header: judul + jumlah, aksi di kanan -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-baseline gap-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Pengguna</h1>
                <span class="text-2xl leading-8 text-gray-400 tabular-nums">{{ filteredUsers.length }}</span>
            </div>
            <Link
                :href="route('admin.users.create')"
                class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah pengguna
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Tab status di dalam kartu -->
            <div class="no-scrollbar overflow-x-auto border-b border-gray-200 px-5">
                <nav class="flex min-w-max gap-6 whitespace-nowrap text-sm" aria-label="Tabs">
                    <button
                        type="button"
                        @click="setStatusFilter('all')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'all' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Semua
                        <span class="rounded-md px-2 py-0.5 text-xs tabular-nums" :class="currentStatus === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'">
                            {{ stats.all }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('active')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'active' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Aktif
                        <span class="rounded-md px-2 py-0.5 text-xs tabular-nums" :class="currentStatus === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                            {{ stats.active }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('pending')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'pending' ? 'border-amber-600 text-amber-700 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Menunggu
                        <span
                            class="rounded-md px-2 py-0.5 text-xs font-medium tabular-nums"
                            :class="stats.pending > 0 ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-300' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ stats.pending }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('inactive')"
                        class="-mb-px flex h-12 items-center gap-2 border-b-2 px-1 font-medium transition-colors duration-150"
                        :class="currentStatus === 'inactive' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    >
                        Nonaktif / ditolak
                        <span class="rounded-md px-2 py-0.5 text-xs tabular-nums" :class="currentStatus === 'inactive' ? 'bg-gray-200 text-gray-800' : 'bg-gray-100 text-gray-600'">
                            {{ stats.inactive }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Filter di dalam kartu tabel -->
            <div class="flex flex-col gap-3 border-b border-gray-200 p-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari"
                        aria-label="Cari nama, email, atau no. WhatsApp"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    />
                </div>
                <div class="flex w-full gap-3 sm:ml-auto sm:w-auto">
                    <select
                        v-model="roleFilter"
                        aria-label="Jabatan"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 sm:w-auto shadow-sm"
                    >
                        <option value="">Semua jabatan</option>
                        <option value="admin">Admin</option>
                        <option value="chief">Chief Auditor</option>
                        <option value="asmen">Asisten Manager</option>
                        <option value="coordinator">Koordinator</option>
                        <option value="auditor">Auditor</option>
                        <option value="auditee">Auditee Toko</option>
                    </select>
                </div>
            </div>

            <!-- Mobile -->
            <div class="block divide-y divide-gray-200 md:hidden">
                <div v-if="filteredUsers.length === 0" class="p-8 text-center text-sm text-gray-400">
                    Tidak ada pengguna
                </div>
                <div
                    v-for="u in paginatedUsers"
                    :key="'mobile-' + u.id"
                    class="space-y-3 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900">{{ u.name }}</div>
                            <div class="mt-0.5 truncate text-sm text-gray-500">{{ u.email }}</div>
                        </div>

                        <div class="shrink-0">
                            <template v-if="u.approval_status === 'pending'">
                                <span class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    Menunggu
                                </span>
                            </template>
                            <template v-else-if="u.approval_status === 'rejected'">
                                <span class="rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-800">
                                    Ditolak
                                </span>
                            </template>
                            <template v-else>
                                <button
                                    @click="toggleActive(u)"
                                    class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium"
                                    :class="u.is_active ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                    title="Ubah status aktif"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="u.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0 text-xs text-gray-600">
                            <span class="font-medium text-slate-800">
                                {{ u.approval_status === 'pending' ? getRoleLabel(u.requested_role) : getRoleLabel(u.role) }}
                            </span>
                            <span class="tabular-nums"> · {{ u.phone || '—' }}</span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <template v-if="u.approval_status === 'pending'">
                                <button
                                    @click="openApproveModal(u)"
                                    class="inline-flex h-8 items-center rounded-lg bg-emerald-600 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700"
                                >
                                    Setujui
                                </button>
                                <button
                                    @click="openRejectModal(u)"
                                    class="inline-flex h-8 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition-colors duration-150 hover:bg-rose-100"
                                >
                                    Tolak
                                </button>
                            </template>
                            <template v-else-if="canManage(u)">
                                <Link
                                    :href="route('admin.users.edit', u.id)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700 transition-colors duration-150 hover:bg-slate-200"
                                    title="Edit"
                                    aria-label="Edit"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                </Link>
                                <button
                                    @click="deleteUser(u)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-700 transition-colors duration-150 hover:bg-red-100"
                                    title="Hapus"
                                    aria-label="Hapus"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 13h12l1-13M9 7V4h6v3" /></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop -->
            <div v-stable-table="filteredUsers.length" class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="border-b border-gray-200 bg-slate-50 text-xs text-slate-700">
                        <tr>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Nama</th>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">WhatsApp</th>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Jabatan</th>
                            <th class="h-10 whitespace-nowrap px-5 font-medium">Daftar</th>
                            <th class="h-10 whitespace-nowrap px-5 text-center font-medium">Status</th>
                            <th class="h-10 whitespace-nowrap px-5"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody :key="currentPage" class="page-fade divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                                Tidak ada pengguna
                            </td>
                        </tr>
                        <tr
                            v-for="u in paginatedUsers"
                            :key="u.id"
                            class="transition-colors duration-150 hover:bg-slate-50/70"
                        >
                            <td class="h-14 px-5 py-3 align-middle">
                                <div class="font-medium text-gray-900">{{ u.name }}</div>
                                <div class="mt-0.5 text-xs text-gray-500">{{ u.email }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 align-middle text-gray-700 tabular-nums">
                                {{ u.phone || '—' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 align-middle">
                                <template v-if="u.approval_status === 'pending'">
                                    <div class="text-sm text-amber-800" title="Jabatan yang diajukan">
                                        <span class="font-medium underline">{{ getRoleLabel(u.requested_role) }}</span>
                                    </div>
                                    <div v-if="u.stores !== '—'" class="mt-0.5 text-xs text-gray-500">
                                        {{ u.stores }}
                                    </div>
                                </template>
                                <template v-else>
                                    <span class="inline-block rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        {{ getRoleLabel(u.role) }}
                                    </span>
                                </template>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 align-middle text-xs text-gray-400 tabular-nums">
                                {{ u.created_at || '—' }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-center align-middle">
                                <template v-if="u.approval_status === 'pending'">
                                    <span class="inline-flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Menunggu
                                    </span>
                                </template>
                                <template v-else-if="u.approval_status === 'rejected'">
                                    <span class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-800">
                                        Ditolak
                                    </span>
                                </template>
                                <template v-else>
                                    <button
                                        @click="toggleActive(u)"
                                        class="inline-flex items-center gap-2 rounded-md border px-2 py-0.5 text-xs font-medium transition-colors duration-150"
                                        :class="u.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100' : 'text-gray-500 bg-gray-50 border-gray-200 hover:bg-gray-100'"
                                        title="Ubah status aktif"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="u.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                        {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </template>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right align-middle">
                                <div v-if="u.approval_status === 'pending'" class="inline-flex items-center gap-2">
                                    <button
                                        @click="openApproveModal(u)"
                                        class="inline-flex h-8 items-center rounded-lg bg-emerald-600 px-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700"
                                    >
                                        Setujui
                                    </button>
                                    <button
                                        @click="openRejectModal(u)"
                                        class="inline-flex h-8 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition-colors duration-150 hover:bg-rose-100"
                                    >
                                        Tolak
                                    </button>
                                </div>

                                <div v-else-if="canManage(u)" class="inline-flex items-center gap-1">
                                    <Link
                                        :href="route('admin.users.edit', u.id)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 transition-colors duration-150 hover:text-slate-900"
                                        title="Edit"
                                        aria-label="Edit"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" /><path d="m14.5 7.5 3 3" /></svg>
                                    </Link>
                                    <button
                                        @click="deleteUser(u)"
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
                :total-items="filteredUsers.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Approve Modal -->
        <div v-if="approveModal.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-sm space-y-5 rounded-2xl bg-white p-6 text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">Setujui pendaftaran</h3>
                </div>

                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-slate-700">
                    <dt class="text-gray-500">Nama</dt>
                    <dd class="font-medium text-slate-900">{{ approveModal.user?.name }}</dd>
                    <dt class="text-gray-500">WhatsApp</dt>
                    <dd class="tabular-nums">{{ approveModal.user?.phone }}</dd>
                    <dt class="text-gray-500">Pengajuan</dt>
                    <dd class="font-medium text-blue-700">{{ getRoleLabel(approveModal.user?.requested_role) }}</dd>
                </dl>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-900">Jabatan disetujui</label>
                    <select
                        v-model="approveModal.selectedRole"
                        class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option v-for="r in approveRoles" :key="r" :value="r">{{ getRoleLabel(r) }}</option>
                    </select>
                    <div v-if="approveModal.error" class="mt-1 text-sm text-red-600">{{ approveModal.error }}</div>
                    <p class="mt-2 text-xs text-gray-400">
                        Notifikasi WA terkirim otomatis
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                    <button
                        type="button"
                        @click="approveModal.show = false"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitApprove"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-emerald-700"
                    >
                        Setujui
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="rejectModal.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-sm space-y-5 rounded-2xl bg-white p-6 text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">Tolak {{ rejectModal.user?.name }}?</h3>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-900">Alasan <span class="font-normal text-gray-400">opsional</span></label>
                    <textarea
                        v-model="rejectModal.reason"
                        rows="2"
                        class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                    <button
                        type="button"
                        @click="rejectModal.show = false"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitReject"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-rose-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-rose-700"
                    >
                        Tolak
                    </button>
                </div>
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
