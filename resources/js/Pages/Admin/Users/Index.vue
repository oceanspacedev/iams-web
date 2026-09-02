<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const roleFilter = ref('');
const currentPage = ref(1);

const roleLabels = {
    admin: 'Administrator',
    chief: 'Chief Auditor',
    asmen: 'Asisten Manager',
    coordinator: 'Koordinator',
    auditor: 'Auditor',
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

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredUsers.value.slice(start, start + 10);
});

const toggleActive = (user) => {
    router.patch(route('admin.users.toggle-active', user.id), {}, {
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

const openConfirm = (config) => {
    confirmModal.value = {
        show: true,
        title: config.title || 'Konfirmasi Tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, Lanjutkan',
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

const deleteUser = (user) => {
    openConfirm({
        title: 'Hapus Akun Pengguna',
        message: `Apakah Anda yakin ingin menghapus user ${user.name} (${user.email})?`,
        confirmText: 'Ya, Hapus Pengguna',
        type: 'danger',
        action: () => router.delete(route('admin.users.destroy', user.id)),
    });
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna">
        <Head title="Manajemen Pengguna — Admin" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Pengguna</h1>
                <p class="text-xs text-gray-500 mt-1">Daftar akun dan hak akses pengguna sistem audit</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-mono">
                    {{ filteredUsers.length }} User terdaftar
                </span>
                <Link
                    :href="route('admin.users.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs"
                >
                    + Tambah Pengguna
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-2xs mb-5 text-xs">
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, email, atau no. WhatsApp..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    />
                </div>
                <div class="w-full sm:w-56 shrink-0">
                    <select
                        v-model="roleFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Jabatan</option>
                        <option value="admin">Administrator</option>
                        <option value="chief">Chief Auditor</option>
                        <option value="asmen">Asisten Manager</option>
                        <option value="coordinator">Koordinator</option>
                        <option value="auditor">Auditor</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clean Enterprise Table with 10-Item Pagination -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap w-56">Nama Lengkap</th>
                            <th class="px-4 py-3 whitespace-nowrap w-56">Email</th>
                            <th class="px-4 py-3 whitespace-nowrap w-40">No. WhatsApp</th>
                            <th class="px-4 py-3 whitespace-nowrap w-40">Jabatan / Role</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data pengguna yang sesuai filter.
                            </td>
                        </tr>
                        <tr
                            v-for="u in paginatedUsers"
                            :key="u.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle whitespace-nowrap font-medium text-gray-900">
                                {{ u.name }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-gray-600 font-mono text-[11px]">
                                {{ u.email }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-gray-700 font-mono text-[11px]">
                                {{ u.phone || '—' }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ getRoleLabel(u.role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <button
                                    @click="toggleActive(u)"
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium transition-colors border"
                                    :class="u.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100' : 'text-gray-500 bg-gray-50 border-gray-200 hover:bg-gray-100'"
                                    title="Klik untuk mengubah status aktif"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="u.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        :href="route('admin.users.edit', u.id)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Edit
                                    </Link>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteUser(u)"
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
                :total-items="filteredUsers.length"
                @update:current-page="currentPage = $event"
            />
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
