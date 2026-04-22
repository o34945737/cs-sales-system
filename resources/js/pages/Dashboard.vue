<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    BarChart2,
    BoxesIcon,
    CheckCircle2,
    ChevronRight,
    CircleDot,
    ClipboardList,
    MessageSquare,
    RefreshCw,
    Star,
    Truck,
    UserCheck,
    X,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface LabelTotal {
    label: string | null;
    total: number;
}

interface SubCaseRow extends LabelTotal {
    sla_ok: number;
    sla_breach: number;
}

interface AgentRow {
    agent: string | null;
    total: number;
}

interface AgentRecapRow extends AgentRow {
    solved: number;
    pending: number;
}

interface BrandRealTimeRow {
    label: string | null;
    total: number;
    hard: number;
    normal: number;
}

interface WeeklyPoint {
    date: string;
    total?: number;
    new?: number;
    solved?: number;
}

interface DailyProductivity {
    id?: number;
    cs_name: string;
    tanggal: string;
    complaint_handled: number;
    complaint_solved: number;
    bad_review_handled: number;
    order_tracking_handled: number;
    oos_handled: number;
    total_ticket: number;
    notes: string;
}

const props = defineProps<{
    pendingComplaintCount: number;
    pendingOtCount: number;
    oosTodayCount: number;
    totalTaskCount: number;
    weeklyComplaint: WeeklyPoint[];
    weeklyBadReview: WeeklyPoint[];
    weeklyOos: WeeklyPoint[];
    pendingByCauseBy: LabelTotal[];
    pendingByPlatform: LabelTotal[];
    pendingByLevel: LabelTotal[];
    pendingBySubCase: SubCaseRow[];
    pendingByLastStepExt: LabelTotal[];
    pendingByLastStepInt: LabelTotal[];
    badReviewByBrand: LabelTotal[];
    badReviewByCategory: LabelTotal[];
    pendingOtByBrand: LabelTotal[];
    pendingOtByPlatform: LabelTotal[];
    pendingOtByLogistics: LabelTotal[];
    pendingOtByOrderDate: LabelTotal[];
    pendingOtByAutoTrack: LabelTotal[];
    pendingOtByDataSource: LabelTotal[];
    oosTodayNoDoneBlast: number;
    oosYesterdayNoDoneBlast: number;
    oosByBrand: LabelTotal[];
    agentDistributed: AgentRow[];
    agentHandled: AgentRow[];
    agentSolved: AgentRow[];
    agentRecap: AgentRecapRow[];
    productivity: DailyProductivity[];
    today: string;
    brandRealTime: BrandRealTimeRow[];
    agentInterface28: { solved: number; pending: number; whitelist: number };
    agentInterface29: LabelTotal[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
const page = usePage<SharedData>();

// ── Productivity form ─────────────────────────────────────────────────────
const showProductivityModal = ref(false);
const prodForm = reactive<DailyProductivity>({
    cs_name: '',
    tanggal: props.today,
    complaint_handled: 0,
    complaint_solved: 0,
    bad_review_handled: 0,
    order_tracking_handled: 0,
    oos_handled: 0,
    total_ticket: 0,
    notes: '',
});

function openProductivityModal(row?: DailyProductivity) {
    if (row) {
        Object.assign(prodForm, row);
    } else {
        Object.assign(prodForm, {
            cs_name: '',
            tanggal: props.today,
            complaint_handled: 0,
            complaint_solved: 0,
            bad_review_handled: 0,
            order_tracking_handled: 0,
            oos_handled: 0,
            total_ticket: 0,
            notes: '',
        });
    }
    showProductivityModal.value = true;
}

function submitProductivity() {
    router.post(route('dashboard.productivity.store'), prodForm as any, {
        onSuccess: () => { showProductivityModal.value = false; },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────
function barWidth(value: number, max: number): string {
    if (!max) return '0%';
    return Math.round((value / max) * 100) + '%';
}

function maxOf(arr: LabelTotal[]): number {
    return arr.reduce((m, r) => Math.max(m, r.total), 0) || 1;
}

function maxWeekly(arr: WeeklyPoint[], key: 'total' | 'new' | 'solved'): number {
    return arr.reduce((m, r) => Math.max(m, (r as any)[key] ?? 0), 0) || 1;
}

function shortDate(iso: string): string {
    return iso.slice(5); // MM-DD
}

function priorityClass(p: string | null): string {
    if (p === 'High') return 'bg-rose-50 text-rose-600';
    if (p === 'Medium') return 'bg-amber-50 text-amber-600';
    return 'bg-slate-50 text-slate-500';
}

function refreshPage() {
    router.reload({ only: [] });
}

const quickLinks = computed(() => {
    const links: { label: string; description: string; href: string }[] = [];
    if (page.props.auth.can.access_complaints)
        links.push({ label: 'Complaint Board', description: 'Track active cases and follow up.', href: '/complaints' });
    if (page.props.auth.can.access_bad_reviews)
        links.push({ label: 'Bad Reviews', description: 'Recover low ratings and customer trust.', href: '/bad-reviews' });
    if (page.props.auth.can.access_order_trackings)
        links.push({ label: 'Order Tracking', description: 'Monitor shipment and AWB progress.', href: '/order-trackings' });
    if (page.props.auth.can.access_oos)
        links.push({ label: 'OOS Monitoring', description: 'Handle stock issue escalation quickly.', href: '/oos' });
    return links;
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 pb-8">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-[var(--app-ink)]">Dashboard CS</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Real-time monitoring &amp; analytics</p>
                </div>
                <button
                    class="flex items-center gap-1.5 rounded-xl border border-[var(--app-border)] bg-white px-3 py-2 text-xs font-semibold text-slate-500 shadow-sm hover:bg-slate-50 transition"
                    @click="refreshPage"
                >
                    <RefreshCw class="h-3.5 w-3.5" /> Refresh
                </button>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 1-4: Summary Cards
            ──────────────────────────────────────────────────────────────── -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="app-grid-card flex items-center gap-3.5 px-4 py-4">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-rose-50">
                        <MessageSquare class="h-5 w-5 text-rose-500" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pending Complaint</p>
                        <p class="text-2xl font-black text-[var(--app-ink)]">{{ pendingComplaintCount }}</p>
                        <p class="text-[10px] text-slate-400">Real-time</p>
                    </div>
                </article>

                <article class="app-grid-card flex items-center gap-3.5 px-4 py-4">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-blue-50">
                        <Truck class="h-5 w-5 text-blue-500" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pending OT</p>
                        <p class="text-2xl font-black text-[var(--app-ink)]">{{ pendingOtCount }}</p>
                        <p class="text-[10px] text-slate-400">Real-time</p>
                    </div>
                </article>

                <article class="app-grid-card flex items-center gap-3.5 px-4 py-4">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-amber-50">
                        <BoxesIcon class="h-5 w-5 text-amber-500" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">OOS Hari Ini</p>
                        <p class="text-2xl font-black text-[var(--app-ink)]">{{ oosTodayCount }}</p>
                        <p class="text-[10px] text-slate-400">Today</p>
                    </div>
                </article>

                <article class="app-grid-card flex items-center gap-3.5 px-4 py-4">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-violet-50">
                        <ClipboardList class="h-5 w-5 text-violet-500" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Task Pending</p>
                        <p class="text-2xl font-black text-[var(--app-ink)]">{{ totalTaskCount }}</p>
                        <p class="text-[10px] text-slate-400">Sum of 1+2+3</p>
                    </div>
                </article>
            </section>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 5-7: Weekly Trend Charts (CSS bars)
            ──────────────────────────────────────────────────────────────── -->
            <section class="grid gap-4 md:grid-cols-3">
                <!-- #5 Complaint trend -->
                <div class="app-grid-card p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <BarChart2 class="h-4 w-4 text-rose-400" />
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">Trend 7 Hari (Incoming vs Solved)</p>
                    </div>
                    <div class="flex items-end gap-1 h-16">
                        <template v-for="(pt, i) in weeklyComplaint" :key="i">
                            <div class="flex-1 flex flex-col items-center gap-0.5">
                                <div class="w-full flex flex-col gap-0.5">
                                    <div
                                        class="w-full rounded-sm bg-rose-200 transition-all"
                                        :style="{ height: Math.max(2, Math.round(((pt.new ?? 0) / maxWeekly(weeklyComplaint, 'new')) * 48)) + 'px' }"
                                        :title="'Incoming: ' + pt.new"
                                    ></div>
                                    <div
                                        class="w-full rounded-sm bg-emerald-300 transition-all"
                                        :style="{ height: Math.max(2, Math.round(((pt.solved ?? 0) / maxWeekly(weeklyComplaint, 'solved')) * 48)) + 'px' }"
                                        :title="'Solved: ' + pt.solved"
                                    ></div>
                                </div>
                                <span class="text-[9px] text-slate-300">{{ shortDate(pt.date) }}</span>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center gap-3 mt-2 text-[10px] text-slate-400">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-rose-200 inline-block"></span>Incoming</span>
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-emerald-300 inline-block"></span>Solved</span>
                    </div>
                </div>

                <!-- #6 Bad Review trend -->
                <div class="app-grid-card p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <BarChart2 class="h-4 w-4 text-violet-400" />
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">Bad Review 7 Hari</p>
                    </div>
                    <div class="flex items-end gap-1 h-16">
                        <template v-for="(pt, i) in weeklyBadReview" :key="i">
                            <div class="flex-1 flex flex-col items-center gap-0.5">
                                <div
                                    class="w-full rounded-sm bg-violet-200 transition-all"
                                    :style="{ height: Math.max(2, Math.round(((pt.total ?? 0) / maxWeekly(weeklyBadReview, 'total')) * 56)) + 'px' }"
                                    :title="String(pt.total)"
                                ></div>
                                <span class="text-[9px] text-slate-300">{{ shortDate(pt.date) }}</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- #7 OOS trend -->
                <div class="app-grid-card p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <BarChart2 class="h-4 w-4 text-amber-400" />
                        <p class="text-xs font-black uppercase tracking-wider text-slate-500">OOS 7 Hari</p>
                    </div>
                    <div class="flex items-end gap-1 h-16">
                        <template v-for="(pt, i) in weeklyOos" :key="i">
                            <div class="flex-1 flex flex-col items-center gap-0.5">
                                <div
                                    class="w-full rounded-sm bg-amber-200 transition-all"
                                    :style="{ height: Math.max(2, Math.round(((pt.total ?? 0) / maxWeekly(weeklyOos, 'total')) * 56)) + 'px' }"
                                    :title="String(pt.total)"
                                ></div>
                                <span class="text-[9px] text-slate-300">{{ shortDate(pt.date) }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION A: Brand Real Time Controller
            ──────────────────────────────────────────────────────────────── -->
            <section class="app-table-shell overflow-hidden">
                <div class="border-b border-[var(--app-border)] px-5 py-4 flex items-center gap-2">
                    <Activity class="h-4 w-4 text-rose-500" />
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-rose-500">Controller Brand Real Time</p>
                        <h2 class="text-base font-black text-[var(--app-ink)]">Pending per Brand &amp; Level Customer</h2>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] text-[10px] font-black uppercase tracking-wider text-slate-400">
                                <th class="px-4 py-2.5 text-left">Brand</th>
                                <th class="px-4 py-2.5 text-center">Total</th>
                                <th class="px-4 py-2.5 text-center">Hard Complaint</th>
                                <th class="px-4 py-2.5 text-center">Normal Complaint</th>
                                <th class="px-4 py-2.5 text-left">Proporsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!brandRealTime.length">
                                <td colspan="5" class="px-4 py-6 text-center text-xs text-slate-400">Tidak ada pending complaint</td>
                            </tr>
                            <tr
                                v-for="row in brandRealTime"
                                :key="row.label ?? 'null'"
                                class="border-b border-slate-50 hover:bg-slate-50/60"
                            >
                                <td class="px-4 py-2.5 font-semibold text-[var(--app-ink)]">{{ row.label || '-' }}</td>
                                <td class="px-4 py-2.5 text-center font-black">{{ row.total }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <span v-if="row.hard" class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-600">{{ row.hard }}</span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span v-if="row.normal" class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-600">{{ row.normal }}</span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>
                                <td class="px-4 py-2.5 w-36">
                                    <div class="flex h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div class="bg-rose-400 rounded-l-full" :style="{ width: barWidth(row.hard, row.total) }"></div>
                                        <div class="bg-blue-400" :style="{ width: barWidth(row.normal, row.total) }"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION B: Agent Interface
            ──────────────────────────────────────────────────────────────── -->
            <section class="grid gap-4 md:grid-cols-2">
                <!-- B28 -->
                <div class="app-grid-card p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Interface Agent — Totals</p>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-emerald-50 px-3 py-4 text-center">
                            <p class="text-2xl font-black text-emerald-600">{{ agentInterface28.solved }}</p>
                            <p class="text-[10px] font-bold text-emerald-400 mt-1">Solved Today</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 px-3 py-4 text-center">
                            <p class="text-2xl font-black text-amber-600">{{ agentInterface28.pending }}</p>
                            <p class="text-[10px] font-bold text-amber-400 mt-1">Pending</p>
                        </div>
                        <div class="rounded-xl bg-rose-50 px-3 py-4 text-center">
                            <p class="text-2xl font-black text-rose-600">{{ agentInterface28.whitelist }}</p>
                            <p class="text-[10px] font-bold text-rose-400 mt-1">Whitelist</p>
                        </div>
                    </div>
                </div>

                <!-- B29 -->
                <div class="app-grid-card p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Pending by Priority</p>
                    <div class="space-y-2">
                        <div
                            v-for="row in agentInterface29"
                            :key="row.label ?? 'null'"
                            class="flex items-center justify-between rounded-lg px-3 py-2"
                            :class="priorityClass(row.label)"
                        >
                            <span class="text-xs font-bold">{{ row.label || 'Tidak ada priority' }}</span>
                            <span class="text-sm font-black">{{ row.total }}</span>
                        </div>
                        <div v-if="!agentInterface29.length" class="text-xs text-slate-400 text-center py-4">Kosong</div>
                    </div>
                </div>
            </section>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 8-12: Pending Complaint Analysis
            ──────────────────────────────────────────────────────────────── -->
            <div>
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-3">Analisa Pending Complaint</h2>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                    <!-- #8 By Cause By -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Cause By (Penyebab)</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingByCauseBy" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-rose-400" :style="{ width: barWidth(row.total, maxOf(pendingByCauseBy)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingByCauseBy.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #8.1 By Platform -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Platform</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingByPlatform" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-blue-400" :style="{ width: barWidth(row.total, maxOf(pendingByPlatform)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingByPlatform.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #9 By Level Customer -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Level Customer</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingByLevel" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div
                                            class="h-1.5 rounded-full"
                                            :class="row.label === 'Hard Complaint' ? 'bg-rose-500' : 'bg-blue-400'"
                                            :style="{ width: barWidth(row.total, maxOf(pendingByLevel)) }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingByLevel.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #10 By Sub Case + SLA -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Sub Case &amp; SLA</p>
                        <div class="space-y-1">
                            <div v-for="row in pendingBySubCase" :key="row.label ?? 'null'">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-[var(--app-ink)] truncate flex-1">{{ row.label || '-' }}</span>
                                    <div class="flex items-center gap-1 ml-2 flex-shrink-0">
                                        <span class="rounded-sm bg-emerald-50 px-1.5 text-[10px] font-bold text-emerald-600">{{ row.sla_ok }}</span>
                                        <span class="rounded-sm bg-rose-50 px-1.5 text-[10px] font-bold text-rose-500">{{ row.sla_breach }}</span>
                                        <span class="text-xs font-black text-slate-400">{{ row.total }}</span>
                                    </div>
                                </div>
                                <div class="h-1 w-full rounded-full bg-slate-100 mt-0.5">
                                    <div class="h-1 rounded-full bg-indigo-400" :style="{ width: barWidth(row.total, maxOf(pendingBySubCase)) }"></div>
                                </div>
                            </div>
                            <div v-if="!pendingBySubCase.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #11 Last Step External -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Last Step External</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingByLastStepExt" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs text-[var(--app-ink)] truncate" :title="row.label ?? ''">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-orange-400" :style="{ width: barWidth(row.total, maxOf(pendingByLastStepExt)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingByLastStepExt.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #12 Last Step Internal -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Last Step Internal</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingByLastStepInt" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs text-[var(--app-ink)] truncate" :title="row.label ?? ''">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-violet-400" :style="{ width: barWidth(row.total, maxOf(pendingByLastStepInt)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingByLastStepInt.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 13-14: Bad Review Bulan Ini
            ──────────────────────────────────────────────────────────────── -->
            <div>
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-3">Bad Review Bulan Ini</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- #13 By Brand -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Brand</p>
                        <div class="space-y-1.5">
                            <div v-for="row in badReviewByBrand" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-violet-400" :style="{ width: barWidth(row.total, maxOf(badReviewByBrand)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!badReviewByBrand.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #14 By Category -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Category Review</p>
                        <div class="space-y-1.5">
                            <div v-for="row in badReviewByCategory" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-pink-400" :style="{ width: barWidth(row.total, maxOf(badReviewByCategory)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!badReviewByCategory.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 15-20: Pending Order Tracking
            ──────────────────────────────────────────────────────────────── -->
            <div>
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-3">Pending Order Tracking</h2>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                    <!-- #15 By Brand -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Brand</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingOtByBrand" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-blue-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByBrand)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingOtByBrand.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #16 By Platform -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Platform</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingOtByPlatform" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-cyan-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByPlatform)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingOtByPlatform.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #17 By Logistics -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Logistics</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingOtByLogistics" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-teal-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByLogistics)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingOtByLogistics.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #18 By Order Date -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Tanggal Order (Top 10)</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingOtByOrderDate" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-sky-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByOrderDate)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingOtByOrderDate.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #19 By Auto Track -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Auto Track</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingOtByAutoTrack" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || 'Belum' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-indigo-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByAutoTrack)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingOtByAutoTrack.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #20 By Data Source -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">By Data Source</p>
                        <div class="space-y-1.5">
                            <div v-for="row in pendingOtByDataSource" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-fuchsia-400" :style="{ width: barWidth(row.total, maxOf(pendingOtByDataSource)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!pendingOtByDataSource.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 21-22: OOS
            ──────────────────────────────────────────────────────────────── -->
            <div>
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-3">OOS Monitoring</h2>
                <div class="grid gap-4 md:grid-cols-2">

                    <!-- #21 OOS Today vs Yesterday -->
                    <div class="app-grid-card p-5">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-3">OOS Tanpa Done Blast</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-amber-50 px-4 py-4 text-center">
                                <p class="text-2xl font-black text-amber-600">{{ oosTodayNoDoneBlast }}</p>
                                <p class="text-[10px] font-bold text-amber-400 mt-1">Hari Ini</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-4 text-center">
                                <p class="text-2xl font-black text-slate-500">{{ oosYesterdayNoDoneBlast }}</p>
                                <p class="text-[10px] font-bold text-slate-400 mt-1">Kemarin</p>
                            </div>
                        </div>
                    </div>

                    <!-- #22 OOS by Brand this month -->
                    <div class="app-grid-card p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">OOS Bulan Ini by Brand</p>
                        <div class="space-y-1.5">
                            <div v-for="row in oosByBrand" :key="row.label ?? 'null'" class="flex items-center gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.label || '-' }}</span>
                                        <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100">
                                        <div class="h-1.5 rounded-full bg-amber-400" :style="{ width: barWidth(row.total, maxOf(oosByBrand)) }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!oosByBrand.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 23-26: Agent Performance
            ──────────────────────────────────────────────────────────────── -->
            <div>
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-3">Agent Performance</h2>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    <!-- #23 Distributed today -->
                    <div class="app-grid-card p-4">
                        <div class="flex items-center gap-1.5 mb-2">
                            <UserCheck class="h-3.5 w-3.5 text-blue-400" />
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Distributed Hari Ini</p>
                        </div>
                        <div class="space-y-1">
                            <div v-for="row in agentDistributed" :key="row.agent ?? 'null'" class="flex items-center justify-between rounded-lg px-2 py-1.5 bg-slate-50">
                                <span class="text-xs text-[var(--app-ink)] truncate">{{ row.agent || 'Unassigned' }}</span>
                                <span class="text-xs font-black text-blue-600 ml-2">{{ row.total }}</span>
                            </div>
                            <div v-if="!agentDistributed.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #24 Handled (pending) -->
                    <div class="app-grid-card p-4">
                        <div class="flex items-center gap-1.5 mb-2">
                            <Activity class="h-3.5 w-3.5 text-amber-400" />
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Sedang Ditangani</p>
                        </div>
                        <div class="space-y-1">
                            <div v-for="row in agentHandled" :key="row.agent ?? 'null'" class="flex items-center justify-between rounded-lg px-2 py-1.5 bg-slate-50">
                                <span class="text-xs text-[var(--app-ink)] truncate">{{ row.agent || 'Unassigned' }}</span>
                                <span class="text-xs font-black text-amber-600 ml-2">{{ row.total }}</span>
                            </div>
                            <div v-if="!agentHandled.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #25 Solved today -->
                    <div class="app-grid-card p-4">
                        <div class="flex items-center gap-1.5 mb-2">
                            <CheckCircle2 class="h-3.5 w-3.5 text-emerald-400" />
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Solved Hari Ini</p>
                        </div>
                        <div class="space-y-1">
                            <div v-for="row in agentSolved" :key="row.agent ?? 'null'" class="flex items-center justify-between rounded-lg px-2 py-1.5 bg-slate-50">
                                <span class="text-xs text-[var(--app-ink)] truncate">{{ row.agent || 'Unassigned' }}</span>
                                <span class="text-xs font-black text-emerald-600 ml-2">{{ row.total }}</span>
                            </div>
                            <div v-if="!agentSolved.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>

                    <!-- #26 Recap this month -->
                    <div class="app-grid-card p-4">
                        <div class="flex items-center gap-1.5 mb-2">
                            <Star class="h-3.5 w-3.5 text-violet-400" />
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Recap Bulan Ini</p>
                        </div>
                        <div class="space-y-1">
                            <div v-for="row in agentRecap" :key="row.agent ?? 'null'" class="rounded-lg bg-slate-50 px-2 py-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-[var(--app-ink)] truncate">{{ row.agent || 'Unassigned' }}</span>
                                    <span class="text-xs font-black text-slate-500 ml-2">{{ row.total }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[9px] text-emerald-500 font-bold">✓ {{ row.solved }}</span>
                                    <span class="text-[9px] text-amber-500 font-bold">⏳ {{ row.pending }}</span>
                                </div>
                            </div>
                            <div v-if="!agentRecap.length" class="text-xs text-slate-400 text-center py-3">Tidak ada data</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 SECTION 27: Daily Productivity
            ──────────────────────────────────────────────────────────────── -->
            <div class="app-table-shell overflow-hidden">
                <div class="border-b border-[var(--app-border)] px-5 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Produktivitas Harian</p>
                        <h2 class="text-base font-black text-[var(--app-ink)]">Input Produktivitas CS — {{ today }}</h2>
                    </div>
                    <button
                        class="rounded-xl bg-[var(--app-primary)] px-3 py-2 text-xs font-bold text-white shadow-sm hover:opacity-90 transition"
                        @click="openProductivityModal()"
                    >
                        + Tambah
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--app-border)] text-[10px] font-black uppercase tracking-wider text-slate-400">
                                <th class="px-4 py-2.5 text-left">Nama CS</th>
                                <th class="px-3 py-2.5 text-center">Complaint Handled</th>
                                <th class="px-3 py-2.5 text-center">Complaint Solved</th>
                                <th class="px-3 py-2.5 text-center">Bad Review</th>
                                <th class="px-3 py-2.5 text-center">OT</th>
                                <th class="px-3 py-2.5 text-center">OOS</th>
                                <th class="px-3 py-2.5 text-center">Total Ticket</th>
                                <th class="px-4 py-2.5 text-left">Notes</th>
                                <th class="px-4 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!productivity.length">
                                <td colspan="9" class="px-4 py-8 text-center text-xs text-slate-400">
                                    <AlertCircle class="mx-auto h-8 w-8 text-slate-200 mb-2" />
                                    Belum ada data produktivitas hari ini
                                </td>
                            </tr>
                            <tr
                                v-for="row in productivity"
                                :key="row.id"
                                class="border-b border-slate-50 hover:bg-slate-50/60"
                            >
                                <td class="px-4 py-2.5 font-semibold text-[var(--app-ink)]">{{ row.cs_name }}</td>
                                <td class="px-3 py-2.5 text-center">{{ row.complaint_handled }}</td>
                                <td class="px-3 py-2.5 text-center">{{ row.complaint_solved }}</td>
                                <td class="px-3 py-2.5 text-center">{{ row.bad_review_handled }}</td>
                                <td class="px-3 py-2.5 text-center">{{ row.order_tracking_handled }}</td>
                                <td class="px-3 py-2.5 text-center">{{ row.oos_handled }}</td>
                                <td class="px-3 py-2.5 text-center font-black text-[var(--app-ink)]">{{ row.total_ticket }}</td>
                                <td class="px-4 py-2.5 text-xs text-slate-400 max-w-[160px] truncate">{{ row.notes || '-' }}</td>
                                <td class="px-4 py-2.5">
                                    <button
                                        class="text-xs font-semibold text-[var(--app-primary)] hover:underline"
                                        @click="openProductivityModal(row)"
                                    >Edit</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
                 Quick Links
            ──────────────────────────────────────────────────────────────── -->
            <section class="app-table-shell overflow-hidden">
                <div class="border-b border-[var(--app-border)] px-5 py-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Quick Access</p>
                </div>
                <div class="flex flex-wrap gap-2 p-4">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.label"
                        :href="link.href"
                        class="group flex items-center gap-1.5 rounded-xl border border-slate-100 bg-white px-4 py-2.5 text-sm font-semibold text-[var(--app-ink)] shadow-sm hover:border-[var(--app-primary)]/30 hover:bg-[var(--app-primary-soft)] transition"
                    >
                        {{ link.label }}
                        <ChevronRight class="h-3.5 w-3.5 text-slate-400 group-hover:text-[var(--app-primary)]" />
                    </Link>
                </div>
            </section>

        </div>

        <!-- ─────────────────────────────────────────────────────────────────
             Productivity Modal
        ──────────────────────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showProductivityModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showProductivityModal = false"></div>
                <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl mx-4">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="text-base font-black text-[var(--app-ink)]">Input Produktivitas</h3>
                        <button class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" @click="showProductivityModal = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form class="px-6 py-5 space-y-4" @submit.prevent="submitProductivity">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Nama CS</label>
                                <input v-model="prodForm.cs_name" type="text" class="app-input w-full" placeholder="Nama CS" required />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Tanggal</label>
                                <input v-model="prodForm.tanggal" type="date" class="app-input w-full" required />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Complaint Handled</label>
                                <input v-model.number="prodForm.complaint_handled" type="number" min="0" class="app-input w-full" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Complaint Solved</label>
                                <input v-model.number="prodForm.complaint_solved" type="number" min="0" class="app-input w-full" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Bad Review</label>
                                <input v-model.number="prodForm.bad_review_handled" type="number" min="0" class="app-input w-full" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Order Tracking</label>
                                <input v-model.number="prodForm.order_tracking_handled" type="number" min="0" class="app-input w-full" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">OOS Handled</label>
                                <input v-model.number="prodForm.oos_handled" type="number" min="0" class="app-input w-full" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Total Ticket</label>
                                <input v-model.number="prodForm.total_ticket" type="number" min="0" class="app-input w-full" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1">Notes</label>
                                <textarea v-model="prodForm.notes" class="app-input w-full" rows="2" placeholder="Catatan (opsional)"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-50 transition" @click="showProductivityModal = false">Batal</button>
                            <button type="submit" class="rounded-xl bg-[var(--app-primary)] px-5 py-2 text-sm font-bold text-white shadow-sm hover:opacity-90 transition">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
