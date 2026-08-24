<script setup>
defineProps({
    type: {
        type: String,
        default: 'dashboard', // 'dashboard', 'table', 'card'
    },
});
</script>

<template>
    <div class="w-full space-y-6 animate-pulse">
        <!-- Dashboard Skeleton -->
        <template v-if="type === 'dashboard'">
            <!-- Stat Cards Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="i in 4" :key="i" class="p-5 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-3">
                    <div class="h-3.5 w-24 rounded animate-shimmer"></div>
                    <div class="h-8 w-32 rounded animate-shimmer"></div>
                    <div class="h-3 w-20 rounded animate-shimmer"></div>
                </div>
            </div>

            <!-- Content Area / Tables Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 p-6 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-4">
                    <div class="h-4 w-40 rounded animate-shimmer"></div>
                    <div class="space-y-3 pt-2">
                        <div v-for="i in 5" :key="i" class="h-10 w-full rounded animate-shimmer"></div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-4">
                    <div class="h-4 w-32 rounded animate-shimmer"></div>
                    <div class="space-y-3 pt-2">
                        <div v-for="i in 4" :key="i" class="h-12 w-full rounded animate-shimmer"></div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Table Skeleton -->
        <template v-else-if="type === 'table'">
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex justify-between items-center">
                    <div class="h-4 w-48 rounded animate-shimmer"></div>
                    <div class="h-8 w-32 rounded animate-shimmer"></div>
                </div>
                <div class="p-4 space-y-3">
                    <div v-for="i in 6" :key="i" class="h-10 w-full rounded animate-shimmer"></div>
                </div>
            </div>
        </template>

        <!-- Card Skeleton -->
        <template v-else>
            <div class="p-6 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-4">
                <div class="h-5 w-48 rounded animate-shimmer"></div>
                <div class="h-3.5 w-full rounded animate-shimmer"></div>
                <div class="h-3.5 w-3/4 rounded animate-shimmer"></div>
                <div class="h-24 w-full rounded animate-shimmer"></div>
            </div>
        </template>
    </div>
</template>
