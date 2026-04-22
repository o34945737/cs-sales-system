<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, RefreshCw, ShieldCheck, UserCheck } from 'lucide-vue-next';

interface DailySolvedRow {
    date: string;
    count: number;
}

interface AgentInterfaceRow {
    agent: string;
    pending: number;
    whitelist: number;
    solved_total: number;
    daily_solved: DailySolvedRow[];
}

interface PriorityRow {
    priority: string | null;
    total: number;
}

interface AgentPriorityRow {
    agent: string;
    priorities: PriorityRow[];
}

const props = defineProps<{
    agentInterface: AgentInterfaceRow[];
    pendingByAgentPriority: AgentPriorityRow[];
    dates: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Agent Interface', href: '/dashboard/agents' },
];

function refreshPage() {
    router.reload({ only: [] });
}

function shortDate(iso: string): string {
    const date = new Date(iso);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
}

function barHeight(value: number, max: number): string {
    if (!max) return '8px';
    return Math.max(8, Math.round((value / max) * 92)) + 'px';
}

function maxDaily(rows: DailySolvedRow[]): number {
    return rows.reduce((max, row) => Math.max(max, row.count), 0) || 1;
}
</script>

<template>
    <Head title="Agent Interface" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 pb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-[var(--app-ink)]">Agent Interface</h1>
                    <p class="mt-0.5 text-xs text-slate-400">Solved trend 7 hari dan pending priority per agent</p>
                </div>
                <button
                    class="flex items-center gap-1.5 rounded-xl border border-[var(--app-border)] bg-white px-3 py-2 text-xs font-semibold text-slate-500 shadow-sm transition hover:bg-slate-50"
                    @click="refreshPage"
                >
                    <RefreshCw class="h-3.5 w-3.5" /> Refresh
                </button>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
                <section class="app-table-shell overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-[var(--app-border)] px-5 py-4">
                        <UserCheck class="h-4 w-4 text-[var(--app-primary)]" />
                        <div>
                            <h2 class="text-base font-black text-[var(--app-ink)]">Solved Trend Per Agent</h2>
                            <p class="text-[11px] font-medium text-slate-400">7 hari terakhir dan status complaint aktif</p>
                        </div>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        <article v-for="row in agentInterface" :key="row.agent" class="rounded-[24px] border border-slate-100 bg-white p-4 shadow-sm">
                            <div class="mb-4 flex items-start justify-between">
                                <div>
                                    <h3 class="text-sm font-black text-slate-900">{{ row.agent }}</h3>
                                    <p class="text-[11px] font-medium text-slate-400">Status complaint by agent</p>
                                </div>
                                <div class="rounded-full bg-slate-900 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-white">
                                    {{ row.solved_total }} solved
                                </div>
                            </div>

                            <div class="mb-4 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl bg-amber-50 px-3 py-3">
                                    <div class="text-lg font-black text-amber-600">{{ row.pending }}</div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-amber-400">Pending</div>
                                </div>
                                <div class="rounded-xl bg-rose-50 px-3 py-3">
                                    <div class="text-lg font-black text-rose-600">{{ row.whitelist }}</div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-rose-400">Whitelist</div>
                                </div>
                                <div class="rounded-xl bg-emerald-50 px-3 py-3">
                                    <div class="text-lg font-black text-emerald-600">{{ row.solved_total }}</div>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Solved</div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center gap-2">
                                    <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400">Daily Solved</p>
                                </div>
                                <div class="flex h-32 items-end gap-2">
                                    <div v-for="day in row.daily_solved" :key="day.date" class="flex flex-1 flex-col items-center gap-1">
                                        <div class="w-full rounded-md bg-emerald-300" :style="{ height: barHeight(day.count, maxDaily(row.daily_solved)) }"></div>
                                        <span class="text-[9px] font-bold text-slate-400">{{ shortDate(day.date) }}</span>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <div v-if="!agentInterface.length" class="col-span-full py-10 text-center text-xs font-bold text-slate-400">
                            Belum ada data agent interface
                        </div>
                    </div>
                </section>

                <section class="app-table-shell overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-[var(--app-border)] px-5 py-4">
                        <ShieldCheck class="h-4 w-4 text-violet-500" />
                        <div>
                            <h2 class="text-base font-black text-[var(--app-ink)]">Pending by Priority</h2>
                            <p class="text-[11px] font-medium text-slate-400">Mapping priority last step per agent</p>
                        </div>
                    </div>

                    <div class="space-y-4 p-5">
                        <article v-for="row in pendingByAgentPriority" :key="row.agent" class="rounded-[24px] border border-slate-100 bg-slate-50/50 p-4">
                            <div class="mb-3 text-sm font-black text-slate-900">{{ row.agent }}</div>
                            <div class="space-y-3">
                                <div v-for="priority in row.priorities" :key="`${row.agent}-${priority.priority}`" class="flex items-center gap-3">
                                    <span class="w-14 rounded-full bg-white px-2 py-1 text-center text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        {{ priority.priority || '-' }}
                                    </span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-white">
                                        <div class="h-full rounded-full bg-violet-500" :style="{ width: `${Math.max(8, priority.total * 18)}px` }"></div>
                                    </div>
                                    <span class="w-8 text-right text-xs font-black text-slate-500">{{ priority.total }}</span>
                                </div>
                            </div>
                        </article>
                        <div v-if="!pendingByAgentPriority.length" class="py-10 text-center text-xs font-bold text-slate-400">
                            Belum ada pending priority per agent
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
