<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { MessageSquareText, Star, UserRound } from 'lucide-vue-next';

const props = defineProps({
    badReviews: Object,
});

const pageData = computed(() => props.badReviews || {});
const rows = computed(() => pageData.value.data || []);

const metrics = computed(() => [
    { label: 'Total Reviews', value: pageData.value.total || 0, icon: MessageSquareText, tone: 'bg-blue-50 text-blue-500' },
    { label: 'Solved', value: rows.value.filter((item) => item.status === 'Solved').length, icon: Star, tone: 'bg-emerald-50 text-emerald-500' },
    { label: 'Assigned CS', value: new Set(rows.value.map((item) => item.cs_name).filter(Boolean)).size, icon: UserRound, tone: 'bg-violet-50 text-violet-500' },
]);
</script>

<template>
    <Head title="Bad Reviews" />

    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }, { title: 'Bad Reviews', href: '/bad-reviews' }]">
        <div class="space-y-4">
            <section class="app-table-shell overflow-hidden">
                <div class="flex flex-col gap-4 border-b border-[var(--app-border)] px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Review Recovery</p>
                        <h2 class="mt-1 text-xl font-black tracking-tight text-[var(--app-ink)]">Bad Review Workspace</h2>
                        <p class="mt-1 text-xs font-medium text-slate-500">Monitoring review negatif dan status follow up secara real-time.</p>
                    </div>
 
                    <div class="inline-flex rounded-xl bg-[var(--app-primary-soft)] px-3 py-2 text-[11px] font-bold text-[var(--app-primary)]">
                        Workspace Active Pool
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
                    <h3 class="text-base font-black text-[var(--app-ink)] uppercase tracking-wide">Review List</h3>
                </div>
 
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)]">
                        <thead class="bg-slate-50/50">
                            <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-3">Order ID</th>
                                <th class="px-5 py-3">Customer</th>
                                <th class="px-5 py-3">Star</th>
                                <th class="px-5 py-3">Category</th>
                                <th class="px-5 py-3">Progress</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">CS Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--app-border)] bg-white text-[13px]">
                            <tr v-for="item in rows" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3 font-bold text-[var(--app-ink)]">{{ item.order_id || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600">{{ item.username || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600">{{ item.star || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600">{{ item.category_review || '-' }}</td>
                                <td class="px-5 py-3 font-medium text-slate-600">{{ item.progress || '-' }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                        :class="item.status === 'Solved' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                                    >
                                        {{ item.status || 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-medium text-slate-600">{{ item.cs_name || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
 
                <div v-if="!rows.length" class="px-6 py-10 text-center">
                    <p class="text-base font-bold text-[var(--app-ink)]">Belum ada data bad review</p>
                    <p class="mt-1 text-xs text-slate-500">Queue akan muncul di sini.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
