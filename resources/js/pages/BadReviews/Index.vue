<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
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
    const catalog: Record<string, { product_name: string }> = {};
    (props.skuCodeOptions || []).forEach((item: any) => {
        if (item?.sku) {
            catalog[item.sku] = {
                product_name: item.product_name || '',
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

const statusCards = computed(() => [
    { key: 'All', label: 'All', value: statusSummary.value.all || 0 },
    { key: 'Pending', label: 'Pending', value: statusSummary.value.pending || 0 },
    { key: 'Solved', label: 'Solved', value: statusSummary.value.solved || 0 },
]);

const activeFilterCount = computed(() =>
    [Boolean(search.value), currentStatus.value !== 'All', Boolean(currentCs.value), currentBrand.value !== 'All', currentPlatform.value !== 'All', currentStar.value !== 'All'].filter(Boolean).length
);

const activeTab = ref('cs'); // 'cs', 'status', 'star'

// ============ Form State ============
const isModalOpen = ref(false);
const modalMode = ref('create');
const detailItem = ref<Record<string, any> | null>(null);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref<Record<string, any> | null>(null);

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
    cause_by: '?',
    review_notes: '',
    progress: '',
    tanggal_update: '',
    cs_name: '',
});

const createInitialFormState = () => ({
    tanggal_review: '',
    brand: '',
    platform: '',
    order_id: '',
    username: '',
    star: '',
    product_name: '',
    sku: '',
    category_review: '',
    cause_by: '?',
    review_notes: '',
    progress: '',
    tanggal_update: '',
    cs_name: '',
});

const readonlyInputClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-[14px] text-slate-400 outline-none';
const inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const selectClass = 'w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2 pr-12 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const textAreaClass = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';

const fieldError = (field: string) => form.errors[field as keyof typeof form.errors];
const controlClass = (field: string, variant = 'input') => {
    const baseClass = variant === 'select' ? selectClass : variant === 'textarea' ? textAreaClass : inputClass;
    return fieldError(field) ? `${baseClass} border-rose-300 bg-rose-50/60 focus:border-rose-400` : baseClass;
};

const autoCauseByMap = computed(() => (props.autoCauseByMap && !Array.isArray(props.autoCauseByMap) ? props.autoCauseByMap : {}));
const resolvedAutoCauseBy = computed(() => autoCauseByMap.value[form.category_review] || null);
const causeByLocked = computed(() => Boolean(resolvedAutoCauseBy.value));
const selectedSku = computed(() => skuCatalog.value[form.sku] || {});
const statusPreview = computed(() => {
    if (form.progress === 'Auto Reply') return 'Solved';
    if (form.progress === 'Follow Up Customer') return 'Pending';
    return '-';
});
const automationResults = computed(() => ({
    cause_by: resolvedAutoCauseBy.value || form.cause_by || '?',
    status: statusPreview.value,
}));
const isHydratingEditForm = ref(false);

const syncBadReviewDerivedFields = () => {
    if (resolvedAutoCauseBy.value) {
        form.cause_by = resolvedAutoCauseBy.value;
    } else if (!form.cause_by) {
        form.cause_by = '?';
    }
};

const normalizeDateOnly = (value: unknown) => {
    if (typeof value !== 'string' || !value) return '';
    return value.slice(0, 10);
};

const completionSummary = computed(() => {
    const fields = ['tanggal_review', 'brand', 'platform', 'order_id', 'username', 'star', 'category_review', 'cause_by', 'review_notes', 'progress', 'tanggal_update', 'cs_name'];
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
            ...overrides,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    search.value = '';
    visitIndex({
        status: undefined,
        star: undefined,
        cs_name: undefined,
        brand: undefined,
        platform: undefined,
        page: 1
    });
};

watch(search, debounce((val) => visitIndex({ search: val || undefined, page: 1 }), 400));

// ============ Modal Functions ============
const openCreateModal = () => {
    modalMode.value = 'create';
    isHydratingEditForm.value = false;
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    modalMode.value = 'edit';
    detailItem.value = item;
    isHydratingEditForm.value = true;
    isModalOpen.value = true;

    nextTick(() => {
        const initialState = createInitialFormState();
        const hydratedState = { ...initialState };

        Object.keys(initialState).forEach((key) => {
            if (item[key] !== undefined) {
                hydratedState[key] = item[key] ?? initialState[key];
            }
        });

        hydratedState.tanggal_update = normalizeDateOnly(hydratedState.tanggal_update);

        form.defaults(hydratedState);
        form.reset();
        form.clearErrors();
        syncBadReviewDerivedFields();

        nextTick(() => {
            isHydratingEditForm.value = false;
        });
    });
};

const openDetail = (item) => { detailItem.value = item; };
const closeDetail = () => { detailItem.value = null; };
const discardForm = () => {
    isModalOpen.value = false;
    isHydratingEditForm.value = false;
    form.defaults(createInitialFormState());
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

watch(
    selectedSku,
    (matchedSku) => {
        if (isHydratingEditForm.value) return;
        form.product_name = matchedSku?.product_name || '';
    },
    { immediate: true, deep: true },
);

watch(() => form.category_review, (val) => {
    if (isHydratingEditForm.value) {
        return;
    }

    if (resolvedAutoCauseBy.value) {
        form.cause_by = resolvedAutoCauseBy.value;
    } else {
        form.cause_by = '?';
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

const statusDotClass = (status) => (status === 'Solved' ? 'bg-emerald-500' : 'bg-amber-500');

const starClass = (star: number | string) =>
    String(star) === '1' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700';

const selectButtonClass = (currentValue, expectedValue) =>
    currentValue === expectedValue
        ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white shadow-md'
        : 'border-slate-200 bg-white text-slate-600 hover:border-[var(--app-primary)]/40 hover:bg-slate-50';

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
                                        v-for="tab in ['cs', 'status', 'star']"
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
                                        <button v-if="link.url || link.active"
                                            type="button"
                                            class="flex h-10 min-w-[40px] items-center justify-center rounded-xl px-3 text-[13px] font-black transition-all"
                                            :class=" link.active ? 'bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20' : link.url ? 'bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 ring-1 ring-slate-200/60' : 'cursor-not-allowed bg-slate-50 text-slate-300' " :disabled="!link.url" @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true, replace: true })">
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

                    <div class="space-y-6">
                        <!-- Row 1: Date, Star, Month, Status -->
                        <section class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-2xl bg-slate-50/50 border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Review Date</p>
                                <p class="text-[14px] font-black text-slate-900 mt-1.5">{{ formatDate(detailItem.tanggal_review) }}</p>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50/50 border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Month</p>
                                <p class="text-[14px] font-black text-slate-900 mt-1.5">{{ detailItem.month || '-' }}</p>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50/50 border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Star Rating</p>
                                <div class="inline-flex items-center gap-1.5 mt-1.5 px-2.5 py-1 rounded-full text-[12px] font-black" :class="starClass(detailItem.star)">
                                    <Star class="h-3 w-3 fill-current" /> {{ detailItem.star }} Stars
                                </div>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50/50 border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Status</p>
                                <span class="inline-flex mt-1.5 px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider" :class="statusClass(detailItem.status)">{{ detailItem.status }}</span>
                            </div>
                        </section>

                        <!-- Row 2: Customer Info -->
                        <section class="p-5 rounded-2xl border border-slate-100 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 flex items-center justify-center rounded-full bg-blue-50 text-[var(--app-primary)]"><Users class="h-4 w-4" /></div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Customer</p>
                                    <p class="text-[15px] font-black text-slate-900">{{ detailItem.username || '-' }}</p>
                                    <p class="text-[12px] font-medium text-slate-500">{{ detailItem.brand }} · {{ detailItem.platform }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-50">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">SKU</p>
                                    <p class="text-[13px] font-black text-slate-800 mt-0.5">{{ detailItem.sku || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Product</p>
                                    <p class="text-[13px] font-bold text-slate-700 mt-0.5 truncate">{{ detailItem.product_name || '-' }}</p>
                                </div>
                            </div>
                        </section>

                        <!-- Row 3: Review Notes -->
                        <section class="space-y-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase px-1">Review Notes</p>
                            <div class="p-5 rounded-2xl bg-[#f8fbff]/50 border border-slate-100 ring-1 ring-slate-100/50 italic text-slate-600 leading-relaxed text-[13px]">
                                "{{ detailItem.review_notes || 'No review content provided.' }}"
                            </div>
                        </section>

                        <!-- Row 4: Category, By, Progress -->
                        <section class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Category Review</p>
                                <p class="text-[13px] font-black text-slate-800 mt-1">{{ detailItem.category_review || '-' }}</p>
                            </div>
                            <div class="p-4 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">By</p>
                                <p class="text-[13px] font-black text-slate-800 mt-1">{{ detailItem.cause_by || '-' }}</p>
                            </div>
                            <div class="col-span-2 p-4 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Progress</p>
                                <p class="text-[13px] font-black text-slate-800 mt-1">{{ detailItem.progress || '-' }}</p>
                            </div>
                        </section>

                        <!-- Row 5: CS Name, Tanggal Update -->
                        <section class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">CS Name</p>
                                <p class="text-[13px] font-black text-slate-800 mt-1">{{ detailItem.cs_name || '-' }}</p>
                            </div>
                            <div class="p-4 rounded-2xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Tanggal Update</p>
                                <p class="text-[13px] font-black text-slate-800 mt-1">{{ formatDate(detailItem.tanggal_update) }}</p>
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
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Automated Classification</div>
                                        <div class="mt-1 text-[13px] font-semibold text-slate-700">
                                            {{ statusPreview }} | {{ form.progress || 'Waiting progress' }}
                                        </div>
                                    </div>
                                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-400">Assignment & Root Cause</div>
                                        <div class="mt-1 text-[13px] font-semibold text-indigo-700">
                                            {{ form.cs_name || 'Unassigned' }} | By: {{ automationResults.cause_by }}
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION 01: ORIGIN -->
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]">
                                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex-1">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]"
                                            >
                                                <span>Section 01</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Essential Information</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                                Capture the core review details so ownership and product mapping stay consistent.
                                            </p>
                                        </div>
                                        <div class="rounded-2xl bg-indigo-50/50 p-4 ring-1 ring-indigo-100 sm:max-w-xs">
                                            <p class="text-[13px] font-medium leading-relaxed text-indigo-700">
                                                Product detail will stay aligned with the selected review date and SKU.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2">
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

                                        <div class="grid gap-5 sm:grid-cols-2">
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
                                    </div>
                                </section>

                                <!-- SECTION 02: TRANSACTION -->
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]">
                                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex-1">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]"
                                            >
                                                <span>Section 02</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Order & Customer</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                                Use consistent order and customer identity so the review can be traced back cleanly.
                                            </p>
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
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]">
                                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex-1">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]"
                                            >
                                                <span>Section 03</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Product & Root Cause</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                                Keep SKU, category, and root cause aligned so automation can classify the review correctly.
                                            </p>
                                        </div>
                                        <div class="rounded-2xl bg-indigo-50/50 p-4 ring-1 ring-indigo-100 sm:max-w-xs">
                                            <p class="text-[13px] font-medium leading-relaxed text-indigo-700">
                                                The "By" field will auto-lock when the selected sub case already has a mapped cause.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                       <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">SKU Code</label>
                                                <div class="relative">
                                                    <select v-model="form.sku" :class="controlClass('sku', 'select')">
                                                        <option value="" disabled>Pilih SKU Code</option>
                                                        <option v-for="s in props.skuCodeOptions" :key="(s as any).sku" :value="(s as any).sku">{{ (s as any).sku }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Product Name</label>
                                                <input v-model="form.product_name" type="text" :class="readonlyInputClass" readonly />
                                            </div>
                                        </div>

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
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">By (Root Cause)*</label>
                                                <span v-if="causeByLocked" class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--accent)]"
                                                    >Auto from Sub Case</span
                                                >
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <template v-if="causeByLocked">
                                                    <button
                                                        type="button"
                                                        class="cursor-not-allowed rounded-lg border px-4 py-3 text-[15px] font-bold transition"
                                                        :class="selectButtonClass(form.cause_by, form.cause_by)"
                                                        disabled
                                                    >
                                                        {{ form.cause_by }}
                                                    </button>
                                                </template>
                                                <template v-else>
                                                    <button
                                                        v-for="cb in props.causeByOptions"
                                                        :key="cb"
                                                        type="button"
                                                        class="rounded-lg border px-4 py-3 text-[15px] font-bold transition"
                                                        :class="selectButtonClass(form.cause_by, cb)"
                                                        @click="form.cause_by = cb"
                                                    >
                                                        {{ cb }}
                                                    </button>
                                                </template>
                                            </div>
                                            <p v-if="form.errors.cause_by" class="mt-2 text-xs font-medium text-rose-600">{{ form.errors.cause_by }}</p>
                                        </div>

                                        <div class="mt-4 space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Review Notes (Customer Feedback)*</label>
                                            <textarea
                                                v-model="form.review_notes"
                                                rows="4"
                                                :class="controlClass('review_notes', 'textarea')"
                                                placeholder="Tuliskan isi review customer di sini..."
                                            ></textarea>
                                        </div>
                                    </div>
                                </section>

                                <!-- SECTION 04: RESOLUTION -->
                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]">
                                    <div class="mb-6 border-b border-slate-50 pb-5">
                                        <div
                                            class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]"
                                        >
                                            <span>Section 04</span>
                                        </div>
                                        <h3 class="mt-2 text-lg font-black text-slate-900">Resolution</h3>
                                        <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                            Define the next handling step and ensure consistency with automation rules.
                                        </p>
                                    </div>

                                    <div class="space-y-4">
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
                                                <input v-model="form.tanggal_update" type="date" :class="controlClass('tanggal_update')" />
                                            </div>
                                        </div>
                                </section>
                            </div>

                            <!-- Right Helper Sidebar -->
                            <aside class="space-y-6">
                                <div class="sticky top-12 space-y-6">
                                    <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/50">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Live Outcome</p>
                                        <div class="mt-4 space-y-4">
                                            <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-slate-400">Projected Status</p>
                                                <div
                                                    class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm"
                                                    :class="statusClass(statusPreview)"
                                                >
                                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full" :class="statusDotClass(statusPreview)"></span>
                                                    {{ statusPreview }}
                                                </div>
                                            </div>

                                            <div class="grid gap-2.5 sm:grid-cols-1">
                                                <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-slate-400">Progress</p>
                                                    <p class="mt-0.5 text-[10px] font-black uppercase text-[var(--app-primary)]">
                                                        {{ form.progress || 'Not set' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div v-if="resolvedAutoCauseBy" class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-slate-400">Categorization</p>
                                                <p class="mt-1 text-[13px] font-bold leading-tight text-slate-700">{{ resolvedAutoCauseBy }}</p>
                                            </div>

                                            <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-blue-400">Product Snapshot</p>
                                                <p class="mt-0.5 text-[12px] font-bold text-blue-700">
                                                    {{ form.product_name || 'Waiting SKU selection' }}
                                                </p>
                                            </div>

                                            <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-amber-400">SKU Source</p>
                                                <p class="mt-0.5 text-[11px] font-bold text-amber-700">
                                                    {{ form.sku || 'Waiting SKU selection' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-slate-100 bg-[var(--app-ink)] p-6 text-white shadow-xl">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-white/40">Quick Guidelines</p>
                                        <ul class="mt-4 space-y-3">
                                            <li class="flex items-start gap-2.5">
                                                <div class="h-4.5 w-4.5 flex shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                    <CheckCircle2 class="h-2.5 w-2.5" />
                                                </div>
                                                <p class="text-[12px] font-medium leading-tight text-white/80">
                                                    Start with <span class="font-bold text-white">review origin</span> before mapping product and owner.
                                                </p>
                                            </li>
                                            <li class="flex items-start gap-2.5">
                                                <div class="h-4.5 w-4.5 flex shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                    <CheckCircle2 class="h-2.5 w-2.5" />
                                                </div>
                                                <p class="text-[12px] font-medium leading-tight text-white/80">
                                                    The <span class="font-bold text-white">"By"</span> field will auto-lock based on sub-case mapping.
                                                </p>
                                            </li>
                                            <li class="flex items-start gap-2.5">
                                                <div class="h-4.5 w-4.5 flex shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                    <CheckCircle2 class="h-2.5 w-2.5" />
                                                </div>
                                                <p class="text-[12px] font-medium leading-tight text-white/80">
                                                    Choose <span class="font-bold text-white">Auto Reply</span> only when the review is already fully handled.
                                                </p>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </aside>
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
