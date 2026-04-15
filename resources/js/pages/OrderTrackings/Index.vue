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
        <div class="space-y-6">
            <section class="app-table-shell overflow-hidden">
                <div class="flex flex-col gap-5 border-b border-[var(--app-border)] px-6 py-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Shipment Visibility</p>
                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-[var(--app-ink)]">Order Tracking Workspace</h2>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500">Modul tracking kini mengikuti shell baru dan tetap memakai data pagination dari backend saat ini.</p>
                    </div>

                    <div class="inline-flex rounded-[18px] bg-[var(--app-primary-soft)] px-4 py-3 text-sm font-semibold text-[var(--app-primary)]">
                        Monitor AWB, status, dan jalur penyelesaian
                    </div>
                </div>

                <div class="grid gap-4 px-5 py-5 md:grid-cols-3">
                    <article v-for="card in metrics" :key="card.label" class="rounded-[24px] border border-[var(--app-border)] bg-white px-5 py-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-4xl font-extrabold text-[var(--app-ink)]">{{ card.value }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full" :class="card.tone">
                                <component :is="card.icon" class="h-5 w-5" />
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="app-table-shell overflow-hidden">
                <div class="border-b border-[var(--app-border)] px-6 py-5">
                    <h3 class="text-xl font-extrabold text-[var(--app-ink)]">Tracking List</h3>
                    <p class="mt-1 text-sm text-slate-500">Panel ini siap dipakai tanpa mengubah logic backend yang sudah berjalan.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)]">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Platform</th>
                                <th class="px-6 py-4">AWB</th>
                                <th class="px-6 py-4">Logistics</th>
                                <th class="px-6 py-4">Last Step</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--app-border)] bg-white">
                            <tr v-for="item in rows" :key="item.id">
                                <td class="px-6 py-4 text-sm font-semibold text-[var(--app-ink)]">{{ item.order_id || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.platform || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.awb || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.logistics || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.last_step || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="item.status === 'Solved' ? 'bg-emerald-50 text-emerald-600' : item.status === 'Whitelist' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'"
                                    >
                                        {{ item.status || 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!rows.length" class="px-6 py-14 text-center">
                    <p class="text-lg font-bold text-[var(--app-ink)]">Belum ada data tracking</p>
                    <p class="mt-2 text-sm text-slate-500">Saat order tracking mulai dibuat, daftar akan muncul di sini.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
