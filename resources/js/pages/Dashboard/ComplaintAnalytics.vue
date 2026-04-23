<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Activity, BarChart3, FileText, Footprints, Layers, PieChart, RefreshCw, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

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
    complaintByStatus: LabelTotal[];
    pendingByLastStep: LabelTotal[];
    pendingByLastStepExternal?: LabelTotal[];
    pendingByLastStepInternal?: LabelTotal[];
    totalComplaintCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Complaint Analytics', href: '/dashboard/complaints' },
];

const externalLastSteps = computed(() => props.pendingByLastStepExternal ?? []);
const internalLastSteps = computed(() => props.pendingByLastStepInternal ?? []);

function barWidth(value: number, max: number): string {
    if (!max) return '0%';
    return Math.round((value / max) * 100) + '%';
}

function maxOf(arr: LabelTotal[]): number {
    return arr.reduce((max, row) => Math.max(max, row.total), 0) || 1;
}

function maxWeekly(arr: WeeklyPoint[], key: 'new' | 'solved'): number {
    return arr.reduce((max, row) => Math.max(max, row[key] ?? 0), 0) || 1;
}

function shortDate(iso: string): string {
    const date = new Date(iso);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
}

function refreshPage() {
    router.reload({ only: [] });
}

function statusColor(label: string | null): string {
    switch (label) {
        case 'Pending':
            return 'bg-rose-500';
        case 'Solved':
            return 'bg-emerald-500';
        case 'Whitelist':
            return 'bg-sky-500';
        default:
            return 'bg-slate-400';
    }
}

function statusBadgeClass(label: string | null): string {
    switch (label) {
        case 'Pending':
            return 'bg-rose-50 text-rose-600 border-rose-100';
        case 'Solved':
            return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'Whitelist':
            return 'bg-sky-50 text-sky-600 border-sky-100';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-100';
    }
}
</script>

