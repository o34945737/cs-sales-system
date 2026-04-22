<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    Activity,
    BarChart3,
    ChevronRight,
    PieChart,
    RefreshCw,
    TrendingUp,
} from 'lucide-vue-next';

interface LabelTotal {
    label: string | null;
    total: number;
}

interface SubCaseRow extends LabelTotal {
    sla_ok: number;
    sla_breach: number;
}

interface BrandRealTimeRow {
    label: string | null;
    total: number;
    hard: number;
    normal: number;
}

interface WeeklyPoint {
    date: string;
    new?: number;
    solved?: number;
}

const props = defineProps<{
    weeklyComplaint: WeeklyPoint[];
    pendingByCauseBy: LabelTotal[];
    pendingByPlatform: LabelTotal[];
    pendingByLevel: LabelTotal[];
    pendingBySubCase: SubCaseRow[];
    brandRealTime: BrandRealTimeRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Complaint Analytics', href: '/dashboard/complaints' }
];

function barWidth(value: number, max: number): string {
    if (!max) return '0%';
    return Math.round((value / max) * 100) + '%';
}

function maxOf(arr: LabelTotal[]): number {
    return arr.reduce((m, r) => Math.max(m, r.total), 0) || 1;
}

function maxWeekly(arr: WeeklyPoint[], key: 'new' | 'solved'): number {
    return arr.reduce((m, r) => Math.max(m, (r as any)[key] ?? 0), 0) || 1;
}

function shortDate(iso: string): string {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
}

function refreshPage() {
    router.reload({ only: [] });
}
</script>

