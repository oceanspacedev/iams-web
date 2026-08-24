<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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
        const matchesSearch =
            u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            u.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            u.stores.toLowerCase().includes(searchQuery.value.toLowerCase());

        const matchesRole = !roleFilter.value || u.role === roleFilter.value;

        return matchesSearch && matchesRole;
    });
});

const toggleActive = (user) => {
    router.patch(route('admin.users.toggle-active', user.id), {}, {
        preserveScroll: true,
    });
};

const deleteUser = (user) => {
    if (confirm(`Yakin ingin menghapus user ${user.name}?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna">
        <Head title="Manajemen User" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Manajemen Pengguna</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola data akun pengguna, penugasan role, status aktif, dan penugasan toko</p>
            </div>

            <Link
                :href="route('admin.users.create')"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
            >
                + Tambah User Baru
            </Link>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded border border-gray-200 mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="w-full sm:w-80">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama, email, atau toko..."
                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
            <div class="w-full sm:w-auto">
                <select
                    v-model="roleFilter"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                >
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="coordinator">Koordinator Audit</option>
                    <option value="auditor">Auditor</option>
                    <option value="auditee">Auditee (Toko)</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3.5">Nama Lengkap</th>
                            <th class="px-5 py-3.5">Email</th>
                            <th class="px-5 py-3.5">No. WhatsApp</th>
                            <th class="px-5 py-3.5">Role</th>
                            <th class="px-5 py-3.5">Toko Ditugaskan</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">Tidak ada user ditemukan.</td>
                        </tr>
                        <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-medium text-gray-900">{{ u.name }}</td>
                            <td class="px-5 py-3.5 text-gray-600 font-mono">{{ u.email }}</td>
                            <td class="px-5 py-3.5 text-gray-800 font-mono">{{ u.phone }}</td>
                            <td class="px-5 py-3.5">
                                <span
                                    class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider"
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
                            <td class="px-5 py-3.5 text-gray-700 max-w-xs truncate">{{ u.stores }}</td>
                            <td class="px-5 py-3.5">
                                <button
                                    @click="toggleActive(u)"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border"
                                    :class="u.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200'"
                                >
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                <Link
                                    :href="route('admin.users.edit', u.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteUser(u)"
                                    class="px-2.5 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
