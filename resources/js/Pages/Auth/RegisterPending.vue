<script setup>
import { Head, Link } from '@inertiajs/vue3';
import LoginLayout from '@/Layouts/LoginLayout.vue';

defineProps({
    name: {
        type: String,
        default: '',
    },
    phone: {
        type: String,
        default: '',
    },
    role: {
        type: String,
        default: '',
    },
});

const getRoleLabel = (role) => {
    switch (role) {
        case 'chief': return 'Chief Auditor (Head of Audit)';
        case 'asmen': return 'Asisten Manager (Asmen)';
        case 'coordinator': return 'Koordinator Audit';
        case 'auditor': return 'Staff Audit';
        default: return role || 'Pengguna';
    }
};
</script>

<template>
    <LoginLayout>
        <Head title="Menunggu persetujuan" />

        <h1 class="text-center text-xl font-semibold text-gray-900">Menunggu persetujuan</h1>

        <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Pendaftaran berhasil. Akun aktif setelah disetujui admin, notifikasi dikirim via WhatsApp.
        </div>

        <dl v-if="name || phone || role" class="mt-6 divide-y divide-gray-200 rounded-lg border border-gray-200 text-sm">
            <div v-if="name" class="flex justify-between gap-4 px-4 py-3">
                <dt class="shrink-0 text-gray-500">Nama</dt>
                <dd class="text-right font-medium text-gray-900">{{ name }}</dd>
            </div>
            <div v-if="phone" class="flex justify-between gap-4 px-4 py-3">
                <dt class="shrink-0 text-gray-500">WhatsApp</dt>
                <dd class="text-right font-medium tabular-nums text-gray-900">{{ phone }}</dd>
            </div>
            <div v-if="role" class="flex justify-between gap-4 px-4 py-3">
                <dt class="shrink-0 text-gray-500">Jabatan</dt>
                <dd class="text-right font-medium text-gray-900">{{ getRoleLabel(role) }}</dd>
            </div>
        </dl>

        <Link
            :href="route('login')"
            class="mt-6 flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors duration-150 hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40"
        >
            Kembali ke halaman masuk
        </Link>
    </LoginLayout>
</template>
