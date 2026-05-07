<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import {
    AlertCircle,
    AlertTriangle,
    BoxesIcon,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
    Eye,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch, type PropType } from 'vue';

const DEFAULT_UPDATE_CS_OPTIONS = ['Done Blast', 'Cancel'];

const today = () => {
    const d = new Date();
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().split('T')[0];
};

const createEmptyPaginator = () => ({
    current_page: 1,
    data: [] as any[],
    from: 0,
    last_page: 1,
    links: [] as any[],
    per_page: 10,
    to: 0,
    total: 0,
});

const inputClass =
    'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const readonlyInputClass =
    'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-[14px] text-slate-400 outline-none';
const selectClass =
    'w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2 pr-12 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const textAreaClass =
    'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';

const props = defineProps({
    oosData: Object,
    filters: Object,
    overview: Object,
    brandOptions: Array as PropType<string[]>,
    platformOptions: Array as PropType<string[]>,
    csNameOptions: Array as PropType<string[]>,
    supportsAgentAssignment: Boolean,
    reasonOptions: Array as PropType<string[]>,
    solutionOptions: Array as PropType<string[]>,
    updateCsOptions: Array as PropType<string[]>,
});

const oosPage = computed(() => ({ ...createEmptyPaginator(), ...(props.oosData || {}) }));
const oosRows = computed(() => (Array.isArray(oosPage.value.data) ? oosPage.value.data : []));
const paginationLinks = computed(() => (Array.isArray(oosPage.value.links) ? oosPage.value.links : []));
const filterState = computed(() => (props.filters && !Array.isArray(props.filters) ? props.filters : {}));
const overview = computed(() => props.overview || { total: 0, done_blast: 0, cancel: 0, pending: 0 });

const brandFilterOptions = computed(() => ['All', ...(props.brandOptions || [])]);
const platformFilterOptions = computed(() => ['All', ...(props.platformOptions || [])]);
const reasonFilterOptions = computed(() => ['All', ...(props.reasonOptions || [])]);
const updateCsFilterOptions = computed(() => ['All', ...(props.updateCsOptions || DEFAULT_UPDATE_CS_OPTIONS)]);

const overviewCards = computed(() => [
    { label: 'Total Cases', value: overview.value.total, icon: ClipboardList, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Done Blast', value: overview.value.done_blast, icon: CheckCircle2, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { label: 'Cancel', value: overview.value.cancel, icon: AlertTriangle, color: 'text-rose-600', bg: 'bg-rose-50' },
    { label: 'Pending', value: overview.value.pending, icon: AlertCircle, color: 'text-amber-600', bg: 'bg-amber-50' },
]);

const search = ref(filterState.value.search || '');
const currentBrand = computed(() => filterState.value.brand || 'All');
const currentPlatform = computed(() => filterState.value.platform || 'All');
const currentReason = computed(() => filterState.value.reason || 'All');
const currentUpdateCs = computed(() => filterState.value.update_cs || 'All');

const hasActiveFilters = computed(() =>
    Boolean(
        search.value ||
            currentBrand.value !== 'All' ||
            currentPlatform.value !== 'All' ||
            currentReason.value !== 'All' ||
            currentUpdateCs.value !== 'All',
    ),
);

const activeFilterCount = computed(() =>
    [
        Boolean(search.value),
        currentBrand.value !== 'All',
        currentPlatform.value !== 'All',
        currentReason.value !== 'All',
        currentUpdateCs.value !== 'All',
    ].filter(Boolean).length,
);

// ============ Bulk Delete ============
const selectedIds = ref<number[]>([]);
const isBulkDeleteModalOpen = ref(false);
const bulkDeleteForm = useForm({ ids: [] as number[] });

const currentPageIds = computed(() => oosRows.value.map((item: any) => item.id));
const isAllSelected = computed(
    () => currentPageIds.value.length > 0 && currentPageIds.value.every((id: number) => selectedIds.value.includes(id)),
);

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !currentPageIds.value.includes(id));
    } else {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...currentPageIds.value]));
    }
};

