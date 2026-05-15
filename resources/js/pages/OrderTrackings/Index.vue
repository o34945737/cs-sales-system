<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import {
    Activity,
    AlertCircle,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
    Eye,
    FileText,
    Pencil,
    PlayCircle,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    Upload,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const DEFAULT_SOURCE_OPTIONS = ['WH', 'Finance', 'Reject Return'];
const DEFAULT_PAYMENT_METHOD_OPTIONS = ['COD', 'NON COD'];
const DEFAULT_INSURANCE_OPTIONS = ['Y', 'N'];

const today = () => {
    const currentDate = new Date();
    const timezoneOffset = currentDate.getTimezoneOffset() * 60000;
    return new Date(currentDate.getTime() - timezoneOffset).toISOString().split('T')[0];
};

const createEmptyPaginator = () => ({
    current_page: 1,
    data: [],
    from: 0,
    last_page: 1,
    links: [],
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
    orderTrackings: Object,
    filters: Object,
    csSummary: Array,
    statusSummary: Object,
    overview: Object,
    brandOptions: Array,
    csNameOptions: Array,
    platformOptions: Array,
    sourceOptions: Array,
    erpStatusOptions: Array,
    paymentMethodOptions: Array,
    categoryOptions: Array,
    causeByOptions: Array,
    lastStepOptions: Array,
    reasonWhitelistOptions: Array,
    reasonLateResponseOptions: Array,
    autoCauseByMap: Object,
    complaintSyncMap: Object, // { [orderId]: { category, last_step, status, reason_whitelist, reason_late_respons } }
    rgoOrderIds: Array,
    rgoLastSynced: String,
    jetTrackMap: Object,      // { [awb]: "KONDISI ..." }
    platformTtsDaysMap: Object, // { [platformName]: tts_days }
});

const orderTrackingPage = computed(() => ({
    ...createEmptyPaginator(),
    ...(props.orderTrackings || {}),
}));

const orderTrackingRows = computed(() =>
    Array.isArray(orderTrackingPage.value.data) ? orderTrackingPage.value.data : []
);

const paginationLinks = computed(() =>
    Array.isArray(orderTrackingPage.value.links) ? orderTrackingPage.value.links : []
);

const filterState = computed(() => (props.filters && !Array.isArray(props.filters) ? props.filters : {}));
const csSummary = computed(() => (Array.isArray(props.csSummary) ? props.csSummary : []));
const statusSummary = computed(() => props.statusSummary || { all: 0, pending: 0, solved: 0, whitelist: 0 });
const overview = computed(() => props.overview || { total: 0, pending: 0, solved: 0, whitelist: 0 });

const sourceOptions = computed(() =>
    Array.isArray(props.sourceOptions) && props.sourceOptions.length ? props.sourceOptions : DEFAULT_SOURCE_OPTIONS
);
const erpStatusOptions = computed(() => (Array.isArray(props.erpStatusOptions) ? props.erpStatusOptions : []));
const erpStatusOptionValue = (item: any) => String(item?.name ?? item?.label ?? item ?? '');
const erpStatusOptionLabel = (item: any) => String(item?.name ?? item?.label ?? item ?? '');
const causeByOptions = computed(() => (Array.isArray(props.causeByOptions) ? props.causeByOptions : []));
const paymentMethodOptions = computed(() =>
    Array.isArray(props.paymentMethodOptions) && props.paymentMethodOptions.length
        ? props.paymentMethodOptions
        : DEFAULT_PAYMENT_METHOD_OPTIONS
);
const insuranceOptions = computed(() => DEFAULT_INSURANCE_OPTIONS);

const brandFilterOptions = computed(() => ['All', ...(props.brandOptions || [])]);
const platformFilterOptions = computed(() => ['All', ...(props.platformOptions || [])]);
const categoryFilterOptions = computed(() => ['All', ...(props.categoryOptions || [])]);

const categoryOptions = computed(() => (Array.isArray(props.categoryOptions) ? props.categoryOptions : []));
const autoCauseByMap = computed(() => (props.autoCauseByMap && !Array.isArray(props.autoCauseByMap) ? props.autoCauseByMap : {}));
const lastStepOptions = computed(() => (Array.isArray(props.lastStepOptions) ? props.lastStepOptions : []));
const reasonWhitelistOptions = computed(() => (Array.isArray(props.reasonWhitelistOptions) ? props.reasonWhitelistOptions : []));
const reasonLateResponseOptions = computed(() => (Array.isArray(props.reasonLateResponseOptions) ? props.reasonLateResponseOptions : []));
const rgoOrderIds = computed(() => (Array.isArray(props.rgoOrderIds) ? props.rgoOrderIds : []));
const jetTrackMap = computed(() => (props.jetTrackMap && !Array.isArray(props.jetTrackMap) ? props.jetTrackMap : {}));
const normalizeOrderKey = (value: unknown) => String(value || '').trim().toLowerCase();
const complaintSyncMap = computed(() => {
    const source = props.complaintSyncMap && !Array.isArray(props.complaintSyncMap) ? props.complaintSyncMap : {};

    return Object.entries(source).reduce((carry: Record<string, any>, [key, value]) => {
        const normalizedKey = normalizeOrderKey(key);

        if (normalizedKey) {
            carry[normalizedKey] = value;
        }

        return carry;
    }, {});
});

const search = ref(filterState.value.search || '');
const agentSearchQuery = ref('');

const currentStatus = computed(() => filterState.value.status || 'All');
const currentCs = computed(() => filterState.value.cs_name || '');
const currentBrand = computed(() => filterState.value.brand || 'All');
const currentSource = computed(() => filterState.value.source || 'All');
const currentPlatform = computed(() => filterState.value.platform || 'All');
const currentCategory = computed(() => filterState.value.category || 'All');

const filteredCsSummary = computed(() => {
    let list = [...csSummary.value];

    if (agentSearchQuery.value) {
        const query = agentSearchQuery.value.toLowerCase();
        list = list.filter((cs: any) => cs.cs_name?.toLowerCase().includes(query));
    }

    return list.sort((a: any, b: any) => (b.total || 0) - (a.total || 0));
});

const overviewCards = computed(() => [
    { label: 'Total', value: overview.value.total || statusSummary.value.all || 0, icon: ClipboardList },
    { label: 'Pending', value: overview.value.pending || statusSummary.value.pending || 0, icon: AlertCircle },
    { label: 'Solved', value: overview.value.solved || statusSummary.value.solved || 0, icon: CheckCircle2 },
    { label: 'Active Agents', value: csSummary.value.length || 0, icon: Users },
]);

const statusCards = computed(() => [
    { key: 'All', label: 'All', value: statusSummary.value.all || 0 },
    { key: 'Pending', label: 'Pending', value: statusSummary.value.pending || 0 },
    { key: 'Solved', label: 'Solved', value: statusSummary.value.solved || 0 },
    { key: 'Whitelist', label: 'Whitelist', value: statusSummary.value.whitelist || 0 },
]);

const hasActiveFilters = computed(() =>
    Boolean(
        search.value ||
            currentStatus.value !== 'All' ||
            currentCs.value ||
            currentBrand.value !== 'All' ||
            currentSource.value !== 'All' ||
            currentPlatform.value !== 'All' ||
            currentCategory.value !== 'All'
    )
);

const activeFilterCount = computed(() =>
    [
        Boolean(search.value),
        currentStatus.value !== 'All',
        Boolean(currentCs.value),
        currentBrand.value !== 'All',
        currentSource.value !== 'All',
        currentPlatform.value !== 'All',
        currentCategory.value !== 'All',
    ].filter(Boolean).length
);

const page = usePage<SharedData>();
const canImportOrderTrackings = computed(() => page.props.auth?.can?.import_order_trackings ?? false);
const canImportErpStatuses = computed(() => page.props.auth?.can?.import_order_tracking_erp_statuses ?? false);
const canImportRgoEntries = computed(() => page.props.auth?.can?.import_order_trackings ?? false);
const canExportOrderTrackings = computed(() => page.props.auth?.can?.export_order_trackings ?? false);
const canDeleteOrderTrackings = computed(() => page.props.auth?.can?.delete_order_trackings ?? false);
const canUseDeleteActions = computed(() => canDeleteOrderTrackings.value || (page.props.auth?.can?.access_order_trackings ?? false));

const exportUrl = computed(() => {
    const params: Record<string, string | undefined> = {
        search: search.value || undefined,
        status: currentStatus.value !== 'All' ? currentStatus.value : undefined,
        cs_name: currentCs.value || undefined,
        brand: currentBrand.value !== 'All' ? currentBrand.value : undefined,
        source: currentSource.value !== 'All' ? currentSource.value : undefined,
        platform: currentPlatform.value !== 'All' ? currentPlatform.value : undefined,
        category: currentCategory.value !== 'All' ? currentCategory.value : undefined,
    };

    return route('order-trackings.export', params);
});

// ============ Import ============
const isImportOpen = ref(false);
const importFile = ref<File | null>(null);
const importFileInput = ref<HTMLInputElement | null>(null);
const importResult = computed(() => (page.props.flash as any)?.import_result ?? null);
const importForm = useForm({ file: null as File | null });

const isErpImportOpen = ref(false);
const erpImportFile = ref<File | null>(null);
const erpImportFileInput = ref<HTMLInputElement | null>(null);
const erpImportResult = computed(() => (page.props.flash as any)?.erp_import_result ?? null);
const erpImportForm = useForm({ file: null as File | null });

const isRgoImportOpen = ref(false);
const rgoImportFile = ref<File | null>(null);
const rgoImportFileInput = ref<HTMLInputElement | null>(null);
const rgoImportResult = computed(() => (page.props.flash as any)?.rgo_import_result ?? null);
const rgoImportForm = useForm({ file: null as File | null });

const isSyncRgoOpen = ref(false);
const rgoSyncResult = computed(() => page.props.flash?.rgo_sync_result ?? null);
const rgoSyncFilter = ref<'all' | 'matched' | 'updated' | 'not_in_system'>('all');
const syncRgoForm = useForm({});

const submitSyncRgo = () => {
    syncRgoForm.post(route('order-trackings.sync-rgo'), {
        preserveScroll: true,
    });
};

const filteredSyncEntries = computed(() => {
    const entries = rgoSyncResult.value?.entries ?? [];
    if (rgoSyncFilter.value === 'all') return entries;
    return entries.filter((e: any) => e.reason === rgoSyncFilter.value);
});

const openImportModal = () => {
    importFile.value = null;
    importForm.clearErrors();
    isImportOpen.value = true;
};

const onImportFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    importFile.value = target.files?.[0] ?? null;
};

