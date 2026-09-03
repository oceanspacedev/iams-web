<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    showDemoAccounts: {
        type: Boolean,
        default: true,
    },
});

const isTogglingDemo = ref(false);

const toggleDemoAccounts = () => {
    isTogglingDemo.value = true;
    router.post(
        route('admin.settings.toggle-demo-accounts'),
        { enabled: !props.showDemoAccounts },
        {
            preserveScroll: true,
            onFinish: () => {
                isTogglingDemo.value = false;
            },
        }
    );
};

// Modal Reset Transaksional
const showResetTransactionalModal = ref(false);
const isResettingTransactional = ref(false);

const confirmResetTransactional = () => {
    isResettingTransactional.value = true;
    router.post(
        route('admin.settings.reset-transactional'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isResettingTransactional.value = false;
                showResetTransactionalModal.value = false;
            },
        }
    );
};

// Modal Factory Reset
const showFactoryResetModal = ref(false);
const isFactoryResetting = ref(false);
const confirmText = ref('');

const openFactoryResetModal = () => {
    confirmText.value = '';
    showFactoryResetModal.value = true;
};

const confirmFactoryReset = () => {
    if (confirmText.value !== 'RESET') return;

    isFactoryResetting.value = true;
    router.post(
        route('admin.settings.factory-reset'),
        { confirmation: confirmText.value },
        {
            onFinish: () => {
                isFactoryResetting.value = false;
                showFactoryResetModal.value = false;
                confirmText.value = '';
            },
        }
    );
};
</script>

<template>
    <AppLayout title="Pengaturan Sistem">
        <Head title="Pengaturan Sistem — Admin" />

        <!-- Header Standar -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Pengaturan Sistem</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola akun demo login dan pemeliharaan data sistem</p>
            </div>
        </div>

        <div class="space-y-6 max-w-4xl">
            <!-- 1. Akun Demo Toggle -->
            <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-2xs">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Tampilkan Akun Demo di Halaman Login</h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Jika diaktifkan, tombol login instan untuk akun demo akan muncul di halaman login.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span
                            class="text-xs font-medium"
                            :class="showDemoAccounts ? 'text-green-600' : 'text-gray-400'"
                        >
                            {{ showDemoAccounts ? 'Aktif' : 'Nonaktif' }}
                        </span>

                        <button
                            type="button"
                            @click="toggleDemoAccounts"
                            :disabled="isTogglingDemo"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:opacity-50"
                            :class="showDemoAccounts ? 'bg-blue-600' : 'bg-gray-200'"
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                :class="showDemoAccounts ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Ringkasan Data -->
            <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-2xs">
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Ringkasan Data Saat Ini</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="border border-gray-100 rounded p-3 bg-gray-50/50">
                        <div class="text-gray-500">Total Audit</div>
                        <div class="text-lg font-semibold text-gray-900 mt-1">{{ stats.total_audits ?? 0 }}</div>
                    </div>
                    <div class="border border-gray-100 rounded p-3 bg-gray-50/50">
                        <div class="text-gray-500">Total Temuan</div>
                        <div class="text-lg font-semibold text-gray-900 mt-1">{{ stats.total_findings ?? 0 }}</div>
                    </div>
                    <div class="border border-gray-100 rounded p-3 bg-gray-50/50">
                        <div class="text-gray-500">Toko / Gudang</div>
                        <div class="text-lg font-semibold text-gray-900 mt-1">{{ stats.total_stores ?? 0 }}</div>
                    </div>
                    <div class="border border-gray-100 rounded p-3 bg-gray-50/50">
                        <div class="text-gray-500">Pengguna</div>
                        <div class="text-lg font-semibold text-gray-900 mt-1">{{ stats.total_users ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <!-- 3. Reset Data -->
            <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-200 shadow-2xs">
                <div class="p-5">
                    <h2 class="text-sm font-semibold text-gray-900">Reset Data</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Pilihan untuk membersihkan data transaksi atau mereset seluruh database.</p>
                </div>

                <!-- Reset Transaksional -->
                <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-semibold text-gray-900">Reset Data Transaksional Audit</h3>
                        <p class="text-xs text-gray-500 mt-1 max-w-xl">
                            Menghapus semua audit, temuan, action plan, dan dokumen bukti. Data master (user, toko, SOP, kategori) tidak akan terhapus.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showResetTransactionalModal = true"
                        class="px-3.5 py-2 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:text-red-600 transition-colors shrink-0"
                    >
                        Reset Data Audit
                    </button>
                </div>

                <!-- Factory Reset -->
                <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xs font-semibold text-red-600">Reset Semua Data (Factory Reset)</h3>
                        <p class="text-xs text-gray-500 mt-1 max-w-xl">
                            Mengosongkan semua data dan memulihkan data bawaan awal (demo). Akun admin saat ini tetap aman.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="openFactoryResetModal"
                        class="px-3.5 py-2 text-xs font-medium rounded-md bg-red-600 text-white hover:bg-red-700 transition-colors shrink-0"
                    >
                        Reset Total
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Reset Transaksional -->
        <div
            v-if="showResetTransactionalModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
        >
            <div
                @click="!isResettingTransactional && (showResetTransactionalModal = false)"
                class="fixed inset-0 bg-black/40"
            ></div>

            <div class="relative bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-gray-200 z-10 text-xs">
                <h3 class="text-sm font-semibold text-gray-900">Konfirmasi Reset Data Audit</h3>
                <p class="text-gray-500 mt-2">
                    Tindakan ini akan menghapus seluruh data audit, temuan, dan action plan. Data master toko dan user tetap ada.
                </p>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showResetTransactionalModal = false"
                        :disabled="isResettingTransactional"
                        class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmResetTransactional"
                        :disabled="isResettingTransactional"
                        class="px-3 py-1.5 rounded bg-red-600 text-white hover:bg-red-700 font-medium disabled:opacity-50"
                    >
                        {{ isResettingTransactional ? 'Memproses...' : 'Ya, Reset Data' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Factory Reset -->
        <div
            v-if="showFactoryResetModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
        >
            <div
                @click="!isFactoryResetting && (showFactoryResetModal = false)"
                class="fixed inset-0 bg-black/40"
            ></div>

            <div class="relative bg-white rounded-lg max-w-md w-full p-5 shadow-lg border border-gray-200 z-10 text-xs">
                <h3 class="text-sm font-semibold text-red-600">Konfirmasi Reset Total</h3>
                <p class="text-gray-500 mt-2">
                    Semua data akan dihapus dan dikembalikan ke kondisi awal (demo data bawaan).
                </p>

                <div class="mt-4">
                    <label class="block text-gray-700 mb-1">Ketik <strong class="text-red-600">RESET</strong> untuk melanjutkan:</label>
                    <input
                        type="text"
                        v-model="confirmText"
                        placeholder="RESET"
                        class="w-full text-xs rounded border-gray-300 focus:border-red-500 focus:ring-red-500 uppercase font-mono"
                        :disabled="isFactoryResetting"
                    />
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showFactoryResetModal = false"
                        :disabled="isFactoryResetting"
                        class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmFactoryReset"
                        :disabled="confirmText !== 'RESET' || isFactoryResetting"
                        class="px-3 py-1.5 rounded bg-red-600 text-white hover:bg-red-700 font-medium disabled:opacity-40"
                    >
                        {{ isFactoryResetting ? 'Memproses...' : 'Reset Total' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
