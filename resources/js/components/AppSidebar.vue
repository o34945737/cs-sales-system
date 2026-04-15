<script setup lang="ts">
import { useInitials } from '@/composables/useInitials';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Archive,
    Boxes,
    ChevronDown,
    ClipboardList,
    Database,
    LayoutGrid,
    ListChecks,
    LogOut,
    MessageSquare,
    MonitorSmartphone,
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
const masterDataOpen = ref(false);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (page.props.auth.can.view_dashboard) {
        items.push({ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid });
    }

    return items;
});

const masterDataItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (page.props.auth.can.view_brands) {
        items.push({ title: 'Brands', href: '/brands', icon: Tag });
    }

    if (page.props.auth.can.view_platforms) {
        items.push({ title: 'Platforms', href: '/platforms', icon: MonitorSmartphone });
    }

    if (page.props.auth.can.view_complaint_sources) {
        items.push({ title: 'Complaint Sources', href: '/complaint-sources', icon: Database });
    }

    if (page.props.auth.can.view_complaint_powers) {
        items.push({ title: 'Complaint Powers', href: '/complaint-powers', icon: ShieldAlert });
    }

    if (page.props.auth.can.view_complaint_step_statuses) {
        items.push({ title: 'Step Statuses', href: '/complaint-step-statuses', icon: ClipboardList });
    }

    if (page.props.auth.can.view_sku_codes) {
        items.push({ title: 'SKU Codes', href: '/sku-codes', icon: Boxes });
    }

    if (page.props.auth.can.view_logistics) {
        items.push({ title: 'Logistics', href: '/logistics', icon: Truck });
    }

    if (page.props.auth.can.view_sub_cases) {
        items.push({ title: 'Sub Cases', href: '/sub-cases', icon: ClipboardList });
    }

    if (page.props.auth.can.view_last_steps) {
        items.push({ title: 'Last Steps', href: '/last-steps', icon: ListChecks });
    }

    if (page.props.auth.can.view_reason_whitelists) {
        items.push({ title: 'Whitelist Reasons', href: '/reason-whitelists', icon: ShieldAlert });
    }

    if (page.props.auth.can.view_reason_late_responses) {
        items.push({ title: 'Reason Late Responses', href: '/reason-late-responses', icon: ShieldAlert });
    }

    if (page.props.auth.can.view_order_tracking_data_sources) {
        items.push({ title: 'Tracking Data Sources', href: '/order-tracking-data-sources', icon: Database });
    }

    if (page.props.auth.can.view_oos_reasons) {
        items.push({ title: 'OOS Reasons', href: '/oos-reasons', icon: Archive });
    }

    if (page.props.auth.can.view_oos_solutions) {
        items.push({ title: 'OOS Solutions', href: '/oos-solutions', icon: Wrench });
    }

    if (page.props.auth.can.view_cause_bys) {
        items.push({ title: 'Cause By', href: '/cause-bys', icon: ShieldCheck });
    }

    if (page.props.auth.can.view_part_of_bads) {
        items.push({ title: 'Part Of Bad', href: '/part-of-bads', icon: Archive });
    }

    return items;
});

const hasMasterDataAccess = computed(() => masterDataItems.value.length > 0);
const isMasterDataActive = computed(() => masterDataItems.value.some((item) => isActive(item.href)));

const isActive = (href: string) => {
    if (href === '/dashboard') {
        return page.url === href;
    }

    return page.url === href || page.url.startsWith(`${href}/`);
};

watch(
    () => page.url,
    () => {
        if (isMasterDataActive.value) {
            masterDataOpen.value = true;
        }
    },
    { immediate: true },
);

const toggleMasterData = () => {
    masterDataOpen.value = !masterDataOpen.value;
};

const closeSidebar = () => emit('close');
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[280px] max-w-[88vw] -translate-x-full flex-col border-r border-[var(--app-border)] bg-white transition-transform duration-300 ease-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
        :class="props.mobileOpen ? 'translate-x-0 shadow-2xl shadow-slate-900/15' : ''"
    >
        <div class="flex items-center justify-between border-b border-[var(--app-border)] px-5 py-5 lg:justify-start">
            <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3" @click="closeSidebar">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-base font-extrabold text-white shadow-[0_12px_20px_rgba(53,103,232,0.28)]"
                >
                    {{ getInitials(workspaceName) }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-extrabold text-[var(--app-ink)]">{{ workspaceName }}</p>
                    <span
                        class="mt-1 inline-flex rounded-full bg-[var(--app-primary-soft)] px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-[var(--app-primary)]"
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

        <div class="border-b border-[var(--app-border)] px-4 py-5">
            <div class="rounded-[22px] border border-[var(--app-border)] bg-[var(--app-primary-soft)] px-4 py-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full border border-white/80 bg-white text-sm font-bold text-[var(--app-primary)]"
                    >
                        {{ getInitials(user?.name || 'Guest') }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-base font-bold text-[var(--app-ink)]">{{ user?.name || 'Guest' }}</p>
                        <p class="truncate text-sm text-slate-500">{{ primaryRole }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            <p class="px-3 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Workspace</p>

            <nav class="mt-3 space-y-1.5">
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

                <div v-if="hasMasterDataAccess" class="space-y-1.5">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-[18px] px-4 py-3 text-left text-sm font-semibold transition"
                        :class="
                            isMasterDataActive
                                ? 'bg-[var(--app-primary-soft)] text-[var(--app-primary)]'
                                : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                        "
                        @click="toggleMasterData"
                    >
                        <Tags class="h-5 w-5 shrink-0" />
                        <span class="flex-1 truncate">Master Data</span>
                        <ChevronDown class="h-4 w-4 shrink-0 transition" :class="masterDataOpen ? 'rotate-180' : ''" />
                    </button>

                    <div v-if="masterDataOpen" class="space-y-1 pl-4">
                        <Link
                            v-for="item in masterDataItems"
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
                    v-if="page.props.auth.can.view_users"
                    href="/users"
                    class="group flex items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold transition"
                    :class="
                        isActive('/users')
                            ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]'
                            : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'
                    "
                    @click="closeSidebar"
                >
                    <Users class="h-5 w-5 shrink-0" />
                    <span class="truncate">Management Users</span>
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

        <div class="border-t border-[var(--app-border)] px-4 py-4">
            <Link
                method="post"
                as="button"
                :href="route('logout')"
                class="flex w-full items-center gap-3 rounded-[18px] px-4 py-3 text-sm font-semibold text-rose-500 transition hover:bg-rose-50"
            >
                <LogOut class="h-5 w-5" />
                <span>Sign Out</span>
            </Link>
        </div>
    </aside>
</template>
