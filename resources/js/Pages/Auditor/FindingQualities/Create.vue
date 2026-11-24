<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { qualityCategoryColor } from '@/Utils/qualityCategoryColors';

const props = defineProps({
    findings: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    preselectedFinding: {
        type: [String, Number],
        default: null,
    },
    preselectedAudit: {
        type: [String, Number],
        default: null,
    },
    routePrefix: {
        type: String,
        default: '',
    },
});

const page = usePage();
const currentPrefix = computed(() => {
    if (props.routePrefix) return props.routePrefix;
    if (typeof route === 'function' && route().current('coordinator.*')) return 'coordinator.';
    if (typeof route === 'function' && route().current('auditor.*')) return 'auditor.';
    return (page.props.auth?.user?.roles || []).includes('auditor') ? 'auditor.' : 'coordinator.';
});

const form = useForm({
    finding_id: props.preselectedFinding || '',
    quality_categories: ['impact_50m'],
    title: '',
    impact_amount: '',
    root_cause: '',
    systemic_issue: '',
    recommendation: '',
    auditor_notes: '',
});

const toggleCategory = (key) => {
    const index = form.quality_categories.indexOf(key);
    if (index > -1) {
        form.quality_categories.splice(index, 1);
    } else {
        form.quality_categories.push(key);
    }
};

const selectAllCategories = () => {
    const allKeys = Object.keys(props.categories);
    if (form.quality_categories.length === allKeys.length) {
        form.quality_categories = [];
    } else {
        form.quality_categories = [...allKeys];
    }
};

// Auto fill loss amount when finding is selected
watch(
    () => form.finding_id,
    (selectedId) => {
        const found = props.findings.find(f => f.id === Number(selectedId));
        if (found) {
            if (!form.title) {
                form.title = `Temuan Kritis: ${found.finding.substring(0, 70)}...`;
            }
            if (found.loss_amount && !form.impact_amount) {
                form.impact_amount = found.loss_amount;
            }
        }
    }
);

const categoryColor = qualityCategoryColor;

const selectedFindingObj = computed(() => {
    return props.findings.find(f => f.id === Number(form.finding_id));
});

const submit = () => {
    form.post(route(`${currentPrefix.value}finding-qualities.store`));
};
</script>

<template>
    <AppLayout title="Buat laporan kualitas temuan">
        <Head title="Buat laporan kualitas temuan" />

        <div class="mb-6">
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                <Link :href="route(`${currentPrefix}finding-qualities.index`)" class="transition-colors duration-150 hover:text-blue-600">Kualitas temuan</Link>
                <span>/</span>
                <span class="font-medium text-gray-900">Baru</span>
            </div>
            <h1 class="text-2xl font-semibold leading-8 text-gray-900">Buat laporan</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-4xl space-y-6 text-sm">
            <!-- Temuan referensi -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Temuan <span class="text-red-600">*</span></h2>
                </div>
                <div class="p-5">
                    <select
                        v-model="form.finding_id"
                        required
                        aria-label="Temuan audit"
                        class="h-10 w-full rounded-lg border-gray-300 bg-white pl-3 pr-9 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                    >
                        <option value="" disabled>Pilih temuan</option>
                        <option v-for="f in findings" :key="f.id" :value="f.id">
                            {{ f.display_label }}
                        </option>
                    </select>
                    <div v-if="form.errors.finding_id" class="mt-1 text-xs text-red-600">{{ form.errors.finding_id }}</div>

                    <!-- Highlight box for selected finding -->
                    <div v-if="selectedFindingObj" class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex flex-wrap items-center gap-2 font-medium text-gray-900">
                            <span class="tabular-nums">{{ selectedFindingObj.audit_number }}</span>
                            <span>• {{ selectedFindingObj.store_name }}</span>
                            <span class="rounded-md bg-slate-200 px-2 py-0.5 text-xs font-normal">{{ selectedFindingObj.category }}</span>
                        </div>
                        <p class="mt-1 font-normal text-gray-700">"{{ selectedFindingObj.finding }}"</p>
                    </div>
                </div>
            </section>

            <!-- Kategori (multi-pilih) -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Kategori <span class="text-red-600">*</span></h2>
                    <button
                        type="button"
                        @click="selectAllCategories"
                        class="shrink-0 cursor-pointer text-sm font-medium text-blue-600 transition-colors duration-150 hover:text-blue-800"
                    >
                        {{ form.quality_categories.length === Object.keys(categories).length ? 'Batal pilih semua' : 'Pilih semua' }}
                    </button>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div
                            v-for="(cat, key) in categories"
                            :key="key"
                            @click="toggleCategory(key)"
                            class="flex cursor-pointer select-none items-start gap-3 rounded-xl border p-4 transition-colors duration-150"
                            :class="form.quality_categories.includes(key) ? categoryColor(key).selected : categoryColor(key).card"
                        >
                            <input
                                type="checkbox"
                                :value="key"
                                :checked="form.quality_categories.includes(key)"
                                @click.stop="toggleCategory(key)"
                                class="mt-0.5 h-4 w-4 cursor-pointer rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-600"
                                :class="categoryColor(key).checkbox"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium" :class="categoryColor(key).label">
                                    {{ cat.label }}
                                </div>
                                <p class="mt-1 line-clamp-2 text-xs leading-relaxed" :class="categoryColor(key).desc" :title="cat.description">
                                    {{ cat.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.quality_categories" class="mt-1 text-xs text-red-600">{{ form.errors.quality_categories }}</div>
                </div>
            </section>

            <!-- Isi laporan -->
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex h-14 items-center justify-between gap-4 border-b border-gray-200 px-5">
                    <h2 class="text-base font-semibold text-gray-900">Laporan</h2>
                </div>
                <div class="space-y-4 p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Judul <span class="text-red-600">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            class="h-10 w-full rounded-lg border-gray-300 bg-white px-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        />
                        <div v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Nilai dampak
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span>
                            <input
                                v-model="form.impact_amount"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="h-10 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm tabular-nums focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                            />
                        </div>
                        <div v-if="form.errors.impact_amount" class="mt-1 text-xs text-red-600">{{ form.errors.impact_amount }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Root cause <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            v-model="form.root_cause"
                            rows="4"
                            required
                            class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        ></textarea>
                        <div v-if="form.errors.root_cause" class="mt-1 text-xs text-red-600">{{ form.errors.root_cause }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Kelemahan sistemik
                        </label>
                        <textarea
                            v-model="form.systemic_issue"
                            rows="4"
                            class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        ></textarea>
                        <div v-if="form.errors.systemic_issue" class="mt-1 text-xs text-red-600">{{ form.errors.systemic_issue }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Rekomendasi <span class="text-red-600">*</span>
                        </label>
                        <textarea
                            v-model="form.recommendation"
                            rows="3"
                            required
                            class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        ></textarea>
                        <div v-if="form.errors.recommendation" class="mt-1 text-xs text-red-600">{{ form.errors.recommendation }}</div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900">
                            Catatan auditor
                        </label>
                        <textarea
                            v-model="form.auditor_notes"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 shadow-sm"
                        ></textarea>
                    </div>
                </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-4">
                    <Link
                        :href="route(`${currentPrefix}finding-qualities.index`)"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 shadow-sm"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-700 disabled:opacity-50 shadow-sm"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan…' : 'Simpan laporan' }}</span>
                    </button>
                </div>
            </section>
        </form>
    </AppLayout>
</template>
