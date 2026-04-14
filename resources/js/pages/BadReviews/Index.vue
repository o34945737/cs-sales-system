<script setup>
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
        <div class="space-y-6">
            <section class="app-table-shell overflow-hidden">
                <div class="flex flex-col gap-5 border-b border-[var(--app-border)] px-6 py-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Review Recovery</p>
                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-[var(--app-ink)]">Bad Review Workspace</h2>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500">Tampilan modul disesuaikan ke template baru tanpa mengubah data dan route yang sudah ada.</p>
                    </div>

                    <div class="inline-flex rounded-[18px] bg-[var(--app-primary-soft)] px-4 py-3 text-sm font-semibold text-[var(--app-primary)]">
                        Monitoring review negatif dan status follow up
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
                    <h3 class="text-xl font-extrabold text-[var(--app-ink)]">Review List</h3>
                    <p class="mt-1 text-sm text-slate-500">List data mengikuti pagination backend yang sudah ada.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--app-border)]">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4">Star</th>
                                <th class="px-6 py-4">Category</th>
                                <th class="px-6 py-4">Progress</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">CS Name</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--app-border)] bg-white">
                            <tr v-for="item in rows" :key="item.id">
                                <td class="px-6 py-4 text-sm font-semibold text-[var(--app-ink)]">{{ item.order_id || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.username || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.star || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.category_review || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.progress || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="item.status === 'Solved' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'"
                                    >
                                        {{ item.status || 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ item.cs_name || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!rows.length" class="px-6 py-14 text-center">
                    <p class="text-lg font-bold text-[var(--app-ink)]">Belum ada data bad review</p>
                    <p class="mt-2 text-sm text-slate-500">Saat data masuk, daftar review akan muncul di area ini.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
