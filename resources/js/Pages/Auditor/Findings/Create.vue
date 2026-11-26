<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    audit: {
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
});

const form = useForm({
    category_id: props.audit.category_id || (props.categories.length > 0 ? props.categories[0].id : ''),
    sop_id: '',
    finding: '',
    loss_amount: '',
    surplus_amount: '',
    auditor_opinion: '',
    recommendation: '',
    severity: 'MINOR',
});

const submit = () => {
    form.post(route('auditor.findings.store', props.audit.id));
};

const sopSelectOptions = computed(() => [
    { value: '', label: 'Tanpa SOP / SE' },
    ...(props.sops || []).map((sop) => ({ value: sop.id, label: `${sop.code} - ${sop.title}` })),
]);
</script>

<template>
    <AppLayout title="Tambah temuan">
        <Head :title="`Tambah temuan - ${audit.audit_number}`" />

        <!-- Header -->
        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route('auditor.audits.index')" class="transition-colors duration-150 hover:text-blue-600">Audit saya</Link>
                <span>/</span>
                <Link :href="route('auditor.audits.show', audit.id)" class="tabular-nums transition-colors duration-150 hover:text-blue-600">{{ audit.audit_number }}</Link>
                <span>/</span>
                <span class="font-medium text-gray-900">Temuan baru</span>
            </div>
            <div class="flex flex-wrap items-baseline gap-x-3">
                <h1 class="text-2xl font-semibold leading-8 text-gray-900">Tambah temuan</h1>
                <span class="text-2xl leading-8 text-gray-500">{{ audit.store.name }}</span>
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
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</div>
                    </div>

                    <div>
                        <label for="finding-sop" class="mb-2 block text-sm font-medium text-gray-900">
                            SOP / SE
                        </label>
                        <SearchableSelect
                            id="finding-sop"
                            v-model="form.sop_id"
                            :options="sopSelectOptions"
                            placeholder="Pilih SOP / SE"
                            :invalid="!!form.errors.sop_id"
                        />
                        <div v-if="form.errors.sop_id" class="mt-1 text-xs text-red-600">{{ form.errors.sop_id }}</div>
                    </div>

                    <div>
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
                            <option value="MINOR">Minor (3 - 7 hari)</option>
                            <option value="MEDIUM">Medium (8 - 14 hari)</option>
                            <option value="MAJOR">Major (15 - 30 hari)</option>
                            <option value="CONTINUOUS">Continuous (monitoring)</option>
                        </select>

                        <!-- Pilihan severity -->
                        <div class="grid grid-cols-2 gap-2 text-center text-sm" role="group" aria-labelledby="finding-severity-label">
                            <button
                                type="button"
                                @click="form.severity = 'MINOR'"
                                aria-label="Severity minor, 3 - 7 hari"
                                :aria-pressed="form.severity === 'MINOR'"
                                class="cursor-pointer select-none rounded-lg border p-3 text-center transition-colors duration-150"
                                :class="form.severity === 'MINOR'
                                    ? 'bg-emerald-100 border-emerald-500 ring-2 ring-emerald-400 text-emerald-950 font-medium'
                                    : 'bg-emerald-50/70 border-emerald-200 text-emerald-800 hover:bg-emerald-100/60 hover:border-emerald-300'"
                            >
                                <div class="flex items-center justify-center gap-1 text-sm font-medium">
                                    <span>Minor</span>
                                    <span v-if="form.severity === 'MINOR'" class="text-xs text-emerald-700">✓</span>
                                </div>
                                <div class="mt-0.5 text-xs text-emerald-700/90 tabular-nums">3 - 7 hari</div>
                            </button>

                            <button
                                type="button"
                                @click="form.severity = 'MEDIUM'"
                                aria-label="Severity medium, 8 - 14 hari"
                                :aria-pressed="form.severity === 'MEDIUM'"
                                class="cursor-pointer select-none rounded-lg border p-3 text-center transition-colors duration-150"
                                :class="form.severity === 'MEDIUM'
                                    ? 'bg-amber-100 border-amber-500 ring-2 ring-amber-400 text-amber-950 font-medium'
                                    : 'bg-amber-50/70 border-amber-200 text-amber-800 hover:bg-amber-100/60 hover:border-amber-300'"
                            >
                                <div class="flex items-center justify-center gap-1 text-sm font-medium">
                                    <span>Medium</span>
                                    <span v-if="form.severity === 'MEDIUM'" class="text-xs text-amber-700">✓</span>
                                </div>
                                <div class="mt-0.5 text-xs text-amber-700/90 tabular-nums">8 - 14 hari</div>
                            </button>

                            <button
                                type="button"
                                @click="form.severity = 'MAJOR'"
                                aria-label="Severity major, 15 - 30 hari"
                                :aria-pressed="form.severity === 'MAJOR'"
                                class="cursor-pointer select-none rounded-lg border p-3 text-center transition-colors duration-150"
                                :class="form.severity === 'MAJOR'
                                    ? 'bg-rose-100 border-rose-500 ring-2 ring-rose-400 text-rose-950 font-medium'
                                    : 'bg-rose-50/70 border-rose-200 text-rose-800 hover:bg-rose-100/60 hover:border-rose-300'"
                            >
                                <div class="flex items-center justify-center gap-1 text-sm font-medium">
                                    <span>Major</span>
                                    <span v-if="form.severity === 'MAJOR'" class="text-xs text-rose-700">✓</span>
                                </div>
                                <div class="mt-0.5 text-xs text-rose-700/90 tabular-nums">15 - 30 hari</div>
                            </button>

                            <button
                                type="button"
                                @click="form.severity = 'CONTINUOUS'"
                                aria-label="Severity continuous, monitoring"
                                :aria-pressed="form.severity === 'CONTINUOUS'"
                                class="cursor-pointer select-none rounded-lg border p-3 text-center transition-colors duration-150"
                                :class="form.severity === 'CONTINUOUS'
                                    ? 'bg-purple-100 border-purple-500 ring-2 ring-purple-400 text-purple-950 font-medium'
                                    : 'bg-purple-50/70 border-purple-200 text-purple-800 hover:bg-purple-100/60 hover:border-purple-300'"
                            >
                                <div class="flex items-center justify-center gap-1 text-sm font-medium">
                                    <span>Continuous</span>
                                    <span v-if="form.severity === 'CONTINUOUS'" class="text-xs text-purple-700">✓</span>
                                </div>
                                <div class="mt-0.5 text-xs text-purple-700/90">Monitoring</div>
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
                        :href="route('auditor.audits.show', audit.id)"
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
                        <span>{{ form.processing ? 'Menyimpan…' : 'Simpan temuan' }}</span>
                    </button>
                </div>
            </section>
        </form>
    </AppLayout>
</template>
