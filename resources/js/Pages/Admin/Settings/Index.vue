<script setup>
import { ref, computed } from 'vue';
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
const isConfirmValid = computed(() => confirmText.value.trim().toUpperCase() === 'RESET');

const openFactoryResetModal = () => {
    confirmText.value = '';
    showFactoryResetModal.value = true;
};

const confirmFactoryReset = () => {
    if (!isConfirmValid.value) return;

    isFactoryResetting.value = true;
    router.post(
        route('admin.settings.factory-reset'),
        { confirmation: 'RESET' },
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
    <AppLayout title="Pengaturan sistem">
        <Head title="Pengaturan sistem" />

        <h1 class="mb-6 text-2xl font-semibold leading-8 text-gray-900">Pengaturan sistem</h1>

        <div class="max-w-4xl space-y-6">
            <!-- Ringkasan data: satu panel angka -->
            <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-gray-200 bg-white sm:grid-cols-4 shadow-sm">
                <div
                    v-for="item in [
                        { label: 'Audit', value: stats.total_audits ?? 0 },
                        { label: 'Temuan', value: stats.total_findings ?? 0 },
                        { label: 'Toko / gudang', value: stats.total_stores ?? 0 },
                        { label: 'Pengguna', value: stats.total_users ?? 0 },
                    ]"
                    :key="item.label"
                    class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5"
                >
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                        {{ item.label }}
                    </div>
                    <div class="mt-3 text-4xl font-medium leading-10 text-gray-900 tabular-nums">{{ item.value }}</div>
                </div>
            </div>

            <!-- Akun demo -->
            <div class="flex h-14 items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-5 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">Akun demo di halaman login</h2>

                <div class="flex shrink-0 items-center gap-3">
                    <span
                        class="text-sm font-medium"
                        :class="showDemoAccounts ? 'text-green-600' : 'text-gray-400'"
                    >
                        {{ showDemoAccounts ? 'Aktif' : 'Nonaktif' }}
                    </span>

                    <button
                        type="button"
                        @click="toggleDemoAccounts"
                        :disabled="isTogglingDemo"
                        :aria-pressed="showDemoAccounts"
                        aria-label="Tampilkan akun demo di halaman login"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:opacity-50"
                        :class="showDemoAccounts ? 'bg-blue-600' : 'bg-gray-200'"
                    >
                        <span
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 ease-in-out"
                            :class="showDemoAccounts ? 'translate-x-5' : 'translate-x-0'"
                        />
                    </button>
                </div>
            </div>

            <!-- Reset data -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Reset data</h2>
                </div>

                <div class="divide-y divide-gray-200">
                    <!-- Reset transaksional -->
                    <div class="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Reset data audit</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Hapus audit, temuan, action plan, dan bukti. Master data tetap.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="showResetTransactionalModal = true"
                            class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 hover:text-red-600 shadow-sm"
                        >
                            Reset data audit
                        </button>
                    </div>

                    <!-- Factory reset -->
                    <div class="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="text-sm font-medium text-red-600">Factory reset</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Semua data kembali ke demo bawaan. Akun admin Anda tetap aman.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="openFactoryResetModal"
                            class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700"
                        >
                            Reset total
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <!-- Modal Reset Transaksional -->
        <div
            v-if="showResetTransactionalModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
        >
            <div
                @click="!isResettingTransactional && (showResetTransactionalModal = false)"
                class="fixed inset-0 bg-black/40"
            ></div>

            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="p-5">
                    <h3 class="text-base font-semibold text-gray-900">Reset data audit?</h3>
                    <p class="mt-2 text-gray-500">
                        Semua audit, temuan, dan action plan akan dihapus. Toko dan user tetap ada.
                    </p>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                    <button
                        type="button"
                        @click="showResetTransactionalModal = false"
                        :disabled="isResettingTransactional"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmResetTransactional"
                        :disabled="isResettingTransactional"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700 disabled:opacity-50"
                    >
                        {{ isResettingTransactional ? 'Memproses…' : 'Ya, reset data' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Factory Reset -->
        <div
            v-if="showFactoryResetModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
        >
            <div
                @click="!isFactoryResetting && (showFactoryResetModal = false)"
                class="fixed inset-0 bg-black/40"
            ></div>

            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white text-sm shadow-[0_8px_24px_rgba(0,0,0,0.08)]">
                <div class="p-5">
                    <h3 class="text-base font-medium text-red-600">Reset total?</h3>
                    <p class="mt-2 text-gray-500">
                        Semua data dihapus dan kembali ke demo bawaan.
                    </p>

                    <div class="mt-4">
                        <label class="mb-2 block text-sm text-gray-700">Ketik <strong class="font-medium text-red-600">RESET</strong> untuk lanjut</label>
                        <input
                            type="text"
                            v-model="confirmText"
                            placeholder="RESET"
                            class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm uppercase tabular-nums shadow-sm focus:border-red-500 focus:ring-red-500"
                            :disabled="isFactoryResetting"
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                    <button
                        type="button"
                        @click="showFactoryResetModal = false"
                        :disabled="isFactoryResetting"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmFactoryReset"
                        :disabled="!isConfirmValid || isFactoryResetting"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700 disabled:opacity-40"
                    >
                        {{ isFactoryResetting ? 'Memproses…' : 'Reset total' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
