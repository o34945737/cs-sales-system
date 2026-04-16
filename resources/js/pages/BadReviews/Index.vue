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
    Tag,
    Edit2,
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
    prioritySummary: Object,
    autoCauseByMap: Array,
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
const currentPlatform = computed(() => filterState.value.platform || 'All');

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
    [Boolean(search.value), currentStatus.value !== 'All', Boolean(currentCs.value), currentBrand.value !== 'All', currentPlatform.value !== 'All', currentStar.value !== 'All', currentPriority.value !== 'All'].filter(Boolean).length
);

const activeTab = ref('cs'); // 'cs', 'status', 'star', 'priority'
const priorityLevels = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];
const currentPriority = computed(() => filterState.value.priority || 'All');

// ============ Form State ============
const isModalOpen = ref(false);
const modalMode = ref('create');
const detailItem = ref(null);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    tanggal_review: '',
    month: '',
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
    progress: '',
    tanggal_update: '',
    cs_name: '',
    priority: '',
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

const completionSummary = computed(() => {
    const fields = ['tanggal_review', 'brand', 'platform', 'order_id', 'username', 'star', 'category_review', 'cause_by', 'review_notes', 'progress', 'tanggal_update', 'cs_name', 'priority'];
    const completed = fields.filter(f => !!form[f]).length;
    return { completed, total: fields.length };
});

// ============ Filter Functions ============
const setStatus = (status) => visitIndex({ status: status === 'All' ? undefined : status, page: 1 });
const setStar = (star) => visitIndex({ star: star === 'All' ? undefined : star, page: 1 });
const setCsFilter = (name) => visitIndex({ cs_name: name || undefined, page: 1 });
const setBrandFilter = (brand) => visitIndex({ brand: brand === 'All' ? undefined : brand, page: 1 });
const setPlatform = (platform) => visitIndex({ platform: platform === 'All' ? undefined : platform, page: 1 });

