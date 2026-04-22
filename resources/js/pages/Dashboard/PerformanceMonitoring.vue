<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { BarChart2, BoxesIcon, PieChart, RefreshCw, Truck } from 'lucide-vue-next';

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
    badReviewByCategory: LabelTotal[];
    pendingOtByBrand: LabelTotal[];
    pendingOtByPlatform: LabelTotal[];
    pendingOtByLogistics: LabelTotal[];
    pendingOtByOrderDate: LabelTotal[];
    pendingOtByAutoTrack: LabelTotal[];
    pendingOtByDataSource: LabelTotal[];
    oosNeedingBlast: LabelTotal[];
    oosNeedingBlastTotal: number;
    oosByBrand: LabelTotal[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Performance Monitor', href: '/dashboard/performance' },
];

function barWidth(value: number, max: number): string {
    if (!max) return '0%';
    return Math.round((value / max) * 100) + '%';
}

function maxOf(arr: LabelTotal[]): number {
    return arr.reduce((max, row) => Math.max(max, row.total), 0) || 1;
}

function maxWeekly(arr: WeeklyPoint[]): number {
    return arr.reduce((max, row) => Math.max(max, row.total ?? 0), 0) || 1;
}

function shortDate(iso: string): string {
    const date = new Date(iso);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
}

function refreshPage() {
    router.reload({ only: [] });
}
</script>

