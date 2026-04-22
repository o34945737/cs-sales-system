<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    BoxesIcon,
    CheckCircle2,
    Clock,
    LayoutDashboard,
    MessageSquare,
    RefreshCw,
    Truck,
    UserCheck,
} from 'lucide-vue-next';

interface AgentRecapRow {
    agent: string | null;
    total: number;
    solved: number;
    pending: number;
}

const props = defineProps<{
    pendingComplaintCount: number;
    pendingOtCount: number;
    oosTodayCount: number;
    totalTaskCount: number;
    agentRecap: AgentRecapRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Overview', href: '/dashboard' }
];

function refreshPage() {
    router.reload({ only: [] });
}

function getInitials(name: string | null) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}
</script>

<template>
    <Head title="Dashboard Overview" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 pb-10">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20">
                        <LayoutDashboard class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">Dashboard Overview</h1>
                        <p class="text-[13px] font-medium text-slate-500">Real-time system monitoring & agent productivity</p>
                    </div>
                </div>
                <button
                    class="group flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 active:scale-95"
                    @click="refreshPage"
                >
                    <RefreshCw class="h-3.5 w-3.5 transition-transform group-hover:rotate-180" />
                    <span>Refresh Data</span>
                </button>
            </div>

            <!-- Summary Cards -->
            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="group relative overflow-hidden rounded-[32px] border border-rose-100 bg-white p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-rose-500/5">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-rose-50 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/30">
                            <MessageSquare class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-rose-500/70">Pending Complaint</p>
                            <h3 class="mt-1 text-4xl font-black text-slate-900">{{ pendingComplaintCount }}</h3>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400">
                            <Clock class="h-3 w-3" /> Real-time tracking
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-[32px] border border-blue-100 bg-white p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-blue-500/5">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-blue-50 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-lg shadow-blue-500/30">
                            <Truck class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-blue-500/70">Pending OT</p>
                            <h3 class="mt-1 text-4xl font-black text-slate-900">{{ pendingOtCount }}</h3>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400">
                            <Clock class="h-3 w-3" /> Shipping progress
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-[32px] border border-amber-100 bg-white p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-amber-500/5">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-amber-50 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/30">
                            <BoxesIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-amber-500/70">OOS Hari Ini</p>
                            <h3 class="mt-1 text-4xl font-black text-slate-900">{{ oosTodayCount }}</h3>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400">
                            <Clock class="h-3 w-3" /> Stock incidents
                        </div>
                    </div>
                </div>

                <div class="group relative overflow-hidden rounded-[32px] border border-indigo-100 bg-slate-900 p-6 shadow-sm transition-all hover:shadow-xl hover:shadow-indigo-500/10">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/5 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500 text-white shadow-lg shadow-indigo-500/30">
                            <CheckCircle2 class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-indigo-400/80">Total Task Pending</p>
                            <h3 class="mt-1 text-4xl font-black text-white">{{ totalTaskCount }}</h3>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500">
                            <CheckCircle2 class="h-3 w-3" /> Sum of 1+2+3
                        </div>
                    </div>
                </div>
            </section>

            <!-- Agent Recap -->
            <div class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-base font-black text-slate-900">Recap Agen Bulan Ini</h2>
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-50 text-violet-500">
                        <UserCheck class="h-4 w-4" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div
                        v-for="row in agentRecap"
                        :key="row.agent ?? 'null'"
                        class="rounded-2xl border border-slate-50 bg-slate-50/30 p-4 transition hover:border-blue-100 hover:bg-blue-50/20"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[10px] font-black text-slate-400 shadow-sm">
                                    {{ getInitials(row.agent) }}
                                </div>
                                <span class="text-sm font-black text-slate-700">{{ row.agent || 'Unassigned' }}</span>
                            </div>
                            <span class="text-[11px] font-black text-slate-400">{{ row.total }} Tiket</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex flex-col rounded-xl bg-white p-2 shadow-sm">
                                <span class="text-[9px] font-black uppercase tracking-wider text-emerald-400">Solved</span>
                                <span class="text-lg font-black text-emerald-600">{{ row.solved }}</span>
                            </div>
                            <div class="flex flex-col rounded-xl bg-white p-2 shadow-sm">
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-400">Pending</span>
                                <span class="text-lg font-black text-amber-600">{{ row.pending }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="!agentRecap.length" class="col-span-full py-10 text-center text-xs font-bold text-slate-400">
                        Belum ada aktivitas bulan ini
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
