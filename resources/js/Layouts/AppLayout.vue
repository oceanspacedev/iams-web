<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Sistem Audit (IAMS)',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const roles = computed(() => user.value?.roles || []);
const isAuditor = computed(() => roles.value.includes('auditor'));
const isCoordinator = computed(() => roles.value.includes('coordinator'));
const isAdmin = computed(() => roles.value.includes('admin'));
const isChief = computed(() => roles.value.includes('chief'));
const isAsmen = computed(() => roles.value.includes('asmen'));
const isAuditee = computed(() => roles.value.includes('auditee'));
const isManagement = computed(() => isCoordinator.value || isChief.value || isAsmen.value || isAdmin.value);

// Mobile drawer state
const mobileMenuOpen = ref(false);

// Page Loading state for smooth transitions
const isPageLoading = ref(false);

// Desktop Collapsible state (stored in localStorage)
const isCollapsed = ref(false);
const userDropdownOpen = ref(false);
const notificationsOpen = ref(false);
const notifications = computed(() => page.props.notifications || { count: 0, items: [] });

const toggleUserDropdown = () => {
    userDropdownOpen.value = !userDropdownOpen.value;
    if (userDropdownOpen.value) {
        notificationsOpen.value = false;
    }
};

const toggleNotifications = () => {
    notificationsOpen.value = !notificationsOpen.value;
    if (notificationsOpen.value) {
        userDropdownOpen.value = false;
    }
};

// Compute user initials for avatar
const userInitials = computed(() => {
    if (!user.value?.name) return 'U';
    const names = user.value.name.split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[1][0]).toUpperCase();
    }
    return user.value.name.substring(0, 2).toUpperCase();
});

const roleDisplayLabel = computed(() => {
    if (isAdmin.value) return 'Administrator';
    if (isChief.value) return 'Chief Auditor';
    if (isAsmen.value) return 'Asisten Manager';
    if (isCoordinator.value) return 'Koordinator Audit';
    if (isAuditor.value) return 'Auditor';
    if (isAuditee.value) return 'Auditee Toko';
    return 'User';
});

