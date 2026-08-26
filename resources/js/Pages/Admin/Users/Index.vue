<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const roleFilter = ref('');

const filteredUsers = computed(() => {
    return props.users.filter((u) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (u.name && u.name.toLowerCase().includes(query)) ||
            (u.email && u.email.toLowerCase().includes(query)) ||
            (u.stores && u.stores.toLowerCase().includes(query)) ||
            (u.phone && u.phone.toLowerCase().includes(query));

        const matchesRole = !roleFilter.value || u.role === roleFilter.value;

        return matchesSearch && matchesRole;
    });
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
        <Head title="Manajemen User — Admin" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Pengguna</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola data akun pengguna, penugasan role, status aktif, dan penugasan toko</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Total: {{ filteredUsers.length }} Pengguna
                </span>
                <Link
                    :href="route('admin.users.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    + Tambah User Baru
                </Link>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-xs mb-6">
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
                        placeholder="Cari nama, email, no. HP, atau toko..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-gray-50/50 focus:bg-white transition-colors"
                    />
                </div>
                <div class="w-full sm:w-56 shrink-0">
                    <select
                        v-model="roleFilter"
                        class="w-full py-2 px-3 text-xs rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-white"
                    >
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="coordinator">Koordinator Audit</option>
                        <option value="auditor">Auditor</option>
                        <option value="auditee">Auditee (Toko)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3.5 whitespace-nowrap w-44">Nama Lengkap</th>
                            <th class="px-4 py-3.5 whitespace-nowrap w-48">Email</th>
                            <th class="px-4 py-3.5 whitespace-nowrap w-36">No. WhatsApp</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Role</th>
                            <th class="px-4 py-3.5 min-w-[180px]">Toko Ditugaskan</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3.5 whitespace-nowrap text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="text-xs font-medium">Tidak ada user ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="u in filteredUsers"
                            :key="u.id"
                            class="hover:bg-slate-50/80 transition-colors"
                        >
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap font-medium text-gray-900">
                                {{ u.name }}
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-gray-600 font-mono">
                                {{ u.email }}
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-gray-800 font-mono">
                                {{ u.phone || '—' }}
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-center">
                                <span
                                    class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="{
                                        'bg-red-50 text-red-700 border border-red-200': u.role === 'admin',
                                        'bg-indigo-50 text-indigo-700 border border-indigo-200': u.role === 'coordinator',
                                        'bg-amber-50 text-amber-700 border border-amber-200': u.role === 'auditor',
                                        'bg-blue-50 text-blue-700 border border-blue-200': u.role === 'auditee',
                                    }"
                                >
                                    {{ u.role }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 align-middle text-gray-700">
                                <div class="truncate max-w-xs font-medium">{{ u.stores || '—' }}</div>
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-center">
                                <button
                                    @click="toggleActive(u)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border transition-colors"
                                    :class="u.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200'"
                                >
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="px-4 py-3.5 align-middle whitespace-nowrap text-right space-x-1.5">
                                <Link
                                    :href="route('admin.users.edit', u.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-xs transition-colors"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteUser(u)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md border border-red-200 text-red-600 bg-white hover:bg-red-50 shadow-xs transition-colors"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
