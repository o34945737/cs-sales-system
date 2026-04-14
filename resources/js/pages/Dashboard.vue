<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Activity, AlertCircle, Boxes, ChevronRight, CircleDot, MessageSquare, Star, Truck } from 'lucide-vue-next';
import { computed } from 'vue';

interface StatItem {
    label: string;
    value: number;
    helper: string;
    tone: 'amber' | 'blue' | 'green' | 'violet';
}

interface RecentComplaint {
    id: number;
    order_id: string | null;
    username: string | null;
    brand: string | null;
    platform: string | null;
    status: string | null;
    priority: string | null;
    cs_name: string | null;
    updated_at: string | null;
}

const props = defineProps<{
    stats?: StatItem[];
    recentComplaints?: RecentComplaint[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const page = usePage<SharedData>();

const iconMap = {
    blue: MessageSquare,
    violet: Star,
    green: Truck,
    amber: Boxes,
};

const iconToneClassMap = {
    blue: 'bg-blue-50 text-blue-500',
    violet: 'bg-violet-50 text-violet-500',
    green: 'bg-emerald-50 text-emerald-500',
    amber: 'bg-amber-50 text-amber-500',
};

const stats = computed(() => props.stats ?? []);
const recentComplaints = computed(() => props.recentComplaints ?? []);

const quickLinks = computed(() => {
    const links = [];

    if (page.props.auth.can.access_complaints) {
        links.push({ label: 'Complaint Board', description: 'Track active cases and follow up.', href: '/complaints' });
    }

    if (page.props.auth.can.access_bad_reviews) {
        links.push({ label: 'Bad Reviews', description: 'Recover low ratings and customer trust.', href: '/bad-reviews' });
    }

    if (page.props.auth.can.access_order_trackings) {
        links.push({ label: 'Order Tracking', description: 'Monitor shipment and AWB progress.', href: '/order-trackings' });
    }

    if (page.props.auth.can.access_oos) {
        links.push({ label: 'OOS Monitoring', description: 'Handle stock issue escalation quickly.', href: '/oos' });
    }

    if (page.props.auth.can.view_users) {
        links.push({ label: 'Management Users', description: 'Review role access and account health.', href: '/users' });
    }

    return links.slice(0, 4);
});

const formatDate = (value: string | null) => {
    if (!value) return '-';

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return value;

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsed);
};

const statusClass = (status: string | null) => {
    if (status === 'Solved') return 'bg-emerald-50 text-emerald-600';
    if (status === 'Whitelist') return 'bg-rose-50 text-rose-500';

    return 'bg-amber-50 text-amber-600';
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 pb-4">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="item in stats"
                    :key="item.label"
                    class="app-grid-card flex items-center gap-4 px-5 py-5"
                >
                    <div class="flex h-14 w-14 items-center justify-center rounded-full" :class="iconToneClassMap[item.tone]">
                        <component :is="iconMap[item.tone]" class="h-6 w-6" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-400">{{ item.label }}</p>
                        <p class="mt-1 text-4xl font-extrabold tracking-tight text-[var(--app-ink)]">{{ item.value }}</p>
                        <p class="mt-1 text-sm font-medium text-slate-500">{{ item.helper }}</p>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                <div class="app-table-shell overflow-hidden">
                    <div class="border-b border-[var(--app-border)] px-6 py-5">
                        <div class="flex items-center gap-2 text-sm font-semibold text-rose-500">
                            <Activity class="h-4 w-4" />
                            Live Monitoring
                        </div>
                        <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-[var(--app-ink)]">Recent Complaint Queue</h2>
                        <p class="mt-1 text-sm text-slate-500">Pantau case terbaru tanpa mengubah flow complaint yang sudah berjalan.</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <article
                            v-for="item in recentComplaints"
                            :key="item.id"
                            class="rounded-[26px] border border-[var(--app-border)] bg-white px-5 py-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)]"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-rose-500">Live</span>
                                    <span class="text-xs font-semibold text-slate-400">#{{ item.order_id || item.id }}</span>
                                </div>

                                <div class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(item.status)">
                                    {{ item.status || 'Pending' }}
                                </div>
                            </div>

                            <div class="mt-4">
                                <p class="text-xl font-bold text-[var(--app-ink)]">{{ item.username || 'Unknown Customer' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ item.brand || '-' }} / {{ item.platform || '-' }}</p>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Priority</p>
                                    <p class="mt-2 text-sm font-bold text-[var(--app-ink)]">{{ item.priority || '-' }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Agent</p>
                                    <p class="mt-2 text-sm font-bold text-[var(--app-ink)]">{{ item.cs_name || 'Unassigned' }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Updated</p>
                                    <p class="mt-2 text-sm font-bold text-[var(--app-ink)]">{{ formatDate(item.updated_at) }}</p>
                                </div>
                            </div>
                        </article>

                        <div v-if="!recentComplaints.length" class="rounded-[26px] border border-dashed border-[var(--app-border)] bg-[var(--app-panel-soft)] px-5 py-10 text-center">
                            <AlertCircle class="mx-auto h-10 w-10 text-slate-300" />
                            <p class="mt-4 text-lg font-bold text-[var(--app-ink)]">Belum ada complaint terbaru</p>
                            <p class="mt-2 text-sm text-slate-500">Queue akan muncul di sini begitu data mulai masuk.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="app-table-shell overflow-hidden">
                        <div class="border-b border-[var(--app-border)] px-6 py-5">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Quick Access</p>
                            <h2 class="mt-2 text-xl font-extrabold text-[var(--app-ink)]">Workspace Modules</h2>
                        </div>

                        <div class="space-y-3 p-5">
                            <Link
                                v-for="link in quickLinks"
                                :key="link.label"
                                :href="link.href"
                                class="flex items-center justify-between rounded-[22px] border border-[var(--app-border)] bg-white px-4 py-4 transition hover:border-[var(--app-primary)] hover:bg-[var(--app-primary-soft)]"
                            >
                                <div>
                                    <p class="font-bold text-[var(--app-ink)]">{{ link.label }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ link.description }}</p>
                                </div>
                                <ChevronRight class="h-5 w-5 text-slate-400" />
                            </Link>
                        </div>
                    </section>

                    <section class="app-table-shell overflow-hidden">
                        <div class="border-b border-[var(--app-border)] px-6 py-5">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Status Ringkas</p>
                            <h2 class="mt-2 text-xl font-extrabold text-[var(--app-ink)]">Operational Snapshot</h2>
                        </div>

                        <div class="space-y-3 p-5">
                            <div class="flex items-center justify-between rounded-[22px] bg-slate-50 px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <CircleDot class="h-4 w-4 text-emerald-500" />
                                    <span class="text-sm font-semibold text-[var(--app-ink)]">Account Status</span>
                                </div>
                                <span class="text-sm font-bold text-emerald-600">Active</span>
                            </div>

                            <div class="flex items-center justify-between rounded-[22px] bg-slate-50 px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <CircleDot class="h-4 w-4 text-blue-500" />
                                    <span class="text-sm font-semibold text-[var(--app-ink)]">Primary Role</span>
                                </div>
                                <span class="text-sm font-bold text-blue-600">{{ page.props.auth.user?.roles?.[0] || 'Workspace' }}</span>
                            </div>

                            <div class="flex items-center justify-between rounded-[22px] bg-slate-50 px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <CircleDot class="h-4 w-4 text-violet-500" />
                                    <span class="text-sm font-semibold text-[var(--app-ink)]">Accessible Modules</span>
                                </div>
                                <span class="text-sm font-bold text-violet-600">{{ quickLinks.length }}</span>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
