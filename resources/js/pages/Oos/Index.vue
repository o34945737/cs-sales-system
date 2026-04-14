<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { AlertTriangle, Boxes, CalendarClock } from 'lucide-vue-next';

const props = defineProps({
    oosData: Object,
});

const pageData = computed(() => props.oosData || {});
const rows = computed(() => pageData.value.data || []);

const metrics = computed(() => [
    { label: 'Total Cases', value: pageData.value.total || 0, icon: AlertTriangle, tone: 'bg-rose-50 text-rose-500' },
    { label: 'Unique Orders', value: new Set(rows.value.map((item) => item.order_id).filter(Boolean)).size, icon: Boxes, tone: 'bg-blue-50 text-blue-500' },
    { label: 'Month Buckets', value: new Set(rows.value.map((item) => item.month).filter(Boolean)).size, icon: CalendarClock, tone: 'bg-amber-50 text-amber-500' },
]);
</script>

<template>
    <Head title="OOS Data" />

    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }, { title: 'OOS', href: '/oos' }]">
        <div class="space-y-6">
            <section class="app-table-shell overflow-hidden">
                <div class="flex flex-col gap-5 border-b border-[var(--app-border)] px-6 py-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Stock Incident</p>
                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-[var(--app-ink)]">OOS Workspace</h2>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500">Area monitoring stock issue dibawa ke tampilan template baru tanpa mengubah data dan proses backend.</p>
                    </div>

                    <div class="inline-flex rounded-[18px] bg-[var(--app-primary-soft)] px-4 py-3 text-sm font-semibold text-[var(--app-primary)]">
                        Fokus pada order yang terkendala stok
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
                    <h3 class="text-xl font-extrabold text-[var(--app-ink)]">OOS List</h3>
                    <p class="mt-1 text-sm text-slate-500">Data ditampilkan dari tabel OOS yang sekarang sudah ada.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)]">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Tanggal Input</th>
                                <th class="px-6 py-4">Brand</th>
                                <th class="px-6 py-4">Platform</th>
                                <th class="px-6 py-4">SKU</th>
                                <th class="px-6 py-4">Month</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--app-border)] bg-white">
                            <tr v-for="item in rows" :key="item.id">
                                <td class="px-6 py-4 text-sm font-semibold text-[var(--app-ink)]">{{ item.order_id || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.tanggal_input || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.brand || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.platform || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.sku || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.month || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!rows.length" class="px-6 py-14 text-center">
                    <p class="text-lg font-bold text-[var(--app-ink)]">Belum ada data OOS</p>
                    <p class="mt-2 text-sm text-slate-500">Begitu incident stok tercatat, detailnya akan muncul di sini.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