const visitIndex = (overrides = {}) => {
    router.get(
        route('bad-reviews.index'),
        {
            search: search.value || undefined,
            status: filterState.value.status !== 'All' ? filterState.value.status : undefined,
            star: filterState.value.star !== 'All' ? filterState.value.star : undefined,
            cs_name: filterState.value.cs_name || undefined,
            brand: filterState.value.brand !== 'All' ? filterState.value.brand : undefined,
            platform: filterState.value.platform !== 'All' ? filterState.value.platform : undefined,
            priority: filterState.value.priority !== 'All' ? filterState.value.priority : undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const setPriority = (p) => visitIndex({ priority: p === 'All' ? undefined : p, page: 1 });
const resetFilters = () => {
    search.value = '';
    visitIndex({
        status: undefined,
        star: undefined,
        cs_name: undefined,
        brand: undefined,
        platform: undefined,
        priority: undefined,
        page: 1
    });
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

// Auto-calculate month from tanggal_review
watch(() => form.tanggal_review, (newDate) => {
    if (newDate) {
        const d = new Date(newDate);
        if (!isNaN(d.getTime())) {
            const monthNames = [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            form.month = `${monthNames[d.getMonth()]} ${d.getFullYear()}`;
        }
    }
});

const handleSkuChange = () => {
    if (form.sku && skuCatalog.value[form.sku]) {
        form.product_name = skuCatalog.value[form.sku].product_name;
    } else {
        form.product_name = '';
    }
};

watch(() => form.category_review, (val) => {
    if (!val || !props.autoCauseByMap) return;
    
    // Pastikan mencari di array yang benar
    const matched = props.autoCauseByMap.find(item => item.name === val);
    if (matched) {
        form.cause_by = matched.default_cause_by;
    }
});

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

const priorityColorClass = (p) => {
    switch (p) {
        case 'P1': return 'bg-rose-100 text-rose-700 shadow-sm ring-1 ring-rose-200';
        case 'P2': return 'bg-orange-100 text-orange-700 shadow-sm ring-1 ring-orange-200';
        case 'P3': return 'bg-amber-100 text-amber-700 shadow-sm ring-1 ring-amber-200';
        case 'P4': return 'bg-yellow-100 text-yellow-700 shadow-sm ring-1 ring-yellow-200';
        case 'P5': return 'bg-blue-100 text-blue-700 shadow-sm ring-1 ring-blue-200';
        case 'P6': return 'bg-indigo-100 text-indigo-700 shadow-sm ring-1 ring-indigo-200';
        case 'P7': return 'bg-slate-100 text-slate-700 shadow-sm ring-1 ring-slate-200';
        default: return 'bg-slate-50 text-slate-400';
    }
};

</script>

<template>
    <Head title="Bad Reviews" />

    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Bad Reviews', href: '/bad-reviews' },
        ]"
    >
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

                    <!-- 2. Independent Filter Bar -->
                    <div class="rounded-3xl border border-slate-100 bg-slate-50/50 p-2 shadow-sm ring-1 ring-slate-100/10">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3 px-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-200/50 text-blue-500">
                                    <AlertCircle class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 leading-none">Global Filters</p>
                                    <p class="mt-1 text-[13px] font-black text-slate-900 leading-none">Refine Workspace</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pr-1">
                                <div class="relative min-w-[140px]">
                                    <select :value="currentBrand" class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50 shadow-sm" @change="setBrandFilter($event.target.value)">
                                        <option v-for="option in brandOptions" :key="option" :value="option">{{ option === 'All' ? 'ANY BRAND' : option }}</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[140px]">
                                    <select :value="currentPlatform" class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50 shadow-sm" @change="setPlatform($event.target.value)">
                                        <option value="All">ANY PLATFORM</option>
                                        <option v-for="p in props.platformOptions" :key="p" :value="p">{{ p.toUpperCase() }}</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[140px]">
                                    <select :value="currentPriority" class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50 shadow-sm" @change="setPriority($event.target.value)">
                                        <option value="All">ANY PRIORITY</option>
                                        <option v-for="p in priorityLevels" :key="p" :value="p">{{ p }} PRIORITY</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[120px]">
                                    <select :value="currentStar" class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50 shadow-sm" @change="setStar($event.target.value)">
                                        <option v-for="star in starOptions" :key="star" :value="star">{{ star === 'All' ? 'ANY RATING' : `STARS: ${star}` }}</option>
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
                            <!-- MULTI-TAB SIDEBAR NAVIGATOR -->
                            <div class="rounded-[24px] border border-slate-100 bg-white p-2 shadow-sm ring-1 ring-slate-100/50">
                                <div class="flex flex-wrap gap-1 p-1 rounded-xl bg-slate-50 border border-slate-100">
                                    <button 
                                        v-for="tab in ['cs', 'status', 'star', 'priority']" 
                                        :key="tab"
                                        @click="activeTab = tab"
                                        class="flex-1 py-1.5 px-2 rounded-lg text-[10px] font-black transition-all"
                                        :class="activeTab === tab ? 'bg-white text-[var(--app-primary)] shadow-sm ring-1 ring-slate-200' : 'text-slate-400 hover:text-slate-600'"
                                    >
                                        {{ tab.toUpperCase() }}
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/50 min-h-[500px]">
                                <!-- CS TAB -->
                                <div v-if="activeTab === 'cs'" class="space-y-4">
                                    <header class="flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">CS Groupings</p>
                                            <h2 class="mt-0.5 text-lg font-black text-slate-900">Review Desk</h2>
                                        </div>
                                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-[var(--app-primary)]">
                                            <Users class="h-4 w-4" />
                                        </div>
                                    </header>
                                    <div class="mt-4 space-y-2.5">
                                        <button @click="setCsFilter('')" class="w-full h-10 px-4 flex items-center justify-between rounded-xl transition-all font-black text-[11px]" :class="!currentCs ? 'bg-[var(--app-primary)] text-white shadow-lg' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                                            <span>All Active Agents</span>
                                            <span class="text-[10px] opacity-60">{{ statusSummary.all }}</span>
                                        </button>
                                        <div class="max-h-[350px] overflow-y-auto space-y-1.5 custom-scrollbar">
                                            <button v-for="cs in filteredCsSummary" :key="cs.cs_name" @click="setCsFilter(cs.cs_name)" class="w-full group p-3 flex items-center justify-between rounded-xl border transition-all text-left" :class="currentCs === cs.cs_name ? 'border-[var(--app-primary)] bg-blue-50/50' : 'border-slate-50 bg-white hover:border-slate-200'">
                                                <p class="text-[12px] font-black text-slate-900 leading-tight">{{ cs.cs_name }}</p>
                                                <span class="text-[10px] font-black text-slate-400">{{ cs.total }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- STATUS TAB -->
                                <div v-if="activeTab === 'status'" class="space-y-4">
                                    <header class="flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Ticket Progress</p>
                                            <h2 class="mt-0.5 text-lg font-black text-slate-900">Workflow</h2>
                                        </div>
                                    </header>
                                    <div class="mt-4 space-y-2">
                                        <button v-for="stat in statusCards" :key="stat.key" @click="setStatus(stat.key)" class="w-full p-4 flex items-center justify-between rounded-2xl border transition-all" :class="currentStatus === stat.key ? 'border-[var(--app-primary)] bg-blue-50/50 ring-1 ring-[var(--app-primary)]/10' : 'border-slate-50 bg-white hover:border-slate-200'">
                                            <span class="text-[12px] font-black text-slate-900 uppercase tracking-tight">{{ stat.label }}</span>
                                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-black">{{ stat.value }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- STAR TAB -->
                                <div v-if="activeTab === 'star'" class="space-y-4">
                                    <header class="flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Review Quality</p>
                                            <h2 class="mt-0.5 text-lg font-black text-slate-900">Rating Monitor</h2>
                                        </div>
                                    </header>
                                    <div class="mt-4 space-y-2">
                                        <button @click="setStar('All')" class="w-full p-4 flex items-center justify-between rounded-2xl border transition-all" :class="currentStar === 'All' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                                            <span class="text-[12px] font-black uppercase tracking-tight">Show All Reviews</span>
                                            <span class="text-[10px] font-black opacity-60">{{ starSummary.all }}</span>
                                        </button>
                                        <button v-for="s in ['1','2','3']" :key="s" @click="setStar(s)" class="w-full p-4 flex items-center justify-between rounded-2xl border transition-all" :class="currentStar === s ? 'border-amber-400 bg-amber-50/50' : 'border-slate-50 bg-white hover:border-slate-200'">
                                            <div class="flex items-center gap-2">
                                                <Star class="h-4 w-4 fill-amber-400 text-amber-400" />
                                                <span class="text-[13px] font-black text-slate-900">{{ s }} Stars</span>
                                            </div>
                                            <span class="text-[11px] font-black text-slate-400">{{ props.starSummary ? props.starSummary[s] : 0 }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- PRIORITY TAB -->
                                <div v-if="activeTab === 'priority'" class="space-y-4">
                                    <header class="flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Handling Priority</p>
                                            <h2 class="mt-0.5 text-lg font-black text-slate-900">Urgency Desk</h2>
                                        </div>
                                    </header>
                                    <div class="mt-4 space-y-1.5 max-h-[400px] overflow-y-auto custom-scrollbar pr-1">
                                        <button v-for="p in priorityLevels" :key="p" @click="setPriority(p)" class="w-full p-3 flex items-center justify-between rounded-xl border transition-all" :class="currentPriority === p ? 'border-rose-500 bg-rose-50/50 text-rose-700' : 'border-slate-50 bg-white hover:border-slate-200'">
                                            <span class="text-[12px] font-black">{{ p }} Priority</span>
                                            <span class="text-[11px] font-black opacity-60">{{ props.prioritySummary ? props.prioritySummary[p] : 0 }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <div class="min-w-0 space-y-6">
                            <section class="rounded-[24px] border border-slate-100 bg-white shadow-sm overflow-hidden">
                                <div class="flex flex-col gap-6 border-b border-slate-100 px-6 py-7 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                             Operational Database
                                        </div>
                                        <h2 class="mt-1 text-2xl font-black text-slate-900 tracking-tight">Current Reviews</h2>
                                        <div class="mt-2.5 flex items-center gap-2">
                                            <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500 ring-1 ring-slate-200/50">
                                                 <span>SHOWING {{ badReviewPage.from || 0 }}-{{ badReviewPage.to || 0 }} OF {{ badReviewPage.total || 0 }}</span>
                                            </div>
                                            <div
                                                class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                                                :class="activeFilterCount ? 'border-amber-200 bg-amber-50 text-amber-600 shadow-sm shadow-amber-500/5' : 'border-slate-100 bg-white text-slate-400'"
                                            >
                                                {{ activeFilterCount ? `${activeFilterCount} Active Filters` : 'No Filter' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-1 flex-col gap-4 lg:max-w-md lg:flex-row lg:items-center lg:justify-end">
                                        <div class="relative flex-1 group">
                                            <Search class="absolute inset-y-0 left-4 flex items-center pointer-events-none h-full w-4 text-slate-400 group-focus-within:text-[var(--app-primary)]" />
                                            <input v-model="search" type="text" placeholder="Search customer or order..." class="h-11 w-full rounded-2xl border-slate-200/60 bg-slate-50/50 pl-11 pr-4 text-[13px] font-bold outline-none focus:bg-white focus:ring-[6px] focus:ring-blue-500/5 shadow-inner" />
                                        </div>
                                        <button
                                            type="button"
                                            class="group flex h-12 items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-6 text-[14px] font-black text-white shadow-[0_15px_30px_rgba(53,103,232,0.25)] transition-all hover:bg-[var(--app-primary-dark)] hover:-translate-y-1 hover:shadow-[0_20px_40px_rgba(53,103,232,0.35)] active:scale-[0.98]"
                                            @click="openCreateModal"
                                        >
                                            <Plus class="h-5 w-5 stroke-[3px] transition-transform group-hover:rotate-90" />
                                            <span>Add Review</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto custom-scrollbar">
                                    <table class="w-full border-collapse text-left border-slate-100">
                                        <thead>
                                            <tr class="border-b border-slate-100 bg-slate-50/30">
                                                <th class="py-4 pl-6 pr-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Identity</th>
                                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Date</th>
                                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Order Ref</th>
                                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Customer & Product</th>
                                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Handling Agent</th>
                                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-center">Status</th>
                                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-center">Priority</th>
                                                <th class="px-4 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-center">Rating</th>
                                                <th class="py-4 pl-4 pr-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <tr v-for="item in badReviewRows" :key="item.id" class="group transition-colors hover:bg-slate-50/70 align-top">
                                                <!-- IDENTITY -->
                                                <td class="py-4 pl-6 pr-4">
                                                    <div class="space-y-0.5">
                                                        <p class="text-[13px] font-black text-slate-900">{{ item.brand || '-' }}</p>
                                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ item.platform || '-' }}</p>
                                                    </div>
                                                </td>

                                                <!-- DATE -->
                                                <td class="px-4 py-4">
                                                    <p class="text-[13px] font-bold text-slate-600">{{ formatDate(item.tanggal_review) }}</p>
                                                    <p v-if="item.month" class="text-[10px] font-medium text-slate-400 mt-0.5">{{ item.month }}</p>
                                                </td>

                                                <!-- ORDER REF -->
                                                <td class="px-4 py-4">
                                                    <div class="space-y-0.5">
                                                        <p class="text-[13px] font-black text-slate-900">#{{ item.order_id || '-' }}</p>
                                                        <div class="flex items-center gap-1.5">
                                                            <Tag class="h-3 w-3 text-slate-300" />
                                                            <span class="text-[10px] font-bold text-slate-400">{{ item.sku || 'No SKU' }}</span>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- CUSTOMER & PRODUCT -->
                                                <td class="px-4 py-4">
                                                    <div class="space-y-0.5 max-w-[200px]">
                                                        <p class="text-[13px] font-black text-slate-900 truncate">{{ item.username || '-' }}</p>
                                                        <p class="text-[11px] font-medium text-slate-500 line-clamp-1 italic">{{ item.product_name || '-' }}</p>
                                                    </div>
                                                </td>

                                                <!-- HANDLING AGENT -->
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <div class="h-7 w-7 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500">
                                                            {{ (item.cs_name || '?').substring(0, 2).toUpperCase() }}
                                                        </div>
                                                        <div class="space-y-0.5">
                                                            <p class="text-[13px] font-bold text-slate-700">{{ item.cs_name || 'Unassigned' }}</p>
                                                            <p class="text-[9px] font-black uppercase text-slate-400 tracking-wider">BY: {{ item.cause_by || '-' }}</p>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- STATUS -->
                                                <td class="px-4 py-4 text-center">
                                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider" :class="statusClass(item.status)">
                                                        {{ item.status }}
                                                    </span>
                                                </td>

                                                <!-- PRIORITY -->
                                                <td class="px-4 py-4 text-center">
                                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider" :class="priorityColorClass(item.priority)">
                                                        {{ item.priority || '-' }}
                                                    </span>
                                                </td>

                                                <!-- RATING -->
                                                <td class="px-4 py-4 text-center">
                                                    <div class="flex items-center justify-center gap-0.5">
                                                        <Star v-for="i in 3" :key="i" class="h-3 w-3" :class="i <= (item.star || 0) ? 'fill-amber-400 text-amber-400' : 'text-slate-200'" />
                                                    </div>
                                                </td>

                                                <!-- ACTION -->
                                                <td class="py-4 pl-4 pr-6">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        <button @click="openDetail(item)" class="h-8 w-8 rounded-lg border border-slate-100 bg-white flex items-center justify-center text-slate-400 transition-all hover:bg-blue-50 hover:text-blue-600 hover:border-blue-100 shadow-sm">
                                                            <Eye class="h-3.5 w-3.5" />
                                                        </button>
                                                        <button @click="openEditModal(item)" class="h-8 w-8 rounded-lg border border-slate-100 bg-white flex items-center justify-center text-slate-400 transition-all hover:bg-amber-50 hover:text-amber-600 hover:border-amber-100 shadow-sm">
                                                            <Edit2 class="h-3.5 w-3.5" />
                                                        </button>
                                                        <button @click="confirmDelete(item)" class="h-8 w-8 rounded-lg border border-slate-100 bg-white flex items-center justify-center text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-600 hover:border-rose-100 shadow-sm">
                                                            <Trash2 class="h-3.5 w-3.5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            <!-- EMPTY STATE -->
                            <div v-if="!badReviewRows.length" class="border-t border-slate-50 px-6 py-24 text-center">
                                <div class="mx-auto max-w-sm space-y-5">
                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[2.5rem] bg-slate-50 text-slate-300 shadow-inner">
                                        <ClipboardList class="h-10 w-10" />
                                    </div>
                                    <div class="space-y-2">
                                        <h3 class="text-2xl font-black tracking-tight text-slate-900">
                                            {{ activeFilterCount ? 'No Results Found' : 'Clean Slate' }}
                                        </h3>
                                        <p class="text-[13px] font-medium text-slate-500">
                                            {{ activeFilterCount ? 'Try adjusting your filters to find what you are looking for.' : 'No bad reviews to handle right now. Great job!' }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-center gap-3 pt-4">
                                        <button
                                            type="button"
                                            class="group inline-flex items-center justify-center gap-3 rounded-2xl bg-[var(--app-primary)] px-8 py-4 text-sm font-black text-white shadow-[0_12px_30_rgba(53,103,232,0.25)] transition-all hover:bg-[var(--app-primary-dark)] hover:-translate-y-1 active:scale-95"
                                            @click="openCreateModal"
                                        >
                                            <Plus class="h-5 w-5 stroke-[3px]" />
                                            <span>Add Review Baru</span>
                                        </button>
                                        <button
                                            v-if="activeFilterCount"
                                            type="button"
                                            class="text-sm font-bold text-slate-500 underline underline-offset-4 transition hover:text-[var(--app-primary)]"
                                            @click="resetFilters"
                                        >
                                            Reset all filters
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- PAGINATION -->
                            <div class="flex flex-col gap-5 border-t border-slate-100 bg-slate-50/30 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-[13px] font-bold text-slate-400">
                                    <span class="text-slate-900">Listing {{ badReviewPage.from || 0 }} - {{ badReviewPage.to || 0 }}</span>
                                    <span class="mx-2 text-slate-300">/</span>
                                    Total {{ badReviewPage.total || 0 }} Reviews
                                </p>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template v-for="(link, index) in paginationLinks" :key="index">
                                        <button
                                            v-if="link.url || link.active"
                                            type="button"
                                            class="flex h-10 min-w-[40px] items-center justify-center rounded-xl px-3 text-[13px] font-black transition-all"
                                            :class="
                                                link.active
                                                    ? 'bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20'
                                                    : link.url
                                                        ? 'bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 ring-1 ring-slate-200/60'
                                                        : 'cursor-not-allowed bg-slate-50 text-slate-300'
                                            "
                                            :disabled="!link.url"
                                            @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true, replace: true })"
                                        >
                                            <span v-html="link.label"></span>
                                        </button>
                                    </template>
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
                                    <span class="text-[13px] font-black text-slate-500">{{ detailItem.progress || 'Untracked' }}</span>
                                </div>
                             </div>
                        </section>
                    </div>
                </aside>
            </div>
        </transition>

        <!-- Upsert Modal (Now Uniform with Complaints) -->
        <transition name="fade">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-950/35 backdrop-blur-[2px]">
                <div class="absolute inset-y-0 right-0 h-full w-full overflow-y-auto bg-[#f8fbff] shadow-2xl 2xl:max-w-[1240px]">
                    <!-- Sticky Header -->
                    <div class="sticky top-0 z-20 flex items-center justify-between border-b px-5 py-6 sm:px-8 transition-all duration-500" :class="modalMode === 'edit' ? 'bg-slate-900 border-slate-800' : 'bg-[#EEF2FF] border-[#E0E7FF]'">
                        <div class="flex items-center gap-5">
                            <button type="button" class="flex h-11 w-11 items-center justify-center rounded-2xl transition-all active:scale-90" :class="modalMode === 'edit' ? 'bg-white/10 text-slate-300 hover:bg-white/20 hover:text-white' : 'bg-slate-900/5 text-slate-500 hover:bg-slate-900/10 hover:text-slate-900'" @click="discardForm">
                                <X class="h-5 w-5" />
                            </button>
                            <div>
                                <h2 class="text-2xl font-black tracking-tight transition-colors" :class="modalMode === 'edit' ? 'text-white' : 'text-slate-900'">{{ modalMode === 'edit' ? 'Edit Bad Review' : 'Create New Review' }}</h2>
                                <p class="mt-0.5 text-[13px] font-medium transition-colors" :class="modalMode === 'edit' ? 'text-slate-400' : 'text-slate-500'">Pastikan detail review akurat untuk pelaporan KPI yang tepat.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" class="h-11 rounded-xl px-5 text-sm font-black transition-all active:scale-95" :class="modalMode === 'edit' ? 'text-slate-400 hover:bg-white/5' : 'text-slate-500 hover:bg-slate-100'" @click="discardForm">Cancel</button>
                            <button type="button" class="h-11 rounded-xl px-6 text-sm font-black text-white shadow-xl transition-all hover:-translate-y-0.5 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50" :class="modalMode === 'edit' ? 'bg-blue-600 shadow-blue-500/20 hover:bg-blue-500' : 'bg-[var(--app-primary)] shadow-indigo-500/20 hover:bg-[var(--app-primary-dark)]'" :disabled="form.processing" @click="submitForm">
                                <div class="flex items-center gap-2">
                                    <Plus v-if="!form.processing && modalMode === 'create'" class="h-4 w-4 stroke-[3px]" />
                                    <CheckCircle2 v-else-if="!form.processing && modalMode === 'edit'" class="h-4 w-4 stroke-[3px]" />
                                    <span>{{ form.processing ? 'Syncing...' : (modalMode === 'edit' ? 'Update Data' : 'Submit Review') }}</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-5 py-8 sm:px-8">
                        <div class="mx-auto grid max-w-[1160px] gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                            <div class="space-y-7">
                                <!-- Error Alert -->
                                <div v-if="Object.keys(form.errors).length" class="rounded-[18px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                                    <p class="font-semibold">Mohon lengkapi field mandatory:</p>
                                    <ul class="mt-2 list-disc pl-5">
                                        <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                                    </ul>
                                </div>

                                <!-- Automation Preview Bar -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                        <div class="text-[11px] font-black uppercase tracking-wider text-slate-400">Automation Result</div>
                                        <div class="mt-1 text-[13px] font-semibold text-slate-700">{{ form.progress === 'Auto Reply' ? 'SOLVED' : 'PENDING' }} (Auto)</div>
                                    </div>
                                    <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                        <div class="text-[11px] font-black uppercase tracking-wider text-blue-400">Target Month</div>
                                        <div class="mt-1 text-[13px] font-semibold text-blue-700">{{ form.tanggal_review ? new Date(form.tanggal_review).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'Waiting for date...' }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                                        <div class="text-[11px] font-black uppercase tracking-wider text-indigo-400">Assigned Agent</div>
                                        <div class="mt-1 text-[13px] font-semibold text-indigo-700">{{ form.cs_name || 'Unassigned' }} | By: {{ form.cause_by || 'Auto' }}</div>
                                    </div>
                                </div>

                                <!-- SECTION 01: ORIGIN -->
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex-1">
                                            <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-blue-600">
                                                <span>Section 01</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Review Origin</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">Kapan dan dimana bad review ini diberikan oleh customer.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal Review*</label>
                                            <input v-model="form.tanggal_review" type="date" :class="controlClass('tanggal_review')" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Star Rating*</label>
                                            <div class="relative">
                                                <select v-model="form.star" :class="controlClass('star', 'select')">
                                                    <option value="" disabled>Pilih Rating Bintang</option>
                                                    <option v-for="s in ['1','2','3']" :key="s" :value="s">{{ s }} Star(s)</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2 mt-4">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Brand*</label>
                                            <div class="relative">
                                                <select v-model="form.brand" :class="controlClass('brand', 'select')">
                                                    <option value="" disabled>Pilih Brand Produk</option>
                                                    <option v-for="b in props.brandOptions" :key="b" :value="b">{{ b }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Platform*</label>
                                            <div class="relative">
                                                <select v-model="form.platform" :class="controlClass('platform', 'select')">
                                                    <option value="" disabled>Pilih Platform Sales</option>
                                                    <option v-for="p in props.platformOptions" :key="p" :value="p">{{ p }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <!-- SECTION 02: TRANSACTION -->
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex-1">
                                            <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-amber-600">
                                                <span>Section 02</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Transaction Details</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">Informasi identitas pesanan dan customer yang bersangkutan.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Order ID*</label>
                                            <input v-model="form.order_id" type="text" placeholder="Masukkan Nomor Order" :class="controlClass('order_id')" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Username*</label>
                                            <input v-model="form.username" type="text" placeholder="Masukkan Username" :class="controlClass('username')" />
                                        </div>
                                    </div>
                                </section>

                                <!-- SECTION 03: CONTENT -->
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex-1">
                                            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-emerald-600">
                                                <span>Section 03</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Problem & Sku Info</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">Detail produk dan alasan utama bad review diberikan.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">SKU Code</label>
                                            <div class="relative">
                                                <select v-model="form.sku" :class="controlClass('sku', 'select')" @change="handleSkuChange">
                                                    <option value="" disabled>Pilih SKU Code</option>
                                                    <option v-for="s in props.skuCodeOptions" :key="s.sku" :value="s.sku">{{ s.sku }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Product Name</label>
                                            <input v-model="form.product_name" type="text" :class="readonlyInputClass" readonly />
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-1 mt-4">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Sub Case (Category)*</label>
                                            <div class="relative">
                                                <select v-model="form.category_review" :class="controlClass('category_review', 'select')">
                                                    <option value="" disabled>Pilih Sub Case</option>
                                                    <option v-for="cat in props.categoryReviewOptions" :key="cat" :value="cat">{{ cat }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                            <p v-if="form.errors.category_review" class="mt-1 text-xs font-bold text-rose-500">{{ form.errors.category_review }}</p>
                                        </div>

                                        <div class="space-y-3 pt-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">By (Root Cause)*</label>
                                            <div class="flex flex-wrap gap-2">
                                                <button 
                                                    v-for="cb in props.causeByOptions" 
                                                    :key="cb" 
                                                    type="button"
                                                    class="px-4 py-2.5 rounded-xl border text-[11px] font-black uppercase tracking-wider transition-all duration-200 active:scale-95"
                                                    :class="form.cause_by === cb 
                                                        ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-500/30' 
                                                        : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                                    @click="form.cause_by = cb"
                                                >
                                                    {{ cb }}
                                                </button>
                                            </div>
                                            <p v-if="form.errors.cause_by" class="mt-1 text-xs font-bold text-rose-500">{{ form.errors.cause_by }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-2">
                                        <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Review Notes (Customer Feedback)*</label>
                                        <textarea v-model="form.review_notes" rows="4" :class="controlClass('review_notes', 'textarea')" placeholder="Tuliskan isi review customer di sini..."></textarea>
                                    </div>
                                </section>

                                <!-- SECTION 04: RESOLUTION -->
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-sm transition-all hover:shadow-md pb-12">
                                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex-1">
                                            <div class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-rose-600">
                                                <span>Section 04</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Resolution & Priority</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">Langkah tindak lanjut dan urgensi penanganan.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Progress Tracking*</label>
                                            <div class="relative">
                                            <select v-model="form.progress" :class="controlClass('progress', 'select')">
                                                <option value="" disabled>Pilih Progress Handling</option>
                                                <option value="Follow Up Customer">Follow Up Customer</option>
                                                <option value="Auto Reply">Auto Reply</option>
                                            </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Priority Level*</label>
                                            <div class="relative">
                                            <select v-model="form.priority" :class="controlClass('priority', 'select')">
                                                <option value="" disabled>Pilih Level Prioritas</option>
                                                <option v-for="p in priorityLevels" :key="p" :value="p">{{ p }} Priority</option>
                                            </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-5 sm:grid-cols-2 mt-4">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">CS Assigned*</label>
                                            <div class="relative">
                                            <select v-model="form.cs_name" :class="controlClass('cs_name', 'select')">
                                                <option value="" disabled>Pilih CS Penanggung Jawab</option>
                                                <option v-for="cs in props.csNameOptions" :key="cs" :value="cs">{{ cs }}</option>
                                            </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal Update (SLA)*</label>
                                            <input v-model="form.tanggal_update" type="datetime-local" :class="controlClass('tanggal_update')" />
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <!-- Right Helper Sidebar -->
                            <div class="hidden xl:block">
                                <div class="sticky top-[120px] space-y-6">
                                    <div class="rounded-3xl border border-blue-100 bg-blue-50/30 p-6">
                                        <h4 class="text-[14px] font-black text-blue-900">Submission Helper</h4>
                                        <p class="mt-2 text-[12px] font-medium leading-relaxed text-blue-700/70">
                                            Pastikan semua data bertanda bintang (*) telah terisi sebelum menyimpan. Status review akan berubah otomatis menjadi <span class="font-bold">SOLVED</span> jika Anda memilih <span class="font-bold underline">Auto Reply</span>.
                                        </p>
                                    </div>
                                    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                                        <h4 class="text-[14px] font-black text-slate-900">Targeting Strategy</h4>
                                        <div class="mt-4 space-y-4 text-[12px]">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white text-[8px] font-black">1</div>
                                                <p class="font-medium text-slate-600 italic">P1-P3 untuk review bintang 1 yang disertai cacat produk.</p>
                                            </div>
                                            <div class="flex items-start gap-3">
                                                <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-blue-500 text-white text-[8px] font-black">2</div>
                                                <p class="font-medium text-slate-600 italic">P4-P7 untuk review bintang 2-3 terkait keterlambatan pengiriman.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
