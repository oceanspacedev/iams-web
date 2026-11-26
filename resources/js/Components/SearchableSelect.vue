<script setup>
// Dropdown dengan kolom pencarian, pengganti <select> untuk daftar panjang (toko, SOP, temuan, auditor).
// options: [{ value, label, hint? }]. Panel di-teleport ke body supaya tidak terpotong kartu overflow-hidden.
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Pilih' },
    searchPlaceholder: { type: String, default: 'Cari' },
    size: { type: String, default: 'md' }, // md = h-10 (form), sm = h-9 (filter)
    disabled: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
    id: { type: String, default: null },
    ariaLabel: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const button = ref(null);
const panel = ref(null);
const search = ref(null);
const list = ref(null);
const panelStyle = ref({});

const same = (a, b) => String(a ?? '') === String(b ?? '');

const selected = computed(() => props.options.find((o) => same(o.value, props.modelValue)) || null);

const filtered = computed(() => {
    const q = query.value.toLowerCase().trim();
    if (!q) return props.options;
    const words = q.split(/\s+/);
    return props.options.filter((o) => {
        const text = `${o.label} ${o.hint || ''}`.toLowerCase();
        return words.every((w) => text.includes(w));
    });
});

watch(filtered, () => {
    activeIndex.value = 0;
});

const place = () => {
    if (!button.value) return;
    const rect = button.value.getBoundingClientRect();
    const width = Math.max(rect.width, 240);
    const left = Math.min(rect.left, window.innerWidth - width - 8);
    const spaceBelow = window.innerHeight - rect.bottom;
    const style = { left: `${Math.max(8, left)}px`, width: `${width}px` };
    if (spaceBelow < 320 && rect.top > spaceBelow) {
        style.bottom = `${window.innerHeight - rect.top + 4}px`;
    } else {
        style.top = `${rect.bottom + 4}px`;
    }
    panelStyle.value = style;
};

const onOutside = (e) => {
    if (button.value?.contains(e.target) || panel.value?.contains(e.target)) return;
    close(false);
};

const onViewport = (e) => {
    // Scroll di dalam daftar pilihan tidak memindahkan panel
    if (e?.type === 'scroll' && panel.value?.contains(e.target)) return;
    place();
};

const openPanel = async () => {
    if (props.disabled || open.value) return;
    query.value = '';
    open.value = true;
    place();
    const idx = filtered.value.findIndex((o) => same(o.value, props.modelValue));
    activeIndex.value = idx >= 0 ? idx : 0;
    document.addEventListener('mousedown', onOutside, true);
    window.addEventListener('scroll', onViewport, true);
    window.addEventListener('resize', onViewport);
    await nextTick();
    search.value?.focus();
    scrollActiveIntoView();
};

const close = (refocus = true) => {
    if (!open.value) return;
    open.value = false;
    document.removeEventListener('mousedown', onOutside, true);
    window.removeEventListener('scroll', onViewport, true);
    window.removeEventListener('resize', onViewport);
    if (refocus) button.value?.focus();
};

const choose = (option) => {
    if (!option || option.disabled) return;
    emit('update:modelValue', option.value);
    emit('change', option.value);
    close();
};

const scrollActiveIntoView = () => {
    nextTick(() => {
        const el = list.value?.querySelector(`[data-index="${activeIndex.value}"]`);
        el?.scrollIntoView({ block: 'nearest' });
    });
};

const move = (step) => {
    const n = filtered.value.length;
    if (!n) return;
    activeIndex.value = (activeIndex.value + step + n) % n;
    scrollActiveIntoView();
};

const onSearchKey = (e) => {
    if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
    else if (e.key === 'Enter') { e.preventDefault(); choose(filtered.value[activeIndex.value]); }
    else if (e.key === 'Escape') { e.preventDefault(); close(); }
    else if (e.key === 'Tab') { close(false); }
};

const onButtonKey = (e) => {
    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
        e.preventDefault();
        openPanel();
    }
};

onBeforeUnmount(() => close(false));

const sizeClass = computed(() => (props.size === 'sm' ? 'h-9' : 'h-10'));
</script>

<template>
    <div class="relative" :class="$attrs.class">
        <button
            :id="id"
            ref="button"
            type="button"
            :disabled="disabled"
            :aria-label="ariaLabel"
            aria-haspopup="listbox"
            :aria-expanded="open"
            class="flex w-full items-center rounded-lg border bg-white pl-3 pr-9 text-left text-sm shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400"
            :class="[
                sizeClass,
                invalid
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                    : open
                        ? 'border-blue-600 ring-2 ring-blue-600/20'
                        : 'border-gray-300 focus:border-blue-600 focus:ring-blue-600/20',
            ]"
            @click="open ? close() : openPanel()"
            @keydown="onButtonKey"
        >
            <span class="truncate" :class="selected ? 'text-gray-900' : 'text-gray-500'">
                {{ selected ? selected.label : placeholder }}
            </span>
            <svg class="pointer-events-none absolute right-3 h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m7 10 5 5 5-5" />
            </svg>
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                ref="panel"
                class="fixed z-[70] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                :style="panelStyle"
            >
                <div class="relative border-b border-gray-200 p-2">
                    <svg class="pointer-events-none absolute left-5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input
                        ref="search"
                        v-model="query"
                        type="text"
                        :placeholder="searchPlaceholder"
                        aria-label="Cari pilihan"
                        autocomplete="off"
                        class="h-9 w-full rounded-lg border-gray-300 bg-white pl-9 pr-3 text-sm shadow-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20"
                        @keydown="onSearchKey"
                    />
                </div>
                <ul ref="list" role="listbox" class="max-h-64 overflow-y-auto py-1">
                    <li v-if="filtered.length === 0" class="px-3 py-6 text-center text-sm text-gray-400">Tidak ada hasil</li>
                    <li
                        v-for="(option, i) in filtered"
                        :key="String(option.value)"
                        :data-index="i"
                        role="option"
                        :aria-selected="same(option.value, modelValue)"
                        class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm"
                        :class="[
                            i === activeIndex ? 'bg-gray-50' : '',
                            option.disabled ? 'cursor-not-allowed text-gray-400' : 'text-gray-900',
                        ]"
                        @mouseenter="activeIndex = i"
                        @mousedown.prevent="choose(option)"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block truncate" :class="same(option.value, modelValue) ? 'font-medium' : ''">{{ option.label }}</span>
                            <span v-if="option.hint" class="block truncate text-xs text-gray-500">{{ option.hint }}</span>
                        </span>
                        <svg v-if="same(option.value, modelValue)" class="h-4 w-4 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7" /></svg>
                    </li>
                </ul>
            </div>
        </Teleport>
    </div>
</template>
