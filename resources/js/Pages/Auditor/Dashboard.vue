<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_audits: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <h1 class="mb-6 text-2xl font-semibold leading-8 text-gray-900">Dashboard</h1>

        <!-- Satu panel angka: lebih tegas dan ringkas dibanding 6 kartu terpisah -->
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-gray-200 bg-white sm:grid-cols-3 xl:grid-cols-6 shadow-sm">
            <div
                v-for="item in [
                    { label: 'Audit', value: stats.assigned_audits, dot: 'bg-gray-400', text: 'text-gray-900' },
                    { label: 'Terbuka', value: stats.open_findings, dot: 'bg-red-600', text: 'text-red-600' },
                    { label: 'Dalam proses', value: stats.in_progress_findings, dot: 'bg-amber-600', text: 'text-amber-600' },
                    { label: 'Menunggu verifikasi', value: stats.waiting_verification, dot: 'bg-blue-700', text: 'text-blue-700' },
                    { label: 'Ditutup', value: stats.closed_findings, dot: 'bg-emerald-700', text: 'text-emerald-700', closed: true },
                    { label: 'Terlambat', value: stats.overdue_findings, dot: 'bg-red-700', text: 'text-red-700' },
                ]"
                :key="item.label"
                class="-mb-px -mr-px border-b border-r border-gray-200 px-5 py-5"
            >
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span class="h-1.5 w-1.5 rounded-full" :class="item.dot"></span>
                    {{ item.label }}
                </div>
                <div class="mt-3 text-4xl font-medium leading-10 tabular-nums" :class="item.text">{{ item.value }}</div>
                <div v-if="item.closed" class="mt-1 text-xs text-gray-500 tabular-nums" title="Tepat waktu · terlambat">
                    {{ stats.closed_on_time ?? 0 }} tepat · {{ stats.closed_overdue ?? 0 }} telat
                </div>
            </div>
        </div>

        <section class="mt-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                <h2 class="text-base font-semibold text-gray-900">Penugasan terbaru</h2>
                <Link
                    :href="route('auditor.audits.index')"
                    class="shrink-0 text-sm font-medium text-blue-600 transition-colors duration-150 hover:text-blue-800"
                >
                    Lihat semua
                </Link>
            </div>

            <!-- Mobile -->
            <div class="divide-y divide-gray-100 sm:hidden">
                <div v-if="recent_audits.length === 0" class="p-6 text-center text-sm text-gray-500">
                    Belum ada penugasan audit untuk akun Anda
                </div>
                <Link
                    v-for="audit in recent_audits"
                    :key="'m-auditor-audit-' + audit.id"
                    :href="route('auditor.audits.show', audit.id)"
                    class="block p-4 transition-colors duration-150 hover:bg-gray-50"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-900">{{ audit.store }}</div>
                            <div class="mt-0.5 text-sm text-gray-500 tabular-nums">{{ audit.audit_number }}</div>
                        </div>
                        <StatusBadge :status="audit.status" />
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500 tabular-nums">
                        <span>{{ audit.audit_date }}</span>
                        <span>{{ audit.findings_count }} temuan</span>
                    </div>
                </Link>
            </div>

            <!-- Desktop -->
            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs text-gray-500">
                        <tr>
                            <th class="h-10 px-5 font-medium">No. audit</th>
                            <th class="h-10 px-5 font-medium">Toko</th>
                            <th class="h-10 px-5 font-medium">Tanggal audit</th>
                            <th class="h-10 px-5 text-right font-medium">Temuan</th>
                            <th class="h-10 px-5 font-medium">Status</th>
                            <th class="h-10 px-5"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="recent_audits.length === 0">
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                Belum ada penugasan audit untuk akun Anda
                            </td>
                        </tr>
                        <tr
                            v-for="audit in recent_audits"
                            :key="audit.id"
                            class="group cursor-pointer transition-colors duration-150 hover:bg-gray-50"
                            @click="router.visit(route('auditor.audits.show', audit.id))"
                        >
                            <td class="h-14 whitespace-nowrap px-5 font-medium text-gray-900 tabular-nums">{{ audit.audit_number }}</td>
                            <td class="h-14 px-5 text-gray-900">{{ audit.store }}</td>
                            <td class="h-14 whitespace-nowrap px-5 text-gray-600 tabular-nums">{{ audit.audit_date }}</td>
                            <td class="h-14 px-5 text-right text-gray-800 tabular-nums">{{ audit.findings_count }}</td>
                            <td class="h-14 whitespace-nowrap px-5"><StatusBadge :status="audit.status" /></td>
                            <td class="h-14 w-12 whitespace-nowrap px-5 text-right">
                                <Link
                                    :href="route('auditor.audits.show', audit.id)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 group-hover:text-gray-900"
                                    :aria-label="'Buka ' + audit.audit_number"
                                    @click.stop
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>
</template>
