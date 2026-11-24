<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    finding: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    sops: {
        type: Array,
        default: () => [],
    },
    audit: {
        type: Object,
        default: null,
    },
    severityOptions: {
        type: Array,
        default: () => [],
    },
});

const auditInfo = computed(() => props.audit || props.finding.audit || {});

const SEVERITY_TILES = [
    {
        value: 'MINOR',
        label: 'Minor',
        timeline: '3 - 7 hari',
        active: 'bg-emerald-100 border-emerald-500 ring-2 ring-emerald-400 text-emerald-950 font-medium',
        idle: 'bg-emerald-50/70 border-emerald-200 text-emerald-800 hover:bg-emerald-100/60 hover:border-emerald-300',
        sub: 'text-emerald-700',
    },
    {
        value: 'MEDIUM',
        label: 'Medium',
        timeline: '8 - 14 hari',
        active: 'bg-amber-100 border-amber-500 ring-2 ring-amber-400 text-amber-950 font-medium',
        idle: 'bg-amber-50/70 border-amber-200 text-amber-800 hover:bg-amber-100/60 hover:border-amber-300',
        sub: 'text-amber-700',
    },
    {
        value: 'MAJOR',
        label: 'Major',
        timeline: '15 - 30 hari',
        active: 'bg-rose-100 border-rose-500 ring-2 ring-rose-400 text-rose-950 font-medium',
        idle: 'bg-rose-50/70 border-rose-200 text-rose-800 hover:bg-rose-100/60 hover:border-rose-300',
        sub: 'text-rose-700',
    },
    {
        value: 'CONTINUOUS',
        label: 'Continuous',
        timeline: 'Monitoring',
        active: 'bg-purple-100 border-purple-500 ring-2 ring-purple-400 text-purple-950 font-medium',
        idle: 'bg-purple-50/70 border-purple-200 text-purple-800 hover:bg-purple-100/60 hover:border-purple-300',
        sub: 'text-purple-700',
    },
];

const isLocked = computed(() => !!props.finding.is_severity_locked);

// Severity lama (CRITICAL/OBSERVATION) tetap dipertahankan bila tidak diubah
const legacySeverity = computed(() =>
    SEVERITY_TILES.some((t) => t.value === props.finding.severity) ? null : props.finding.severity
);

// Kategori yang sudah nonaktif tetap ditampilkan agar pilihan tersimpan tidak hilang
const categoryOptions = computed(() => {
    const list = [...props.categories];
    if (props.finding.category_id && !list.some((c) => c.id === props.finding.category_id) && props.finding.category) {
        list.push({ id: props.finding.category.id, name: props.finding.category.name });
    }
    return list;
});

const sopOptions = computed(() => {
    const list = [...props.sops];
    if (props.finding.sop_id && !list.some((s) => s.id === props.finding.sop_id) && props.finding.sop) {
        list.push({ id: props.finding.sop.id, code: props.finding.sop.code, title: props.finding.sop.title });
    }
    return list;
});

const form = useForm({
    category_id: props.finding.category_id ?? '',
    sop_id: props.finding.sop_id ?? '',
    finding: props.finding.finding ?? '',
    loss_amount: props.finding.loss_amount ?? '',
    surplus_amount: props.finding.surplus_amount ?? '',
    auditor_opinion: props.finding.auditor_opinion ?? '',
    recommendation: props.finding.recommendation ?? '',
    severity: props.finding.severity || 'MINOR',
});

const submit = () => {
    form
        .transform((data) => {
            const payload = { ...data };
            if (isLocked.value) delete payload.severity;
            return payload;
        })
        .patch(route('auditor.findings.update', props.finding.id));
};

const findingCode = computed(() => `#${String(props.finding.id).padStart(4, '0')}`);
</script>

