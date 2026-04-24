<script setup lang="ts">
import { useInitials } from '@/composables/useInitials';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Archive,
    Boxes,
    ChevronDown,
    ClipboardList,
    Database,
    FireExtinguisherIcon,
    LayoutGrid,
    ListChecks,
    LogOut,
    MessageSquare,
    MonitorSmartphone,
    PieChart,
    Settings,
    ShieldAlert,
    ShieldCheck,
    Star,
    Tag,
    Tags,
    Truck,
    Users,
    Wrench,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        mobileOpen?: boolean;
    }>(),
    {
        mobileOpen: false,
    },
);

const emit = defineEmits<{
    close: [];
}>();

const page = usePage<SharedData>();
const { getInitials } = useInitials();

const user = computed(() => page.props.auth.user);
const primaryRole = computed(() => user.value?.roles?.[0] ?? 'Workspace');
const workspaceName = computed(() => page.props.name || 'CS Sales System');
const dashboardOpen = ref(false);
const masterDataComplainOpen = ref(false);
const otherConfigOpen = ref(false);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (page.props.auth.can.view_users) {
        items.push({ title: 'Management Users', href: '/users', icon: Users });
    }

    return items;
});

const dashboardItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    if (page.props.auth.can.view_dashboard) {
        items.push({ title: 'Overview', href: '/dashboard', icon: LayoutGrid });
        items.push({ title: 'Complaint Analytics', href: '/dashboard/complaints', icon: Activity });
        items.push({ title: 'Performance Monitor', href: '/dashboard/performance', icon: PieChart });
        items.push({ title: 'Agent Interface', href: '/dashboard/agents', icon: Users });
    }
    return items;
});

const masterDataComplainItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (page.props.auth.can.view_brands) {
        items.push({ title: 'Brands', href: '/brands', icon: Tag });
    }

    if (page.props.auth.can.view_platforms) {
        items.push({ title: 'Platforms', href: '/platforms', icon: MonitorSmartphone });
    }

    if (page.props.auth.can.view_sku_codes) {
        items.push({ title: 'SKU Codes', href: '/sku-codes', icon: Boxes });
    }

    if (page.props.auth.can.view_sub_cases) {
        items.push({ title: 'Sub Cases', href: '/sub-cases', icon: ClipboardList });
    }

    if (page.props.auth.can.view_last_steps) {
        items.push({ title: 'Last Steps', href: '/last-steps', icon: ListChecks });
    }

    return items;
});

const otherConfigItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (page.props.auth.can.view_complaint_sources) {
        items.push({ title: 'Complaint Sources', href: '/complaint-sources', icon: Database });
    }


    if (page.props.auth.can.view_order_tracking_erp_statuses) {
        items.push({ title: 'ERP Statuses', href: '/order-tracking-erp-statuses', icon: FireExtinguisherIcon });
    }

    if (page.props.auth.can.view_reason_whitelists) {
        items.push({ title: 'Whitelist Reasons', href: '/reason-whitelists', icon: ShieldAlert });
    }

    if (page.props.auth.can.view_reason_late_responses) {
        items.push({ title: 'Reason Late Response', href: '/reason-late-responses', icon: ShieldAlert });
    }

    if (page.props.auth.can.view_cause_bys) {
        items.push({ title: 'Cause By', href: '/cause-bys', icon: ShieldCheck });
    }

    if (page.props.auth.can.view_order_tracking_data_sources) {
        items.push({ title: 'Tracking Data Sources', href: '/order-tracking-data-sources', icon: Database });
    }

    if (page.props.auth.can.view_order_tracking_rgo_entries) {
        items.push({ title: 'Tracking RGO List', href: '/order-tracking-rgo-entries', icon: Database });
    }

    if (page.props.auth.can.view_jet_track_entries) {
        items.push({ title: 'Jet Track', href: '/jet-track-entries', icon: Database });
    }

    if (page.props.auth.can.view_oos_reasons) {
        items.push({ title: 'OOS Reasons', href: '/oos-reasons', icon: Archive });
    }

    if (page.props.auth.can.view_oos_solutions) {
        items.push({ title: 'OOS Solutions', href: '/oos-solutions', icon: Wrench });
    }

    return items;
});

const hasDashboardAccess = computed(() => dashboardItems.value.length > 0);
const isDashboardActive = computed(() => dashboardItems.value.some((item) => isActive(item.href)));

