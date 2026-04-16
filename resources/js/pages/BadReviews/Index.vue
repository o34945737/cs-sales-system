<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import debounce from 'lodash/debounce';
import {
    AlertCircle,
    ArrowUpDown,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
    Eye,
    Pencil,
    Plus,
    Search,
    Trash2,
    Users,
    X,
    Star,
    Activity,
} from 'lucide-vue-next';

const props = defineProps({
    badReviews: Object,
    brandOptions: Array,
    platformOptions: Array,
    categoryReviewOptions: Array,
    causeByOptions: Array,
    skuCodeOptions: Array,
    csNameOptions: Array,
    statusSummary: Object,
    starSummary: Object,
    csSummary: Array,
    filters: Object,
});

// ============ Computed Properties ============
const badReviewPage = computed(() => ({
    data: [],
    links: [],
    from: 0,
    to: 0,
    total: 0,
    ...(props.badReviews || {}),
}));

const badReviewRows = computed(() => Array.isArray(badReviewPage.value.data) ? badReviewPage.value.data : []);
const paginationLinks = computed(() => Array.isArray(badReviewPage.value.links) ? badReviewPage.value.links : []);
const filterState = computed(() => (props.filters && !Array.isArray(props.filters) ? props.filters : {}));
const statusSummary = computed(() => props.statusSummary || { all: 0, pending: 0, solved: 0 });
const starSummary = computed(() => props.starSummary || { all: 0, '1': 0, '2': 0, '3': 0 });
const csSummary = computed(() => props.csSummary || []);

const skuCatalog = computed(() => {
    const catalog = {};
    (props.skuCodeOptions || []).forEach((item) => {
        if (item?.sku) {
            catalog[item.sku] = {
                product_name: item.product_name || '',
                available_qty: item.available_qty || 0,
                status_qty: item.status_qty || '-',
            };
        }
    });
    return catalog;
});

// Filter states
const currentStatus = computed(() => filterState.value.status || 'All');
const currentStar = computed(() => filterState.value.star || 'All');
const currentCs = computed(() => filterState.value.cs_name || '');
const currentBrand = computed(() => filterState.value.brand || 'All');

const search = ref(filterState.value.search || '');
const agentSearchQuery = ref('');

const filteredCsSummary = computed(() => {
    let list = [...csSummary.value];
    if (agentSearchQuery.value) {
        const query = agentSearchQuery.value.toLowerCase();
        list = list.filter((cs) => cs.cs_name?.toLowerCase().includes(query));
    }
    return list.sort((a, b) => (b.total || 0) - (a.total || 0));
});

const brandOptions = computed(() => ['All', ...(props.brandOptions || [])]);
const starOptions = ['All', '1', '2', '3'];

const overviewCards = computed(() => [
    { label: 'Total Reviews', value: statusSummary.value.all || 0 },
    { label: 'Pending', value: statusSummary.value.pending || 0 },
    { label: 'Solved', value: statusSummary.value.solved || 0 },
    { label: 'Active Reviewers', value: csSummary.value.length || 0 },
]);

const statusCards = [
    { key: 'All', label: 'All', value: statusSummary.value.all || 0 },
    { key: 'Pending', label: 'Pending', value: statusSummary.value.pending || 0 },
    { key: 'Solved', label: 'Solved', value: statusSummary.value.solved || 0 },
];

const activeFilterCount = computed(() =>
    [Boolean(search.value), currentStatus.value !== 'All', Boolean(currentCs.value), currentBrand.value !== 'All', currentStar.value !== 'All'].filter(Boolean).length
);

// ============ Form State ============
const isModalOpen = ref(false);
const modalMode = ref('create');
const detailItem = ref(null);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    tanggal_review: '',
    brand: '',
    platform: '',
    order_id: '',
    username: '',
    star: '',
    product_name: '',
    sku: '',
    category_review: '',
    cause_by: '',
    review_notes: '',
    progress: 'Follow Up Customer',
    tanggal_update: '',
    cs_name: '',
});

const readonlyInputClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-[14px] text-slate-400 outline-none';
const inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const selectClass = 'w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2 pr-12 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const textAreaClass = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';

