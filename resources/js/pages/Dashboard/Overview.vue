<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { BoxesIcon, CheckCircle2, Clock, LayoutDashboard, MessageSquare, RefreshCw, Truck, UserCheck, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface AgentRecapRow {
    agent: string | null;
    distributed: number;
    handled: number;
    solved: number;
    productivity_total: number;
    productivity_entries: number;
}

interface AgentDdayRow {
    agent: string;
    dist_complaint: number;
    dist_bad_review: number;
    dist_ot: number;
    dist_oos: number;
    dist_total: number;
    handled_complaint: number;
    handled_bad_review: number;
    handled_ot: number;
    handled_oos: number;
    handled_total: number;
    solved_complaint: number;
    solved_bad_review: number;
    solved_ot: number;
    solved_total: number;
}

interface DailyProductivityRow {
    id: number;
    cs_name: string;
    tanggal: string;
    complaint_handled: number;
    complaint_solved: number;
    bad_review_handled: number;
    order_tracking_handled: number;
    oos_handled: number;
    total_ticket: number;
    notes: string | null;
}

const props = defineProps<{
    pendingComplaintCount: number;
    pendingOtCount: number;
    oosTodayCount: number;
    totalTaskCount: number;
    agentDdayStats: AgentDdayRow[];
    agentRecap: AgentRecapRow[];
    todayProductivity: DailyProductivityRow[];
    today: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Overview', href: '/dashboard' },
];

const showProductivityModal = ref(false);

const productivityForm = useForm({
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

const summaryCards = computed(() => [
    {
        label: 'Pending Complaint',
        value: props.pendingComplaintCount,
        helper: 'Real-time tracking',
        icon: MessageSquare,
        tone: 'border-rose-100',
        iconTone: 'bg-rose-500 shadow-rose-500/30',
        textTone: 'text-rose-500/70',
    },
    {
        label: 'Pending OT',
        value: props.pendingOtCount,
        helper: 'Shipping progress',
        icon: Truck,
        tone: 'border-blue-100',
        iconTone: 'bg-blue-500 shadow-blue-500/30',
        textTone: 'text-blue-500/70',
    },
    {
        label: 'OOS Hari Ini',
        value: props.oosTodayCount,
        helper: 'Stock incidents',
        icon: BoxesIcon,
        tone: 'border-amber-100',
        iconTone: 'bg-amber-500 shadow-amber-500/30',
        textTone: 'text-amber-500/70',
    },
    {
        label: 'Total Task Pending',
        value: props.totalTaskCount,
        helper: 'Complaint + OT + OOS',
        icon: CheckCircle2,
        tone: 'border-indigo-100 bg-slate-900',
        iconTone: 'bg-indigo-500 shadow-indigo-500/30',
        textTone: 'text-indigo-300/80',
    },
]);

function refreshPage() {
    router.reload({ only: [] });
}

function getInitials(name: string | null) {
    if (!name) return '?';
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function openProductivityModal(row?: DailyProductivityRow) {
    productivityForm.defaults({
        cs_name: row?.cs_name ?? '',
        tanggal: row?.tanggal ?? props.today,
        complaint_handled: row?.complaint_handled ?? 0,
        complaint_solved: row?.complaint_solved ?? 0,
        bad_review_handled: row?.bad_review_handled ?? 0,
        order_tracking_handled: row?.order_tracking_handled ?? 0,
        oos_handled: row?.oos_handled ?? 0,
        total_ticket: row?.total_ticket ?? 0,
        notes: row?.notes ?? '',
    });
    productivityForm.reset();
    productivityForm.clearErrors();
    showProductivityModal.value = true;
}

function submitProductivity() {
    productivityForm.post(route('dashboard.productivity.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showProductivityModal.value = false;
        },
    });
}
</script>

<template>
    <Head title="Dashboard Overview" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 pb-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20">
                        <LayoutDashboard class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">Dashboard Overview</h1>
                        <p class="text-[13px] font-medium text-slate-500">Real-time system monitoring and agent productivity</p>
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

            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="group relative overflow-hidden rounded-[32px] border p-6 shadow-sm transition-all hover:shadow-xl"
                    :class="card.tone"
                >
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-slate-50 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-lg" :class="card.iconTone">
                            <component :is="card.icon" class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest" :class="card.textTone">{{ card.label }}</p>
                            <h3 class="mt-1 text-4xl font-black" :class="card.label === 'Total Task Pending' ? 'text-white' : 'text-slate-900'">{{ card.value }}</h3>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold" :class="card.label === 'Total Task Pending' ? 'text-slate-500' : 'text-slate-400'">
                            <Clock class="h-3 w-3" /> {{ card.helper }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Performa Agen Hari Ini</h2>
                        <p class="text-[12px] font-medium text-slate-400">Distribusi, handled, dan solved per agent</p>
                    </div>
                    <div class="rounded-xl bg-blue-50 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-blue-600">
                        {{ today }}
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-4 py-3 text-left">Agent</th>
                                <th class="px-4 py-3 text-center">Distributed</th>
                                <th class="px-4 py-3 text-center">Handled</th>
                                <th class="px-4 py-3 text-center">Solved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in agentDdayStats" :key="row.agent" class="border-b border-slate-50">
                                <td class="px-4 py-3 font-black text-slate-800">{{ row.agent }}</td>
                                <td class="px-4 py-3">
                                    <div class="grid grid-cols-5 gap-2 text-center text-[11px]">
                                        <div class="rounded-xl bg-rose-50 px-2 py-2">
                                            <div class="font-black text-rose-600">{{ row.dist_complaint }}</div>
                                            <div class="text-slate-400">C</div>
                                        </div>
                                        <div class="rounded-xl bg-violet-50 px-2 py-2">
                                            <div class="font-black text-violet-600">{{ row.dist_bad_review }}</div>
                                            <div class="text-slate-400">BR</div>
                                        </div>
                                        <div class="rounded-xl bg-blue-50 px-2 py-2">
                                            <div class="font-black text-blue-600">{{ row.dist_ot }}</div>
                                            <div class="text-slate-400">OT</div>
                                        </div>
                                        <div class="rounded-xl bg-amber-50 px-2 py-2">
                                            <div class="font-black text-amber-600">{{ row.dist_oos }}</div>
                                            <div class="text-slate-400">OOS</div>
                                        </div>
                                        <div class="rounded-xl bg-slate-900 px-2 py-2 text-white">
                                            <div class="font-black">{{ row.dist_total }}</div>
                                            <div class="text-slate-400">All</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="grid grid-cols-5 gap-2 text-center text-[11px]">
                                        <div class="rounded-xl bg-rose-50 px-2 py-2">
                                            <div class="font-black text-rose-600">{{ row.handled_complaint }}</div>
                                            <div class="text-slate-400">C</div>
                                        </div>
                                        <div class="rounded-xl bg-violet-50 px-2 py-2">
                                            <div class="font-black text-violet-600">{{ row.handled_bad_review }}</div>
                                            <div class="text-slate-400">BR</div>
                                        </div>
                                        <div class="rounded-xl bg-blue-50 px-2 py-2">
                                            <div class="font-black text-blue-600">{{ row.handled_ot }}</div>
                                            <div class="text-slate-400">OT</div>
                                        </div>
                                        <div class="rounded-xl bg-amber-50 px-2 py-2">
                                            <div class="font-black text-amber-600">{{ row.handled_oos }}</div>
                                            <div class="text-slate-400">OOS</div>
                                        </div>
                                        <div class="rounded-xl bg-slate-900 px-2 py-2 text-white">
                                            <div class="font-black">{{ row.handled_total }}</div>
                                            <div class="text-slate-400">All</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="grid grid-cols-4 gap-2 text-center text-[11px]">
                                        <div class="rounded-xl bg-rose-50 px-2 py-2">
                                            <div class="font-black text-rose-600">{{ row.solved_complaint }}</div>
                                            <div class="text-slate-400">C</div>
                                        </div>
                                        <div class="rounded-xl bg-violet-50 px-2 py-2">
                                            <div class="font-black text-violet-600">{{ row.solved_bad_review }}</div>
                                            <div class="text-slate-400">BR</div>
                                        </div>
                                        <div class="rounded-xl bg-blue-50 px-2 py-2">
                                            <div class="font-black text-blue-600">{{ row.solved_ot }}</div>
                                            <div class="text-slate-400">OT</div>
                                        </div>
                                        <div class="rounded-xl bg-emerald-600 px-2 py-2 text-white">
                                            <div class="font-black">{{ row.solved_total }}</div>
                                            <div class="text-emerald-100">All</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!agentDdayStats.length">
                                <td colspan="4" class="px-4 py-8 text-center text-xs font-bold text-slate-400">Belum ada aktivitas agent hari ini</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
                <section class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-black text-slate-900">Rekap Agent Hari Ini</h2>
                            <p class="text-[12px] font-medium text-slate-400">Distribusi, handled, solved, dan input produktivitas harian</p>
                        </div>
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-violet-50 text-violet-500">
                            <UserCheck class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
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
                                <span class="text-[11px] font-black text-slate-400">{{ row.productivity_entries }} Input</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded-xl bg-white p-3 shadow-sm">
                                    <div class="text-[9px] font-black uppercase tracking-wider text-blue-400">Distributed</div>
                                    <div class="text-lg font-black text-blue-600">{{ row.distributed }}</div>
                                </div>
                                <div class="rounded-xl bg-white p-3 shadow-sm">
                                    <div class="text-[9px] font-black uppercase tracking-wider text-violet-400">Handled</div>
                                    <div class="text-lg font-black text-violet-600">{{ row.handled }}</div>
                                </div>
                                <div class="rounded-xl bg-white p-3 shadow-sm">
                                    <div class="text-[9px] font-black uppercase tracking-wider text-emerald-400">Solved</div>
                                    <div class="text-lg font-black text-emerald-600">{{ row.solved }}</div>
                                </div>
                                <div class="rounded-xl bg-white p-3 shadow-sm">
                                    <div class="text-[9px] font-black uppercase tracking-wider text-amber-400">Produktivitas</div>
                                    <div class="text-lg font-black text-amber-600">{{ row.productivity_total }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!agentRecap.length" class="col-span-full py-10 text-center text-xs font-bold text-slate-400">
                            Belum ada aktivitas bulan ini
                        </div>
                    </div>
                </section>

                <section class="rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-black text-slate-900">Produktivitas Harian</h2>
                            <p class="text-[12px] font-medium text-slate-400">Input dan review output agent per hari</p>
                        </div>
                        <button
                            class="rounded-xl bg-[var(--app-primary)] px-4 py-2 text-xs font-black text-white shadow-sm transition hover:opacity-90"
                            @click="openProductivityModal()"
                        >
                            Tambah
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="row in todayProductivity"
                            :key="row.id"
                            class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-black text-slate-800">{{ row.cs_name }}</div>
                                    <div class="text-[11px] font-medium text-slate-400">{{ row.tanggal }}</div>
                                </div>
                                <button class="text-xs font-black text-[var(--app-primary)]" @click="openProductivityModal(row)">Edit</button>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center text-[11px] sm:grid-cols-6">
                                <div class="rounded-xl bg-white px-2 py-2">
                                    <div class="font-black text-slate-800">{{ row.complaint_handled }}</div>
                                    <div class="text-slate-400">C H</div>
                                </div>
                                <div class="rounded-xl bg-white px-2 py-2">
                                    <div class="font-black text-slate-800">{{ row.complaint_solved }}</div>
                                    <div class="text-slate-400">C S</div>
                                </div>
                                <div class="rounded-xl bg-white px-2 py-2">
                                    <div class="font-black text-slate-800">{{ row.bad_review_handled }}</div>
                                    <div class="text-slate-400">BR</div>
                                </div>
                                <div class="rounded-xl bg-white px-2 py-2">
                                    <div class="font-black text-slate-800">{{ row.order_tracking_handled }}</div>
                                    <div class="text-slate-400">OT</div>
                                </div>
                                <div class="rounded-xl bg-white px-2 py-2">
                                    <div class="font-black text-slate-800">{{ row.oos_handled }}</div>
                                    <div class="text-slate-400">OOS</div>
                                </div>
                                <div class="rounded-xl bg-slate-900 px-2 py-2 text-white">
                                    <div class="font-black">{{ row.total_ticket }}</div>
                                    <div class="text-slate-400">Total</div>
                                </div>
                            </div>
                            <div class="mt-3 text-xs text-slate-500">{{ row.notes || 'Tanpa catatan.' }}</div>
                        </div>
                        <div v-if="!todayProductivity.length" class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-xs font-bold text-slate-400">
                            Belum ada data produktivitas hari ini
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showProductivityModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-slate-950/40" @click="showProductivityModal = false"></div>
                <div class="relative z-10 mx-4 w-full max-w-xl rounded-[28px] bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h3 class="text-lg font-black text-slate-900">Input Produktivitas</h3>
                            <p class="text-xs font-medium text-slate-400">Simpan produktivitas agent per tanggal kerja</p>
                        </div>
                        <button class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100" @click="showProductivityModal = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <form class="space-y-4 px-6 py-5" @submit.prevent="submitProductivity">
                        <div>
                            <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Nama CS</label>
                            <input v-model="productivityForm.cs_name" type="text" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                            <div v-if="productivityForm.errors.cs_name" class="mt-1 text-xs font-medium text-rose-500">{{ productivityForm.errors.cs_name }}</div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Tanggal</label>
                                <input v-model="productivityForm.tanggal" type="date" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                                <div v-if="productivityForm.errors.tanggal" class="mt-1 text-xs font-medium text-rose-500">{{ productivityForm.errors.tanggal }}</div>
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Total Ticket</label>
                                <input v-model.number="productivityForm.total_ticket" type="number" min="0" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                                <div v-if="productivityForm.errors.total_ticket" class="mt-1 text-xs font-medium text-rose-500">{{ productivityForm.errors.total_ticket }}</div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Complaint Handled</label>
                                <input v-model.number="productivityForm.complaint_handled" type="number" min="0" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Complaint Solved</label>
                                <input v-model.number="productivityForm.complaint_solved" type="number" min="0" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Bad Review</label>
                                <input v-model.number="productivityForm.bad_review_handled" type="number" min="0" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Order Tracking</label>
                                <input v-model.number="productivityForm.order_tracking_handled" type="number" min="0" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">OOS</label>
                                <input v-model.number="productivityForm.oos_handled" type="number" min="0" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-black uppercase tracking-wider text-slate-400">Notes</label>
                            <textarea v-model="productivityForm.notes" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-blue-300"></textarea>
                            <div v-if="productivityForm.errors.notes" class="mt-1 text-xs font-medium text-rose-500">{{ productivityForm.errors.notes }}</div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-black text-slate-500" @click="showProductivityModal = false">Batal</button>
                            <button type="submit" class="rounded-xl bg-[var(--app-primary)] px-5 py-2 text-sm font-black text-white" :disabled="productivityForm.processing">
                                {{ productivityForm.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