// --- Navigation (satu sumber untuk sidebar desktop & drawer mobile) ---
const icons = {
    home: ['M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    clipboard: ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
    alert: ['M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    document: ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    check: ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    chart: ['M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    users: ['M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    building: ['M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    tag: ['M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
    bell: ['M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
    cog: [
        'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    ],
    badgeCheck: ['M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
    logout: ['M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'],
};

const navSections = computed(() => {
    const sections = [];

    if (isAdmin.value) {
        sections.push(
            { items: [{ label: 'Dashboard', route: 'admin.dashboard', active: ['admin.dashboard'], icon: 'home' }] },
            {
                label: 'Audit management',
                items: [
                    { label: 'Audits', route: 'admin.audits.index', active: ['admin.audits.*'], icon: 'clipboard' },
                    { label: 'Findings', route: 'admin.findings.index', active: ['admin.findings.*'], icon: 'alert' },
                    { label: 'Finding quality', route: 'coordinator.finding-qualities.index', active: ['coordinator.finding-qualities.*'], icon: 'document' },
                    { label: 'Action plan', route: 'admin.action-plans.index', active: ['admin.action-plans.*'], icon: 'check' },
                    { label: 'Reports', route: 'admin.reports.index', active: ['admin.reports.*'], icon: 'chart' },
                ],
            },
            {
                label: 'Master data',
                items: [
                    { label: 'Users', route: 'admin.users.index', active: ['admin.users.*'], icon: 'users' },
                    { label: 'Stores & warehouses', route: 'admin.stores.index', active: ['admin.stores.*'], icon: 'building' },
                    { label: 'Audit categories', route: 'admin.audit-categories.index', active: ['admin.audit-categories.*'], icon: 'tag' },
                    { label: 'SOP / SE', route: 'admin.sops.index', active: ['admin.sops.*'], icon: 'document' },
                    { label: 'Notification schedule', route: 'admin.notification-rules.index', active: ['admin.notification-rules.*'], icon: 'bell' },
                    { label: 'System settings', route: 'admin.settings.index', active: ['admin.settings.*'], icon: 'cog' },
                ],
            },
        );
    } else if (isManagement.value) {
        sections.push(
            { items: [{ label: 'Dashboard', route: 'coordinator.dashboard', active: ['coordinator.dashboard'], icon: 'home' }] },
            {
                label: 'Audit management',
                items: [
                    { label: 'Audits', route: 'coordinator.audits.index', active: ['coordinator.audits.*'], icon: 'clipboard' },
                    { label: 'Findings', route: 'coordinator.findings.index', active: ['coordinator.findings.*'], icon: 'alert' },
                    { label: 'Finding quality', route: 'coordinator.finding-qualities.index', active: ['coordinator.finding-qualities.*'], icon: 'document' },
                    { label: 'Action plan', route: 'coordinator.action-plans.index', active: ['coordinator.action-plans.*'], icon: 'check' },
                    { label: 'Reports', route: 'coordinator.reports.index', active: ['coordinator.reports.*'], icon: 'chart' },
                ],
            },
            {
                label: 'Master data',
                items: [
                    { label: 'Stores & warehouses', route: 'coordinator.stores.index', active: ['coordinator.stores.*'], icon: 'building' },
                ],
            },
        );
    }

    if (isAuditor.value) {
        sections.push({
            label: 'Auditor',
            items: [
                { label: 'Dashboard', route: 'auditor.dashboard', active: ['auditor.dashboard'], icon: 'home' },
                { label: 'My audits', route: 'auditor.audits.index', active: ['auditor.audits.*', 'auditor.findings.*'], icon: 'clipboard' },
                { label: 'Finding quality', route: 'auditor.finding-qualities.index', active: ['auditor.finding-qualities.*'], icon: 'document' },
                { label: 'Reports', route: 'auditor.reports.index', active: ['auditor.reports.*'], icon: 'chart' },
                { label: 'Verification queue', route: 'auditor.verification.index', active: ['auditor.verification.*'], icon: 'badgeCheck' },
            ],
        });
    }

    if (isAuditee.value) {
        sections.push({
            label: 'Store',
            items: [
                { label: 'Dashboard', route: 'auditee.dashboard', active: ['auditee.dashboard'], icon: 'home' },
                { label: 'Store audits', route: 'auditee.audits.index', active: ['auditee.audits.*', 'auditee.findings.*'], icon: 'clipboard' },
            ],
        });
    }

    return sections;
});

const isActive = (item) => item.active.some((pattern) => route().current(pattern));

const navItemClass = (item, collapsed = false) => [
    'flex h-9 items-center gap-3 rounded-lg px-3 text-sm transition-colors duration-150',
    isActive(item)
        ? 'bg-slate-800 text-white ring-1 ring-inset ring-white/5'
        : 'text-gray-300 hover:bg-gray-800 hover:text-white',
    collapsed ? 'justify-center px-0' : '',
];

let removeStartListener = null;
let removeFinishListener = null;

const handleOutsideClick = (e) => {
    const dropdown = document.getElementById('user-profile-dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        userDropdownOpen.value = false;
    }
    const notifDropdown = document.getElementById('notification-dropdown');
    if (notifDropdown && !notifDropdown.contains(e.target)) {
        notificationsOpen.value = false;
    }
};

onMounted(() => {
    const saved = localStorage.getItem('sidebar_collapsed');
    if (saved !== null) {
        isCollapsed.value = saved === 'true';
    }

    window.addEventListener('click', handleOutsideClick);

    removeStartListener = router.on('start', () => {
        isPageLoading.value = true;
    });

    removeFinishListener = router.on('finish', () => {
        isPageLoading.value = false;
    });
});

onUnmounted(() => {
    window.removeEventListener('click', handleOutsideClick);
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
});


const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
    localStorage.setItem('sidebar_collapsed', isCollapsed.value);
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-[#F9FAFB] font-sans text-gray-900 antialiased dark:bg-gray-950">
        <!-- ================= DESKTOP SIDEBAR ================= -->
        <aside
            class="theme-static z-30 hidden h-screen shrink-0 flex-col border-r border-gray-800 bg-[#111827] text-gray-300 md:flex"
            :class="isCollapsed ? 'w-[72px]' : 'w-64'"
        >
            <!-- Brand -->
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-800 bg-[#0B0F17] px-4">
                <div v-if="!isCollapsed" class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
                    <ApplicationLogo size="h-8 w-8" />
                    <div>
                        <div class="text-sm font-semibold leading-5 text-white">IAMS</div>
                        <div class="text-xs leading-4 text-gray-400">Sistem Audit</div>
                    </div>
                </div>
                <div v-else class="mx-auto">
                    <ApplicationLogo size="h-8 w-8" />
                </div>

                <button
                    v-if="!isCollapsed"
                    @click="toggleSidebar"
                    title="Collapse menu"
                    aria-label="Collapse menu"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 hover:bg-gray-800 hover:text-white"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <div v-if="isCollapsed" class="flex justify-center border-b border-gray-800/60 py-2">
                <button
                    @click="toggleSidebar"
                    title="Expand menu"
                    aria-label="Expand menu"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 hover:bg-gray-800 hover:text-white"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4">
                <div v-for="(section, si) in navSections" :key="si" :class="si > 0 ? 'mt-6' : ''">
                    <template v-if="section.label">
                        <div v-if="!isCollapsed" class="mb-2 px-3 text-xs text-gray-500">{{ section.label }}</div>
                        <div v-else class="mx-2 mb-2 border-t border-gray-800"></div>
                    </template>
                    <div class="space-y-0.5">
                        <template v-for="item in section.items" :key="item.route">
                            <Link
                                v-if="!item.mobileOnly"
                                :href="route(item.route)"
                                :title="isCollapsed ? item.label : ''"
                                :class="navItemClass(item, isCollapsed)"
                            >
                                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path v-for="(d, di) in icons[item.icon]" :key="di" stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                </svg>
                                <span v-if="!isCollapsed" class="truncate">{{ item.label }}</span>
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-800 pt-4">
                    <button
                        @click="logout"
                        :title="isCollapsed ? 'Log out' : ''"
                        type="button"
                        class="flex h-9 w-full items-center gap-3 rounded-lg px-3 text-sm text-gray-300 transition-colors duration-150 hover:bg-gray-800 hover:text-white"
                        :class="isCollapsed ? 'justify-center px-0' : ''"
                    >
                        <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="icons.logout[0]" />
                        </svg>
                        <span v-if="!isCollapsed" class="truncate">Log out</span>
                    </button>
                </div>
            </nav>
        </aside>

        <!-- ================= MOBILE DRAWER ================= -->
        <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 flex md:hidden">
            <div @click="mobileMenuOpen = false" class="fixed inset-0 bg-black/60"></div>

            <aside class="theme-static relative z-10 flex h-full w-72 max-w-[80vw] flex-col border-r border-gray-800 bg-[#111827] text-gray-300">
                <div class="flex h-16 items-center justify-between border-b border-gray-800 bg-[#0B0F17] px-4">
                    <div class="flex items-center gap-3">
                        <ApplicationLogo size="h-8 w-8" />
                        <div>
                            <div class="text-sm font-semibold leading-5 text-white">IAMS</div>
                            <div class="text-xs leading-4 text-gray-400">Sistem Audit</div>
                        </div>
                    </div>
                    <button
                        @click="mobileMenuOpen = false"
                        aria-label="Tutup menu"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors duration-150 hover:bg-gray-800 hover:text-white"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-4" @click="mobileMenuOpen = false">
                    <div v-for="(section, si) in navSections" :key="'m' + si" :class="si > 0 ? 'mt-6' : ''">
                        <div v-if="section.label" class="mb-2 px-3 text-xs text-gray-500">{{ section.label }}</div>
                        <div class="space-y-0.5">
                            <Link
                                v-for="item in section.items"
                                :key="'m' + item.route"
                                :href="route(item.route)"
                                :class="navItemClass(item)"
                            >
                                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path v-for="(d, di) in icons[item.icon]" :key="di" stroke-linecap="round" stroke-linejoin="round" :d="d" />
                                </svg>
                                <span class="truncate">{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-800 pt-4">
                        <button
                            @click="logout"
                            type="button"
                            class="flex h-9 w-full items-center gap-3 rounded-lg px-3 text-sm text-gray-300 transition-colors duration-150 hover:bg-gray-800 hover:text-white"
                        >
                            <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="icons.logout[0]" />
                            </svg>
                            <span>Log out</span>
                        </button>
                    </div>
                </nav>
            </aside>
        </div>

        <!-- ================= MAIN CONTENT AREA ================= -->
        <div class="flex h-screen min-w-0 flex-1 flex-col overflow-y-auto">
            <!-- Topbar -->
            <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        @click="mobileMenuOpen = true"
                        aria-label="Buka menu"
                        class="-ml-2 flex h-9 w-9 items-center justify-center rounded-lg text-gray-600 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-900 md:hidden"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <slot name="header">
                        <!-- Di desktop disembunyikan: setiap halaman sudah punya judul sendiri -->
                        <div class="truncate text-base font-medium text-gray-900 md:hidden">{{ title }}</div>
                    </slot>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Notifications -->
                    <div id="notification-dropdown" class="relative">
                        <button
                            @click.stop="toggleNotifications"
                            type="button"
                            title="Notifikasi"
                            aria-label="Notifikasi"
                            class="relative flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-900"
                            :class="notificationsOpen ? 'bg-gray-100 text-gray-900' : ''"
                        >
                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="icons.bell[0]" />
                            </svg>
                            <span
                                v-if="notifications.count > 0"
                                class="absolute right-1 top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-medium leading-none text-white ring-2 ring-white tabular-nums"
                            >
                                {{ notifications.count > 9 ? '9+' : notifications.count }}
                            </span>
                        </button>

                        <div
                            v-if="notificationsOpen"
                            class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                        >
                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">Notifikasi</div>
                                <span
                                    v-if="notifications.count > 0"
                                    class="rounded-md bg-amber-100 px-2 py-0.5 text-xs text-amber-800 tabular-nums"
                                >
                                    {{ notifications.count }} pendaftar baru
                                </span>
                            </div>

                            <div class="max-h-72 divide-y divide-gray-100 overflow-y-auto">
                                <template v-if="notifications.items && notifications.items.length > 0">
                                    <Link
                                        v-for="item in notifications.items"
                                        :key="item.id"
                                        :href="item.url"
                                        @click="notificationsOpen = false"
                                        class="block px-4 py-3 transition-colors duration-150 hover:bg-slate-50"
                                    >
                                        <div class="flex items-start gap-3">
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-medium text-gray-900">{{ item.title }}</div>
                                                <div class="mt-0.5 text-sm leading-5 text-gray-600">{{ item.message }}</div>
                                                <div class="mt-1 text-xs text-gray-400 tabular-nums">{{ item.time }}</div>
                                            </div>
                                        </div>
                                    </Link>
                                </template>
                                <div v-else class="px-4 py-8 text-center text-sm text-gray-500">
                                    Tidak ada notifikasi baru
                                </div>
                            </div>

                            <div v-if="notifications.count > 0" class="border-t border-gray-100 bg-slate-50 p-2">
                                <Link
                                    :href="route('admin.users.index', { status: 'pending' })"
                                    @click="notificationsOpen = false"
                                    class="block rounded-lg py-2 text-center text-sm font-medium text-blue-600 transition-colors duration-150 hover:text-blue-800"
                                >
                                    Tinjau semua pendaftar
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- User menu -->
                    <div id="user-profile-dropdown" class="relative ml-1">
                        <button
                            @click.stop="toggleUserDropdown"
                            type="button"
                            title="Akun pengguna"
                            aria-label="Akun pengguna"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-xs font-medium text-white transition-shadow duration-150 hover:ring-2 hover:ring-slate-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            :class="userDropdownOpen ? 'ring-2 ring-blue-500' : ''"
                        >
                            {{ userInitials }}
                        </button>

                        <div
                            v-if="userDropdownOpen"
                            class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_8px_24px_rgba(0,0,0,0.08)]"
                        >
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p class="truncate text-sm font-medium text-gray-900">{{ user.name }}</p>
                                <p class="mt-0.5 truncate text-sm text-gray-500">{{ user.email }}</p>
                                <span class="mt-2 inline-block rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-800">
                                    {{ roleDisplayLabel }}
                                </span>
                            </div>

                            <div class="p-1">
                                <button
                                    @click="logout"
                                    type="button"
                                    class="flex h-9 w-full items-center gap-3 rounded-lg px-3 text-left text-sm text-red-600 transition-colors duration-150 hover:bg-red-50"
                                >
                                    <svg class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="icons.logout[0]" />
                                    </svg>
                                    Keluar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Navigation progress -->
            <div v-if="isPageLoading" class="sticky top-16 z-30 h-0.5 w-full overflow-hidden bg-blue-100">
                <div class="animate-shimmer h-full w-full bg-blue-600"></div>
            </div>

            <main class="mx-auto w-full max-w-[1440px] flex-1 p-4 sm:p-6 lg:p-8">
                <FlashMessage />
                <slot />
            </main>
        </div>
    </div>
</template>