const fieldError = (field) => form.errors[field];
const controlClass = (field, variant = 'input') => {
    const baseClass = variant === 'select' ? selectClass : variant === 'textarea' ? textAreaClass : inputClass;
    return fieldError(field) ? `${baseClass} border-rose-300 bg-rose-50/60 focus:border-rose-400` : baseClass;
};

// ============ Filter Functions ============
const setStatus = (status) => visitIndex({ status: status === 'All' ? undefined : status, page: 1 });
const setStar = (star) => visitIndex({ star: star === 'All' ? undefined : star, page: 1 });
const setCsFilter = (name) => visitIndex({ cs_name: name || undefined, page: 1 });
const setBrandFilter = (brand) => visitIndex({ brand: brand === 'All' ? undefined : brand, page: 1 });

const visitIndex = (overrides = {}) => {
    router.get(
        route('bad-reviews.index'),
        {
            search: search.value || undefined,
            status: filterState.value.status !== 'All' ? filterState.value.status : undefined,
            star: filterState.value.star !== 'All' ? filterState.value.star : undefined,
            cs_name: filterState.value.cs_name || undefined,
            brand: filterState.value.brand !== 'All' ? filterState.value.brand : undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true }
    );
};

watch(search, debounce((val) => visitIndex({ search: val || undefined, page: 1 }), 400));

// ============ Modal Functions ============
const openCreateModal = () => {
    modalMode.value = 'create';
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    modalMode.value = 'edit';
    detailItem.value = item;
    form.reset();
    form.clearErrors();
    Object.keys(form.data()).forEach((key) => {
        form[key] = item[key] ?? '';
    });
    isModalOpen.value = true;
};

const openDetail = (item) => { detailItem.value = item; };
const closeDetail = () => { detailItem.value = null; };
const discardForm = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    const url = modalMode.value === 'edit'
        ? route('bad-reviews.update', detailItem.value.id)
        : route('bad-reviews.store');

    form.transform((data) => ({
        ...data,
        _method: modalMode.value === 'edit' ? 'PUT' : 'POST'
    })).post(url, {
        preserveScroll: true,
        onSuccess: () => discardForm(),
    });
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const submitDelete = () => {
    router.delete(route('bad-reviews.destroy', itemToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            itemToDelete.value = null;
        }
    });
};

const handleSkuChange = () => {
    if (form.sku && skuCatalog.value[form.sku]) {
        form.product_name = skuCatalog.value[form.sku].product_name;
    }
};

const formatDate = (value) => {
    if (!value) return '-';
    try {
        const parsed = new Date(value);
        return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parsed);
    } catch { return value; }
};

const statusClass = (status) =>
    status === 'Solved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700';

const starClass = (star) =>
    star === '1' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700';

const selectButtonClass = (currentValue, expectedValue) =>
    currentValue === expectedValue
        ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white shadow-md'
        : 'border-slate-200 bg-white text-slate-600 hover:border-[var(--app-primary)]/40 hover:bg-slate-50';

</script>