const toggleSelect = (id: number) => {
    const index = selectedIds.value.indexOf(id);
    if (index !== -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const confirmBulkDelete = () => {
    if (!selectedIds.value.length) return;
    isBulkDeleteModalOpen.value = true;
};

const submitBulkDelete = () => {
    if (!selectedIds.value.length) return;
    bulkDeleteForm.ids = [...selectedIds.value];
    bulkDeleteForm.post(route('oos.bulk-delete'), {
        preserveScroll: true,
        onSuccess: () => {
            isBulkDeleteModalOpen.value = false;
            selectedIds.value = [];
        },
    });
};

const visitIndex = (overrides = {}, options = {}) => {
    selectedIds.value = [];
    router.get(
        route('oos.index'),
        {
            search: search.value || undefined,
            brand: filterState.value.brand && filterState.value.brand !== 'All' ? filterState.value.brand : undefined,
            platform: filterState.value.platform && filterState.value.platform !== 'All' ? filterState.value.platform : undefined,
            reason: filterState.value.reason && filterState.value.reason !== 'All' ? filterState.value.reason : undefined,
            update_cs: filterState.value.update_cs && filterState.value.update_cs !== 'All' ? filterState.value.update_cs : undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true, ...options },
    );
};

watch(search, debounce((value: string) => visitIndex({ search: value || undefined, page: 1 }), 350));

const setBrandFilter = (v: string) => visitIndex({ brand: v === 'All' ? undefined : v, page: 1 }, { replace: false });
const setPlatformFilter = (v: string) => visitIndex({ platform: v === 'All' ? undefined : v, page: 1 }, { replace: false });
const setReasonFilter = (v: string) => visitIndex({ reason: v === 'All' ? undefined : v, page: 1 }, { replace: false });
const setUpdateCsFilter = (v: string) => visitIndex({ update_cs: v === 'All' ? undefined : v, page: 1 }, { replace: false });

const resetFilters = () => {
    search.value = '';
    visitIndex({ search: undefined, brand: undefined, platform: undefined, reason: undefined, update_cs: undefined, page: 1 }, { replace: false });
};

const createInitialFormState = () => ({
    tanggal_input: today(),
    brand: '',
    platform: '',
    cs_name: '',
    order_id: '',
    product_name: '',
    sku: '',
    reason: '',
    solusi: '',
    note_detail_varian: '',
    update_cs: '',
    tanggal_blast: '',
    feedback_customers: '',
});

const form = useForm(createInitialFormState());

const isModalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const editId = ref<number | null>(null);
const detailItem = ref<any | null>(null);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref<any | null>(null);
const submitError = ref('');

const fieldError = (field: string) => (form.errors as Record<string, string>)[field];
const controlClass = (field: string, variant = 'input') => {
    const base = variant === 'select' ? selectClass : variant === 'textarea' ? textAreaClass : inputClass;
    return fieldError(field) ? `${base} border-rose-300 bg-rose-50/60` : base;
};

const monthPreview = computed(() => {
    if (!form.tanggal_input) return '';
    const d = new Date(form.tanggal_input);
    if (isNaN(d.getTime())) return '';
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    return `${months[d.getMonth()]} ${d.getFullYear()}`;
});

const discardForm = () => {
    submitError.value = '';
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    editId.value = null;
    isModalOpen.value = false;
    modalMode.value = 'create';
};

const openCreateModal = () => {
    modalMode.value = 'create';
    editId.value = null;
    submitError.value = '';
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (item: any) => {
    modalMode.value = 'edit';
    editId.value = item.id;
    submitError.value = '';
    isModalOpen.value = true;

    const initial = createInitialFormState();
    const hydrated: Record<string, any> = { ...initial };
    Object.keys(initial).forEach((key) => {
        if (item[key] !== undefined) hydrated[key] = item[key] ?? initial[key as keyof typeof initial];
    });

    form.defaults(hydrated as any);
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    submitError.value = '';
    form.transform((data) => ({
        ...data,
        _method: modalMode.value === 'edit' ? 'PUT' : 'POST',
    })).post(
        modalMode.value === 'edit' ? route('oos.update', editId.value as any) : route('oos.store'),
        {
            preserveScroll: true,
            onSuccess: () => discardForm(),
            onError: () => { submitError.value = 'Data gagal disimpan. Periksa field wajib.'; },
        },
    );
};

const openDetail = (item: any) => { detailItem.value = item; };
const closeDetail = () => { detailItem.value = null; };
const confirmDelete = (item: any) => { itemToDelete.value = item; isDeleteModalOpen.value = true; };

const submitDelete = () => {
    if (!itemToDelete.value) return;
    router.delete(route('oos.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => { isDeleteModalOpen.value = false; itemToDelete.value = null; },
    });
};

const formatDate = (value: string) => {
    if (!value) return '-';
    const d = new Date(value);
    return isNaN(d.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
};

const updateCsClass = (v: string) =>
    v === 'Done Blast'
        ? 'bg-emerald-50 text-emerald-700'
        : v === 'Cancel'
          ? 'bg-rose-50 text-rose-700'
          : 'bg-amber-50 text-amber-700';
</script>

<template>
    <Head title="OOS" />

    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'OOS', href: '/oos' },
        ]"
    >
        <div class="pb-20">
            <div class="mx-auto flex max-w-[90rem] flex-col gap-10 px-4 font-sans sm:px-6 lg:px-8">

                <!-- Overview cards -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="card in overviewCards"
                        :key="card.label"
                        class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-[var(--app-primary)]/5 blur-2xl"></div>
                        <div class="relative z-10">
                            <p class="text-[9px] font-black uppercase tracking-[0.15rem] text-slate-400">{{ card.label }}</p>
                            <div class="mt-1.5 flex items-end justify-between">
                                <p class="text-2xl font-black tracking-tight text-slate-900">{{ card.value }}</p>
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg" :class="card.bg">
                                    <component :is="card.icon" class="h-3 w-3" :class="card.color" />
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Filters bar -->
                <div class="rounded-3xl border border-slate-100 bg-slate-50/50 p-2 shadow-sm ring-1 ring-slate-100/10">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3 px-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-blue-500 shadow-sm ring-1 ring-slate-200/50">
                                <AlertCircle class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase leading-none tracking-widest text-slate-400">Global Filters</p>
                                <p class="mt-1 text-[13px] font-black leading-none text-slate-900">Refine Workspace</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pr-1">
                            <div class="relative min-w-[140px]">
                                <select
                                    :value="currentBrand"
                                    class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300"
                                    @change="setBrandFilter(($event.target as HTMLSelectElement).value)"
                                >
                                    <option v-for="opt in brandFilterOptions" :key="opt" :value="opt">
                                        {{ opt === 'All' ? 'ANY BRAND' : opt }}
                                    </option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>

                            <div class="relative min-w-[130px]">
                                <select
                                    :value="currentPlatform"
                                    class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300"
                                    @change="setPlatformFilter(($event.target as HTMLSelectElement).value)"
                                >
                                    <option v-for="opt in platformFilterOptions" :key="opt" :value="opt">
                                        {{ opt === 'All' ? 'ANY PLATFORM' : opt }}
                                    </option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>

                            <div class="relative min-w-[150px]">
                                <select
                                    :value="currentReason"
                                    class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300"
                                    @change="setReasonFilter(($event.target as HTMLSelectElement).value)"
                                >
                                    <option v-for="opt in reasonFilterOptions" :key="opt" :value="opt">
                                        {{ opt === 'All' ? 'ANY REASON' : opt }}
                                    </option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>

                            <div class="relative min-w-[140px]">
                                <select
                                    :value="currentUpdateCs"
                                    class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300"
                                    @change="setUpdateCsFilter(($event.target as HTMLSelectElement).value)"
                                >
                                    <option v-for="opt in updateCsFilterOptions" :key="opt" :value="opt">
                                        {{ opt === 'All' ? 'ANY UPDATE CS' : opt }}
                                    </option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            </div>

                            <transition name="fade">
                                <button
                                    v-if="hasActiveFilters"
                                    type="button"
                                    @click="resetFilters"
                                    class="flex h-10 items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 text-[11px] font-black uppercase tracking-wider text-rose-600 shadow-sm transition-all hover:border-rose-300 hover:bg-rose-100"
                                >
                                    <RotateCcw class="h-3.5 w-3.5" />
                                    <span>Reset</span>
                                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white">
                                        {{ activeFilterCount }}
                                    </span>
                                </button>
                            </transition>
                        </div>
                    </div>
                </div>

                <!-- Table section -->
                <section class="overflow-hidden rounded-[24px] border border-slate-100 bg-white shadow-sm">
                    <div class="flex flex-col gap-6 border-b border-slate-100 px-6 py-7 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                Stock Incident
                            </div>
                            <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">OOS Workspace</h2>
                            <div class="mt-2.5 flex items-center gap-2">
                                <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500 ring-1 ring-slate-200/50">
                                    SHOWING {{ oosPage.from || 0 }}-{{ oosPage.to || 0 }} OF {{ oosPage.total || 0 }}
                                </div>
                                <div
                                    class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                                    :class="activeFilterCount ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-100 bg-white text-slate-400'"
                                >
                                    {{ activeFilterCount ? `${activeFilterCount} Active Filters` : 'No Filter' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col gap-4 lg:max-w-xl lg:flex-row lg:items-center lg:justify-end">
                            <div class="group relative flex-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <Search class="h-4 w-4 text-slate-400 transition-colors group-focus-within:text-[var(--app-primary)]" />
                                </div>
                                <input
                                    v-model="search"
                                    type="text"
                                    class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 pl-11 pr-4 text-[13px] font-medium text-slate-900 outline-none transition-all focus:border-[var(--app-primary)] focus:bg-white focus:ring-4 focus:ring-[var(--app-primary)]/10"
                                    placeholder="Search order ID, SKU, brand, reason..."
                                />
                            </div>

                            <button
                                v-if="selectedIds.length > 0"
                                type="button"
                                class="flex h-12 items-center justify-center gap-2 rounded-2xl bg-rose-600 px-6 text-[14px] font-black text-white shadow-[0_15px_30px_rgba(220,38,38,0.25)] transition-all hover:bg-rose-700 hover:-translate-y-1 active:scale-[0.98]"
                                @click="confirmBulkDelete"
                            >
                                <Trash2 class="h-5 w-5" />
                                <span>Delete ({{ selectedIds.length }})</span>
                            </button>
                            <button
                                type="button"
                                class="group flex h-12 items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-6 text-[14px] font-black text-white shadow-[0_15px_30px_rgba(53,103,232,0.25)] transition-all hover:-translate-y-1 hover:bg-[var(--app-primary-dark)] hover:shadow-[0_20px_40px_rgba(53,103,232,0.35)] active:scale-[0.98]"
                                @click="openCreateModal"
                            >
                                <Plus class="h-5 w-5 stroke-[3px]" />
                                <span>Create OOS</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full min-w-[1100px] border-collapse text-left">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/30 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                                    <th class="py-4 pl-4 pr-2 w-10">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                            :checked="isAllSelected"
                                            @change="toggleSelectAll"
                                        />
                                    </th>
                                    <th class="py-4 pl-6 pr-4">No</th>
                                    <th class="px-4 py-4">Tgl Input</th>
                                    <th class="px-4 py-4">Order ID</th>
                                    <th class="px-4 py-4">Brand / Platform</th>
                                    <th class="px-4 py-4">Product / SKU</th>
                                    <th class="px-4 py-4">Reason</th>
                                    <th class="px-4 py-4">Solusi</th>
                                    <th class="px-4 py-4 text-center">Update CS</th>
                                    <th class="px-4 py-4">Tgl Blast</th>
                                    <th class="px-4 py-4">Month</th>
                                    <th class="py-4 pl-4 pr-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(item, index) in oosRows"
                                    :key="item.id"
                                    class="group align-top transition-colors hover:bg-slate-50/70"
                                    :class="selectedIds.includes(item.id) ? 'bg-blue-50/30' : ''"
                                >
                                    <td class="py-4 pl-4 pr-2">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                            :checked="selectedIds.includes(item.id)"
                                            @change="toggleSelect(item.id)"
                                        />
                                    </td>
                                    <td class="py-4 pl-6 pr-4">
                                        <span class="text-[10px] font-black text-slate-400">
                                            {{ ((oosPage.current_page || 1) - 1) * (oosPage.per_page || 10) + index + 1 }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[12px] font-bold text-slate-700">{{ formatDate(item.tanggal_input) }}</p>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[12px] font-black text-slate-900">#{{ item.order_id || '-' }}</p>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[12px] font-black text-slate-900">{{ item.brand || '-' }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ item.platform || '-' }}</p>
                                        <p v-if="props.supportsAgentAssignment" class="text-[10px] font-medium text-slate-400">{{ item.cs_name || 'Unassigned Agent' }}</p>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[12px] font-bold text-slate-700">{{ item.product_name || '-' }}</p>
                                        <p class="text-[10px] font-medium text-slate-400">{{ item.sku || '-' }}</p>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[12px] font-bold text-slate-600">{{ item.reason || '-' }}</p>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[12px] font-bold text-slate-600">{{ item.solusi || '-' }}</p>
                                    </td>

                                    <td class="px-4 py-4 text-center">
                                        <span
                                            v-if="item.update_cs"
                                            class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider"
                                            :class="updateCsClass(item.update_cs)"
                                        >
                                            {{ item.update_cs }}
                                        </span>
                                        <span v-else class="text-[10px] font-bold text-slate-300">-</span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[12px] font-bold text-slate-600">{{ formatDate(item.tanggal_blast) }}</p>
                                    </td>

                                    <td class="px-4 py-4">
                                        <p class="text-[11px] font-bold text-slate-500">{{ item.month || '-' }}</p>
                                    </td>

                                    <td class="py-4 pl-4 pr-6">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="openDetail(item)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 shadow-sm transition-all hover:border-blue-100 hover:bg-blue-50 hover:text-blue-600"
                                            >
                                                <Eye class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                @click="openEditModal(item)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 shadow-sm transition-all hover:border-amber-100 hover:bg-amber-50 hover:text-amber-600"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                @click="confirmDelete(item)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 shadow-sm transition-all hover:border-rose-100 hover:bg-rose-50 hover:text-rose-600"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!oosRows.length" class="border-t border-slate-50 px-6 py-24 text-center">
                        <div class="mx-auto max-w-sm space-y-5">
                            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[2.5rem] bg-slate-50 text-slate-300 shadow-inner">
                                <BoxesIcon class="h-10 w-10" />
                            </div>
                            <div class="space-y-2">
                                <h3 class="text-2xl font-black tracking-tight text-slate-900">
                                    {{ hasActiveFilters ? 'No Results Found' : 'Clean Slate' }}
                                </h3>
                                <p class="text-[13px] font-medium text-slate-500">
                                    {{ hasActiveFilters ? 'Coba sesuaikan filter pencarian.' : 'Belum ada data OOS.' }}
                                </p>
                            </div>
                            <div class="flex flex-col items-center gap-3 pt-4">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center gap-3 rounded-2xl bg-[var(--app-primary)] px-8 py-4 text-sm font-black text-white shadow-[0_12px_30px_rgba(53,103,232,0.25)] transition-all hover:-translate-y-1 hover:bg-[var(--app-primary-dark)] active:scale-95"
                                    @click="openCreateModal"
                                >
                                    <Plus class="h-5 w-5 stroke-[3px]" />
                                    <span>Tambah Data Baru</span>
                                </button>
                                <button
                                    v-if="hasActiveFilters"
                                    type="button"
                                    class="text-sm font-bold text-slate-500 underline underline-offset-4 transition hover:text-[var(--app-primary)]"
                                    @click="resetFilters"
                                >
                                    Reset all filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="flex flex-col gap-5 border-t border-slate-100 bg-slate-50/30 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[13px] font-bold text-slate-400">
                            <span class="text-slate-900">Listing {{ oosPage.from || 0 }} - {{ oosPage.to || 0 }}</span>
                            <span class="mx-2 text-slate-300">/</span>
                            Total {{ oosPage.total || 0 }} Records
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <template v-for="(link, index) in paginationLinks" :key="index">
                                <button
                                    v-if="link.url || link.active"
                                    type="button"
                                    class="flex h-10 min-w-[40px] items-center justify-center rounded-xl px-3 text-[13px] font-black transition-all"
                                    :class="link.active ? 'bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20' : link.url ? 'bg-white text-slate-600 ring-1 ring-slate-200/60 hover:bg-slate-50' : 'cursor-not-allowed bg-slate-50 text-slate-300'"
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

        <!-- Detail panel -->
        <transition name="fade">
            <div v-if="detailItem" class="fixed inset-0 z-40 bg-slate-950/20 backdrop-blur-[1px]" @click.self="closeDetail">
                <aside class="absolute right-0 top-0 h-full w-full max-w-xl overflow-y-auto bg-white p-8 shadow-2xl">
                    <header class="mb-8 flex items-center justify-between border-b border-slate-100 pb-6">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Detail OOS</p>
                            <h3 class="mt-1 text-2xl font-black text-slate-900">#{{ detailItem.order_id }}</h3>
                        </div>
                        <button @click="closeDetail" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-500">
                            <X class="h-5 w-5" />
                        </button>
                    </header>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Tanggal Input</p>
                                <p class="mt-1.5 text-[14px] font-black text-slate-900">{{ formatDate(detailItem.tanggal_input) }}</p>
                            </div>
                            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-blue-400">Month</p>
                                <p class="mt-1.5 text-[14px] font-black text-blue-900">{{ detailItem.month || '-' }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 p-5">
                            <p class="text-[10px] font-bold uppercase text-slate-400">Brand / Platform</p>
                            <p class="mt-1.5 text-[15px] font-black text-slate-900">{{ detailItem.brand || '-' }} / {{ detailItem.platform || '-' }}</p>
                            <p v-if="props.supportsAgentAssignment" class="mt-1 text-[12px] font-bold text-slate-500">{{ detailItem.cs_name || 'Unassigned Agent' }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Product Name</p>
                                <p class="mt-1.5 text-[13px] font-bold text-slate-700">{{ detailItem.product_name || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-slate-400">SKU</p>
                                <p class="mt-1.5 text-[13px] font-bold text-slate-700">{{ detailItem.sku || '-' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Reason</p>
                                <p class="mt-1.5 text-[13px] font-bold text-slate-700">{{ detailItem.reason || '-' }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Solusi</p>
                                <p class="mt-1.5 text-[13px] font-bold text-slate-700">{{ detailItem.solusi || '-' }}</p>
                            </div>
                        </div>

                        <div v-if="detailItem.note_detail_varian" class="rounded-2xl border border-slate-100 p-5">
                            <p class="text-[10px] font-bold uppercase text-slate-400">Note / Detail Varian</p>
                            <p class="mt-2 text-[13px] font-medium leading-relaxed text-slate-600">{{ detailItem.note_detail_varian }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Update CS</p>
                                <span
                                    v-if="detailItem.update_cs"
                                    class="mt-1.5 inline-flex rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider"
                                    :class="updateCsClass(detailItem.update_cs)"
                                >
                                    {{ detailItem.update_cs }}
                                </span>
                                <p v-else class="mt-1.5 text-[13px] font-bold text-slate-400">-</p>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Tanggal Blast</p>
                                <p class="mt-1.5 text-[13px] font-bold text-slate-700">{{ formatDate(detailItem.tanggal_blast) }}</p>
                            </div>
                        </div>

                        <div v-if="detailItem.feedback_customers" class="rounded-2xl border border-slate-100 p-5">
                            <p class="text-[10px] font-bold uppercase text-slate-400">Feedback Customers</p>
                            <p class="mt-2 text-[13px] font-medium leading-relaxed text-slate-600">{{ detailItem.feedback_customers }}</p>
                        </div>
                    </div>
                </aside>
            </div>
        </transition>

        <!-- Create / Edit modal -->
        <transition name="fade">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-950/35 backdrop-blur-[2px]">
                <div class="absolute inset-y-0 right-0 h-full w-full overflow-y-auto bg-[#f8fbff] shadow-2xl 2xl:max-w-[900px]">
                    <!-- Modal header -->
                    <div
                        class="sticky top-0 z-20 flex items-center justify-between border-b px-5 py-6 transition-all duration-500 sm:px-8"
                        :class="modalMode === 'edit' ? 'border-slate-800 bg-slate-900' : 'border-[#E0E7FF] bg-[#EEF2FF]'"
                    >
                        <div class="flex items-center gap-5">
                            <button
                                type="button"
                                class="flex h-11 w-11 items-center justify-center rounded-2xl transition-all active:scale-90"
                                :class="modalMode === 'edit' ? 'bg-white/10 text-slate-300 hover:bg-white/20 hover:text-white' : 'bg-slate-900/5 text-slate-500 hover:bg-slate-900/10 hover:text-slate-900'"
                                @click="discardForm"
                            >
                                <X class="h-5 w-5" />
                            </button>
                            <div>
                                <h2 class="text-2xl font-black tracking-tight transition-colors" :class="modalMode === 'edit' ? 'text-white' : 'text-slate-900'">
                                    {{ modalMode === 'edit' ? 'Edit OOS' : 'Create OOS' }}
                                </h2>
                                <p class="mt-0.5 text-[13px] font-medium transition-colors" :class="modalMode === 'edit' ? 'text-slate-400' : 'text-slate-500'">
                                    Month otomatis dari Tanggal Input.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="h-11 rounded-xl px-5 text-sm font-black transition-all active:scale-95"
                                :class="modalMode === 'edit' ? 'text-slate-400 hover:bg-white/5' : 'text-slate-500 hover:bg-slate-100'"
                                @click="discardForm"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="h-11 rounded-xl px-6 text-sm font-black text-white shadow-xl transition-all hover:-translate-y-0.5 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="modalMode === 'edit' ? 'bg-blue-600 shadow-blue-500/20 hover:bg-blue-500' : 'bg-[var(--app-primary)] shadow-indigo-500/20 hover:bg-[var(--app-primary-dark)]'"
                                :disabled="form.processing"
                                @click="submitForm"
                            >
                                <div class="flex items-center gap-2">
                                    <Plus v-if="!form.processing && modalMode === 'create'" class="h-4 w-4 stroke-[3px]" />
                                    <CheckCircle2 v-else-if="!form.processing && modalMode === 'edit'" class="h-4 w-4 stroke-[3px]" />
                                    <span>{{ form.processing ? 'Menyimpan...' : (modalMode === 'edit' ? 'Update Data' : 'Submit OOS') }}</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="px-5 py-8 sm:px-8">
                        <div class="mx-auto max-w-[820px] space-y-7">

                            <!-- Error banner -->
                            <div v-if="submitError || Object.keys(form.errors).length" class="rounded-[18px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                                <p class="font-semibold">{{ submitError || 'Mohon lengkapi field mandatory:' }}</p>
                                <ul v-if="Object.keys(form.errors).length" class="mt-2 list-disc pl-5">
                                    <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                                </ul>
                            </div>

                            <!-- Month preview card -->
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-blue-400">Month (Auto)</div>
                                    <div class="mt-1 text-[14px] font-black text-blue-700">{{ monthPreview || '-' }}</div>
                                </div>
                            </div>

                            <!-- Section 01: Basic Info -->
                            <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)]">
                                <div class="mb-6 border-b border-slate-50 pb-5">
                                    <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                        Section 01
                                    </div>
                                    <h3 class="mt-2 text-lg font-black text-slate-900">Basic Information</h3>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal Input*</label>
                                            <input v-model="form.tanggal_input" type="date" :class="controlClass('tanggal_input')" />
                                            <p v-if="fieldError('tanggal_input')" class="text-[11px] text-rose-500">{{ fieldError('tanggal_input') }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Month</label>
                                            <input :value="monthPreview || 'Auto dari tanggal input'" type="text" readonly :class="readonlyInputClass" />
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Brand*</label>
                                            <div class="relative">
                                                <select v-model="form.brand" :class="controlClass('brand', 'select')">
                                                    <option value="" disabled>Pilih Brand</option>
                                                    <option v-for="opt in props.brandOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                            <p v-if="fieldError('brand')" class="text-[11px] text-rose-500">{{ fieldError('brand') }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Platform*</label>
                                            <div class="relative">
                                                <select v-model="form.platform" :class="controlClass('platform', 'select')">
                                                    <option value="" disabled>Pilih Platform</option>
                                                    <option v-for="opt in props.platformOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                            <p v-if="fieldError('platform')" class="text-[11px] text-rose-500">{{ fieldError('platform') }}</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div v-if="props.supportsAgentAssignment" class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">CS Name</label>
                                            <div class="relative">
                                                <select v-model="form.cs_name" :class="controlClass('cs_name', 'select')">
                                                    <option value="">Pilih Agent</option>
                                                    <option v-for="opt in (props.csNameOptions || [])" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                            <p v-if="fieldError('cs_name')" class="text-[11px] text-rose-500">{{ fieldError('cs_name') }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">No Order*</label>
                                            <input v-model="form.order_id" type="text" placeholder="Masukkan No Order" :class="controlClass('order_id')" />
                                            <p v-if="fieldError('order_id')" class="text-[11px] text-rose-500">{{ fieldError('order_id') }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Product Name</label>
                                            <input v-model="form.product_name" type="text" placeholder="Nama produk" :class="controlClass('product_name')" />
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">SKU</label>
                                            <input v-model="form.sku" type="text" placeholder="Kode SKU" :class="controlClass('sku')" />
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Section 02: Handling -->
                            <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)]">
                                <div class="mb-6 border-b border-slate-50 pb-5">
                                    <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                        Section 02
                                    </div>
                                    <h3 class="mt-2 text-lg font-black text-slate-900">Handling & Update</h3>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Reason</label>
                                            <div class="relative">
                                                <select v-model="form.reason" :class="controlClass('reason', 'select')">
                                                    <option value="">Pilih Reason</option>
                                                    <option v-for="opt in props.reasonOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Solusi</label>
                                            <div class="relative">
                                                <select v-model="form.solusi" :class="controlClass('solusi', 'select')">
                                                    <option value="">Pilih Solusi</option>
                                                    <option v-for="opt in props.solutionOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Note / Detail Varian Yang Ditawarkan</label>
                                        <textarea v-model="form.note_detail_varian" rows="3" :class="controlClass('note_detail_varian', 'textarea')" placeholder="Catatan atau detail varian yang ditawarkan..."></textarea>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Update CS</label>
                                            <div class="relative">
                                                <select v-model="form.update_cs" :class="controlClass('update_cs', 'select')">
                                                    <option value="">Pilih Update CS</option>
                                                    <option v-for="opt in (props.updateCsOptions || DEFAULT_UPDATE_CS_OPTIONS)" :key="opt" :value="opt">{{ opt }}</option>
                                                </select>
                                                <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal Blast</label>
                                            <input v-model="form.tanggal_blast" type="date" :class="controlClass('tanggal_blast')" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Feedback Customers</label>
                                        <textarea v-model="form.feedback_customers" rows="3" :class="controlClass('feedback_customers', 'textarea')" placeholder="Feedback dari customers..."></textarea>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Delete modal -->
        <transition name="fade">
            <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isDeleteModalOpen = false">
                <div class="w-full max-w-md overflow-hidden rounded-[32px] bg-white shadow-2xl">
                    <div class="bg-rose-50 px-8 py-10">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-rose-600 shadow-sm ring-1 ring-rose-100">
                            <Trash2 class="h-7 w-7" />
                        </div>
                        <h3 class="text-3xl font-black tracking-tight text-slate-900">Delete OOS</h3>
                        <p class="mt-2 text-[15px] font-medium leading-relaxed text-slate-500">
                            Data OOS untuk order <b>#{{ itemToDelete?.order_id }}</b> akan dihapus permanen.
                        </p>
                    </div>
                    <div class="flex gap-3 bg-white p-8">
                        <button @click="isDeleteModalOpen = false" class="h-12 flex-1 rounded-2xl bg-slate-50 text-[14px] font-black text-slate-500">Keep It</button>
                        <button @click="submitDelete" class="h-12 flex-[2] rounded-2xl bg-rose-600 text-[14px] font-black text-white shadow-lg shadow-rose-500/20">Delete Forever</button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Bulk delete confirmation modal -->
        <transition name="fade">
            <div v-if="isBulkDeleteModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isBulkDeleteModalOpen = false">
                <div class="w-full max-w-md overflow-hidden rounded-[32px] bg-white shadow-2xl">
                    <div class="bg-rose-50 px-8 py-10">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-rose-600 shadow-sm ring-1 ring-rose-100">
                            <Trash2 class="h-7 w-7" />
                        </div>
                        <h3 class="text-3xl font-black tracking-tight text-slate-900">Hapus Data Terpilih</h3>
                        <p class="mt-2 text-[15px] font-medium leading-relaxed text-slate-500">
                            <b>{{ selectedIds.length }} data</b> akan dihapus secara permanen. Tindakan ini tidak bisa dibatalkan.
                        </p>
                    </div>
                    <div class="flex gap-3 bg-white p-8">
                        <button @click="isBulkDeleteModalOpen = false" class="h-12 flex-1 rounded-2xl bg-slate-50 text-[14px] font-black text-slate-500">Batal</button>
                        <button @click="submitBulkDelete" :disabled="bulkDeleteForm.processing" class="h-12 flex-[2] rounded-2xl bg-rose-600 text-[14px] font-black text-white shadow-lg shadow-rose-500/20 disabled:opacity-60">
                            {{ bulkDeleteForm.processing ? 'Menghapus...' : 'Ya, Hapus Semua' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Floating bulk action bar -->
        <div v-if="selectedIds.length > 0" class="fixed bottom-10 left-1/2 z-40 -translate-x-1/2">
            <div class="flex items-center gap-3 rounded-2xl bg-slate-900 px-6 py-3.5 shadow-2xl">
                <span class="text-[13px] font-black text-white">{{ selectedIds.length }} dipilih</span>
                <div class="h-4 w-px bg-slate-700"></div>
                <button @click="confirmBulkDelete" :disabled="bulkDeleteForm.processing" class="flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-[12px] font-black text-white transition-all hover:bg-rose-500 disabled:opacity-60">
                    <Trash2 class="h-3.5 w-3.5" />
                    <span>{{ bulkDeleteForm.processing ? 'Menghapus...' : 'Hapus Semua' }}</span>
                </button>
                <button @click="selectedIds = []" class="rounded-xl px-3 py-2 text-slate-400 transition-all hover:text-white">
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