<template>
    <Head title="Performance Monitor" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 pb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-[var(--app-ink)]">Performance Monitor</h1>
                    <p class="mt-0.5 text-xs text-slate-400">Bad Reviews, pending OT, dan OOS execution</p>
                </div>
                <button
                    class="flex items-center gap-1.5 rounded-xl border border-[var(--app-border)] bg-white px-3 py-2 text-xs font-semibold text-slate-500 shadow-sm transition hover:bg-slate-50"
                    @click="refreshPage"
                >
                    <RefreshCw class="h-3.5 w-3.5" /> Refresh
                </button>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <section class="app-grid-card p-6">
                    <div class="mb-6 flex items-center gap-2">
                        <BarChart2 class="h-5 w-5 text-violet-400" />
                        <p class="text-sm font-black uppercase tracking-wider text-slate-500">Incoming Bad Review</p>
                    </div>
                    <div class="flex h-40 items-end gap-2">
                        <template v-for="(point, index) in weeklyBadReview" :key="index">
                            <div class="flex flex-1 flex-col items-center gap-1">
                                <div
                                    class="w-full rounded-md bg-violet-200 transition-all hover:bg-violet-300"
                                    :style="{ height: Math.max(4, Math.round(((point.total ?? 0) / maxWeekly(weeklyBadReview)) * 140)) + 'px' }"
                                ></div>
                                <span class="text-[10px] font-bold text-slate-400">{{ shortDate(point.date) }}</span>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="app-grid-card p-6">
                    <div class="mb-6 flex items-center gap-2">
                        <BarChart2 class="h-5 w-5 text-amber-400" />
                        <p class="text-sm font-black uppercase tracking-wider text-slate-500">Incoming OOS</p>
                    </div>
                    <div class="flex h-40 items-end gap-2">
                        <template v-for="(point, index) in weeklyOos" :key="index">
                            <div class="flex flex-1 flex-col items-center gap-1">
                                <div
                                    class="w-full rounded-md bg-amber-200 transition-all hover:bg-amber-300"
                                    :style="{ height: Math.max(4, Math.round(((point.total ?? 0) / maxWeekly(weeklyOos)) * 140)) + 'px' }"
                                ></div>
                                <span class="text-[10px] font-bold text-slate-400">{{ shortDate(point.date) }}</span>
                            </div>
                        </template>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="app-table-shell overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-[var(--app-border)] px-5 py-4">
                        <PieChart class="h-4 w-4 text-violet-500" />
                        <h2 class="text-base font-black text-[var(--app-ink)]">Bad Review by Brand</h2>
                    </div>
                    <div class="space-y-3 p-4">
                        <div v-for="row in badReviewByBrand" :key="row.label ?? 'null'" class="flex items-center gap-3">
                            <span class="w-24 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                <div class="h-full bg-violet-400" :style="{ width: barWidth(row.total, maxOf(badReviewByBrand)) }"></div>
                            </div>
                            <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                        </div>
                    </div>
                </section>

                <section class="app-table-shell overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-[var(--app-border)] px-5 py-4">
                        <PieChart class="h-4 w-4 text-fuchsia-500" />
                        <h2 class="text-base font-black text-[var(--app-ink)]">Bad Review by Category</h2>
                    </div>
                    <div class="space-y-3 p-4">
                        <div v-for="row in badReviewByCategory" :key="row.label ?? 'null'" class="flex items-center gap-3">
                            <span class="w-24 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                <div class="h-full bg-fuchsia-400" :style="{ width: barWidth(row.total, maxOf(badReviewByCategory)) }"></div>
                            </div>
                            <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                        </div>
                    </div>
                </section>

                <section class="app-table-shell overflow-hidden">
                    <div class="flex items-center justify-between border-b border-[var(--app-border)] px-5 py-4">
                        <div class="flex items-center gap-2">
                            <BoxesIcon class="h-4 w-4 text-amber-500" />
                            <h2 class="text-base font-black text-[var(--app-ink)]">OOS Need Blast</h2>
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-600">{{ oosNeedingBlastTotal }}</span>
                    </div>
                    <div class="space-y-3 p-4">
                        <div v-for="row in oosNeedingBlast" :key="row.label ?? 'null'" class="flex items-center gap-3">
                            <span class="w-24 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                <div class="h-full bg-amber-400" :style="{ width: barWidth(row.total, maxOf(oosNeedingBlast)) }"></div>
                            </div>
                            <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                        </div>
                        <div v-if="!oosNeedingBlast.length" class="py-4 text-center text-xs font-bold text-slate-400">Tidak ada data</div>
                    </div>
                </section>
            </div>

            <section class="app-table-shell overflow-hidden">
                <div class="flex items-center gap-2 border-b border-[var(--app-border)] px-5 py-4">
                    <Truck class="h-4 w-4 text-blue-500" />
                    <div>
                        <h2 class="text-base font-black text-[var(--app-ink)]">Pending Order Tracking Breakdown</h2>
                        <p class="text-[11px] font-medium text-slate-400">Realtime segmentation untuk follow up OT</p>
                    </div>
                </div>
                <div class="grid gap-6 p-5 lg:grid-cols-3">
                    <div>
                        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">By Brand</p>
                        <div class="space-y-3">
                            <div v-for="row in pendingOtByBrand" :key="row.label ?? 'null'" class="flex items-center gap-3">
                                <span class="w-20 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                    <div class="h-full bg-blue-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByBrand)) }"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">By Platform</p>
                        <div class="space-y-3">
                            <div v-for="row in pendingOtByPlatform" :key="row.label ?? 'null'" class="flex items-center gap-3">
                                <span class="w-20 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                    <div class="h-full bg-cyan-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByPlatform)) }"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">By Logistics</p>
                        <div class="space-y-3">
                            <div v-for="row in pendingOtByLogistics" :key="row.label ?? 'null'" class="flex items-center gap-3">
                                <span class="w-20 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                    <div class="h-full bg-teal-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByLogistics)) }"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">By Order Date</p>
                        <div class="space-y-3">
                            <div v-for="row in pendingOtByOrderDate" :key="row.label ?? 'null'" class="flex items-center gap-3">
                                <span class="w-24 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                    <div class="h-full bg-sky-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByOrderDate)) }"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">By Automation Track</p>
                        <div class="space-y-3">
                            <div v-for="row in pendingOtByAutoTrack" :key="row.label ?? 'null'" class="flex items-center gap-3">
                                <span class="w-24 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                    <div class="h-full bg-indigo-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByAutoTrack)) }"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-[10px] font-black uppercase tracking-widest text-slate-400">By Data Source</p>
                        <div class="space-y-3">
                            <div v-for="row in pendingOtByDataSource" :key="row.label ?? 'null'" class="flex items-center gap-3">
                                <span class="w-20 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                                    <div class="h-full bg-fuchsia-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByDataSource)) }"></div>
                                </div>
                                <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="app-table-shell overflow-hidden">
                <div class="flex items-center gap-2 border-b border-[var(--app-border)] px-5 py-4">
                    <PieChart class="h-4 w-4 text-amber-500" />
                    <h2 class="text-base font-black text-[var(--app-ink)]">OOS by Brand</h2>
                </div>
                <div class="space-y-3 p-4">
                    <div v-for="row in oosByBrand" :key="row.label ?? 'null'" class="flex items-center gap-3">
                        <span class="w-24 truncate text-xs font-bold text-slate-600">{{ row.label || '-' }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-50">
                            <div class="h-full bg-amber-400" :style="{ width: barWidth(row.total, maxOf(oosByBrand)) }"></div>
                        </div>
                        <span class="w-8 text-right text-xs font-black text-slate-400">{{ row.total }}</span>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
