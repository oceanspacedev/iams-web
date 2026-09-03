<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

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
        case 'auditor': return 'Auditor Lapangan';
        default: return role || 'Pengguna';
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-4 font-sans text-slate-800">
        <Head title="Pendaftaran Berhasil" />

        <div class="w-full max-w-md bg-white border border-gray-200 rounded-lg p-6 sm:p-7 shadow-xs">
            <div class="flex items-center gap-3 mb-4">
                <ApplicationLogo size="w-9 h-9" />
                <div>
                    <h1 class="text-base font-semibold text-gray-900">Pendaftaran Berhasil</h1>
                    <p class="text-xs text-gray-500">Menunggu persetujuan Administrator</p>
                </div>
            </div>

            <div class="text-xs text-gray-600 mb-4 leading-relaxed">
                Data pendaftaran akun Anda telah diterima. Akun saat ini sedang dalam proses verifikasi oleh Administrator.
            </div>

            <div class="border-t border-b border-gray-100 py-3 mb-4 text-xs space-y-2">
                <div v-if="name" class="flex justify-between">
                    <span class="text-gray-500">Nama:</span>
                    <span class="font-medium text-gray-900">{{ name }}</span>
                </div>
                <div v-if="phone" class="flex justify-between">
                    <span class="text-gray-500">WhatsApp:</span>
                    <span class="font-medium text-gray-900">{{ phone }}</span>
                </div>
                <div v-if="role" class="flex justify-between">
                    <span class="text-gray-500">Jabatan Diajukan:</span>
                    <span class="font-medium text-gray-900">{{ getRoleLabel(role) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status Akun:</span>
                    <span class="font-medium text-amber-700">Menunggu Persetujuan</span>
                </div>
            </div>

            <p class="text-[11px] text-gray-500 mb-5">
                Pemberitahuan persetujuan akan dikirimkan otomatis melalui WhatsApp ke nomor Anda.
            </p>

            <Link
                :href="route('login')"
                class="block w-full py-2 px-4 text-center rounded-md bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition"
            >
                Kembali ke Halaman Login
            </Link>
        </div>
    </div>
</template>
