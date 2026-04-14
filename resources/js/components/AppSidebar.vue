<script setup lang="ts">
import { useInitials } from '@/composables/useInitials';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Archive, LayoutGrid, LogOut, MessageSquare, Settings, Star, Truck, Users, X } from 'lucide-vue-next';
import { computed } from 'vue';

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

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (page.props.auth.can.view_dashboard) {
        items.push({ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid });
    }

    if (page.props.auth.can.access_complaints) {
        items.push({ title: 'Complaints', href: '/complaints', icon: MessageSquare });
    }

    if (page.props.auth.can.access_bad_reviews) {
        items.push({ title: 'Bad Reviews', href: '/bad-reviews', icon: Star });
    }

    if (page.props.auth.can.access_order_trackings) {
        items.push({ title: 'Order Tracking', href: '/order-trackings', icon: Truck });
    }

    if (page.props.auth.can.access_oos) {
        items.push({ title: 'OOS Data', href: '/oos', icon: Archive });
    }

    if (page.props.auth.can.view_users) {
        items.push({ title: 'Management Users', href: '/users', icon: Users });
    }

    items.push({ title: 'Settings', href: '/settings/profile', icon: Settings });

    return items;
});

const isActive = (href: string) => {
    if (href === '/dashboard') {
        return page.url === href;
    }

    return page.url === href || page.url.startsWith(`${href}/`);
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
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-base font-extrabold text-white shadow-[0_12px_20px_rgba(53,103,232,0.28)]">
                    {{ getInitials(workspaceName) }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-extrabold text-[var(--app-ink)]">{{ workspaceName }}</p>
                    <span class="mt-1 inline-flex rounded-full bg-[var(--app-primary-soft)] px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-[var(--app-primary)]">
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
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-white/80 bg-white text-sm font-bold text-[var(--app-primary)]">
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
                    :class="isActive(item.href) ? 'bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)]' : 'text-slate-600 hover:bg-[var(--app-primary-soft)] hover:text-[var(--app-primary)]'"
                    @click="closeSidebar"
                >
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                    <span class="truncate">{{ item.title }}</span>
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