const submitImport = () => {
    if (!importFile.value) return;
    importForm.file = importFile.value;
    importForm.post(route('order-trackings.import'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importFile.value = null;
            if (importFileInput.value) importFileInput.value.value = '';
        },
    });
};

const openErpImportModal = () => {
    erpImportFile.value = null;
    erpImportForm.clearErrors();
    isErpImportOpen.value = true;
};

const onErpImportFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    erpImportFile.value = target.files?.[0] ?? null;
};

const submitErpImport = () => {
    if (!erpImportFile.value) return;
    erpImportForm.file = erpImportFile.value;
    erpImportForm.post(route('order-trackings.import-erp-status'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            erpImportFile.value = null;
            if (erpImportFileInput.value) erpImportFileInput.value.value = '';
        },
    });
};

const openRgoImportModal = () => {
    rgoImportFile.value = null;
    rgoImportForm.clearErrors();
    isRgoImportOpen.value = true;
};

const onRgoImportFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    rgoImportFile.value = target.files?.[0] ?? null;
};

const submitRgoImport = () => {
    if (!rgoImportFile.value) return;
    rgoImportForm.file = rgoImportFile.value;
    rgoImportForm.post(route('order-trackings.import-rgo'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            rgoImportFile.value = null;
            if (rgoImportFileInput.value) rgoImportFileInput.value.value = '';
        },
    });
};

// ============ Bulk Delete ============
const selectedIds = ref<number[]>([]);
const isBulkDeleteModalOpen = ref(false);
const bulkDeleteForm = useForm({ ids: [] as number[] });

const currentPageIds = computed(() => orderTrackingRows.value.map((item: any) => item.id));
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
    if (!canUseDeleteActions.value || !selectedIds.value.length) return;
    isBulkDeleteModalOpen.value = true;
};

const submitBulkDelete = () => {
    if (!canUseDeleteActions.value || !selectedIds.value.length) return;
    bulkDeleteForm.ids = [...selectedIds.value];
    bulkDeleteForm.post(route('order-trackings.bulk-delete'), {
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
        route('order-trackings.index'),
        {
            search: search.value || undefined,
            status: filterState.value.status && filterState.value.status !== 'All' ? filterState.value.status : undefined,
            cs_name: filterState.value.cs_name || undefined,
            brand: filterState.value.brand && filterState.value.brand !== 'All' ? filterState.value.brand : undefined,
            source: filterState.value.source && filterState.value.source !== 'All' ? filterState.value.source : undefined,
            platform: filterState.value.platform && filterState.value.platform !== 'All' ? filterState.value.platform : undefined,
            category: filterState.value.category && filterState.value.category !== 'All' ? filterState.value.category : undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true, ...options }
    );
};

watch(search, debounce((value) => visitIndex({ search: value || undefined, page: 1 }), 350));

const setStatus = (status: string) => visitIndex({ status: status === 'All' ? undefined : status, page: 1 }, { replace: false });
const setCsFilter = (name: string) => visitIndex({ cs_name: name || undefined, page: 1 }, { replace: false });
const setBrandFilter = (brand: string) => visitIndex({ brand: brand === 'All' ? undefined : brand, page: 1 }, { replace: false });
const setSourceFilter = (source: string) => visitIndex({ source: source === 'All' ? undefined : source, page: 1 }, { replace: false });
const setPlatformFilter = (platform: string) => visitIndex({ platform: platform === 'All' ? undefined : platform, page: 1 }, { replace: false });
const setCategoryFilter = (category: string) => visitIndex({ category: category === 'All' ? undefined : category, page: 1 }, { replace: false });

const resetFilters = () => {
    search.value = '';
    visitIndex(
        {
            search: undefined,
            status: undefined,
            cs_name: undefined,
            brand: undefined,
            source: undefined,
            platform: undefined,
            category: undefined,
            page: 1,
        },
        { replace: false }
    );
};

const createInitialFormState = () => ({
    data_source: '',
    tanggal_input: today(),
    tanggal_order: '',
    brand: '',
    platform: '',
    order_id: '',
    value: null as number | null,
    cause_by: '?',
    awb: '',
    payment_method: '',
    wh_note: '',
    cs_name: '',
    category: '',
    last_step: '',
    update: '',
    tanggal_update: '',
    value_receive: null as number | null,
    insurance_info: 'N',
    video_unboxing_wh: null as File | null,
    bap_wh: null as File | null,
    update_wh: '',
    update_finance: '',
    status: '',
    automation_track: '',
    tanggal_tts: '',
    reason_whitelist: '',
    reason_late_respons: '',
});

const form = useForm(createInitialFormState());

const isModalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const editId = ref<number | null>(null);
const detailItem = ref<any | null>(null);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref<any | null>(null);
const isDeletingSingle = ref(false);
const submitError = ref('');
const isHydratingEditForm = ref(false);

const fieldError = (field: string) => (form.errors as Record<string, string>)[field];

const controlClass = (field: string, variant = 'input') => {
    const baseClass = variant === 'select' ? selectClass : variant === 'textarea' ? textAreaClass : inputClass;
    return fieldError(field) ? `${baseClass} border-rose-300 bg-rose-50/60 focus:border-rose-400` : baseClass;
};

const complaintLinkedRecord = computed(() => {
    const key = normalizeOrderKey(form.order_id);
    return key ? complaintSyncMap.value[key] || null : null;
});

const isComplaintLinked = computed(() => Boolean(complaintLinkedRecord.value));

const resolveStatusFromLastStep = (lastStep: string) => {
    const matched = lastStepOptions.value.find((item: any) => item?.value === lastStep);
    return matched?.status_result || 'Pending';
};

const resolvedAutoCauseBy = computed(() => autoCauseByMap.value[form.category] || null);
const causeByLocked = computed(() => Boolean(resolvedAutoCauseBy.value));

const monthPreview = computed(() => {
    if (!form.tanggal_input) return '';

    const d = new Date(form.tanggal_input);
    if (isNaN(d.getTime())) return '';

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    return `${monthNames[d.getMonth()]} ${d.getFullYear()}`;
});

const tanggalTtsPreview = computed(() => {
    if (!form.tanggal_order || !form.platform) return '';

    const ttsDaysMap = (props.platformTtsDaysMap || {}) as Record<string, number>;
    // Case-insensitive lookup
    const platformKey = Object.keys(ttsDaysMap).find(
        (k) => k.toLowerCase() === String(form.platform).toLowerCase()
    );
    const ttsDays = platformKey ? ttsDaysMap[platformKey] : null;
    if (!ttsDays) return '';

    const baseDate = new Date(form.tanggal_order);
    if (isNaN(baseDate.getTime())) return '';

    baseDate.setDate(baseDate.getDate() + ttsDays);
    return baseDate.toISOString().split('T')[0];
});

const automationTrackPreview = computed(() => {
    const orderId = String(form.order_id || '').trim();
    const awb = String(form.awb || '').trim();
    const normalizedOrderId = normalizeOrderKey(orderId);

    if (orderId && complaintLinkedRecord.value) {
        return 'MERGER';
    }

    if (orderId && rgoOrderIds.value.some((item) => normalizeOrderKey(item) === normalizedOrderId)) {
        return 'Sudah diRGO';
    }

    if (awb && jetTrackMap.value[awb]) {
        const jetInfo = jetTrackMap.value[awb];
        return typeof jetInfo === 'string'
            ? `ADA DI JET TRACK - ${jetInfo}`
            : `ADA DI JET TRACK - ${(jetInfo?.kondisi_barang || '').trim()}`;
    }

    return '';
});

const categoryPreview = computed(() => {
    if (complaintLinkedRecord.value?.category) return complaintLinkedRecord.value.category;
    return form.category || '';
});

const lastStepPreview = computed(() => {
    if (complaintLinkedRecord.value?.last_step) return complaintLinkedRecord.value.last_step;
    return form.last_step || '';
});

const statusPreview = computed(() => {
    if (complaintLinkedRecord.value?.status) return complaintLinkedRecord.value.status;
    return resolveStatusFromLastStep(lastStepPreview.value);
});

const reasonWhitelistPreview = computed(() => {
    if (complaintLinkedRecord.value?.reason_whitelist) return complaintLinkedRecord.value.reason_whitelist;
    return form.reason_whitelist || '';
});

const reasonLateResponsPreview = computed(() => {
    if (complaintLinkedRecord.value?.reason_late_respons) return complaintLinkedRecord.value.reason_late_respons;
    return form.reason_late_respons || '';
});

const showReasonWhitelist = computed(() => lastStepPreview.value === 'Claim Reject');
const showReasonLateRespons = computed(() => showReasonWhitelist.value && reasonWhitelistPreview.value === 'Late Respons');

watch(
    [monthPreview, tanggalTtsPreview, automationTrackPreview, statusPreview, categoryPreview, lastStepPreview, reasonWhitelistPreview, reasonLateResponsPreview],
    () => {
        if (isHydratingEditForm.value) return;

        form.tanggal_tts = tanggalTtsPreview.value;
        form.automation_track = automationTrackPreview.value;
        form.status = statusPreview.value;

        if (isComplaintLinked.value) {
            form.category = categoryPreview.value;
            form.last_step = lastStepPreview.value;
            form.reason_whitelist = reasonWhitelistPreview.value;
            form.reason_late_respons = reasonLateResponsPreview.value;
        }
    },
    { immediate: true }
);

watch(
    () => lastStepPreview.value,
    () => {
        if (isHydratingEditForm.value || isComplaintLinked.value) return;

        if (!showReasonWhitelist.value) {
            form.reason_whitelist = '';
            form.reason_late_respons = '';
        }
    }
);

watch(
    () => form.reason_whitelist,
    () => {
        if (isHydratingEditForm.value || isComplaintLinked.value) return;

        if (!showReasonLateRespons.value) {
            form.reason_late_respons = '';
        }
    }
);

watch(
    () => form.category,
    () => {
        if (isHydratingEditForm.value) return;

        if (resolvedAutoCauseBy.value) {
            form.cause_by = resolvedAutoCauseBy.value;
        } else {
            form.cause_by = '?';
        }
    }
);

const videoLabel = computed(() => form.video_unboxing_wh?.name || 'Upload video unboxing');
const bapLabel = computed(() => form.bap_wh?.name || 'Upload BAP image');

const setVideoFile = (event: Event) => {
    const [file] = (event.target as HTMLInputElement).files || [];
    form.video_unboxing_wh = file || null;
};

const setBapFile = (event: Event) => {
    const [file] = (event.target as HTMLInputElement).files || [];
    form.bap_wh = file || null;
};

const discardForm = () => {
    submitError.value = '';
    isHydratingEditForm.value = false;
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
    isHydratingEditForm.value = false;
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (item: any) => {
    modalMode.value = 'edit';
    editId.value = item.id;
    submitError.value = '';
    detailItem.value = null;
    isHydratingEditForm.value = true;
    isModalOpen.value = true;

    nextTick(() => {
        const initialState = createInitialFormState();
        const hydratedState: Record<string, any> = { ...initialState };

        Object.keys(initialState).forEach((key) => {
            if (item[key] !== undefined && key !== 'video_unboxing_wh' && key !== 'bap_wh') {
                hydratedState[key] = item[key] ?? initialState[key];
            }
        });

        form.defaults(hydratedState as any);
        form.reset();
        form.clearErrors();
        form.video_unboxing_wh = null;
        form.bap_wh = null;

        nextTick(() => {
            isHydratingEditForm.value = false;
        });
    });
};

const submitForm = () => {
    submitError.value = '';

    form.transform((data) => ({
        ...data,
        month: monthPreview.value || null,
        category: categoryPreview.value || null,
        last_step: lastStepPreview.value || null,
        status: statusPreview.value || null,
        automation_track: automationTrackPreview.value || null,
        tanggal_tts: tanggalTtsPreview.value || null,
        reason_whitelist: showReasonWhitelist.value ? reasonWhitelistPreview.value || null : null,
        reason_late_respons: showReasonLateRespons.value ? reasonLateResponsPreview.value || null : null,
        _method: modalMode.value === 'edit' ? 'PUT' : 'POST',
    })).post(
        modalMode.value === 'edit'
            ? route('order-trackings.update', editId.value)
            : route('order-trackings.store'),
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => discardForm(),
            onError: () => {
                submitError.value = 'Data gagal disimpan. Periksa field wajib dan validasi backend.';
            },
        }
    );
};

const openDetail = (item: any) => {
    detailItem.value = item;
};

const closeDetail = () => {
    detailItem.value = null;
};

const confirmDelete = (item: any) => {
    if (!canUseDeleteActions.value) return;
    itemToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const submitDelete = () => {
    if (!canUseDeleteActions.value || !itemToDelete.value || isDeletingSingle.value) return;

    isDeletingSingle.value = true;
    router.delete(route('order-trackings.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = selectedIds.value.filter((id) => id !== itemToDelete.value?.id);
            isDeleteModalOpen.value = false;
            itemToDelete.value = null;
        },
        onFinish: () => {
            isDeletingSingle.value = false;
        },
    });
};

const formatDate = (value: string) => {
    if (!value) return '-';

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parsed);
};