<template>
    <AppLayout title="Edit temuan">
        <Head :title="`Edit temuan ${findingCode}`" />

        <!-- Header -->
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('auditor.audits.index')" class="transition-colors duration-150 hover:text-blue-600">Audit saya</Link>
                <span>/</span>
                <Link :href="route('auditor.audits.show', auditInfo.id)" class="tabular-nums transition-colors duration-150 hover:text-blue-600">{{ auditInfo.audit_number }}</Link>
                <span>/</span>
                <Link :href="route('auditor.findings.show', finding.id)" class="tabular-nums transition-colors duration-150 hover:text-blue-600">{{ findingCode }}</Link>
                <span>/</span>
                <span class="font-medium text-gray-900">Edit</span>
            </div>
            <div class="flex flex-wrap items-baseline gap-x-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Edit temuan</h1>
                <span class="text-2xl leading-8 text-gray-500">{{ auditInfo.store?.name }}</span>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 items-start gap-6 text-sm lg:grid-cols-3">
            <!-- Kolom utama: uraian -->
            <section class="rounded-xl border border-gray-200 bg-white lg:col-span-2 shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Temuan</h2>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label for="finding-text" class="mb-2 block text-sm font-medium text-gray-900">
                            Uraian temuan <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            id="finding-text"
                            v-model="form.finding"
                            rows="6"
                            required
                            class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        ></textarea>
                        <div v-if="form.errors.finding" class="mt-1 text-xs text-red-600">{{ form.errors.finding }}</div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="finding-opinion" class="mb-2 block text-sm font-medium text-gray-900">
                                Opini auditor
                            </label>
                            <textarea
                                id="finding-opinion"
                                v-model="form.auditor_opinion"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            ></textarea>
                            <div v-if="form.errors.auditor_opinion" class="mt-1 text-xs text-red-600">{{ form.errors.auditor_opinion }}</div>
                        </div>

                        <div>
                            <label for="finding-recommendation" class="mb-2 block text-sm font-medium text-gray-900">
                                Rekomendasi <span class="text-red-600">*</span>
                            </label>
                            <textarea
                                id="finding-recommendation"
                                v-model="form.recommendation"
                                rows="4"
                                required
                                class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            ></textarea>
                            <div v-if="form.errors.recommendation" class="mt-1 text-xs text-red-600">{{ form.errors.recommendation }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Kolom samping: klasifikasi + nilai -->
            <section class="rounded-xl border border-gray-200 bg-white lg:sticky lg:top-6 shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Klasifikasi</h2>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label for="finding-category" class="mb-2 block text-sm font-medium text-gray-900">
                            Kategori <span class="text-red-600">*</span>
                        </label>
                        <select
                            id="finding-category"
                            v-model="form.category_id"
                            required
                            class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        >
                            <option value="" disabled>Pilih kategori</option>
                            <option v-for="cat in categoryOptions" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</div>
                    </div>

                    <div>
                        <label for="finding-sop" class="mb-2 block text-sm font-medium text-gray-900">
                            SOP / SE
                        </label>
                        <select
                            id="finding-sop"
                            v-model="form.sop_id"
                            class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        >
                            <option value="">Opsional</option>
                            <option v-for="sop in sopOptions" :key="sop.id" :value="sop.id">
                                {{ sop.code }} - {{ sop.title }}
                            </option>
                        </select>
                        <div v-if="form.errors.sop_id" class="mt-1 text-xs text-red-600">{{ form.errors.sop_id }}</div>
                    </div>

                    <!-- Severity: hanya baca bila sudah dikunci koordinator -->
                    <div v-if="isLocked">
                        <div class="mb-2 text-sm font-medium text-gray-900">Severity</div>
                        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                            <SeverityBadge :severity="finding.severity" :show-timeline="true" />
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                Dikunci koordinator
                            </span>
                        </div>
                    </div>

                    <div v-else>
                        <label id="finding-severity-label" for="finding-severity" class="mb-2 block text-sm font-medium text-gray-900">
                            Severity <span class="text-red-600">*</span>
                        </label>
                        <select
                            id="finding-severity"
                            v-model="form.severity"
                            required
                            class="sr-only"
                            tabindex="-1"
                            aria-label="Severity"
                        >
                            <option v-if="legacySeverity" :value="legacySeverity">{{ legacySeverity }}</option>
                            <option value="MINOR">Minor (3 - 7 hari)</option>
                            <option value="MEDIUM">Medium (8 - 14 hari)</option>
                            <option value="MAJOR">Major (15 - 30 hari)</option>
                            <option value="CONTINUOUS">Continuous (monitoring)</option>
                        </select>

                        <!-- Pilihan severity -->
                        <div class="grid grid-cols-2 gap-2 text-center text-sm" role="group" aria-labelledby="finding-severity-label">
                            <button
                                v-for="tile in SEVERITY_TILES"
                                :key="tile.value"
                                type="button"
                                @click="form.severity = tile.value"
                                :aria-pressed="form.severity === tile.value"
                                :aria-label="`Severity ${tile.label.toLowerCase()}, ${tile.timeline.toLowerCase()}`"
                                class="cursor-pointer select-none rounded-lg border p-3 text-center transition-colors duration-150"
                                :class="form.severity === tile.value ? tile.active : tile.idle"
                            >
                                <div class="flex items-center justify-center gap-1 text-sm font-medium">
                                    <span>{{ tile.label }}</span>
                                    <span v-if="form.severity === tile.value" class="text-xs" :class="tile.sub">✓</span>
                                </div>
                                <div class="mt-0.5 text-xs tabular-nums" :class="tile.sub">{{ tile.timeline }}</div>
                            </button>
                        </div>
                        <div v-if="form.errors.severity" class="mt-1 text-xs text-red-600">{{ form.errors.severity }}</div>
                    </div>

                    <!-- NBH & NBL -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="finding-nbh" class="mb-2 block text-sm font-medium text-gray-900" title="Nilai barang hilang">NBH</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span>
                                <input
                                    id="finding-nbh"
                                    v-model="form.loss_amount"
                                    type="number"
                                    min="0"
                                    step="any"
                                    placeholder="0"
                                    aria-label="NBH - nilai barang hilang (Rp)"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                            </div>
                            <div v-if="form.errors.loss_amount" class="mt-1 text-xs text-red-600">{{ form.errors.loss_amount }}</div>
                        </div>
                        <div>
                            <label for="finding-nbl" class="mb-2 block text-sm font-medium text-gray-900" title="Nilai barang lebih">NBL</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span>
                                <input
                                    id="finding-nbl"
                                    v-model="form.surplus_amount"
                                    type="number"
                                    min="0"
                                    step="any"
                                    placeholder="0"
                                    aria-label="NBL - nilai barang lebih (Rp)"
                                    class="h-10 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                                />
                            </div>
                            <div v-if="form.errors.surplus_amount" class="mt-1 text-xs text-red-600">{{ form.errors.surplus_amount }}</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                    <Link
                        :href="route('auditor.findings.show', finding.id)"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan…' : 'Simpan perubahan' }}</span>
                    </button>
                </div>
            </section>
        </form>
    </AppLayout>
</template>
