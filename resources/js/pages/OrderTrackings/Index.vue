<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { PackageCheck, TimerReset, Truck } from 'lucide-vue-next';

const props = defineProps({
    orderTrackings: Object,
});

const pageData = computed(() => props.orderTrackings || {});
const rows = computed(() => pageData.value.data || []);

const metrics = computed(() => [
    { label: 'Total Tracking', value: pageData.value.total || 0, icon: Truck, tone: 'bg-blue-50 text-blue-500' },
    { label: 'Pending', value: rows.value.filter((item) => item.status === 'Pending').length, icon: TimerReset, tone: 'bg-amber-50 text-amber-500' },
    { label: 'Solved', value: rows.value.filter((item) => item.status === 'Solved').length, icon: PackageCheck, tone: 'bg-emerald-50 text-emerald-500' },
]);
</script>

<template>
    <Head title="Order Tracking" />

    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }, { title: 'Order Tracking', href: '/order-trackings' }]">
        <div class="space-y-4">
            <section class="app-table-shell overflow-hidden">
                <div class="flex flex-col gap-4 border-b border-[var(--app-border)] px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Shipment Visibility</p>
                        <h2 class="mt-1 text-xl font-black tracking-tight text-[var(--app-ink)]">Order Tracking Workspace</h2>
                        <p class="mt-1 text-xs font-medium text-slate-500">Monitor AWB, status, dan jalur penyelesaian secara real-time.</p>
                    </div>
 
                    <div class="inline-flex rounded-xl bg-[var(--app-primary-soft)] px-3 py-2 text-[11px] font-bold text-[var(--app-primary)]">
                        Logistics Flow Active
                    </div>
                </div>
 
                <div class="grid gap-3.5 px-4 py-4 md:grid-cols-3">
                    <article v-for="card in metrics" :key="card.label" class="rounded-xl border border-slate-50 bg-white px-4 py-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ card.label }}</p>
                                <p class="mt-1 text-2xl font-black text-[var(--app-ink)]">{{ card.value }}</p>
                            </div>
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg" :class="card.tone">
                                <component :is="card.icon" class="h-4 w-4" />
                            </div>
                        </div>
                    </article>
                </div>
            </section>
 
            <section class="app-table-shell overflow-hidden">
                <div class="border-b border-[var(--app-border)] px-5 py-4">
                    <h3 class="text-base font-black text-[var(--app-ink)] uppercase tracking-wide">Tracking List</h3>
                </div>
 
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)]">
                        <thead class="bg-slate-50/50">
                            <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-3">Order ID</th>
                                <th class="px-5 py-3">Platform</th>
                                <th class="px-5 py-3">AWB</th>
                                <th class="px-5 py-3">Logistics</th>
                                <th class="px-5 py-3">Last Step</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--app-border)] bg-white text-[13px]">
                            <tr v-for="item in rows" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3 font-bold text-[var(--app-ink)]">{{ item.order_id || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600">{{ item.platform || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600 font-mono tracking-tighter">{{ item.awb || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600">{{ item.logistics || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600 leading-tight">{{ item.last_step || '-' }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider shadow-sm"
                                        :class="item.status === 'Solved' ? 'bg-emerald-50 text-emerald-600' : item.status === 'Whitelist' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'"
                                    >
                                        {{ item.status || 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
 
                <div v-if="!rows.length" class="px-6 py-10 text-center">
                    <p class="text-base font-bold text-[var(--app-ink)]">Belum ada data tracking</p>
                    <p class="mt-1 text-xs text-slate-500">Queue akan muncul di sini.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
