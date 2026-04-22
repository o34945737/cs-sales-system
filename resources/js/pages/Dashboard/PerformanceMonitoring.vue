<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    BarChart2,
    PieChart,
    RefreshCw,
} from 'lucide-vue-next';

interface LabelTotal {
    label: string | null;
    total: number;
}

interface WeeklyPoint {
    date: string;
    total?: number;
}

const props = defineProps<{
    weeklyBadReview: WeeklyPoint[];
    weeklyOos: WeeklyPoint[];
    badReviewByBrand: LabelTotal[];
    oosByBrand: LabelTotal[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Performance Monitor', href: '/dashboard/performance' }
];

function barWidth(value: number, max: number): string {
    if (!max) return '0%';
    return Math.round((value / max) * 100) + '%';
}

function maxOf(arr: LabelTotal[]): number {
    return arr.reduce((m, r) => Math.max(m, r.total), 0) || 1;
}

function maxWeekly(arr: WeeklyPoint[]): number {
    return arr.reduce((m, r) => Math.max(m, r.total ?? 0), 0) || 1;
}

function shortDate(iso: string): string {
    return iso.slice(5); // MM-DD
}

function refreshPage() {
    router.reload({ only: [] });
}
</script>

<template>
    <Head title="Performance Monitor" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 pb-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-[var(--app-ink)]">Performance Monitor</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Bad Reviews &amp; OOS Tracking</p>
                </div>
                <button
                    class="flex items-center gap-1.5 rounded-xl border border-[var(--app-border)] bg-white px-3 py-2 text-xs font-semibold text-slate-500 shadow-sm hover:bg-slate-50 transition"
                    @click="refreshPage"
                >
                    <RefreshCw class="h-3.5 w-3.5" /> Refresh
                </button>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Poin 6: Bad Review Weekly -->
                <section class="app-grid-card p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <BarChart2 class="h-5 w-5 text-violet-400" />
                        <p class="text-sm font-black uppercase tracking-wider text-slate-500">Incoming Bad Review (Weekly)</p>
                    </div>
                    <div class="flex items-end gap-2 h-40">
                        <template v-for="(pt, i) in weeklyBadReview" :key="i">
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <div
                                    class="w-full rounded-md bg-violet-200 transition-all hover:bg-violet-300"
                                    :style="{ height: Math.max(4, Math.round(((pt.total ?? 0) / maxWeekly(weeklyBadReview)) * 140)) + 'px' }"
                                    :title="String(pt.total)"
                                ></div>
                                <span class="text-[10px] font-bold text-slate-400">{{ shortDate(pt.date) }}</span>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Poin 7: OOS Weekly -->
                <section class="app-grid-card p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <BarChart2 class="h-5 w-5 text-amber-400" />
                        <p class="text-sm font-black uppercase tracking-wider text-slate-500">Incoming OOS (Weekly)</p>
                    </div>
                    <div class="flex items-end gap-2 h-40">
                        <template v-for="(pt, i) in weeklyOos" :key="i">
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <div
                                    class="w-full rounded-md bg-amber-200 transition-all hover:bg-amber-300"
                                    :style="{ height: Math.max(4, Math.round(((pt.total ?? 0) / maxWeekly(weeklyOos)) * 140)) + 'px' }"
                                    :title="String(pt.total)"
                                ></div>
                                <span class="text-[10px] font-bold text-slate-400">{{ shortDate(pt.date) }}</span>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Bad Review by Brand -->
                <section class="app-table-shell overflow-hidden">
                    <div class="border-b border-[var(--app-border)] px-5 py-4 flex items-center gap-2">
                        <PieChart class="h-4 w-4 text-violet-500" />
                        <h2 class="text-base font-black text-[var(--app-ink)]">Bad Review by Brand</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div v-for="row in badReviewByBrand" :key="row.label ?? 'null'" class="flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-600 w-24 truncate">{{ row.label || '-' }}</span>
                            <div class="flex-1 h-2 rounded-full bg-slate-50 overflow-hidden">
                                <div class="h-full bg-violet-400" :style="{ width: barWidth(row.total, maxOf(badReviewByBrand)) }"></div>
                            </div>
                            <span class="text-xs font-black text-slate-400 w-8 text-right">{{ row.total }}</span>
                        </div>
                    </div>
                </section>

                <!-- OOS by Brand -->
                <section class="app-table-shell overflow-hidden">
                    <div class="border-b border-[var(--app-border)] px-5 py-4 flex items-center gap-2">
                        <PieChart class="h-4 w-4 text-amber-500" />
                        <h2 class="text-base font-black text-[var(--app-ink)]">OOS by Brand</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div v-for="row in oosByBrand" :key="row.label ?? 'null'" class="flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-600 w-24 truncate">{{ row.label || '-' }}</span>
                            <div class="flex-1 h-2 rounded-full bg-slate-50 overflow-hidden">
                                <div class="h-full bg-amber-400" :style="{ width: barWidth(row.total, maxOf(oosByBrand)) }"></div>
                            </div>
                            <span class="text-xs font-black text-slate-400 w-8 text-right">{{ row.total }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