const formatDateTime = (value: string) => {
    if (!value) return '-';

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(parsed);
};

const formatCurrency = (value: any) => {
    if (value === null || value === undefined || value === '') return '-';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
};

const statusClass = (status: string) =>
    status === 'Solved'
        ? 'bg-emerald-50 text-emerald-700'
        : status === 'Whitelist'
          ? 'bg-rose-50 text-rose-700'
          : 'bg-amber-50 text-amber-700';

const statusDotClass = (status: string) =>
    status === 'Solved' ? 'bg-emerald-500' : status === 'Whitelist' ? 'bg-rose-500' : 'bg-amber-500';

const insuranceButtonClass = (value: string) =>
    form.insurance_info === value
        ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white shadow-md'
        : 'border-slate-200 bg-white text-slate-600 hover:border-[var(--app-primary)]/40 hover:bg-slate-50';

const selectButtonClass = (currentValue: string, expectedValue: string) =>
    currentValue === expectedValue
        ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white shadow-md'
        : 'border-slate-200 bg-white text-slate-600 hover:border-[var(--app-primary)]/40 hover:bg-slate-50';
</script>

<template>
    <Head title="Order Trackings" />

    <AppLayout :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Order Trackings', href: '/order-trackings' },
        ]"
>
        <div class="pb-20">
            <div class="mx-auto flex max-w-[90rem] flex-col gap-10 px-4 font-sans sm:px-6 lg:px-8">
                <div class="space-y-10">
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
                                    <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                        <component :is="card.icon" class="h-3 w-3" />
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

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
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setBrandFilter(($event.target as HTMLSelectElement).value)"
                                    >
                                        <option v-for="option in brandFilterOptions" :key="option" :value="option">
                                            {{ option === 'All' ? 'ANY BRAND' : option }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[130px]">
                                    <select
                                        :value="currentSource"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setSourceFilter(($event.target as HTMLSelectElement).value)"
                                    >
                                        <option value="All">ANY SOURCE</option>
                                        <option v-for="source in sourceOptions" :key="source" :value="source">
                                            {{ source }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[130px]">
                                    <select
                                        :value="currentPlatform"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setPlatformFilter(($event.target as HTMLSelectElement).value)"
                                    >
                                        <option value="All">ANY PLATFORM</option>
                                        <option v-for="platform in platformFilterOptions.filter((item) => item !== 'All')" :key="platform" :value="platform">
                                            {{ platform }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[150px]">
                                    <select
                                        :value="currentCategory"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setCategoryFilter(($event.target as HTMLSelectElement).value)"
                                    >
                                        <option v-for="option in categoryFilterOptions" :key="option" :value="option">
                                            {{ option === 'All' ? 'ANY CATEGORY' : option }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <div class="relative min-w-[130px]">
                                    <select
                                        :value="currentStatus"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300"
                                        @change="setStatus(($event.target as HTMLSelectElement).value)"
                                    >
                                        <option value="All">ANY STATUS</option>
                                        <option value="Pending">PENDING</option>
                                        <option value="Solved">SOLVED</option>
                                        <option value="Whitelist">WHITELIST</option>
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

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[320px,1fr]">
                        <aside class="space-y-4">
                            <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/50">
                                <header class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">CS Groupings</p>
                                        <h2 class="mt-0.5 text-lg font-black text-slate-900">Order Tracking Desk</h2>
                                    </div>
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-[var(--app-primary)]">
                                        <Users class="h-4 w-4" />
                                    </div>
                                </header>

                                <div class="mt-6 space-y-2.5">
                                    <button
                                        @click="setCsFilter('')"
                                        class="flex h-10 w-full items-center justify-between rounded-xl px-4 text-[13px] font-black transition-all"
                                        :class="!currentCs ? 'bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Activity class="h-3.5 w-3.5" />
                                            <span>All Active Agents</span>
                                        </div>
                                        <span class="text-[10px] font-black opacity-60">
                                            {{ csSummary.reduce((acc: number, curr: any) => acc + (curr.total || 0), 0) }}
                                        </span>
                                    </button>

                                    <div class="group relative">
                                        <Search class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-[var(--app-primary)]" />
                                        <input
                                            v-model="agentSearchQuery"
                                            type="text"
                                            placeholder="Search agent name..."
                                            class="h-9 w-full rounded-xl border border-slate-100 bg-slate-50/50 pl-9 pr-4 text-[11px] font-bold text-slate-700 outline-none transition-all placeholder:font-medium placeholder:text-slate-400 focus:border-[var(--app-primary)] focus:bg-white focus:ring-4 focus:ring-blue-50/50"
                                        />
                                    </div>

                                    <div class="custom-scrollbar mt-4 max-h-[480px] space-y-3 overflow-y-auto border-b border-dashed border-slate-100 pb-5 pr-1.5">
                                        <button
                                            v-for="cs in filteredCsSummary"
                                            :key="cs.cs_name"
                                            @click="setCsFilter(cs.cs_name)"
                                            class="group relative flex w-full flex-col gap-3 overflow-hidden rounded-[20px] border p-3.5 text-left transition-all"
                                            :class="currentCs === cs.cs_name ? 'border-[var(--app-primary)] bg-blue-50/40 ring-2 ring-[var(--app-primary)]/10' : 'border-slate-50 bg-white hover:border-slate-200 hover:shadow-sm'"
                                        >
                                            <div class="flex items-start justify-between">
                                                <div class="min-w-0">
                                                    <p class="text-[13px] font-black leading-tight text-slate-900 transition-colors group-hover:text-[var(--app-primary)]">
                                                        {{ cs.cs_name }}
                                                    </p>
                                                    <p class="mt-0.5 text-[10px] font-bold text-slate-400">{{ cs.total }} Active Tickets</p>
                                                </div>
                                                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-[10px] font-black text-slate-500">
                                                    {{ cs.total }}
                                                </div>
                                            </div>
                                        </button>

                                        <div v-if="filteredCsSummary.length === 0" class="py-10 text-center">
                                            <Users class="mx-auto h-8 w-8 text-slate-200 opacity-50" />
                                            <p class="mt-2 text-[11px] font-bold uppercase tracking-widest text-slate-400">No agent found</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <div class="min-w-0 space-y-8">
                            <section class="overflow-hidden rounded-[24px] border border-slate-100 bg-white shadow-sm">
                                <div class="flex flex-col gap-6 border-b border-slate-100 px-6 py-7 xl:flex-row xl:items-center xl:justify-between">
                                    <div class="min-w-0">
                                        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                            Operational Database
                                        </div>
                                        <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Order Trackings</h2>
                                        <div class="mt-2.5 flex items-center gap-2">
                                            <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500 ring-1 ring-slate-200/50">
                                                <span>SHOWING {{ orderTrackingPage.from || 0 }}-{{ orderTrackingPage.to || 0 }} OF {{ orderTrackingPage.total || 0 }}</span>
                                            </div>
                                            <div
                                                class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                                                :class="activeFilterCount ? 'border-amber-200 bg-amber-50 text-amber-600 shadow-sm shadow-amber-500/5' : 'border-slate-100 bg-white text-slate-400'"
                                            >
                                                {{ activeFilterCount ? `${activeFilterCount} Active Filters` : 'No Filter' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-1 flex-wrap gap-3 xl:max-w-3xl xl:items-center xl:justify-end">
                                        <div class="group relative min-w-[220px] flex-1 xl:max-w-[300px]">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                <Search class="h-4.5 w-4.5 text-slate-400 transition-colors group-focus-within:text-[var(--app-primary)]" />
                                            </div>
                                            <input
                                                v-model="search"
                                                type="text"
                                                class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-11 pr-4 text-[13px] font-medium text-slate-900 outline-none transition-all focus:border-[var(--app-primary)] focus:bg-white focus:ring-4 focus:ring-[var(--app-primary)]/10"
                                                placeholder="Search order ID, AWB, cause by, brand..."
                                            />
                                        </div>

                                        <button
                                            v-if="canUseDeleteActions && selectedIds.length > 0"
                                            type="button"
                                            class="flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-rose-600 px-4 text-[13px] font-black text-white shadow-[0_15px_30px_rgba(220,38,38,0.25)] transition-all hover:-translate-y-0.5 hover:bg-rose-700 active:scale-[0.98]"
                                            @click="confirmBulkDelete"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            <span>Delete ({{ selectedIds.length }})</span>
                                        </button>
                                        <button
                                            v-if="canImportOrderTrackings"
                                            type="button"
                                            class="flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-black text-slate-700 shadow-sm transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50 active:scale-[0.98]"
                                            @click="openImportModal"
                                        >
                                            <Upload class="h-4 w-4" />
                                            <span>Import</span>
                                        </button>

                                        <button
                                            v-if="canImportErpStatuses"
                                            type="button"
                                            class="flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-blue-200 bg-blue-50 px-4 text-[13px] font-black text-[var(--app-primary)] shadow-sm transition-all hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-100/70 active:scale-[0.98]"
                                            @click="openErpImportModal"
                                        >
                                            <Upload class="h-4 w-4" />
                                            <span>Import ERP Status</span>
                                        </button>

                                        <button
                                            v-if="canImportRgoEntries"
                                            type="button"
                                            class="flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-[13px] font-black text-emerald-700 shadow-sm transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-emerald-100/70 active:scale-[0.98]"
                                            @click="openRgoImportModal"
                                        >
                                            <Upload class="h-4 w-4" />
                                            <span>Import RGO</span>
                                        </button>

                                        <button
                                            v-if="canImportRgoEntries"
                                            type="button"
                                            class="flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-teal-200 bg-teal-50 px-4 text-[13px] font-black text-teal-700 shadow-sm transition-all hover:-translate-y-0.5 hover:border-teal-300 hover:bg-teal-100/70 active:scale-[0.98]"
                                            @click="isSyncRgoOpen = true"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                            <span>Sync</span>
                                        </button>

                                        <a
                                            v-if="canExportOrderTrackings"
                                            :href="exportUrl"
                                            class="flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-black text-slate-700 shadow-sm transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-50 active:scale-[0.98]"
                                        >
                                            <FileText class="h-4 w-4" />
                                            <span>Export Excel</span>
                                        </a>

                                        <button
                                            type="button"
                                            class="group flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-[var(--app-primary)] px-5 text-[13px] font-black text-white shadow-[0_15px_30px_rgba(53,103,232,0.25)] transition-all hover:-translate-y-0.5 hover:bg-[var(--app-primary-dark)] hover:shadow-[0_20px_40px_rgba(53,103,232,0.35)] active:scale-[0.98]"
                                            @click="openCreateModal"
                                        >
                                            <Plus class="h-4 w-4 stroke-[3px]" />
                                            <span>Create Tracking</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto custom-scrollbar">
                                    <table class="w-full min-w-[1160px] border-collapse text-left">
                                        <thead>
                                            <tr class="border-b border-slate-100 bg-slate-50/30 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">
                                                <th v-if="canUseDeleteActions" class="w-10 py-4 pl-4 pr-2">
                                                    <input
                                                        type="checkbox"
                                                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                                        :checked="isAllSelected"
                                                        @change="toggleSelectAll"
                                                    />
                                                </th>
                                                <th class="w-12 py-4 pl-4 pr-3">No</th>
                                                <th class="w-[110px] px-3 py-4">Source</th>
                                                <th class="w-[105px] px-3 py-4">Input Date</th>
                                                <th class="w-[110px] px-3 py-4">Order Date</th>
                                                <th class="w-[110px] px-3 py-4">Order</th>
                                                <th class="w-[130px] px-3 py-4">Brand / Platform</th>
                                                <th class="w-[140px] px-3 py-4">Sub Case / By</th>
                                                <th class="w-[150px] px-3 py-4">Agent / AWB</th>
                                                <th class="w-[150px] px-3 py-4">ERP Status</th>
                                                <th class="w-[120px] px-3 py-4">RGO Status</th>
                                                <th class="w-[95px] px-3 py-4 text-center">Status</th>
                                                <th class="w-[110px] px-3 py-4">Track</th>
                                                <th class="w-[110px] py-4 pl-3 pr-4 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <tr
                                                v-for="(item, index) in orderTrackingRows"
                                                :key="item.id"
                                                class="group align-top transition-colors hover:bg-slate-50/70"
                                                :class="selectedIds.includes(item.id) ? 'bg-blue-50/30' : ''"
                                            >
                                                <td v-if="canUseDeleteActions" class="py-4 pl-4 pr-2">
                                                    <input
                                                        type="checkbox"
                                                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                                        :checked="selectedIds.includes(item.id)"
                                                        @change="toggleSelect(item.id)"
                                                    />
                                                </td>
                                                <td class="py-4 pl-4 pr-3">
                                                    <span class="text-[10px] font-black text-slate-400">
                                                        {{ ((orderTrackingPage.current_page || 1) - 1) * (orderTrackingPage.per_page || 10) + index + 1 }}
                                                    </span>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <div class="space-y-0.5">
                                                        <p class="break-words text-[12px] font-black text-slate-900">{{ item.data_source || '-' }}</p>
                                                    </div>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <p class="text-[12px] font-bold text-slate-700">{{ formatDate(item.tanggal_input) }}</p>
                                                    <p class="mt-0.5 text-[10px] font-medium text-slate-400">{{ item.month || '-' }}</p>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <p class="text-[12px] font-bold text-slate-700">{{ formatDate(item.tanggal_order) }}</p>
                                                    <p class="mt-0.5 text-[10px] font-medium text-slate-400">{{ item.tanggal_tts ? `TTS ${formatDate(item.tanggal_tts)}` : '-' }}</p>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <div class="space-y-1">
                                                        <p class="break-words text-[12px] font-black text-slate-900">#{{ item.order_id || '-' }}</p>
                                                        <p class="text-[10px] font-bold text-slate-400">{{ formatCurrency(item.value) }}</p>
                                                    </div>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <div class="space-y-0.5">
                                                        <p class="break-words text-[12px] font-black text-slate-900">{{ item.brand || '-' }}</p>
                                                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ item.platform || '-' }}</p>
                                                    </div>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <div class="space-y-0.5">
                                                        <p class="break-words text-[12px] font-black text-slate-900">{{ item.category || '-' }}</p>
                                                        <p class="text-[10px] font-bold text-slate-400">{{ item.cause_by || '-' }}</p>
                                                    </div>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-500">
                                                            {{ (item.cs_name || '?').substring(0, 2).toUpperCase() }}
                                                        </div>
                                                        <div class="min-w-0 space-y-0.5">
                                                            <p class="truncate text-[12px] font-bold text-slate-700">{{ item.cs_name || 'Unassigned' }}</p>
                                                            <p class="truncate text-[10px] font-medium text-slate-400">{{ item.awb || '-' }}</p>
                                                            <div v-if="item.awb && jetTrackMap[item.awb]" class="flex items-center gap-1.5 pt-0.5">
                                                                <a v-if="jetTrackMap[item.awb].video_url" :href="jetTrackMap[item.awb].video_url" target="_blank" class="inline-flex items-center gap-0.5 text-[9px] font-black uppercase tracking-wide text-blue-600 hover:text-blue-800">
                                                                    <PlayCircle class="h-2.5 w-2.5" /> Video
                                                                </a>
                                                                <a v-if="jetTrackMap[item.awb].warehouse_doc_link" :href="jetTrackMap[item.awb].warehouse_doc_link" target="_blank" class="inline-flex items-center gap-0.5 text-[9px] font-black uppercase tracking-wide text-emerald-600 hover:text-emerald-800">
                                                                    <FileText class="h-2.5 w-2.5" /> WH Doc
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <span v-if="item.erp_status" class="inline-block rounded-md bg-indigo-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-indigo-600 ring-1 ring-indigo-200">
                                                        {{ item.erp_status }}
                                                    </span>
                                                    <span v-else class="text-[11px] font-bold text-slate-300">-</span>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <div v-if="item.rgo_status" class="space-y-0.5">
                                                        <span class="inline-block rounded-md bg-teal-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-teal-700 ring-1 ring-teal-200">
                                                            {{ item.rgo_status }}
                                                        </span>
                                                        <p v-if="item.rgo_synced_at" class="text-[9px] font-medium text-slate-400">{{ formatDate(item.rgo_synced_at) }}</p>
                                                    </div>
                                                    <span v-else class="text-[11px] font-bold text-slate-300">-</span>
                                                </td>

                                                <td class="px-3 py-4 text-center">
                                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider" :class="statusClass(item.status)">
                                                        {{ item.status || 'Pending' }}
                                                    </span>
                                                </td>

                                                <td class="px-3 py-4">
                                                    <p class="line-clamp-2 text-[11px] font-bold text-slate-600">
                                                        {{ item.automation_track || '-' }}
                                                    </p>
                                                </td>

                                                <td class="py-4 pl-3 pr-4">
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
                                                            v-if="canUseDeleteActions"
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

                                <div v-if="!orderTrackingRows.length" class="border-t border-slate-50 px-6 py-24 text-center">
                                    <div class="mx-auto max-w-sm space-y-5">
                                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[2.5rem] bg-slate-50 text-slate-300 shadow-inner">
                                            <ClipboardList class="h-10 w-10" />
                                        </div>
                                        <div class="space-y-2">
                                            <h3 class="text-2xl font-black tracking-tight text-slate-900">
                                                {{ hasActiveFilters ? 'No Results Found' : 'Clean Slate' }}
                                            </h3>
                                            <p class="text-[13px] font-medium text-slate-500">
                                                {{ hasActiveFilters ? 'Try adjusting your filters to find what you are looking for.' : 'No order tracking records yet.' }}
                                            </p>
                                        </div>
                                        <div class="flex flex-col items-center gap-3 pt-4">
                                            <button
                                                type="button"
                                                class="group inline-flex items-center justify-center gap-3 rounded-2xl bg-[var(--app-primary)] px-8 py-4 text-sm font-black text-white shadow-[0_12px_30_rgba(53,103,232,0.25)] transition-all hover:-translate-y-1 hover:bg-[var(--app-primary-dark)] active:scale-95"
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

                                <div class="flex flex-col gap-5 border-t border-slate-100 bg-slate-50/30 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-[13px] font-bold text-slate-400">
                                        <span class="text-slate-900">Listing {{ orderTrackingPage.from || 0 }} - {{ orderTrackingPage.to || 0 }}</span>
                                        <span class="mx-2 text-slate-300">/</span>
                                        Total {{ orderTrackingPage.total || 0 }} Records
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
                </div>
            </div>
        </div>

        <transition name="fade">
            <div v-if="detailItem" class="fixed inset-0 z-40 bg-slate-950/20 backdrop-blur-[1px]" @click.self="closeDetail">
                <aside class="absolute right-0 top-0 h-full w-full max-w-xl overflow-y-auto bg-white p-8 shadow-2xl">
                    <header class="mb-8 flex items-center justify-between border-b border-slate-100 pb-6">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Details Viewer</p>
                            <h3 class="mt-1 text-2xl font-black text-slate-900">Order #{{ detailItem.order_id }}</h3>
                        </div>
                        <button @click="closeDetail" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-all hover:bg-rose-50 hover:text-rose-500">
                            <X class="h-5 w-5" />
                        </button>
                    </header>

                    <div class="space-y-8">
                        <section class="grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Tanggal Input</p>
                                <p class="mt-2 text-[15px] font-black text-slate-900">{{ formatDate(detailItem.tanggal_input) }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Tanggal Order</p>
                                <p class="mt-2 text-[15px] font-black text-slate-900">{{ formatDate(detailItem.tanggal_order) }}</p>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-100 p-6">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-[var(--app-primary)]">
                                    <Users class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase text-slate-400">Order Summary</p>
                                    <p class="mt-1 text-lg font-black text-slate-900">{{ detailItem.brand || '-' }} / {{ detailItem.platform || '-' }}</p>
                                    <p class="text-[13px] font-medium text-slate-500">
                                        {{ detailItem.category || '-' }} / {{ detailItem.cause_by || '-' }}
                                    </p>
                                    <p class="text-[12px] font-medium text-slate-400">
                                        AWB: {{ detailItem.awb || '-' }}
                                    </p>
                                    <div v-if="detailItem.awb && jetTrackMap[detailItem.awb]" class="mt-1 flex items-center gap-2">
                                        <a v-if="jetTrackMap[detailItem.awb].video_url" :href="jetTrackMap[detailItem.awb].video_url" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 hover:text-blue-800">
                                            <PlayCircle class="h-3.5 w-3.5" /> Video JetTrack
                                        </a>
                                        <a v-if="jetTrackMap[detailItem.awb].warehouse_doc_link" :href="jetTrackMap[detailItem.awb].warehouse_doc_link" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-800">
                                            <FileText class="h-3.5 w-3.5" /> WH Doc
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4">
                            <p class="px-1 text-[10px] font-bold uppercase text-slate-400">Updates & Notes</p>
                            <div class="rounded-3xl border border-slate-100 bg-[#f8fbff]/50 p-6 italic leading-relaxed text-slate-600 ring-1 ring-slate-100/50">
                                "{{ detailItem.update || detailItem.wh_note || 'No update provided.' }}"
                            </div>
                        </section>

                        <section class="grid grid-cols-1 gap-4">
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <p class="text-[10px] font-bold uppercase text-slate-400">Status Tracking</p>
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider" :class="statusClass(detailItem.status)">
                                        {{ detailItem.status }}
                                    </span>
                                    <span class="text-[13px] font-black text-slate-500">{{ detailItem.last_step || 'Untracked' }}</span>
                                </div>
                                <p class="mt-3 text-[12px] font-medium text-slate-500">
                                    Automation: {{ detailItem.automation_track || '-' }}
                                </p>
                                <p class="mt-2 text-[12px] font-medium text-slate-500">
                                    TTS: {{ detailItem.tanggal_tts ? formatDate(detailItem.tanggal_tts) : '-' }}
                                </p>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>
        </transition>

        <transition name="fade">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-950/35 backdrop-blur-[2px]">
                <div class="absolute inset-y-0 right-0 h-full w-full overflow-y-auto bg-[#f8fbff] shadow-2xl 2xl:max-w-[1320px]">
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
                                    {{ modalMode === 'edit' ? 'Edit Order Tracking' : 'Create Order Tracking' }}
                                </h2>
                                <p class="mt-0.5 text-[13px] font-medium transition-colors" :class="modalMode === 'edit' ? 'text-slate-400' : 'text-slate-500'">
                                    Flow sesuai WH, Finance, Reject Return, dan automation complaint.
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
                                    <span>{{ form.processing ? 'Syncing...' : (modalMode === 'edit' ? 'Update Data' : 'Submit Tracking') }}</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="px-5 py-8 sm:px-8">
                        <div class="mx-auto grid max-w-[1240px] gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                            <div class="space-y-7">
                                <div v-if="submitError || Object.keys(form.errors).length" class="rounded-[18px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                                    <p class="font-semibold">{{ submitError || 'Mohon lengkapi field mandatory:' }}</p>
                                    <ul v-if="Object.keys(form.errors).length" class="mt-2 list-disc pl-5">
                                        <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                                    </ul>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</div>
                                        <div class="mt-1 text-[13px] font-semibold text-slate-700">{{ statusPreview }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-400">Tanggal TTS</div>
                                        <div class="mt-1 text-[13px] font-semibold text-indigo-700">{{ tanggalTtsPreview || '-' }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Automation Track</div>
                                        <div class="mt-1 text-[13px] font-semibold text-emerald-700">{{ automationTrackPreview || '-' }}</div>
                                    </div>
                                </div>

                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)]">
                                    <div class="mb-6 border-b border-slate-50 pb-5">
                                        <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                            <span>Section 01</span>
                                        </div>
                                        <h3 class="mt-2 text-lg font-black text-slate-900">Basic Information</h3>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Data Source*</label>
                                                <div class="relative">
                                                    <select v-model="form.data_source" :class="controlClass('data_source', 'select')">
                                                        <option value="" disabled>Pilih Data Source</option>
                                                        <option v-for="item in sourceOptions" :key="item" :value="item">{{ item }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal Input*</label>
                                                <input v-model="form.tanggal_input" type="date" :class="controlClass('tanggal_input')" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal Order*</label>
                                                <input v-model="form.tanggal_order" type="date" :class="controlClass('tanggal_order')" />
                                            </div>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Brand*</label>
                                                <div class="relative">
                                                    <select v-model="form.brand" :class="controlClass('brand', 'select')">
                                                        <option value="" disabled>Pilih Brand</option>
                                                        <option v-for="brand in props.brandOptions" :key="brand" :value="brand">{{ brand }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Platform*</label>
                                                <div class="relative">
                                                    <select v-model="form.platform" :class="controlClass('platform', 'select')">
                                                        <option value="" disabled>Pilih Platform</option>
                                                        <option v-for="platform in props.platformOptions" :key="platform" :value="platform">{{ platform }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Order ID*</label>
                                                <input v-model="form.order_id" type="text" placeholder="Masukkan Order ID" :class="controlClass('order_id')" />
                                            </div>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Value</label>
                                                <input v-model="form.value" type="number" placeholder="Nominal order" :class="controlClass('value')" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">AWB</label>
                                                <input v-model="form.awb" type="text" placeholder="Masukkan AWB" :class="controlClass('awb')" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Payment Method</label>
                                                <div class="relative">
                                                    <select v-model="form.payment_method" :class="controlClass('payment_method', 'select')">
                                                        <option value="" disabled>Pilih Payment Method</option>
                                                        <option v-for="item in paymentMethodOptions" :key="item" :value="item">{{ item }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)]">
                                    <div class="mb-6 border-b border-slate-50 pb-5">
                                        <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                            <span>Section 02</span>
                                        </div>
                                        <h3 class="mt-2 text-lg font-black text-slate-900">Handling</h3>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">WH Note</label>
                                            <textarea v-model="form.wh_note" rows="4" :class="controlClass('wh_note', 'textarea')" placeholder="Catatan panjang dari WH..."></textarea>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">CS Name*</label>
                                                <div class="relative">
                                                    <select v-model="form.cs_name" :class="controlClass('cs_name', 'select')">
                                                        <option value="" disabled>Pilih CS</option>
                                                        <option v-for="cs in props.csNameOptions" :key="cs" :value="cs">{{ cs }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Sub Case*</label>
                                                <template v-if="isComplaintLinked">
                                                    <input :value="categoryPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                                </template>
                                                <template v-else>
                                                    <div class="relative">
                                                        <select v-model="form.category" :class="controlClass('category', 'select')">
                                                            <option value="" disabled>Pilih Category</option>
                                                            <option v-for="item in categoryOptions" :key="item" :value="item">{{ item }}</option>
                                                        </select>
                                                        <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Last Step*</label>
                                                <template v-if="isComplaintLinked">
                                                    <input :value="lastStepPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                                </template>
                                                <template v-else>
                                                    <div class="relative">
                                                        <select v-model="form.last_step" :class="controlClass('last_step', 'select')">
                                                            <option value="" disabled>Pilih Last Step</option>
                                                            <option v-for="option in lastStepOptions" :key="option.value" :value="option.value">
                                                                {{ option.label || option.value }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <div v-if="showReasonWhitelist" class="grid gap-4 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Reason Whitelist*</label>
                                                <template v-if="isComplaintLinked">
                                                    <input :value="reasonWhitelistPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                                </template>
                                                <template v-else>
                                                    <div class="relative">
                                                        <select v-model="form.reason_whitelist" :class="controlClass('reason_whitelist', 'select')">
                                                            <option value="" disabled>Pilih Reason Whitelist</option>
                                                            <option v-for="option in reasonWhitelistOptions" :key="option" :value="option">{{ option }}</option>
                                                        </select>
                                                        <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                    </div>
                                                </template>
                                                <p v-if="fieldError('reason_whitelist')" class="text-xs font-medium text-rose-600">{{ fieldError('reason_whitelist') }}</p>
                                            </div>

                                            <div v-if="showReasonLateRespons" class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Reason Late Respons*</label>
                                                <template v-if="isComplaintLinked">
                                                    <input :value="reasonLateResponsPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                                </template>
                                                <template v-else>
                                                    <div class="relative">
                                                        <select v-model="form.reason_late_respons" :class="controlClass('reason_late_respons', 'select')">
                                                            <option value="" disabled>Pilih Reason Late Respons</option>
                                                            <option v-for="option in reasonLateResponseOptions" :key="option" :value="option">{{ option }}</option>
                                                        </select>
                                                        <ChevronDown class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                                                    </div>
                                                </template>
                                                <p v-if="fieldError('reason_late_respons')" class="text-xs font-medium text-rose-600">{{ fieldError('reason_late_respons') }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-3 pt-1">
                                            <div class="flex items-center justify-between gap-3">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Cause By*</label>
                                                <span v-if="causeByLocked" class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--accent)]">Auto from Category</span>
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
                                                        v-for="option in causeByOptions"
                                                        :key="option"
                                                        type="button"
                                                        class="rounded-lg border px-4 py-3 text-[15px] font-bold transition"
                                                        :class="selectButtonClass(form.cause_by, option)"
                                                        @click="form.cause_by = option"
                                                    >
                                                        {{ option }}
                                                    </button>
                                                </template>
                                            </div>
                                            <p v-if="fieldError('cause_by')" class="mt-2 text-xs font-medium text-rose-600">{{ fieldError('cause_by') }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Update</label>
                                            <textarea v-model="form.update" rows="4" :class="controlClass('update', 'textarea')" placeholder="Update utama order tracking..."></textarea>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal Update*</label>
                                                <input v-model="form.tanggal_update" type="datetime-local" :class="controlClass('tanggal_update')" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Value Receive</label>
                                                <input v-model="form.value_receive" type="number" placeholder="Nominal diterima" :class="controlClass('value_receive')" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Insurance Info</label>
                                                <div class="flex flex-wrap gap-2 pt-1">
                                                    <button
                                                        v-for="option in insuranceOptions"
                                                        :key="option"
                                                        type="button"
                                                        class="rounded-lg border px-4 py-3 text-[15px] font-bold transition"
                                                        :class="insuranceButtonClass(option)"
                                                        @click="form.insurance_info = option"
                                                    >
                                                        {{ option }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)]">
                                    <div class="mb-6 border-b border-slate-50 pb-5">
                                        <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                            <span>Section 03</span>
                                        </div>
                                        <h3 class="mt-2 text-lg font-black text-slate-900">Attachment & Internal Update</h3>
                                    </div>

                                    <div class="space-y-5">
                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Video Unboxing</label>
                                                <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-3 transition hover:border-[var(--app-primary)] hover:bg-blue-50/40">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-400 shadow-sm">
                                                            <Upload class="h-4 w-4" />
                                                        </div>
                                                        <div>
                                                            <p class="text-[13px] font-black text-slate-700">{{ videoLabel }}</p>
                                                            <p class="text-[11px] font-medium text-slate-400">Video from WH</p>
                                                        </div>
                                                    </div>
                                                    <input type="file" class="hidden" accept="video/*" @change="setVideoFile" />
                                                </label>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">BAP</label>
                                                <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-4 py-3 transition hover:border-[var(--app-primary)] hover:bg-blue-50/40">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-400 shadow-sm">
                                                            <Upload class="h-4 w-4" />
                                                        </div>
                                                        <div>
                                                            <p class="text-[13px] font-black text-slate-700">{{ bapLabel }}</p>
                                                            <p class="text-[11px] font-medium text-slate-400">Image from WH</p>
                                                        </div>
                                                    </div>
                                                    <input type="file" class="hidden" accept="image/*" @change="setBapFile" />
                                                </label>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Update dari WH</label>
                                            <textarea v-model="form.update_wh" rows="3" :class="controlClass('update_wh', 'textarea')" placeholder="Update internal dari WH..."></textarea>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Update dari Finance</label>
                                            <textarea v-model="form.update_finance" rows="3" :class="controlClass('update_finance', 'textarea')" placeholder="Update internal dari Finance..."></textarea>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)]">
                                    <div class="mb-6 border-b border-slate-50 pb-5">
                                        <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                            <span>Section 04</span>
                                        </div>
                                        <h3 class="mt-2 text-lg font-black text-slate-900">Automation Result</h3>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Status</label>
                                                <input :value="statusPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Automation Track</label>
                                                <input :value="automationTrackPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Tanggal TTS</label>
                                                <input :value="tanggalTtsPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                            </div>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Reason Whitelist</label>
                                                <input :value="reasonWhitelistPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-black uppercase tracking-wide text-slate-700">Reason Late Respons</label>
                                                <input :value="reasonLateResponsPreview || '-'" type="text" readonly :class="readonlyInputClass" />
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <aside class="space-y-6">
                                <div class="sticky top-12 space-y-6">
                                    <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/50">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Live Outcome</p>
                                        <div class="mt-4 space-y-4">
                                            <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-slate-400">Projected Status</p>
                                                <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm" :class="statusClass(statusPreview)">
                                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full" :class="statusDotClass(statusPreview)"></span>
                                                    {{ statusPreview }}
                                                </div>
                                            </div>

                                            <div class="grid gap-2.5 sm:grid-cols-2">
                                                <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-slate-400">Automation Track</p>
                                                    <p class="mt-0.5 text-[11px] font-bold text-slate-700">{{ automationTrackPreview || '-' }}</p>
                                                </div>
                                                <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-slate-400">Tanggal TTS</p>
                                                    <p class="mt-0.5 text-[11px] font-bold text-slate-700">{{ tanggalTtsPreview || '-' }}</p>
                                                </div>
                                            </div>

                                            <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-blue-400">Order Value</p>
                                                <p class="mt-0.5 text-[12px] font-bold text-blue-700">{{ formatCurrency(form.value) }}</p>
                                            </div>

                                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-emerald-400">Value Receive</p>
                                                <p class="mt-0.5 text-[12px] font-bold text-emerald-700">{{ formatCurrency(form.value_receive) }}</p>
                                            </div>

                                            <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3.5">
                                                <p class="text-[10px] font-bold text-indigo-400">Complaint Sync</p>
                                                <p class="mt-0.5 text-[12px] font-bold text-indigo-700">
                                                    {{ isComplaintLinked ? 'Linked with Complaint' : 'No complaint match' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-slate-100 bg-[var(--app-ink)] p-6 text-white shadow-xl">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-white/40">Quick Guidelines</p>
                                        <ul class="mt-4 space-y-3">
                                            <li class="flex items-start gap-2.5">
                                                <div class="flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                    <CheckCircle2 class="h-2.5 w-2.5" />
                                                </div>
                                                <p class="text-[12px] font-medium leading-tight text-white/80">
                                                    Input manual hanya untuk field operasional. <span class="font-bold text-white">Status, Automation Track, TTS</span> otomatis.
                                                </p>
                                            </li>
                                            <li class="flex items-start gap-2.5">
                                                <div class="flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                    <CheckCircle2 class="h-2.5 w-2.5" />
                                                </div>
                                                <p class="text-[12px] font-medium leading-tight text-white/80">
                                                    Jika order terhubung complaint, maka <span class="font-bold text-white">Category, Last Step, Reason Whitelist, Reason Late Respons</span> ikut sinkron.
                                                </p>
                                            </li>
                                            <li class="flex items-start gap-2.5">
                                                <div class="flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                    <CheckCircle2 class="h-2.5 w-2.5" />
                                                </div>
                                                <p class="text-[12px] font-medium leading-tight text-white/80">
                                                    TTS dihitung otomatis dari tanggal order berdasarkan konfigurasi hari per platform (diatur di master Platform).
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

        <transition name="fade">
            <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isDeleteModalOpen = false">
                <div class="w-full max-w-md overflow-hidden rounded-[32px] bg-white shadow-2xl">
                    <div class="bg-rose-50 px-8 py-10">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-rose-600 shadow-sm ring-1 ring-rose-100">
                            <Trash2 class="h-7 w-7" />
                        </div>
                        <h3 class="text-3xl font-black tracking-tight text-slate-900">Delete Tracking</h3>
                        <p class="mt-2 text-[15px] font-medium leading-relaxed text-slate-500">
                            System will remove order tracking record for <b>#{{ itemToDelete?.order_id }}</b> permanently.
                        </p>
                    </div>
                    <div class="flex gap-3 bg-white p-8">
                        <button @click="isDeleteModalOpen = false" class="h-12 flex-1 rounded-2xl bg-slate-50 text-[14px] font-black text-slate-500">
                            Keep It
                        </button>
                        <button @click="submitDelete" class="h-12 flex-[2] rounded-2xl bg-rose-600 text-[14px] font-black text-white shadow-lg shadow-rose-500/20">
                            Delete Forever
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Import modal -->
        <transition name="fade">
            <div v-if="isImportOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isImportOpen = false">
                <div class="w-full max-w-md overflow-hidden rounded-[32px] bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-8 py-7">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[var(--app-primary)]">
                            <Upload class="h-6 w-6" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-slate-900">Import Order Trackings</h3>
                        <p class="mt-1 text-[13px] font-medium text-slate-500">Upload file Excel/CSV dengan format template. Upsert berdasarkan order_id.</p>
                    </div>

                    <!-- Import result -->
                    <div v-if="importResult" class="mx-8 mt-6 rounded-2xl border p-4" :class="importResult.failed > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50'">
                        <p class="text-[12px] font-black uppercase tracking-wider" :class="importResult.failed > 0 ? 'text-amber-700' : 'text-emerald-700'">Hasil Import</p>
                        <div class="mt-2 flex gap-4 text-[13px] font-bold text-slate-700">
                            <span class="text-emerald-600">+{{ importResult.created ?? 0 }} dibuat</span>
                            <span class="text-blue-600">↻ {{ importResult.updated ?? 0 }} diperbarui</span>
                            <span v-if="importResult.failed > 0" class="text-rose-600">✕ {{ importResult.failed }} gagal</span>
                        </div>
                        <ul v-if="importResult.errors?.length" class="mt-2 max-h-24 space-y-0.5 overflow-y-auto text-[11px] text-rose-600">
                            <li v-for="(err, i) in importResult.errors" :key="i">{{ err }}</li>
                        </ul>
                    </div>

                    <div class="space-y-4 px-8 py-6">
                        <div>
                            <label class="mb-1.5 block text-[12px] font-black uppercase tracking-wider text-slate-500">File Excel / CSV</label>
                            <input
                                ref="importFileInput"
                                type="file"
                                accept=".xlsx,.xls,.csv,.txt"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-[13px] text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-[var(--app-primary)] file:px-3 file:py-1.5 file:text-[11px] file:font-black file:text-white"
                                @change="onImportFileChange"
                            />
                            <p v-if="importForm.errors.file" class="mt-1 text-[11px] text-rose-600">{{ importForm.errors.file }}</p>
                        </div>
                        <a :href="route('order-trackings.template')" class="inline-flex items-center gap-1.5 text-[12px] font-bold text-[var(--app-primary)] hover:underline"
                        > <FileText class="h-3.5 w-3.5" />
                            Download template
                        </a>
                    </div>

                    <div class="flex gap-3 border-t border-slate-100 px-8 py-6">
                        <button type="button" @click="isImportOpen = false" class="h-11 flex-1 rounded-2xl bg-slate-50 text-[13px] font-black text-slate-500 hover:bg-slate-100">Batal</button>
                        <button
                            type="button"
                            :disabled="!importFile || importForm.processing"
                            class="h-11 flex-[2] rounded-2xl bg-[var(--app-primary)] text-[13px] font-black text-white shadow-lg shadow-blue-500/20 transition-all hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitImport"
                        >
                            {{ importForm.processing ? 'Mengimpor...' : 'Import Data' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ERP status import modal -->
        <transition name="fade">
            <div v-if="isErpImportOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isErpImportOpen = false">
                <div class="w-full max-w-md overflow-hidden rounded-[32px] bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-8 py-7">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[var(--app-primary)]">
                            <Upload class="h-6 w-6" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-slate-900">Import ERP Status</h3>
                        <p class="mt-1 text-[13px] font-medium text-slate-500">Upload file template berisi daftar <span class="font-bold text-slate-700">order_id</span>. Sistem akan otomatis menaikkan ERP Status ke urutan berikutnya.</p>
                    </div>

                    <div v-if="erpImportResult" class="mx-8 mt-6 rounded-2xl border p-4" :class="erpImportResult.failed > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50'">
                        <p class="text-[12px] font-black uppercase tracking-wider" :class="erpImportResult.failed > 0 ? 'text-amber-700' : 'text-emerald-700'">Hasil Import ERP</p>
                        <div class="mt-2 grid gap-1 text-[13px] font-bold text-slate-700">
                            <span class="text-blue-600">✓ {{ erpImportResult.updated ?? 0 }} order tracking diperbarui</span>
                            <span v-if="(erpImportResult.pending ?? 0) > 0" class="text-violet-600">⏳ {{ erpImportResult.pending }} disimpan ke history (order belum ada di tracking)</span>
                            <span v-if="(erpImportResult.skipped ?? 0) > 0" class="text-slate-500">⟳ {{ erpImportResult.skipped }} dilewati (sudah di status terakhir)</span>
                            <span v-if="erpImportResult.failed > 0" class="text-rose-600">✕ {{ erpImportResult.failed }} baris gagal</span>
                        </div>
                        <ul v-if="erpImportResult.errors?.length" class="mt-2 max-h-24 space-y-0.5 overflow-y-auto text-[11px] text-rose-600">
                            <li v-for="(err, i) in erpImportResult.errors" :key="i">{{ err }}</li>
                        </ul>
                        <div v-if="erpImportResult.ordered_statuses?.length" class="mt-3 border-t border-current/10 pt-3">
                            <p class="mb-1.5 text-[11px] font-black uppercase tracking-wider text-slate-400">Urutan Status ERP</p>
                            <div class="flex flex-wrap items-center gap-1">
                                <template v-for="(status, idx) in erpImportResult.ordered_statuses" :key="idx">
                                    <span class="rounded-lg bg-white px-2 py-0.5 text-[11px] font-bold text-slate-700 shadow-sm ring-1 ring-slate-200">{{ status }}</span>
                                    <span v-if="idx < erpImportResult.ordered_statuses.length - 1" class="text-[10px] text-slate-400">→</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 px-8 py-6">
                        <div class="rounded-2xl border border-dashed border-blue-200 bg-blue-50/50 p-4 text-[12px] font-semibold text-slate-600">
                            Kolom input hanya <span class="font-black text-slate-900">no</span> dan
                            <span class="font-black text-slate-900">order_id</span>. ERP Status akan otomatis maju ke status berikutnya sesuai urutan master.
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-black uppercase tracking-wider text-slate-500">File Excel / CSV</label>
                            <input
                                ref="erpImportFileInput"
                                type="file"
                                accept=".xlsx,.xls,.csv,.txt"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-[13px] text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-[var(--app-primary)] file:px-3 file:py-1.5 file:text-[11px] file:font-black file:text-white"
                                @change="onErpImportFileChange"
                            />
                            <p v-if="erpImportForm.errors.file" class="mt-1 text-[11px] text-rose-600">{{ erpImportForm.errors.file }}</p>
                        </div>
                        <a
                            :href="route('order-trackings.erp-status-template')"
                            class="inline-flex items-center gap-1.5 text-[12px] font-bold text-[var(--app-primary)] hover:underline"
                        >
                            <FileText class="h-3.5 w-3.5" />
                            Download template ERP Status
                            <span class="text-[10px] font-medium text-slate-400">(kosong)</span>
                        </a>
                    </div>

                    <div class="flex gap-3 border-t border-slate-100 px-8 py-6">
                        <button type="button" @click="isErpImportOpen = false" class="h-11 flex-1 rounded-2xl bg-slate-50 text-[13px] font-black text-slate-500 hover:bg-slate-100">Batal</button>
                        <button
                            type="button"
                            :disabled="!erpImportFile || erpImportForm.processing"
                            class="h-11 flex-[2] rounded-2xl bg-[var(--app-primary)] text-[13px] font-black text-white shadow-lg shadow-blue-500/20 transition-all hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitErpImport"
                        >
                            {{ erpImportForm.processing ? 'Mengimpor...' : 'Import ERP Status' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- RGO import modal -->
        <transition name="fade">
            <div v-if="isRgoImportOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isRgoImportOpen = false">
                <div class="w-full max-w-md overflow-hidden rounded-[32px] bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-8 py-7">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <Upload class="h-6 w-6" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-slate-900">Import RGO</h3>
                        <p class="mt-1 text-[13px] font-medium text-slate-500">Upload daftar order_id RGO agar automation track menjadi Sudah diRGO.</p>
                    </div>

                    <div v-if="rgoImportResult" class="mx-8 mt-6 rounded-2xl border p-4" :class="rgoImportResult.failed > 0 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50'">
                        <p class="text-[12px] font-black uppercase tracking-wider" :class="rgoImportResult.failed > 0 ? 'text-amber-700' : 'text-emerald-700'">Hasil Import RGO</p>
                        <div class="mt-2 grid gap-1 text-[13px] font-bold text-slate-700">
                            <span class="text-blue-600">{{ rgoImportResult.updated ?? 0 }} order_id diperbarui</span>
                            <span v-if="rgoImportResult.skipped > 0" class="text-amber-600">{{ rgoImportResult.skipped }} dilewati (order_id tidak ditemukan)</span>
                            <span v-if="rgoImportResult.failed > 0" class="text-rose-600">{{ rgoImportResult.failed }} baris gagal</span>
                        </div>
                        <ul v-if="rgoImportResult.errors?.length" class="mt-2 max-h-24 space-y-0.5 overflow-y-auto text-[11px] text-rose-600">
                            <li v-for="(err, i) in rgoImportResult.errors" :key="i">{{ err }}</li>
                        </ul>
                    </div>

                    <div class="space-y-4 px-8 py-6">
                        <div class="rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/50 p-4 text-[12px] font-semibold text-slate-600">
                            File harus berisi kolom <span class="font-black text-slate-900">order_id</span> dan <span class="font-black text-slate-900">rgo_status</span>. Sistem akan memperbarui kolom rgo_status di order tracking yang sesuai.
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[12px] font-black uppercase tracking-wider text-slate-500">File Excel / CSV</label>
                            <input
                                ref="rgoImportFileInput"
                                type="file"
                                accept=".xlsx,.xls,.csv,.txt"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-[13px] text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-1.5 file:text-[11px] file:font-black file:text-white"
                                @change="onRgoImportFileChange"
                            />
                            <p v-if="rgoImportForm.errors.file" class="mt-1 text-[11px] text-rose-600">{{ rgoImportForm.errors.file }}</p>
                        </div>
                        <a :href="route('order-trackings.rgo-template')" class="inline-flex items-center gap-1.5 text-[12px] font-bold text-emerald-700 hover:underline">
                            <FileText class="h-3.5 w-3.5" />
                            Download template RGO
                        </a>
                    </div>

                    <div class="flex gap-3 border-t border-slate-100 px-8 py-6">
                        <button type="button" @click="isRgoImportOpen = false" class="h-11 flex-1 rounded-2xl bg-slate-50 text-[13px] font-black text-slate-500 hover:bg-slate-100">Batal</button>
                        <button
                            type="button"
                            :disabled="!rgoImportFile || rgoImportForm.processing"
                            class="h-11 flex-[2] rounded-2xl bg-emerald-600 text-[13px] font-black text-white shadow-lg shadow-emerald-500/20 transition-all hover:-translate-y-0.5 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitRgoImport"
                        >
                            {{ rgoImportForm.processing ? 'Mengimpor...' : 'Import RGO' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Sync RGO modal -->
        <transition name="fade">
            <div v-if="isSyncRgoOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm" @click.self="isSyncRgoOpen = false">
                <div class="w-full max-w-2xl overflow-hidden rounded-[32px] bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-8 py-7">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-600">
                            <RotateCcw class="h-6 w-6" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-slate-900">Sync RGO dengan Google Sheet</h3>
                        <p class="mt-1 text-[13px] font-medium text-slate-500">Tarik data <span class="font-black">rgo_status</span> terbaru dari Google Sheet, cocokkan berdasarkan <span class="font-black">order_id</span>, lalu update sistem mengikuti sheet (sheet sebagai source of truth).</p>
                        <p v-if="props.rgoLastSynced" class="mt-2 text-[11px] font-semibold text-slate-400">Terakhir sync: {{ props.rgoLastSynced }}</p>
                        <p v-else class="mt-2 text-[11px] font-semibold text-slate-400">Belum pernah sync.</p>
                    </div>

                    <div v-if="rgoSyncResult" class="px-8 pt-6">
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" @click="rgoSyncFilter = 'updated'" class="rounded-2xl border p-3 text-left transition" :class="rgoSyncFilter === 'updated' ? 'border-blue-300 bg-blue-50 ring-2 ring-blue-200' : 'border-blue-200 bg-blue-50/60 hover:border-blue-300'">
                                <p class="text-[10px] font-black uppercase tracking-wider text-blue-700">Diperbarui</p>
                                <p class="mt-1 text-2xl font-black text-blue-700">{{ rgoSyncResult.summary?.updated ?? 0 }}</p>
                            </button>
                            <button type="button" @click="rgoSyncFilter = 'matched'" class="rounded-2xl border p-3 text-left transition" :class="rgoSyncFilter === 'matched' ? 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-200' : 'border-emerald-200 bg-emerald-50/60 hover:border-emerald-300'">
                                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Sudah Cocok</p>
                                <p class="mt-1 text-2xl font-black text-emerald-700">{{ rgoSyncResult.summary?.matched ?? 0 }}</p>
                            </button>
                            <button type="button" @click="rgoSyncFilter = 'not_in_system'" class="rounded-2xl border p-3 text-left transition" :class="rgoSyncFilter === 'not_in_system' ? 'border-amber-300 bg-amber-50 ring-2 ring-amber-200' : 'border-amber-200 bg-amber-50/60 hover:border-amber-300'">
                                <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Tidak Ada di Sistem</p>
                                <p class="mt-1 text-2xl font-black text-amber-700">{{ rgoSyncResult.summary?.not_in_system ?? 0 }}</p>
                            </button>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <button type="button" @click="rgoSyncFilter = 'all'" class="text-[11px] font-black uppercase tracking-wider text-slate-500 hover:text-slate-700">
                                {{ rgoSyncFilter === 'all' ? 'Semua' : 'Tampilkan semua' }} ({{ rgoSyncResult.summary?.total ?? 0 }})
                            </button>
                        </div>

                        <div class="mt-3 max-h-72 overflow-y-auto rounded-2xl border border-slate-200">
                            <table class="w-full text-[12px]">
                                <thead class="sticky top-0 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Order ID</th>
                                        <th class="px-3 py-2 text-left">Sebelum</th>
                                        <th class="px-3 py-2 text-left">Sesudah / Sheet</th>
                                        <th class="px-3 py-2 text-left">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(entry, i) in filteredSyncEntries" :key="i" class="border-t border-slate-100">
                                        <td class="px-3 py-2 font-bold text-slate-800">{{ entry.order_id }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ entry.before ?? '—' }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ entry.after ?? entry.sheet ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            <span v-if="entry.reason === 'updated'" class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-blue-700">Diperbarui</span>
                                            <span v-else-if="entry.reason === 'matched'" class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700">Sudah cocok</span>
                                            <span v-else class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-700">Tidak ada di sistem</span>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredSyncEntries.length === 0">
                                        <td colspan="4" class="px-3 py-6 text-center text-[12px] font-semibold text-slate-400">Tidak ada data pada kategori ini.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <ul v-if="rgoSyncResult.errors?.length" class="mt-3 max-h-24 space-y-0.5 overflow-y-auto rounded-2xl bg-rose-50 p-3 text-[11px] text-rose-600">
                            <li v-for="(err, i) in rgoSyncResult.errors" :key="i">{{ err }}</li>
                        </ul>
                    </div>

                    <div v-else class="px-8 py-6">
                        <div class="rounded-2xl border border-dashed border-teal-200 bg-teal-50/50 p-4 text-[12px] font-semibold text-slate-600">
                            Data akan ditarik dari Google Sheet master RGO. Pastikan sheet sudah berisi kolom
                            <span class="font-black text-slate-900">order_id</span> dan
                            <span class="font-black text-slate-900">rgo_status</span>. Sistem akan diperbarui mengikuti sheet.
                        </div>
                    </div>

                    <div class="flex gap-3 border-t border-slate-100 px-8 py-6">
                        <button type="button" @click="isSyncRgoOpen = false" class="h-11 flex-1 rounded-2xl bg-slate-50 text-[13px] font-black text-slate-500 hover:bg-slate-100">Tutup</button>
                        <button
                            type="button"
                            :disabled="syncRgoForm.processing"
                            class="h-11 flex-[2] rounded-2xl bg-teal-600 text-[13px] font-black text-white shadow-lg shadow-teal-500/20 transition-all hover:-translate-y-0.5 hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitSyncRgo"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <RotateCcw v-if="!syncRgoForm.processing" class="h-4 w-4" />
                                <span>{{ syncRgoForm.processing ? 'Menyinkronkan...' : 'Sync Sekarang' }}</span>
                            </span>
                        </button>
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
        <div v-if="canUseDeleteActions && selectedIds.length > 0" class="fixed bottom-10 left-1/2 z-40 -translate-x-1/2">
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
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
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
    .fade-enter-active,
    .fade-leave-active {
        transition: opacity 0.3s ease;
    }
    .fade-enter-from,
    .fade-leave-to {
        opacity: 0;
    }
</style>
