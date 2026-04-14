<script setup lang="ts">
import type { BreadcrumbItemType, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Menu, PanelTopOpen } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

defineEmits<{
    openSidebar: [];
}>();

const page = usePage<SharedData>();

const pageTitle = computed(() => {
    if (props.breadcrumbs?.length) {
        return props.breadcrumbs[props.breadcrumbs.length - 1].title;
    }

    return 'Workspace';
});

const pageSubtitle = computed(() => {
    if (page.url.startsWith('/dashboard')) return 'Real-time monitoring & performance.';
    if (page.url.startsWith('/complaints')) return 'Complaint tracking, follow up, and customer handling.';
    if (page.url.startsWith('/bad-reviews')) return 'Review recovery, escalation, and follow-up queue.';
    if (page.url.startsWith('/order-trackings')) return 'Shipment visibility and order handling progress.';
    if (page.url.startsWith('/oos')) return 'Stock issue monitoring and replenishment coordination.';
    if (page.url.startsWith('/brands')) return 'Master data brand untuk menjaga opsi input tetap konsisten.';
    if (page.url.startsWith('/platforms')) return 'Master data platform untuk menyatukan opsi marketplace di seluruh modul.';
    if (page.url.startsWith('/logistics')) return 'Master data logistics untuk menjaga referensi kurir tetap konsisten di modul tracking.';
    if (page.url.startsWith('/sub-cases')) return 'Master data sub case untuk menyiapkan pilihan complaint dan default cause by yang lebih terstruktur.';
    if (page.url.startsWith('/last-steps')) return 'Master data last step untuk menjaga mapping status dan priority tetap konsisten.';
    if (page.url.startsWith('/reason-whitelists')) return 'Master data reason whitelist untuk menyatukan alasan claim reject di seluruh modul.';
    if (page.url.startsWith('/users')) return 'Manage internal accounts, role access, and activation status.';
    if (page.url.startsWith('/settings')) return 'Profile, password, and security preferences.';

    return 'Workspace overview and operational control.';
});

const currentUser = computed(() => page.props.auth.user);
</script>

<template>
    <header class="px-4 pt-4 sm:px-6 lg:px-8 lg:pt-6">
        <div class="app-surface flex flex-col gap-5 rounded-[30px] px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <button
                    type="button"
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-[var(--app-border)] bg-white text-slate-600 transition hover:border-[var(--app-primary)] hover:text-[var(--app-primary)] lg:hidden"
                    @click="$emit('openSidebar')"
                >
                    <Menu class="h-5 w-5" />
                </button>

                <div class="min-w-0">
                    <div v-if="breadcrumbs?.length" class="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                        <template v-for="(item, index) in breadcrumbs" :key="`${item.title}-${index}`">
                            <Link v-if="index !== breadcrumbs.length - 1" :href="item.href" class="transition hover:text-[var(--app-primary)]">
                                {{ item.title }}
                            </Link>
                            <span v-else class="font-semibold text-slate-500">{{ item.title }}</span>
                            <span v-if="index !== breadcrumbs.length - 1">/</span>
                        </template>
                    </div>

                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-[var(--app-ink)] sm:text-[2rem]">{{ pageTitle }}</h1>
                    <p class="mt-1 text-sm text-slate-500 sm:text-base">{{ pageSubtitle }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-[var(--app-border)] bg-white text-slate-500 transition hover:border-[var(--app-primary)] hover:text-[var(--app-primary)]"
                >
                    <Bell class="h-5 w-5" />
                </button>

                <div class="hidden items-center gap-3 rounded-[22px] border border-[var(--app-border)] bg-[var(--app-primary-soft)] px-4 py-2.5 sm:flex">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-sm font-bold text-[var(--app-primary)]">
                        {{ currentUser?.name?.slice(0, 1) || 'U' }}
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-bold text-[var(--app-ink)]">{{ currentUser?.name || 'Guest' }}</p>
                        <p class="text-xs text-slate-500">{{ currentUser?.roles?.[0] || 'Workspace' }}</p>
                    </div>
                </div>

                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-2 rounded-[18px] bg-[var(--app-primary)] px-4 py-3 text-sm font-semibold text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)] transition hover:bg-[var(--app-primary-dark)]"
                >
                    <PanelTopOpen class="h-4 w-4" />
                    Open Dashboard
                </Link>
            </div>
        </div>
    </header>
</template>
