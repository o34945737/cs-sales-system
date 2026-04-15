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
    if (page.url.startsWith('/complaint-sources'))
        return 'Master data complaint source untuk menjaga asal complaint tetap konsisten di flow complaint.';
    if (page.url.startsWith('/complaint-powers')) return 'Master data complaint power untuk menjaga level customer tetap konsisten di flow complaint.';
    if (page.url.startsWith('/complaint-step-statuses')) return 'Master data step completion untuk menjaga status selesai CS tetap konsisten.';
    if (page.url.startsWith('/sku-codes')) return 'Master data SKU code untuk menyiapkan autofill product, brand, dan value di flow complaint.';
    if (page.url.startsWith('/logistics')) return 'Master data logistics untuk menjaga referensi kurir tetap konsisten di modul tracking.';
    if (page.url.startsWith('/sub-cases'))
        return 'Master data sub case untuk menyiapkan pilihan complaint dan default cause by yang lebih terstruktur.';
    if (page.url.startsWith('/last-steps')) return 'Master data last step untuk menjaga mapping status dan priority tetap konsisten.';
    if (page.url.startsWith('/reason-whitelists')) return 'Master data reason whitelist untuk menyatukan alasan claim reject di seluruh modul.';
    if (page.url.startsWith('/reason-late-responses')) return 'Master data reason late responses untuk menyatukan alasan late di seluruh modul.';
    if (page.url.startsWith('/order-tracking-data-sources'))
        return 'Master data data source order tracking untuk menjaga asal input tracking tetap konsisten.';
    if (page.url.startsWith('/oos-reasons')) return 'Master data OOS reason untuk menjaga alasan stok kosong tetap konsisten.';
    if (page.url.startsWith('/oos-solutions')) return 'Master data OOS solution untuk menjaga opsi penyelesaian OOS tetap konsisten.';
    if (page.url.startsWith('/cause-bys')) return 'Master data cause by untuk menjaga pilihan penanggung jawab penyebab case tetap konsisten.';
    if (page.url.startsWith('/part-of-bads')) return 'Master data part of bad untuk menjaga opsi bagian kerusakan tetap konsisten di flow complaint.';
    if (page.url.startsWith('/users')) return 'Manage internal accounts, role access, and activation status.';
    if (page.url.startsWith('/settings')) return 'Profile, password, and security preferences.';

    return 'Workspace overview and operational control.';
});

const currentUser = computed(() => page.props.auth.user);
</script>

<template>
    <header class="px-4 pt-4 sm:px-6 lg:px-8 lg:pt-6">
        <div
            class="app-surface flex flex-col gap-3 rounded-[24px] bg-white px-4 py-3 shadow-[0_4px_20px_rgb(0,0,0,0.03)] sm:px-5 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    class="hover:border-[var(--app-primary)]/30 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-[12px] border border-white/60 bg-white/80 text-slate-600 shadow-sm transition hover:text-[var(--app-primary)] lg:hidden"
                    @click="$emit('openSidebar')"
                >
                    <Menu class="h-4 w-4" />
                </button>

                <div class="min-w-0">
                    <div v-if="breadcrumbs?.length" class="flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-slate-400">
                        <template v-for="(item, index) in breadcrumbs" :key="`${item.title}-${index}`">
                            <Link v-if="index !== breadcrumbs.length - 1" :href="item.href" class="transition hover:text-[var(--app-primary)]">
                                {{ item.title }}
                            </Link>
                            <span v-else class="font-bold text-slate-500">{{ item.title }}</span>
                            <span v-if="index !== breadcrumbs.length - 1">/</span>
                        </template>
                    </div>

                    <h1 class="text-xl font-extrabold leading-snug tracking-tight text-[var(--app-ink)]">{{ pageTitle }}</h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button
                    type="button"
                    class="hover:border-[var(--app-primary)]/30 inline-flex h-9 w-9 items-center justify-center rounded-[12px] border border-white/60 bg-white/80 text-slate-500 shadow-sm transition-all hover:-translate-y-0.5 hover:text-[var(--app-primary)]"
                >
                    <Bell class="h-4 w-4" />
                </button>

                <div class="hidden items-center gap-2.5 rounded-[16px] border border-white/60 bg-white/80 px-3 py-1.5 shadow-sm sm:flex">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-r from-[var(--app-primary)] to-[var(--app-primary-dark)] text-xs font-bold text-white shadow-sm"
                    >
                        {{ currentUser?.name?.slice(0, 1) || 'U' }}
                    </div>
                    <div class="pr-2 text-left">
                        <p class="text-xs font-bold leading-tight text-[var(--app-ink)]">{{ currentUser?.name || 'Guest' }}</p>
                        <p class="text-[10px] font-medium leading-tight text-slate-500">{{ currentUser?.roles?.[0] || 'Workspace' }}</p>
                    </div>
                </div>

                <Link
                    :href="route('dashboard')"
                    class="group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-[14px] bg-gradient-to-r from-[var(--app-primary)] to-[var(--app-primary-dark)] px-4 py-2 text-sm font-bold text-white shadow-[0_8px_16px_rgba(53,103,232,0.2)] transition-all hover:scale-[1.02] active:scale-95"
                >
                    <div
                        class="absolute inset-0 translate-y-full bg-gradient-to-t from-white/20 to-transparent opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100"
                    ></div>
                    <PanelTopOpen class="relative z-10 h-4 w-4 transition-transform group-hover:-translate-y-0.5" />
                    <span class="relative z-10">Dashboard</span>
                </Link>
            </div>
        </div>
    </header>
</template>