<template>
    <Head title="Complaint Analytics" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 pb-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/20">
                        <TrendingUp class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">Complaint Analytics</h1>
                        <p class="text-[13px] font-medium text-slate-500">Performance trends and bottleneck analysis</p>
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

            <section class="rounded-[32px] border border-slate-100 bg-white p-8 shadow-sm">
                <div class="mb-8 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500">
                        <FileText class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">All Complaint</h2>
                        <p class="text-[12px] font-medium text-slate-400">Total seluruh komplain dan breakdown per status</p>
                    </div>
                </div>

                <div class="flex flex-col gap-8 lg:flex-row lg:items-start">
                    <div class="flex min-w-[200px] flex-col items-center justify-center rounded-2xl bg-indigo-50 p-8 text-center">
                        <span class="text-[56px] font-black leading-none text-indigo-600">{{ totalComplaintCount }}</span>
                        <span class="mt-2 text-[11px] font-black uppercase tracking-widest text-indigo-400">Total Komplain</span>
                    </div>

                    <div class="flex-1 space-y-5">
                        <div v-for="row in complaintByStatus" :key="row.label ?? 'null'" class="group">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="inline-flex rounded-lg border px-2.5 py-0.5 text-[11px] font-black" :class="statusBadgeClass(row.label)">
                                    {{ row.label || '-' }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black text-slate-700">{{ row.total }}</span>
                                    <span class="text-[11px] font-medium text-slate-400">
                                        ({{ totalComplaintCount ? Math.round((row.total / totalComplaintCount) * 100) : 0 }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                <div class="h-full transition-all duration-700" :class="statusColor(row.label)" :style="{ width: barWidth(row.total, totalComplaintCount) }"></div>
                            </div>
                        </div>
                        <p v-if="!complaintByStatus.length" class="text-sm text-slate-400">Belum ada data komplain.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[32px] border border-slate-100 bg-white p-8 shadow-sm">
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-500">
                            <BarChart3 class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Volume Trend</h2>
                            <p class="text-[12px] font-medium text-slate-400">7 hari incoming versus solved</p>
                        </div>
                    </div>
                </div>

                <div class="flex h-48 items-end gap-3">
                    <template v-for="(point, index) in weeklyComplaint" :key="index">
                        <div class="group relative flex flex-1 flex-col items-center gap-2">
                            <div class="absolute -top-12 left-1/2 -translate-x-1/2 rounded-lg bg-slate-900 px-2 py-1 text-[10px] font-bold text-white opacity-0 transition group-hover:opacity-100">
                                In: {{ point.new }} | Sol: {{ point.solved }}
                            </div>
                            <div class="flex h-full w-full flex-col justify-end gap-1">
                                <div class="w-full rounded-lg bg-rose-100" :style="{ height: Math.max(6, Math.round(((point.new ?? 0) / maxWeekly(weeklyComplaint, 'new')) * 100)) + 'px' }"></div>
                                <div class="w-full rounded-lg bg-emerald-400" :style="{ height: Math.max(6, Math.round(((point.solved ?? 0) / maxWeekly(weeklyComplaint, 'solved')) * 100)) + 'px' }"></div>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-tighter text-slate-400">{{ shortDate(point.date) }}</span>
                        </div>
                    </template>
                </div>
            </section>

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
                                <th class="px-8 py-4 text-left">Brand</th>
                                <th class="px-4 py-4 text-center">Pending</th>
                                <th class="px-4 py-4 text-center">Hard</th>
                                <th class="px-4 py-4 text-center">Normal</th>
                                <th class="px-8 py-4 text-left">Severity Ratio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="row in brandRealTime" :key="row.label ?? 'null'" class="transition hover:bg-slate-50/50">
                                <td class="px-8 py-4 font-black text-slate-700">{{ row.label || '-' }}</td>
                                <td class="px-4 py-4 text-center text-lg font-black text-slate-900">{{ row.total }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-xl border border-rose-100 bg-rose-50 px-3 py-1 text-xs font-black text-rose-600">{{ row.hard }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-xl border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-black text-blue-600">{{ row.normal }}</span>
                                </td>
                                <td class="px-8 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                            <div class="bg-rose-500" :style="{ width: barWidth(row.hard, row.total) }"></div>
                                            <div class="bg-blue-500" :style="{ width: barWidth(row.normal, row.total) }"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-400">{{ row.total ? Math.round((row.hard / row.total) * 100) : 0 }}% Hard</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!brandRealTime.length">
                                <td colspan="5" class="px-8 py-8 text-center text-xs font-bold text-slate-400">Tidak ada pending complaint</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div>
                <div class="mb-6 flex items-center gap-2 px-1">
                    <PieChart class="h-5 w-5 text-indigo-500" />
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-900">Analisa Bottleneck Pending</h2>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">By Platform</p>
                        <div class="space-y-4">
                            <div v-for="row in pendingByPlatform" :key="row.label ?? 'null'" class="group">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-600">{{ row.label || '-' }}</span>
                                    <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-50 shadow-inner">
                                    <div class="h-full bg-blue-500" :style="{ width: barWidth(row.total, maxOf(pendingByPlatform)) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">By Root Cause</p>
                        <div class="space-y-4">
                            <div v-for="row in pendingByCauseBy" :key="row.label ?? 'null'" class="group">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="max-w-[120px] truncate text-xs font-black text-slate-600">{{ row.label || '-' }}</span>
                                    <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-50 shadow-inner">
                                    <div class="h-full bg-rose-500" :style="{ width: barWidth(row.total, maxOf(pendingByCauseBy)) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                        <p class="mb-4 text-[10px] font-black uppercase tracking-widest text-slate-400">By Level Customer</p>
                        <div class="space-y-4">
                            <div v-for="row in pendingByLevel" :key="row.label ?? 'null'" class="group">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-600">{{ row.label || '-' }}</span>
                                    <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-50 shadow-inner">
                                    <div class="h-full bg-amber-500" :style="{ width: barWidth(row.total, maxOf(pendingByLevel)) }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="rounded-[32px] border border-slate-100 bg-white p-8 shadow-sm">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-500">
                        <Layers class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Sub Case</h2>
                        <p class="text-[12px] font-medium text-slate-400">Seluruh kategori kasus pending</p>
                    </div>
                </div>
                <div class="grid gap-x-10 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="row in pendingBySubCase" :key="row.label ?? 'null'" class="group">
                        <div class="mb-1.5 flex items-start justify-between gap-3">
                            <span class="max-w-[200px] truncate text-xs font-black text-slate-600">{{ row.label || '-' }}</span>
                            <div class="flex items-center gap-1.5">
                                <span class="rounded-sm bg-emerald-50 px-1.5 text-[10px] font-bold text-emerald-600">{{ row.sla_ok }}</span>
                                <span class="rounded-sm bg-rose-50 px-1.5 text-[10px] font-bold text-rose-500">{{ row.sla_breach }}</span>
                                <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                            </div>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-50 shadow-inner">
                            <div class="h-full bg-violet-500" :style="{ width: barWidth(row.total, maxOf(pendingBySubCase)) }"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-[10px] font-bold text-slate-400">
                            <span>SLA OK</span>
                            <span>SLA Breach</span>
                        </div>
                    </div>
                    <p v-if="!pendingBySubCase.length" class="col-span-full text-sm text-slate-400">Tidak ada data sub case pending.</p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="rounded-[32px] border border-slate-100 bg-white p-8 shadow-sm lg:col-span-1">
                    <div class="mb-6 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-500">
                            <Footprints class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Last Step All</h2>
                            <p class="text-[12px] font-medium text-slate-400">Semua pending last step tercatat</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div v-for="row in pendingByLastStep" :key="row.label ?? 'null'" class="group">
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-black text-slate-700">{{ row.label || '(belum diisi)' }}</span>
                                <span class="text-sm font-black text-slate-500">{{ row.total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                <div class="h-full bg-orange-500" :style="{ width: barWidth(row.total, maxOf(pendingByLastStep)) }"></div>
                            </div>
                        </div>
                        <p v-if="!pendingByLastStep.length" class="text-sm text-slate-400">Tidak ada komplain pending dengan last step tercatat.</p>
                    </div>
                </section>

                <section class="rounded-[32px] border border-slate-100 bg-white p-8 shadow-sm">
                    <div class="mb-6">
                        <h2 class="text-lg font-black text-slate-900">Last Step External</h2>
                        <p class="text-[12px] font-medium text-slate-400">Step yang bergantung pada pihak luar</p>
                    </div>
                    <div class="space-y-4">
                        <div v-for="row in externalLastSteps" :key="row.label ?? 'null'">
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-black text-slate-700">{{ row.label || '-' }}</span>
                                <span class="text-sm font-black text-slate-500">{{ row.total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                <div class="h-full bg-orange-400" :style="{ width: barWidth(row.total, maxOf(externalLastSteps)) }"></div>
                            </div>
                        </div>
                        <p v-if="!externalLastSteps.length" class="text-sm text-slate-400">Belum ada data last step external.</p>
                    </div>
                </section>

                <section class="rounded-[32px] border border-slate-100 bg-white p-8 shadow-sm">
                    <div class="mb-6">
                        <h2 class="text-lg font-black text-slate-900">Last Step Internal</h2>
                        <p class="text-[12px] font-medium text-slate-400">Step yang bergantung pada tim internal</p>
                    </div>
                    <div class="space-y-4">
                        <div v-for="row in internalLastSteps" :key="row.label ?? 'null'">
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-black text-slate-700">{{ row.label || '-' }}</span>
                                <span class="text-sm font-black text-slate-500">{{ row.total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 shadow-inner">
                                <div class="h-full bg-violet-500" :style="{ width: barWidth(row.total, maxOf(internalLastSteps)) }"></div>
                            </div>
                        </div>
                        <p v-if="!internalLastSteps.length" class="text-sm text-slate-400">Belum ada data last step internal.</p>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