<template>
    <Head title="Bad Reviews" />

    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }, { title: 'Bad Reviews', href: '/bad-reviews' }]">
        <div class="pb-20">
            <div class="mx-auto flex max-w-[90rem] flex-col gap-10 px-4 font-sans sm:px-6 lg:px-8">
                <div class="space-y-10">
                    <!-- 1. Metrics -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <article v-for="card in overviewCards" :key="card.label" class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:-translate-y-0.5">
                            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-[var(--app-primary)]/5 blur-2xl"></div>
                            <div class="relative z-10">
                                <p class="text-[9px] font-black uppercase tracking-[0.15rem] text-slate-400">{{ card.label }}</p>
                                <div class="mt-1.5 flex items-end justify-between">
                                    <p class="text-2xl font-black tracking-tight text-slate-900">{{ card.value }}</p>
                                    <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                        <Star v-if="card.label.includes('Review')" class="h-3 w-3" />
                                        <Activity v-else class="h-3 w-3" />
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- 2. Filters -->
                    <div class="rounded-3xl border border-slate-100 bg-slate-50/50 p-2 shadow-sm ring-1 ring-slate-100/10">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3 px-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-200/50 text-blue-500">
                                    <AlertCircle class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 leading-none">Global Filters</p>
                                    <p class="mt-1 text-[13px] font-black text-slate-900 leading-none">Refine Reviews</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pr-1">
                                <div class="relative min-w-[140px]">
                                    <select :value="currentBrand" class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 outline-none transition-all hover:border-slate-300 shadow-sm" @change="setBrandFilter($event.target.value)">
                                        <option v-for="option in brandOptions" :key="option" :value="option">{{ option === 'All' ? 'ANY BRAND' : option }}</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[120px]">
                                    <select :value="currentStar" class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 outline-none transition-all hover:border-slate-300 shadow-sm" @change="setStar($event.target.value)">
                                        <option v-for="star in starOptions" :key="star" :value="star">{{ star === 'All' ? 'ANY STAR' : `STARS: ${star}` }}</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[130px]">
                                    <select :value="currentStatus" class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 outline-none transition-all hover:border-slate-300 shadow-sm" @change="setStatus($event.target.value)">
                                        <option value="All">ANY STATUS</option>
                                        <option value="Pending">PENDING</option>
                                        <option value="Solved">SOLVED</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Main Split Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-[300px,1fr] gap-8">
                        <aside class="space-y-4">
                            <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/50">
                                <header class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Ownership</p>
                                        <h2 class="mt-0.5 text-lg font-black text-slate-900">Reviewers</h2>
                                    </div>
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-[var(--app-primary)]">
                                        <Users class="h-4 w-4" />
                                    </div>
                                </header>
                                <div class="mt-6 space-y-2.5">
                                    <button @click="setCsFilter('')" class="w-full h-10 px-4 flex items-center justify-between rounded-xl transition-all font-black text-[13px]" :class="!currentCs ? 'bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                                        <span>Show All</span>
                                        <span class="text-[10px] opacity-60">{{ badReviewPage.total }}</span>
                                    </button>
                                    <div class="relative group">
                                        <Search class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 group-focus-within:text-[var(--app-primary)]" />
                                        <input v-model="agentSearchQuery" type="text" placeholder="Search agent..." class="h-9 w-full rounded-xl border border-slate-100 bg-slate-50/50 pl-9 pr-4 text-[11px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-blue-50/50" />
                                    </div>
                                    <div class="space-y-1.5 mt-4 max-h-[400px] overflow-y-auto pr-1">
                                        <button v-for="cs in filteredCsSummary" :key="cs.cs_name" @click="setCsFilter(cs.cs_name)" class="w-full group p-3 flex items-center justify-between rounded-xl border transition-all text-left" :class="currentCs === cs.cs_name ? 'border-[var(--app-primary)] bg-blue-50/50' : 'border-slate-50 bg-white hover:border-slate-200'">
                                            <div>
                                                <p class="text-[13px] font-black text-slate-900 leading-tight">{{ cs.cs_name }}</p>
                                                <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ cs.total }} reports</p>
                                            </div>
                                            <div class="h-6 w-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 group-hover:bg-white group-hover:text-[var(--app-primary)]">
                                                <span class="text-[10px] font-black">{{ cs.total }}</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <div class="min-w-0 space-y-6">
                            <section class="rounded-[24px] border border-slate-100 bg-white shadow-sm overflow-hidden">
                                <div class="flex flex-col gap-6 border-b border-slate-100 px-6 py-7 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Recent Reviews</h2>
                                        <p class="text-[13px] font-bold text-slate-400 mt-1">Found {{ badReviewPage.total }} bad reviews to handle.</p>
                                    </div>
                                    <div class="flex flex-1 flex-col gap-4 lg:max-w-md lg:flex-row lg:items-center lg:justify-end">
                                        <div class="relative flex-1 group">
                                            <Search class="absolute inset-y-0 left-4 flex items-center pointer-events-none h-full w-4 text-slate-400 group-focus-within:text-[var(--app-primary)]" />
                                            <input v-model="search" type="text" placeholder="Search customer or order..." class="h-11 w-full rounded-2xl border-slate-200/60 bg-slate-50/50 pl-11 pr-4 text-[13px] font-bold outline-none focus:bg-white focus:ring-[6px] focus:ring-blue-500/5 shadow-inner" />
                                        </div>
                                        <button type="button" class="group flex h-11 items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-5 text-[14px] font-black text-white shadow-lg transition-all hover:-translate-y-1" @click="openCreateModal">
                                            <Plus class="h-5 w-5 stroke-[4px]" />
                                            <span>New Review</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[1000px] table-fixed">
                                        <thead class="bg-slate-50/80">
                                            <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                                                <th class="py-4 pl-6 pr-4">Order ID / Date</th>
                                                <th class="px-4 py-4">Customer</th>
                                                <th class="px-4 py-4 text-center">Star</th>
                                                <th class="px-4 py-4">Brand / Platform</th>
                                                <th class="px-4 py-4">Assigned To</th>
                                                <th class="px-4 py-4 text-center">Status</th>
                                                <th class="py-4 pl-4 pr-6 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50">
                                            <tr v-for="item in badReviewRows" :key="item.id" class="group hover:bg-slate-50/50 transition-colors">
                                                <td class="py-4 pl-6 pr-4">
                                                    <p class="text-[13px] font-black text-slate-900">#{{ item.order_id || '-' }}</p>
                                                    <p class="text-[11px] font-bold text-slate-400 mt-0.5">{{ formatDate(item.tanggal_review) }}</p>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <p class="text-[13px] font-black text-slate-900">{{ item.username || '-' }}</p>
                                                    <p class="truncate text-[11px] font-bold text-slate-400 mt-0.5 line-clamp-1">{{ item.review_notes || '-' }}</p>
                                                </td>
                                                <td class="px-4 py-4 text-center">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black" :class="starClass(item.star)">
                                                        <Star class="h-3 w-3 fill-current" /> {{ item.star }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <p class="text-[13px] font-black text-slate-900">{{ item.brand || '-' }}</p>
                                                    <p class="text-[11px] font-bold text-slate-400 mt-0.5">{{ item.platform || '-' }}</p>
                                                </td>
                                                <td class="px-4 py-4 text-[13px] font-bold text-slate-600">{{ item.cs_name || 'UNASSIGNED' }}</td>
                                                <td class="px-4 py-4 text-center">
                                                    <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider" :class="statusClass(item.status)">{{ item.status || 'Pending' }}</span>
                                                </td>
                                                <td class="py-4 pl-4 pr-6 text-right">
                                                    <div class="flex items-center justify-end gap-1">
                                                        <button @click="openDetail(item)" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-blue-600 transition-all"><Eye class="h-4 w-4" /></button>
                                                        <button @click="openEditModal(item)" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-amber-600 transition-all"><Pencil class="h-4 w-4" /></button>
                                                        <button @click="confirmDelete(item)" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-rose-600 transition-all"><Trash2 class="h-4 w-4" /></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex flex-col gap-5 border-t border-slate-100 bg-slate-50/30 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-[13px] font-bold text-slate-400">Showing {{ badReviewPage.from }}-{{ badReviewPage.to }} of {{ badReviewPage.total }} results</p>
                                    <div class="flex items-center gap-1.5">
                                        <button v-for="(link, i) in paginationLinks" :key="i" class="h-9 min-w-[36px] items-center justify-center rounded-xl px-3 text-[12px] font-black transition-all" :class="link.active ? 'bg-[var(--app-primary)] text-white' : 'bg-white text-slate-500 border border-slate-100 hover:bg-slate-50'" @click="link.url && router.visit(link.url, { preserveScroll: true })" v-html="link.label"></button>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals and Sidebars -->
        <transition name="fade">
            <div v-if="detailItem" class="fixed inset-0 z-40 bg-slate-950/20 backdrop-blur-[1px]" @click.self="closeDetail">
                <aside class="absolute right-0 top-0 h-full w-full max-w-lg bg-white shadow-2xl p-8 overflow-y-auto">
                    <header class="flex items-center justify-between mb-8 pb-6 border-b border-slate-100">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Details Viewer</p>
                            <h3 class="text-2xl font-black text-slate-900 mt-1">Review #{{ detailItem.order_id }}</h3>
                        </div>
                        <button @click="closeDetail" class="h-10 w-10 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all"><X class="h-5 w-5" /></button>
                    </header>

                    <div class="space-y-8">
                        <section class="grid grid-cols-2 gap-4">
                            <div class="p-5 rounded-2xl bg-slate-50/50 border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Review Date</p>
                                <p class="text-[15px] font-black text-slate-900 mt-2">{{ formatDate(detailItem.tanggal_review) }}</p>
                            </div>
                            <div class="p-5 rounded-2xl bg-slate-50/50 border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Star Rating</p>
                                <div class="inline-flex items-center gap-2 mt-2 px-3 py-1 rounded-full text-[12px] font-black" :class="starClass(detailItem.star)">
                                    <Star class="h-3 w-3 fill-current" /> {{ detailItem.star }} Stars
                                </div>
                            </div>
                        </section>

                        <section class="p-6 rounded-2xl border border-slate-100 space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 flex items-center justify-center rounded-full bg-blue-50 text-[var(--app-primary)]"><Users class="h-5 w-5" /></div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Customer Information</p>
                                    <p class="text-lg font-black text-slate-900 mt-1">{{ detailItem.username || '-' }}</p>
                                    <p class="text-[13px] font-medium text-slate-500">{{ detailItem.brand }} / {{ detailItem.platform }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4">
                             <p class="text-[10px] font-bold text-slate-400 uppercase px-1">Review Notes & Content</p>
                             <div class="p-6 rounded-3xl bg-[#f8fbff]/50 border border-slate-100 ring-1 ring-slate-100/50 italic text-slate-600 leading-relaxed">
                                "{{ detailItem.review_notes || 'No review content provided.' }}"
                             </div>
                        </section>

                        <section class="grid grid-cols-1 gap-4">
                             <div class="p-5 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Status Tracking</p>
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider" :class="statusClass(detailItem.status)">{{ detailItem.status }}</span>
                                    <span class="text-[13px] font-bold text-slate-500">{{ detailItem.progress || 'Untracked' }}</span>
                                </div>
                             </div>
                        </section>
                    </div>
                </aside>
            </div>
        </transition>

        <!-- Upsert Modal -->
        <transition name="fade">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-950/30 backdrop-blur-[2px] flex items-center justify-center p-4">
                <div class="w-full max-w-4xl bg-[#f8fbff] rounded-[32px] shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
                    <header class="px-8 py-6 border-b flex items-center justify-between" :class="modalMode === 'edit' ? 'bg-slate-900 border-slate-800' : 'bg-[#EEF2FF] border-[#E0E7FF]'">
                        <div>
                             <h2 class="text-2xl font-black tracking-tight transition-colors" :class="modalMode === 'edit' ? 'text-white' : 'text-slate-900'">{{ modalMode === 'edit' ? 'Edit Bad Review' : 'Create New Review' }}</h2>
                             <p class="text-[13px] font-medium transition-colors" :class="modalMode === 'edit' ? 'text-slate-400' : 'text-slate-500'">Fill in the review details below.</p>
                        </div>
                        <button @click="discardForm" class="h-10 w-10 flex items-center justify-center rounded-xl transition-all" :class="modalMode === 'edit' ? 'bg-white/10 text-slate-300 hover:bg-white/20' : 'bg-slate-900/5 text-slate-500 hover:bg-slate-900/10'"><X class="h-5 w-5" /></button>
                    </header>

                    <div class="flex-1 overflow-y-auto p-8 space-y-8">
                        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Review Date*</label>
                                <input v-model="form.tanggal_review" type="date" :class="controlClass('tanggal_review')" />
                                <p v-if="form.errors.tanggal_review" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.tanggal_review }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Star Rating*</label>
                                <div class="relative">
                                    <select v-model="form.star" :class="controlClass('star', 'select')">
                                        <option value="" disabled>Select Rating</option>
                                        <option v-for="s in ['1','2','3']" :key="s" :value="s">{{ s }} Star(s)</option>
                                    </select>
                                    <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-slate-400" />
                                </div>
                                <p v-if="form.errors.star" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.star }}</p>
                            </div>
                        </section>

                        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Order ID*</label>
                                <input v-model="form.order_id" type="text" placeholder="ORD-XXXXX" :class="controlClass('order_id')" />
                                <p v-if="form.errors.order_id" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.order_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Customer Username*</label>
                                <input v-model="form.username" type="text" placeholder="Username" :class="controlClass('username')" />
                                <p v-if="form.errors.username" class="text-xs font-bold text-rose-500 ml-1">{{ form.errors.username }}</p>
                            </div>
                        </section>

                        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Brand*</label>
                                <div class="relative">
                                    <select v-model="form.brand" :class="controlClass('brand', 'select')">
                                        <option value="" disabled>Choose Brand</option>
                                        <option v-for="b in props.brandOptions" :key="b" :value="b">{{ b }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-slate-400" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Platform*</label>
                                <div class="relative">
                                    <select v-model="form.platform" :class="controlClass('platform', 'select')">
                                        <option value="" disabled>Choose Platform</option>
                                        <option v-for="p in props.platformOptions" :key="p" :value="p">{{ p }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-slate-400" />
                                </div>
                            </div>
                        </section>

                        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">SKU Selection</label>
                                <div class="relative">
                                    <select v-model="form.sku" :class="controlClass('sku', 'select')" @change="handleSkuChange">
                                        <option value="">Manual Entry</option>
                                        <option v-for="s in props.skuCodeOptions" :key="s.sku" :value="s.sku">{{ s.sku }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-slate-400" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Product Name</label>
                                <input v-model="form.product_name" type="text" :class="form.sku ? readonlyInputClass : inputClass" :readonly="!!form.sku" />
                            </div>
                        </section>

                        <section class="space-y-2">
                            <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Review Notes*</label>
                            <textarea v-model="form.review_notes" rows="3" :class="controlClass('review_notes', 'textarea')" placeholder="Detailed notes about the bad review..."></textarea>
                        </section>

                        <section class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-20">
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Progress Tracking</label>
                                <div class="relative">
                                    <select v-model="form.progress" :class="controlClass('progress', 'select')">
                                        <option value="Follow Up Customer">Follow Up Customer</option>
                                        <option value="Auto Reply">Auto Reply</option>
                                    </select>
                                    <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-slate-400" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[12px] font-black uppercase text-slate-600 ml-1">Reviewer Assigned*</label>
                                <div class="relative">
                                    <select v-model="form.cs_name" :class="controlClass('cs_name', 'select')">
                                        <option value="" disabled>Select Agent</option>
                                        <option v-for="cs in props.csNameOptions" :key="cs" :value="cs">{{ cs }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 pointer-events-none text-slate-400" />
                                </div>
                            </div>
                        </section>
                    </div>

                    <footer class="px-8 py-6 border-t bg-slate-50 flex items-center justify-end gap-3">
                        <button @click="discardForm" class="px-6 h-12 text-[14px] font-black text-slate-500 hover:bg-slate-100 rounded-2xl transition-all">Cancel</button>
                        <button @click="submitForm" :disabled="form.processing" class="px-8 h-12 text-[14px] font-black text-white rounded-2xl shadow-xl transition-all hover:-translate-y-1" :class="modalMode === 'edit' ? 'bg-blue-600' : 'bg-[var(--app-primary)]'">
                            {{ form.processing ? 'Saving...' : (modalMode === 'edit' ? 'Update Review' : 'Create Review') }}
                        </button>
                    </footer>
                </div>
            </div>
        </transition>

        <!-- Delete Modal -->
        <transition name="fade">
            <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isDeleteModalOpen = false">
                <div class="w-full max-w-md bg-white rounded-[32px] overflow-hidden shadow-2xl transform scale-100">
                    <div class="bg-rose-50 px-8 py-10">
                        <div class="h-14 w-14 flex items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-rose-100 text-rose-600 mb-6"><Trash2 class="h-7 w-7" /></div>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tight">Delete Review</h3>
                        <p class="mt-2 text-[15px] font-medium text-slate-500 leading-relaxed">System will remove review record for <b>#{{ itemToDelete?.order_id }}</b> permanently.</p>
                    </div>
                    <div class="p-8 flex gap-3 bg-white">
                        <button @click="isDeleteModalOpen = false" class="h-12 flex-1 rounded-2xl bg-slate-50 text-[14px] font-black text-slate-500">Keep It</button>
                        <button @click="submitDelete" class="h-12 flex-[2] rounded-2xl bg-rose-600 text-[14px] font-black text-white shadow-lg shadow-rose-500/20">Delete Forever</button>
                    </div>
                </div>
            </div>
        </transition>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