<template>
    <Head title="Complaint Analytics" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 pb-10">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/20">
                        <TrendingUp class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">Complaint Analytics</h1>
                        <p class="text-[13px] font-medium text-slate-500">Performance trends & bottleneck analysis</p>
                    </div>
                </div>
                <button
                    class="group flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 shadow-sm transition-all hover:bg-slate-50 active:scale-95"
                    @click="refreshPage"
                >
                    <RefreshCw class="h-3.5 w-3.5 transition-transform group-hover:rotate-180" />
                    <span>Sync Metrics</span>
                </button>
            </div>

            <!-- Poin 5: Weekly Trend (Modern Chart Style) -->
            <section class="rounded-[32px] border border-slate-100 bg-white p-8 shadow-sm">
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-500">
                            <BarChart3 class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Volume Trend</h2>
                            <p class="text-[12px] font-medium text-slate-400">7 Days performance comparison</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-rose-200"></span>
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Incoming</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-400">Solved</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-end gap-3 h-48">
                    <template v-for="(pt, i) in weeklyComplaint" :key="i">
                        <div class="group relative flex-1 flex flex-col items-center gap-2">
                            <!-- Tooltip -->
                            <div class="absolute -top-12 left-1/2 -translate-x-1/2 rounded-lg bg-slate-900 px-2 py-1 text-[10px] font-bold text-white opacity-0 transition group-hover:opacity-100">
                                In: {{ pt.new }} | Sol: {{ pt.solved }}
                            </div>
                            
                            <div class="w-full flex flex-col gap-1 justify-end h-full">
                                <div
                                    class="w-full rounded-lg bg-rose-100 transition-all duration-500 group-hover:bg-rose-200"
                                    :style="{ height: Math.max(6, Math.round(((pt.new ?? 0) / maxWeekly(weeklyComplaint, 'new')) * 100)) + 'px' }"
                                ></div>
                                <div
                                    class="w-full rounded-lg bg-emerald-400 transition-all duration-500 group-hover:bg-emerald-500"
                                    :style="{ height: Math.max(6, Math.round(((pt.solved ?? 0) / maxWeekly(weeklyComplaint, 'solved')) * 100)) + 'px' }"
                                ></div>
                            </div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">{{ shortDate(pt.date) }}</span>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Controller Brand Real Time (Enhanced Table) -->
            <section class="overflow-hidden rounded-[32px] border border-slate-100 bg-white shadow-sm">
                <div class="flex items-center gap-3 border-b border-slate-50 px-8 py-6">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-500">
                        <Activity class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Controller Brand Real-Time</h2>
                        <p class="text-[12px] font-medium text-slate-400">Pending tickets categorized by severity</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50/50 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-8 py-4 text-left">Brand Name</th>
                                <th class="px-4 py-4 text-center">Total Pending</th>
                                <th class="px-4 py-4 text-center">Hard Case</th>
                                <th class="px-4 py-4 text-center">Normal Case</th>
                                <th class="px-8 py-4 text-left">Severity Ratio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="row in brandRealTime" :key="row.label ?? 'null'" class="group transition hover:bg-slate-50/50">
                                <td class="px-8 py-4 font-black text-slate-700">{{ row.label || '-' }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-lg font-black text-slate-900">{{ row.total }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span v-if="row.hard" class="inline-flex rounded-xl bg-rose-50 px-3 py-1 text-xs font-black text-rose-600 border border-rose-100">{{ row.hard }}</span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span v-if="row.normal" class="inline-flex rounded-xl bg-blue-50 px-3 py-1 text-xs font-black text-blue-600 border border-blue-100">{{ row.normal }}</span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <td class="px-8 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                            <div class="bg-rose-500 transition-all duration-700" :style="{ width: barWidth(row.hard, row.total) }"></div>
                                            <div class="bg-blue-500 transition-all duration-700" :style="{ width: barWidth(row.normal, row.total) }"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-400">{{ Math.round((row.hard / row.total) * 100) }}% Hard</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Poin 8: Advanced Analytics Grid -->
            <div>
                <div class="mb-6 flex items-center gap-2 px-1">
                    <PieChart class="h-5 w-5 text-indigo-500" />
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-900">Analisa Bottleneck Pending</h2>
                </div>
                
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Analytics Card: By Platform -->
                    <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">By Platform</p>
                        <div class="space-y-4">
                            <div v-for="row in pendingByPlatform" :key="row.label ?? 'null'" class="group">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-600">{{ row.label || '-' }}</span>
                                    <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-50 overflow-hidden shadow-inner">
                                    <div class="h-full bg-blue-500 transition-all duration-1000 group-hover:bg-blue-600" :style="{ width: barWidth(row.total, maxOf(pendingByPlatform)) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Card: By Cause By -->
                    <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">By Root Cause</p>
                        <div class="space-y-4">
                            <div v-for="row in pendingByCauseBy.slice(0, 5)" :key="row.label ?? 'null'" class="group">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-600 truncate max-w-[120px]">{{ row.label || '-' }}</span>
                                    <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-50 overflow-hidden shadow-inner">
                                    <div class="h-full bg-rose-500 transition-all duration-1000 group-hover:bg-rose-600" :style="{ width: barWidth(row.total, maxOf(pendingByCauseBy)) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Card: By Level -->
                    <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">By Priority Level</p>
                        <div class="space-y-4">
                            <div v-for="row in pendingByLevel" :key="row.label ?? 'null'" class="group">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-600">{{ row.label || '-' }}</span>
                                    <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-50 overflow-hidden shadow-inner">
                                    <div class="h-full bg-amber-500 transition-all duration-1000 group-hover:bg-amber-600" :style="{ width: barWidth(row.total, maxOf(pendingByLevel)) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Card: By Sub Case -->
                    <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">By Case Category</p>
                        <div class="space-y-4">
                            <div v-for="row in pendingBySubCase.slice(0, 5)" :key="row.label ?? 'null'" class="group">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-600 truncate max-w-[120px]">{{ row.label || '-' }}</span>
                                    <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-50 overflow-hidden shadow-inner">
                                    <div class="h-full bg-indigo-500 transition-all duration-1000 group-hover:bg-indigo-600" :style="{ width: barWidth(row.total, maxOf(pendingBySubCase)) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