const hasMasterDataComplainAccess = computed(() => masterDataComplainItems.value.length > 0);
const isMasterDataComplainActive = computed(() => masterDataComplainItems.value.some((item) => isActive(item.href)));

const hasOtherConfigAccess = computed(() => otherConfigItems.value.length > 0);
const isOtherConfigActive = computed(() => otherConfigItems.value.some((item) => isActive(item.href)));

const isActive = (href: string) => {
    return page.url === href || page.url.startsWith(`${href}/`);
};

const isExactActive = (href: string) => page.url === href;

watch(
    () => page.url,
    () => {
        if (isDashboardActive.value) {
            dashboardOpen.value = true;
        }
        if (isMasterDataComplainActive.value) {
            masterDataComplainOpen.value = true;
        }
        if (isOtherConfigActive.value) {
            otherConfigOpen.value = true;
        }
    },
    { immediate: true },
);

const toggleDashboard = () => {
    dashboardOpen.value = !dashboardOpen.value;
};

const toggleMasterDataComplain = () => {
    masterDataComplainOpen.value = !masterDataComplainOpen.value;
};

const toggleOtherConfig = () => {
    otherConfigOpen.value = !otherConfigOpen.value;
};

const closeSidebar = () => emit('close');
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[280px] max-w-[88vw] -translate-x-full flex-col border-r border-[var(--app-border)] bg-white transition-transform duration-300 ease-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
        :class="props.mobileOpen ? 'translate-x-0 shadow-2xl shadow-slate-900/15' : ''"
    >
        <div class="flex items-center justify-between border-b border-[var(--app-border)] px-4 py-3.5 lg:justify-start">
            <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-2.5" @click="closeSidebar">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--app-primary)] text-sm font-black text-white shadow-lg"
                >
                    {{ getInitials(workspaceName) }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-[15px] font-black tracking-tight text-[var(--app-ink)]">{{ workspaceName }}</p>
                    <span
                        class="mt-0.5 inline-flex rounded-full bg-[var(--app-primary-soft)] px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-[var(--app-primary)]"
                    >
                        {{ primaryRole }}
                    </span>
                </div>
            </Link>

            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-[var(--app-border)] text-slate-500 transition hover:border-[var(--app-primary)] hover:text-[var(--app-primary)] lg:hidden"
                @click="closeSidebar"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <div class="border-b border-[var(--app-border)] px-4 py-3.5">
            <div class="rounded-xl border border-[var(--app-border)] bg-[var(--app-primary-soft)] px-3 py-3">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-white/80 bg-white text-xs font-black text-[var(--app-primary)]"
                    >
                        {{ getInitials(user?.name || 'Guest') }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-black text-[var(--app-ink)]">{{ user?.name || 'Guest' }}</p>
                        <p class="truncate text-[11px] font-bold text-slate-400 capitalize">{{ primaryRole }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            <p class="px-3 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Workspace</p>

            <nav class="mt-3 space-y-1.5">
                <!-- Dashboard Submenu -->
                <div v-if="hasDashboardAccess" class="space-y-1.5">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-[18px] px-4 py-3 text-left text-sm font-semibold transition"
                        :class="
                            isDashboardActive
                                ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary)]'
                                : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                        "
                        @click="toggleDashboard"
                    >
                        <LayoutGrid class="h-5 w-5 shrink-0" />
                        <span class="flex-1 truncate">Dashboard</span>
                        <ChevronDown class="h-4 w-4 shrink-0 transition" :class="dashboardOpen ? 'rotate-180' : ''" />
                    </button>

                    <div v-if="dashboardOpen" class="space-y-1 pl-4">
                        <Link
                            v-for="item in dashboardItems"
                            :key="item.title"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-[16px] px-4 py-2.5 text-sm font-medium transition"
                            :class="
                                isExactActive(item.href)
                                    ? 'bg-[var(--app-primary)] text-white shadow-[0_12px_22px_rgba(53,103,232,0.22)]'
                                    : 'text-slate-500 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                            "
                            @click="closeSidebar"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </div>
                </div>

                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="group flex items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold transition"
                    :class="
                        isActive(item.href)
                            ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]'
                            : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                    "
                    @click="closeSidebar"
                >
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                    <span class="truncate">{{ item.title }}</span>
                </Link>

                <div v-if="hasMasterDataComplainAccess" class="space-y-1.5">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-[18px] px-4 py-3 text-left text-sm font-semibold transition"
                        :class="
                            isMasterDataComplainActive
                                ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary)]'
                                : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                        "
                        @click="toggleMasterDataComplain"
                    >
                        <Tags class="h-5 w-5 shrink-0" />
                        <span class="flex-1 truncate">Master Data</span>
                        <ChevronDown class="h-4 w-4 shrink-0 transition" :class="masterDataComplainOpen ? 'rotate-180' : ''" />
                    </button>

                    <div v-if="masterDataComplainOpen" class="space-y-1 pl-4">
                        <Link
                            v-for="item in masterDataComplainItems"
                            :key="item.title"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-[16px] px-4 py-2.5 text-sm font-medium transition"
                            :class="
                                isActive(item.href)
                                    ? 'bg-[var(--app-primary)] text-white shadow-[0_12px_22px_rgba(53,103,232,0.22)]'
                                    : 'text-slate-500 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                            "
                            @click="closeSidebar"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </div>
                </div>

                <div v-if="hasOtherConfigAccess" class="space-y-1.5">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-[18px] px-4 py-3 text-left text-sm font-semibold transition"
                        :class="
                            isOtherConfigActive
                                ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary)]'
                                : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                        "
                        @click="toggleOtherConfig"
                    >
                        <Wrench class="h-5 w-5 shrink-0" />
                        <span class="flex-1 truncate">Other Config</span>
                        <ChevronDown class="h-4 w-4 shrink-0 transition" :class="otherConfigOpen ? 'rotate-180' : ''" />
                    </button>

                    <div v-if="otherConfigOpen" class="space-y-1 pl-4">
                        <Link
                            v-for="item in otherConfigItems"
                            :key="item.title"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-[16px] px-4 py-2.5 text-sm font-medium transition"
                            :class="
                                isActive(item.href)
                                    ? 'bg-[var(--app-primary)] text-white shadow-[0_12px_22px_rgba(53,103,232,0.22)]'
                                    : 'text-slate-500 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                            "
                            @click="closeSidebar"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </div>
                </div>

                <Link
                    v-if="page.props.auth.can.access_complaints"
                    href="/complaints"
                    class="group flex items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold transition"
                    :class="
                        isActive('/complaints')
                            ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]'
                            : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                    "
                    @click="closeSidebar"
                >
                    <MessageSquare class="h-5 w-5 shrink-0" />
                    <span class="truncate">Complaints</span>
                </Link>

                <Link
                    v-if="page.props.auth.can.access_bad_reviews"
                    href="/bad-reviews"
                    class="group flex items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold transition"
                    :class="
                        isActive('/bad-reviews')
                            ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]'
                            : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                    "
                    @click="closeSidebar"
                >
                    <Star class="h-5 w-5 shrink-0" />
                    <span class="truncate">Bad Reviews</span>
                </Link>

                <Link
                    v-if="page.props.auth.can.access_order_trackings"
                    href="/order-trackings"
                    class="group flex items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold transition"
                    :class="
                        isActive('/order-trackings')
                            ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]'
                            : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                    "
                    @click="closeSidebar"
                >
                    <Truck class="h-5 w-5 shrink-0" />
                    <span class="truncate">Order Tracking</span>
                </Link>

                <Link
                    v-if="page.props.auth.can.access_oos"
                    href="/oos"
                    class="group flex items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold transition"
                    :class="
                        isActive('/oos')
                            ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]'
                            : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                    "
                    @click="closeSidebar"
                >
                    <Archive class="h-5 w-5 shrink-0" />
                    <span class="truncate">OOS Data</span>
                </Link>

                <Link
                    href="/settings/profile"
                    class="group flex items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold transition"
                    :class="
                        isActive('/settings/profile') || page.url.startsWith('/settings/')
                            ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]'
                            : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                    "
                    @click="closeSidebar"
                >
                    <Settings class="h-5 w-5 shrink-0" />
                    <span class="truncate">Settings</span>
                </Link>
            </nav>
        </div>

        <div class="border-t border-[var(--app-border)] px-4 py-3">
            <Link
                method="post"
                as="button"
                :href="route('logout')"
                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-bold text-rose-500 transition hover:bg-rose-50"
            >
                <LogOut class="h-4 w-4" />
                <span>Sign Out</span>
            </Link>
        </div>
    </aside>
</template>
