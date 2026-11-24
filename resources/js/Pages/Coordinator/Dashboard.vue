<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_audits: {
        type: Array,
        default: () => [],
    },
    recent_findings: {
        type: Array,
        default: () => [],
    },
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number || 0);
};
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Dashboard</h1>

            <Link
                :href="route('coordinator.audits.create')"
                class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 shadow-sm"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Buat audit baru
            </Link>
        </div>

        <!-- Satu panel angka: ringkasan (baris 1) + status temuan (baris 2) -->
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-gray-200 bg-white lg:grid-cols-4 shadow-sm">
            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    Audit
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-gray-900 tabular-nums">{{ stats.total_audits }}</div>
                <div class="mt-1 text-xs text-gray-500 tabular-nums">{{ stats.active_audits }} aktif</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    Temuan
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-gray-900 tabular-nums">{{ stats.total_findings }}</div>
            </div>

            <div class="-mb-px -mr-px min-w-0 border-b border-r border-gray-200 px-5 py-5" title="Nilai barang hilang">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    NBH
                </div>
                <div class="mt-3 break-words text-lg font-medium leading-10 text-gray-900 tabular-nums sm:text-2xl">{{ formatRupiah(stats.total_loss_amount) }}</div>
            </div>

            <div class="-mb-px -mr-px min-w-0 border-b border-r border-gray-200 px-5 py-5" title="Nilai barang lebih">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    NBL
                </div>
                <div class="mt-3 break-words text-lg font-medium leading-10 text-gray-900 tabular-nums sm:text-2xl">{{ formatRupiah(stats.total_surplus_amount) }}</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
                    Terbuka
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-red-600 tabular-nums">{{ stats.open_findings }}</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                    Dalam proses
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-amber-600 tabular-nums">{{ stats.in_progress_findings }}</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-700"></span>
                    Menunggu verifikasi
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-blue-700 tabular-nums">{{ stats.waiting_verification }}</div>
            </div>

            <div class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-700"></span>
                    Ditutup
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 text-emerald-700 tabular-nums">{{ stats.closed_findings }}</div>
                <div class="mt-1 text-xs tabular-nums">
                    <span class="text-emerald-700" title="Ditutup tepat waktu">{{ stats.closed_on_time ?? 0 }} tepat</span>
                    <span class="text-gray-300"> · </span>
                    <span class="text-rose-600" title="Ditutup melewati deadline">{{ stats.closed_overdue ?? 0 }} telat</span>
                </div>
            </div>
        </div>

        <!-- Aktivitas terbaru -->
        <!-- Daftar ringkas (bukan tabel): tetap rapi saat dua kartu berdampingan -->
        <div class="mt-8 grid grid-cols-1 gap-6 2xl:grid-cols-2">
            <!-- Audit terbaru -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Audit terbaru</h2>
                    <Link :href="route('coordinator.audits.index')" class="shrink-0 text-sm font-medium text-blue-600 transition-colors duration-150 hover:text-blue-800">
                        Lihat semua
                    </Link>
                </div>

                <div v-if="recent_audits.length === 0" class="p-6 text-center text-sm text-gray-500">
                    Belum ada audit
                </div>
                <div v-else class="divide-y divide-gray-200">
                    <Link
                        v-for="a in recent_audits"
                        :key="a.id"
                        :href="route('coordinator.audits.show', a.id)"
                        class="group flex items-center gap-4 px-5 py-3 transition-colors duration-150 hover:bg-gray-50/70"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-gray-900">{{ a.store }}</div>
                            <div class="mt-0.5 truncate text-xs text-gray-500">
                                <span class="text-blue-600 tabular-nums">{{ a.audit_number }}</span>
                                <span class="text-gray-300"> · </span>{{ a.auditor }}
                            </div>
                        </div>
                        <StatusBadge :status="a.status" class="shrink-0" />
                        <svg class="hidden h-4 w-4 shrink-0 text-gray-400 transition-colors duration-150 group-hover:text-gray-900 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                    </Link>
                </div>
            </section>

            <!-- Temuan terbaru -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Temuan terbaru</h2>
                    <Link :href="route('coordinator.findings.index')" class="shrink-0 text-sm font-medium text-blue-600 transition-colors duration-150 hover:text-blue-800">
                        Lihat semua
                    </Link>
                </div>

                <div v-if="recent_findings.length === 0" class="p-6 text-center text-sm text-gray-500">
                    Belum ada temuan
                </div>
                <div v-else class="divide-y divide-gray-200">
                    <Link
                        v-for="f in recent_findings"
                        :key="f.id"
                        :href="route('coordinator.findings.show', f.id)"
                        class="group flex items-center gap-4 px-5 py-3 transition-colors duration-150 hover:bg-gray-50/70"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-gray-900">{{ f.store }}</div>
                            <div class="mt-1 flex min-w-0 items-center gap-2">
                                <SeverityBadge :severity="f.severity" class="shrink-0" />
                                <span class="truncate text-xs text-gray-500">{{ f.category }}</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500 tabular-nums sm:hidden">NBH {{ formatRupiah(f.loss_amount) }} · NBL {{ formatRupiah(f.surplus_amount) }}</div>
                        </div>
                        <div class="hidden shrink-0 text-right tabular-nums sm:block">
                            <div class="text-sm text-gray-900"><span class="text-xs text-gray-500">NBH</span> {{ formatRupiah(f.loss_amount) }}</div>
                            <div class="mt-0.5 text-xs text-gray-500">NBL {{ formatRupiah(f.surplus_amount) }}</div>
                        </div>
                        <StatusBadge :status="f.status" class="shrink-0" />
                        <svg class="hidden h-4 w-4 shrink-0 text-gray-400 transition-colors duration-150 group-hover:text-gray-900 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
