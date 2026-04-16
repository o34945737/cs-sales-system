<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
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
    ShieldAlert,
    Trash2,
    Upload,
    Users,
    X,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { computed, ref, watch } from 'vue';

const DEFAULT_SOURCE_OPTIONS = ['After Sales', 'Pre Sales', 'Brand', 'KAE', 'Socmed'];
const DEFAULT_COMPLAINT_POWER_OPTIONS = ['Hard Complaint', 'Normal Complaint'];
const DEFAULT_STEP_STATUS_OPTIONS = ['YES', 'NO'];
const DEFAULT_PRIORITY_OPTIONS = ['Cool', 'Mines', 'P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];

const uniqueOptions = (values) => [...new Set(values.filter(Boolean))];
const pickPreferredOption = (options, preferred = '') => {
    const normalizedOptions = Array.isArray(options) ? options.filter(Boolean) : [];

    if (preferred && normalizedOptions.includes(preferred)) {
        return preferred;
    }

    return normalizedOptions[0] || '';
};
const pickPreferredLastStep = (options, preferred = '') => {
    const normalizedOptions = Array.isArray(options) ? options.filter((option) => option?.value) : [];

    if (preferred) {
        const matchedOption = normalizedOptions.find((option) => option.value === preferred);

        if (matchedOption) {
            return matchedOption.value;
        }
    }

    return normalizedOptions[0]?.value || '';
};
const today = () => {
    const currentDate = new Date();
    const timezoneOffset = currentDate.getTimezoneOffset() * 60000;

    return new Date(currentDate.getTime() - timezoneOffset).toISOString().split('T')[0];
};
const nowTime = () => new Date().toTimeString().slice(0, 8);

const inputClass =
    'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const readonlyInputClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-[14px] text-slate-400 outline-none';
const selectClass =
    'w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2 pr-12 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';
const textAreaClass =
    'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-[14px] text-slate-900 outline-none transition duration-200 focus:border-[var(--app-primary)] focus:ring-4 focus:ring-[var(--app-primary)]/10';

const createEmptyPaginator = () => ({
    current_page: 1,
    data: [],
    from: 0,
    last_page: 1,
    links: [],
    to: 0,
    total: 0,
});

const props = defineProps({
    complaints: Object,
    filters: Object,
    cs_summary: Array,
    status_summary: Object,
    overview: Object,
    brandOptions: Array,
    csNameOptions: Array,
    platformOptions: Array,
    skuCodeOptions: Array,
    sourceOptions: Array,
    complaintPowerOptions: Array,
    partOfBadOptions: Array,
    subCaseOptions: Array,
    causeByOptions: Array,
    lastStepOptions: Array,
    reasonWhitelistOptions: Array,
    reasonLateResponseOptions: Array,
    priority_summary: Object,
    oosOrderIds: Array,
    autoCauseByMap: Object,
});

const complaintPage = computed(() => ({
    ...createEmptyPaginator(),
    ...(props.complaints || {}),
}));
const complaintRows = computed(() => (Array.isArray(complaintPage.value.data) ? complaintPage.value.data : []));
const paginationLinks = computed(() => (Array.isArray(complaintPage.value.links) ? complaintPage.value.links.filter((link) => link?.url) : []));
const filterState = computed(() => (props.filters && !Array.isArray(props.filters) ? props.filters : {}));
const csSummary = computed(() => props.cs_summary || []);
const statusSummary = computed(() => props.status_summary || {});
const prioritySummary = computed(() => (props.priority_summary && !Array.isArray(props.priority_summary) ? props.priority_summary : {}));
const overview = computed(() => props.overview || {});
const sourceOptions = computed(() =>
    Array.isArray(props.sourceOptions) && props.sourceOptions.length ? props.sourceOptions : DEFAULT_SOURCE_OPTIONS,
);
const complaintPowerOptions = computed(() =>
    (Array.isArray(props.complaintPowerOptions) && props.complaintPowerOptions.length
        ? props.complaintPowerOptions
        : DEFAULT_COMPLAINT_POWER_OPTIONS
    ).map((value) => ({
        label: value.toUpperCase(),
        value,
    })),
);
const stepStatusOptions = computed(() => DEFAULT_STEP_STATUS_OPTIONS);
const masterBrandOptions = computed(() => (Array.isArray(props.brandOptions) ? props.brandOptions : []));
const masterPlatformOptions = computed(() => (Array.isArray(props.platformOptions) ? props.platformOptions : []));
const masterSkuCodeOptions = computed(() => (Array.isArray(props.skuCodeOptions) ? props.skuCodeOptions : []));
const partOfBadOptions = computed(() => (Array.isArray(props.partOfBadOptions) ? props.partOfBadOptions : []));
const subCaseOptions = computed(() => (Array.isArray(props.subCaseOptions) ? props.subCaseOptions : []));
const csNameOptions = computed(() => (Array.isArray(props.csNameOptions) ? props.csNameOptions : []));
const causeByOptions = computed(() => (Array.isArray(props.causeByOptions) ? props.causeByOptions : ['?']));
const oosOrderIds = computed(() => (Array.isArray(props.oosOrderIds) ? props.oosOrderIds.filter(Boolean) : []));
const lastStepOptions = computed(() => {
    if (Array.isArray(props.lastStepOptions) && props.lastStepOptions.length) {
        return props.lastStepOptions;
    }

    return uniqueOptions(complaintRows.value.map((item) => item.last_step)).map((value) => ({
        label: value,
        value,
        status_result: null,
        priority_level: null,
    }));
});
const reasonWhitelistOptions = computed(() => {
    if (Array.isArray(props.reasonWhitelistOptions) && props.reasonWhitelistOptions.length) {
        return props.reasonWhitelistOptions;
    }

    return uniqueOptions(complaintRows.value.map((item) => item.reason_whitelist));
});
const reasonLateResponseOptions = computed(() => {
    if (Array.isArray(props.reasonLateResponseOptions) && props.reasonLateResponseOptions.length) {
        return props.reasonLateResponseOptions;
    }

    return uniqueOptions(complaintRows.value.map((item) => item.reason_late_respons));
});
const autoCauseByMap = computed(() => (props.autoCauseByMap && !Array.isArray(props.autoCauseByMap) ? props.autoCauseByMap : {}));
const lastStepMetaMap = computed(() =>
    Object.fromEntries(
        lastStepOptions.value
            .filter((option) => option?.value)
            .map((option) => [
                option.value,
                {
                    status: option.status_result || 'Pending',
                    priority: option.priority_level ?? null,
                },
            ]),
    ),
);

const search = ref(filterState.value.search || '');
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const complaintToDelete = ref(null);
const detailItem = ref(null);
const submitError = ref('');

const visitIndex = (overrides = {}, options = {}) => {
    router.get(
        route('complaints.index'),
        {
            search: search.value || undefined,
            status: filterState.value.status && filterState.value.status !== 'All' ? filterState.value.status : undefined,
            cs_name: filterState.value.cs_name || undefined,
            brand: filterState.value.brand && filterState.value.brand !== 'All' ? filterState.value.brand : undefined,
            priority: filterState.value.priority && filterState.value.priority !== 'All' ? filterState.value.priority : undefined,
            sort: filterState.value.sort || 'tanggal_complaint',
            order: filterState.value.order || 'desc',
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true, ...options },
    );
};

watch(
    search,
    debounce((value) => visitIndex({ search: value || undefined, page: 1 }), 350),
);

const setStatus = (status) => visitIndex({ status: status === 'All' ? undefined : status, page: 1 }, { replace: false });
const setCsFilter = (name) => visitIndex({ cs_name: name || undefined, page: 1 }, { replace: false });
const setBrandFilter = (brand) => visitIndex({ brand: brand === 'All' ? undefined : brand, page: 1 }, { replace: false });
const setPriorityFilter = (priority) => visitIndex({ priority: priority === 'All' ? undefined : priority, page: 1 }, { replace: false });
const sortBy = (field) =>
    visitIndex({ sort: field, order: filterState.value.sort === field && filterState.value.order === 'asc' ? 'desc' : 'asc' }, { replace: false });

const formatDate = (value) => {
    if (!value) return '-';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parsed);
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0);
};

const resolveStatus = (lastStep) => {
    return lastStepMetaMap.value[lastStep]?.status || 'Pending';
};

const resolvePriority = (lastStep) => {
    return lastStepMetaMap.value[lastStep]?.priority ?? null;
};

const overviewCards = computed(() => [
    { label: 'Total', value: overview.value.total || 0 },
    { label: 'Pending', value: overview.value.pending || 0 },
    { label: 'Solved', value: overview.value.solved || 0 },
    { label: 'Active Agents', value: overview.value.agents || 0 },
]);

const statusCards = computed(() => [
    { key: 'All', label: 'Semua', value: statusSummary.value.all || 0, icon: ClipboardList },
    { key: 'Pending', label: 'Pending', value: statusSummary.value.pending || 0, icon: AlertCircle },
    { key: 'Solved', label: 'Solved', value: statusSummary.value.solved || 0, icon: CheckCircle2 },
    { key: 'Whitelist', label: 'Whitelist', value: statusSummary.value.whitelist || 0, icon: ShieldAlert },
]);

const priorityCards = computed(() => [
    { key: 'All', label: 'Semua Priority', value: overview.value.total || 0 },
    ...DEFAULT_PRIORITY_OPTIONS.map((priority) => ({
        key: priority,
        label: priority,
        value: prioritySummary.value[priority] || 0,
    })),
]);

const currentStatus = computed(() => filterState.value.status || 'All');
const currentCs = computed(() => filterState.value.cs_name || '');
const currentBrand = computed(() => filterState.value.brand || 'All');
const currentPriority = computed(() => filterState.value.priority || 'All');
const hasActiveFilters = computed(() =>
    Boolean(search.value || currentStatus.value !== 'All' || currentCs.value || currentBrand.value !== 'All' || currentPriority.value !== 'All'),
);
const activeFilterCount = computed(
    () =>
        [Boolean(search.value), currentStatus.value !== 'All', Boolean(currentCs.value), currentBrand.value !== 'All', currentPriority.value !== 'All'].filter(
            Boolean,
        ).length,
);

const resetFilters = () => {
    search.value = '';
    visitIndex(
        {
            search: undefined,
            status: undefined,
            cs_name: undefined,
            brand: undefined,
            priority: undefined,
            page: 1,
        },
        { replace: false },
    );
};

const complaintBrandOptions = computed(() =>
    masterBrandOptions.value.length ? masterBrandOptions.value : uniqueOptions(complaintRows.value.map((item) => item.brand)),
);
const complaintBrandFilterOptions = computed(() => ['All', ...complaintBrandOptions.value]);

const complaintPlatformOptions = computed(() =>
    masterPlatformOptions.value.length ? masterPlatformOptions.value : uniqueOptions(complaintRows.value.map((item) => item.platform)),
);

const skuOptions = computed(() =>
    uniqueOptions([...masterSkuCodeOptions.value.map((item) => item?.sku), ...complaintRows.value.map((item) => item.sku)]),
);

const skuCatalog = computed(() => {
    const catalog = {};

    masterSkuCodeOptions.value.forEach((item) => {
        if (!item?.sku) {
            return;
        }

        catalog[item.sku] = {
            product_name: item.product_name || '',
            available_qty: item.available_qty || 0,
            status_qty: item.status_qty || '-',
        };
    });

    complaintRows.value.forEach((item) => {
        if (!item.sku) {
            return;
        }

        catalog[item.sku] = {
            ...catalog[item.sku],
            product_name: item.product_name || catalog[item.sku]?.product_name || '',
            available_qty: item.available_qty || catalog[item.sku]?.available_qty || 0,
            status_qty: item.status_qty || catalog[item.sku]?.status_qty || '-',
        };
    });

    return catalog;
});

const createInitialFormState = () => ({
    source: '',
    tanggal_complaint: today(),
    tanggal_order: today(),
    jam_customer_complaint: nowTime(),
    brand: '',
    platform: '',
    order_id: '',
    resi: '',
    product_name: '',
    sku: '',
    sub_case: '',
    cause_by: '?',
    proof: '',
    summary_case: '',
    update_long_text: '',
    part_of_bad: '',
    cs_name: '',
    last_step: '',
    tanggal_step_cs_selesai: '',
    update_ai: '',
    step_cs_selesai: 'NO',
    tanggal_update: today(),
    auto_sync_sla: '',
    complaint_power: 'Normal Complaint',
    report_category: '',
    video_unboxing: null,
    username: '',
    category_customer: '',
    oos: '',
    reason_whitelist: '',
    reason_late_respons: '',
    proof_attachment: null,
});

const form = useForm(createInitialFormState());

const selectedSku = computed(() => skuCatalog.value[form.sku] || {});

watch(
    () => form.sub_case,
    (val) => {
        if (automationResults.value.cause_by !== 'Manual') {
            form.cause_by = automationResults.value.cause_by;
        }
    }
);

watch(
    selectedSku,
    (matchedSku) => {
        form.product_name = matchedSku?.product_name || '';
    },
    { immediate: true, deep: true },
);

watch(
    () => form.last_step,
    (val) => {
        form.status = automationResults.value.status;
        form.priority = automationResults.value.priority;
        
        if (val !== 'Claim Reject') {
            form.reason_whitelist = '';
            form.reason_late_respons = '';
        }
    }
);

watch(
    () => form.username,
    () => {
        form.category_customer = customerHistoryCount.value;
    }
);

watch(
    () => form.reason_whitelist,
    (value) => {
        if (value !== 'Late Respons') {
            form.reason_late_respons = '';
        }
    },
);

watch(
    () => form.step_cs_selesai,
    (value) => {
        if (value !== 'YES') {
            form.tanggal_step_cs_selesai = '';
        }
    },
);

const showReasonWhitelist = computed(() => form.last_step === 'Claim Reject');
const showReasonLateRespons = computed(() => showReasonWhitelist.value && form.reason_whitelist === 'Late Respons');
const showStepCompletedDate = computed(() => form.step_cs_selesai === 'YES');

const cyclePreview = computed(() => {
    if (!form.jam_customer_complaint) {
        return '-';
    }

    return form.jam_customer_complaint >= '21:00:00' || form.jam_customer_complaint <= '15:00:00'
        ? 'Cycle 1 (21.00 - 15.00)'
        : 'Cycle 2 (15.01 - 20.59)';
});

const statusPreview = computed(() => resolveStatus(form.last_step));
const priorityPreview = computed(() => resolvePriority(form.last_step));

const slaPreview = computed(() => {
    if (!form.tanggal_complaint) {
        return 0;
    }

    const startDate = new Date(form.tanggal_complaint);
    const endDate = statusPreview.value === 'Solved' && form.tanggal_update ? new Date(form.tanggal_update) : new Date();

    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
        return 0;
    }

    return Math.max(0, Math.floor((endDate - startDate) / 86400000));
});

const autoSyncSlaPreview = computed(() => {
    if (slaPreview.value <= 0) {
        return 'Within 1 day';
    }

    return `Above ${slaPreview.value} days`;
});

const reportCategoryPreview = computed(() => {
    if (!form.sub_case) {
        return '';
    }

    if (!form.cause_by || form.cause_by === '?') {
        return form.sub_case;
    }

    return `${form.sub_case} by ${form.cause_by}`;
});

const categoryCustomerPreview = computed(() => {
    if (!form.username) {
        return '';
    }

    const repeatedCount = complaintRows.value.filter((item) => item.username && item.username.toLowerCase() === form.username.toLowerCase()).length;

    if (repeatedCount === 0) {
        return '';
    }

    if (repeatedCount === 1) {
        return 'Customer ini complaint ke 2';
    }

    return `Customer ini complaint ke ${repeatedCount + 1}x`;
});

const oosPreview = computed(() => {
    if (!form.order_id) {
        return '';
    }

    const hasOosHistory = oosOrderIds.value.includes(form.order_id);

    return hasOosHistory ? 'Ada Riwayat OOS' : '';
});

const causeByLocked = computed(() => Boolean(autoCauseByMap.value[form.sub_case]));
const videoLabel = computed(() => form.video_unboxing?.name || 'Upload video unboxing');

const modalMode = ref('create'); // 'create' or 'edit'
const editId = ref(null);

const setVideoFile = (event) => {
    const [file] = event.target.files || [];
    form.video_unboxing = file || null;
};

const proofAttachmentLabel = computed(() => form.proof_attachment?.name || 'Upload proof attachment');

const setProofAttachmentFile = (event) => {
    const [file] = event.target.files || [];
    form.proof_attachment = file || null;
};

const discardForm = () => {
    submitError.value = '';
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = false;
    editId.value = null;
    modalMode.value = 'create';
};

const openCreateModal = () => {
    submitError.value = '';
    modalMode.value = 'create';
    editId.value = null;
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    submitError.value = '';
    modalMode.value = 'edit';
    editId.value = item.id;
    
    // Map existing data to form
    const initialState = createInitialFormState();
    Object.keys(initialState).forEach(key => {
        if (item[key] !== undefined) {
            form[key] = item[key];
        }
    });

    // Special handling for legacy/missing fields in form that might be in row
    if (item.level_customer) form.complaint_power = item.level_customer;
    
    isModalOpen.value = true;
};

const confirmDelete = (item) => {
    complaintToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const submitDelete = () => {
    if (!complaintToDelete.value) return;

    router.delete(route('complaints.destroy', complaintToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            complaintToDelete.value = null;
        }
    });
};

const openDetail = (item) => {
    detailItem.value = item;
};

const closeDetail = () => {
    detailItem.value = null;
};

const submitForm = () => {
    console.log('Submitting complaint form...');
    submitError.value = '';

    form
        .transform((data) => {
            const { complaint_power, ...payload } = data;
            const oosValue = oosPreview.value || null;

            return {
                ...payload,
                cycle: cyclePreview.value,
                status: statusPreview.value,
                priority: priorityPreview.value,
                auto_sync_sla: autoSyncSlaPreview.value,
                report_category: reportCategoryPreview.value || null,
                category_customer: categoryCustomerPreview.value || null,
                complaint_power,
                level_customer: complaint_power,
                oos: oosValue,
                riwayat_oos: oosValue === 'Ada Riwayat OOS' ? oosValue : null,
                reason_whitelist: showReasonWhitelist.value ? data.reason_whitelist : null,
                reason_late_respons: showReasonLateRespons.value ? data.reason_late_respons : null,
                tanggal_step_cs_selesai: showStepCompletedDate.value
                    ? data.tanggal_step_cs_selesai || data.tanggal_update
                    : null,
                proof: data.proof || null,
                proof_attachment: data.proof_attachment || null,
                _method: modalMode.value === 'edit' ? 'PUT' : 'POST'
            };
        })
        .post(modalMode.value === 'edit' ? route('complaints.update', editId.value) : route('complaints.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => discardForm(),
            onError: (errors) => {
                console.error('Complaint submit failed:', errors);
                submitError.value = 'Data gagal disimpan. Periksa field wajib dan validasi backend.';
            },
        });
};

const selectButtonClass = (currentValue, expectedValue) =>
    currentValue === expectedValue
        ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white shadow-md'
        : 'border-slate-200 bg-white text-slate-600 hover:border-[var(--app-primary)]/40 hover:bg-slate-50';

const statusClass = (status) =>
    status === 'Solved' ? 'bg-emerald-50 text-emerald-700' : status === 'Whitelist' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700';

const priorityClass = (priority) =>
    ['Mines', 'P1', 'P2'].includes(priority)
        ? 'bg-rose-50 text-rose-700'
        : priority === 'Cool'
          ? 'bg-emerald-50 text-emerald-700'
          : 'bg-slate-100 text-slate-700';

const priorityFilterClass = (priority) =>
    currentPriority.value === priority
        ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white shadow-[0_8px_20px_rgba(53,103,232,0.18)]'
        : 'border-slate-100 bg-white text-slate-700 shadow-sm hover:border-[var(--app-primary)]/30 hover:bg-slate-50 hover:-translate-y-0.5';

const statusDotClass = (status) => (status === 'Solved' ? 'bg-emerald-500' : status === 'Whitelist' ? 'bg-rose-500' : 'bg-amber-500');

const fieldError = (field) => form.errors[field];

const controlClass = (field, variant = 'input') => {
    const baseClass = variant === 'select' ? selectClass : variant === 'textarea' ? textAreaClass : inputClass;

    return fieldError(field) ? `${baseClass} border-rose-300 bg-rose-50/60 focus:border-rose-400` : baseClass;
};

const isFilled = (value) => value !== null && value !== undefined && value !== '';

// --- Automation Preview Logic (Frontend Mirror) ---
const computedCycle = computed(() => {
    if (!form.jam_customer_complaint) return 'Waiting for time...';
    const time = form.jam_customer_complaint;
    // Logic: 
    // >= 21:00 or <= 15:00 -> Cycle 1
    // >= 15:01 and <= 20:59 -> Cycle 2
    if (time >= '21:00' || time <= '15:00') return 'Cycle 1 (21.00 – 15.00)';
    return 'Cycle 2 (15.01 – 20.59)';
});

const computedSLAStatus = computed(() => {
    if (!form.tanggal_complaint) return '-';
    const start = new Date(form.tanggal_complaint);
    const end = form.step_cs_selesai === 'YES' ? new Date(form.tanggal_update) : new Date();
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return `${diffDays} Days`;
});

const customerHistoryCount = computed(() => {
    if (!form.username) return null;
    const count = props.complaints.data.filter(c => c.username === form.username).length;
    if (count === 0) return '';
    if (count === 1) return 'Customer ini complaint ke 2';
    return `Customer ini complaint ke ${count + 1}x`;
});

const automationResults = computed(() => {
    const res = {
        cause_by: 'Manual',
        status: 'Pending',
        priority: 'P3'
    };

    // 1. Cause By Automation
    if (['Bad Quality Product', 'Expired'].includes(form.sub_case)) res.cause_by = 'BRAND';
    else if (['Misunderstanding of the product', 'Change Mind'].includes(form.sub_case)) res.cause_by = 'CUSTOMER';
    else if (form.sub_case === 'OOS') res.cause_by = 'KAE';
    else if (form.sub_case === 'Promotion') res.cause_by = 'PROMO';

    // 2. Status Automation
    const solvedSteps = [
        'Claim Receive (10x shipping fee)', 'Claim Receive (Full)', 
        'Complaint Canceled by buyer/No Respons', 'Product has been delivered (Late Delivery)',
        'Refund has been transferred by finance (SPF)', 'Return Refund (Full)',
        'Return Refund (Partial)', 'Seller Win', 
        'The replacement product has been received by the buyer', 'Return follow up (No further action)'
    ];
    
    if (solvedSteps.includes(form.last_step)) res.status = 'Solved';
    else if (form.last_step === 'Claim Reject') res.status = 'Whitelist';

    // 3. Priority Automation
    if (solvedSteps.includes(form.last_step)) res.priority = 'Cool';
    else if (form.last_step === 'Claim Reject') res.priority = 'Mines';
    else if (['Analysis MP (Non Late Delivery)', 'Follow Up After Sales Team', 'Follow Up WH'].includes(form.last_step)) res.priority = 'P1';
    else if (['On the way return & plan banding', 'Follow Up KAE to KAM', 'Follow Up KAE/Brand'].includes(form.last_step)) res.priority = 'P2';
    else if (['Analysis MP (Late Delivery)', 'Return not authorize'].includes(form.last_step)) res.priority = 'P5';
    else if (['Kingdee Processing (Waiting AWB for replacement product)', 'Refund processing by finance (SPF)', 'Replacement product on the way'].includes(form.last_step)) res.priority = 'P6';
    else if (['Waiting Claim', 'Waiting Money Receive'].includes(form.last_step)) res.priority = 'P7';
    else if (form.last_step?.includes('replace')) res.priority = 'P4';

    return res;
});

const completionSummary = computed(() => {
    const checks = [
        isFilled(form.source),
        isFilled(form.tanggal_complaint),
        isFilled(form.tanggal_order),
        isFilled(form.jam_customer_complaint),
        isFilled(form.brand),
        isFilled(form.platform),
        isFilled(form.order_id),
        isFilled(form.username),
        isFilled(form.resi),
        isFilled(form.sku),
        isFilled(form.sub_case),
        isFilled(form.cause_by),
        isFilled(form.summary_case),
        isFilled(form.update_long_text),
        isFilled(form.cs_name),
        isFilled(form.last_step),
        isFilled(form.step_cs_selesai),
        isFilled(form.tanggal_update),
        isFilled(form.complaint_power),
    ];

    if (showStepCompletedDate.value) {
        checks.push(isFilled(form.tanggal_step_cs_selesai));
    }

    if (showReasonWhitelist.value) {
        checks.push(isFilled(form.reason_whitelist));
    }

    if (showReasonLateRespons.value) {
        checks.push(isFilled(form.reason_late_respons));
    }

    const total = checks.length;
    const completed = checks.filter(Boolean).length;

    return {
        total,
        completed,
        percent: total ? Math.round((completed / total) * 100) : 0,
    };
});

const sectionChecks = computed(() => [
    {
        label: 'Informasi utama',
        complete: [
            form.source,
            form.tanggal_complaint,
            form.tanggal_order,
            form.jam_customer_complaint,
            form.brand,
            form.platform,
            form.order_id,
            form.username,
            form.resi,
            form.sku,
            form.sub_case,
            form.cause_by,
        ].every(isFilled),
    },
    {
        label: 'Handling complaint',
        complete: [
            form.summary_case,
            form.update_long_text,
            form.cs_name,
            form.last_step,
            form.step_cs_selesai,
            form.tanggal_update,
            form.complaint_power,
        ].every(isFilled),
    },
    {
        label: 'Kondisi khusus',
        complete:
            (!showReasonWhitelist.value || isFilled(form.reason_whitelist)) &&
            (!showReasonLateRespons.value || isFilled(form.reason_late_respons)) &&
            (!showStepCompletedDate.value || isFilled(form.tanggal_step_cs_selesai)),
    },
]);
</script>

<template>
    <Head title="Complaints">
        <meta
            name="description"
            content="Manajemen halaman keluhan (complaints). Monitoring tiket case pelanggan yang terstruktur rapi untuk ditindaklanjuti cepat oleh admin dan agen Customer Service."
        />
    </Head>

    <AppLayout
        :breadcrumbs="[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Complaints', href: '/complaints' },
        ]"
    >
        <div class="pb-20">
            <div class="mx-auto flex max-w-[90rem] flex-col gap-10 px-4 font-sans sm:px-6 lg:px-8">
                <div class="space-y-10">
                    <!-- 1. Top Metric Bar (Standard UX) -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <article
                            v-for="card in overviewCards"
                            :key="card.label"
                            class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:-translate-y-0.5"
                        >
                            <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-[var(--app-primary)]/5 blur-2xl"></div>
                            <div class="relative z-10">
                                <p class="text-[9px] font-black uppercase tracking-[0.15rem] text-slate-400">{{ card.label }}</p>
                                <div class="mt-1.5 flex items-end justify-between">
                                    <p class="text-2xl font-black tracking-tight text-slate-900">{{ card.value }}</p>
                                    <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                        <Activity class="h-3 w-3" />
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- 2. Header & Quick Actions -->
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-xl">
                            <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)] ring-1 ring-blue-100">
                                <AlertCircle class="h-2.5 w-2.5" />
                                Operational Support Center
                            </div>
                            <h1 class="mt-2 text-xl font-black tracking-tight text-slate-900 lg:text-2xl">Complaints <span class="text-[var(--app-primary)]">Command.</span></h1>
                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                Visualisasi data komplain secara real-time dengan integrasi inventory dan SLA tracking otomatis.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="group flex h-9 items-center justify-center gap-2 rounded-xl bg-[var(--app-primary)] px-5 text-xs font-bold text-white shadow-lg transition-all hover:bg-[var(--app-primary-dark)] hover:-translate-y-0.5 active:scale-[0.98]"
                            @click="openCreateModal"
                        >
                            <Plus class="h-3.5 w-3.5 stroke-[3px]" />
                            <span>Create Ticket</span>
                        </button>
                    </div>

                    <!-- 3. Unified Filters & Search Row -->
                    <div class="rounded-3xl border border-slate-100 bg-slate-50/30 p-1.5 shadow-sm">
                        <div class="flex flex-col gap-1.5 lg:flex-row lg:items-center">
                            <div class="relative flex-1">
                                <Search class="pointer-events-none absolute left-4.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search order ID, resi, or username..."
                                    class="h-11 w-full rounded-2xl border-none bg-transparent pl-11 pr-4 text-[13px] text-slate-700 outline-none transition focus:ring-0"
                                />
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-1.5 p-0.5">
                                <!-- Brand Select -->
                                <div class="relative min-w-[130px]">
                                    <select 
                                        :value="currentBrand"
                                        class="h-9 w-full appearance-none rounded-xl border border-slate-200/50 bg-white pl-3 pr-8 text-[11px] font-bold text-slate-600 outline-none transition hover:border-slate-300 focus:ring-4 focus:ring-blue-50"
                                        @change="setBrandFilter($event.target.value)"
                                    >
                                        <option v-for="option in complaintBrandFilterOptions" :key="option" :value="option">
                                            {{ option === 'All' ? 'All Platforms' : option }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-2.5 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-400" />
                                </div>

                                <!-- Priority Select -->
                                <div class="relative min-w-[130px]">
                                    <select 
                                        :value="currentPriority"
                                        class="h-9 w-full appearance-none rounded-xl border border-slate-200/50 bg-white pl-3 pr-8 text-[11px] font-bold text-slate-600 outline-none transition hover:border-slate-300 focus:ring-4 focus:ring-blue-50"
                                        @change="setPriorityFilter($event.target.value)"
                                    >
                                        <option v-for="priority in priorityCards" :key="priority.key" :value="priority.key">
                                            {{ priority.label }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-2.5 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-400" />
                                </div>

                                <!-- Status Select -->
                                <div class="relative min-w-[130px]">
                                    <select 
                                        :value="currentStatus"
                                        class="h-9 w-full appearance-none rounded-xl border border-slate-200/50 bg-white pl-3 pr-8 text-[11px] font-bold text-slate-600 outline-none transition hover:border-slate-300 focus:ring-4 focus:ring-blue-50"
                                        @change="setStatus($event.target.value)"
                                    >
                                        <option v-for="status in statusCards" :key="status.key" :value="status.key">
                                            {{ status.label }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-2.5 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[320px,1fr] gap-8">
                        <!-- DESIGNER SIDEBAR: CS Grouping Desk -->
                        <aside class="space-y-4">
                            <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-sm ring-1 ring-slate-100/50">
                                <header class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">CS Groupings</p>
                                        <h2 class="mt-0.5 text-lg font-black text-slate-900">Complaint Desk</h2>
                                    </div>
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-[var(--app-primary)]">
                                        <Users class="h-4 w-4" />
                                    </div>
                                </header>

                                <div class="mt-6 space-y-2.5">
                                    <button 
                                        @click="setCsFilter('')"
                                        class="w-full h-9 px-4 flex items-center justify-between rounded-xl transition-all font-bold text-[13px]"
                                        :class="!currentCs ? 'bg-[var(--app-primary)] text-white shadow-md shadow-blue-500/10' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'"
                                    >
                                        <span>All Active Agents</span>
                                        <span class="text-[10px]">{{ csSummary.reduce((acc, curr) => acc + curr.total, 0) }}</span>
                                    </button>

                                    <p class="pt-3 text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">Select Agent</p>
                                    
                                    <div class="space-y-1.5 max-h-[360px] overflow-y-auto pr-1.5 custom-scrollbar border-b border-dashed border-slate-100 pb-3">
                                        <button 
                                            v-for="cs in csSummary" 
                                            :key="cs.cs_name"
                                            @click="setCsFilter(cs.cs_name)"
                                            class="w-full group p-3 flex items-center justify-between rounded-xl border transition-all text-left"
                                            :class="currentCs === cs.cs_name 
                                                ? 'border-[var(--app-primary)] bg-blue-50/50 ring-1 ring-[var(--app-primary)]/10' 
                                                : 'border-slate-50 bg-white hover:border-slate-200'"
                                        >
                                            <div>
                                                <p class="text-[13px] font-black text-slate-900 leading-tight">{{ cs.cs_name }}</p>
                                                <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ cs.total }} complaints</p>
                                            </div>
                                            <div class="h-6 w-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 group-hover:bg-white group-hover:text-[var(--app-primary)] transition-colors">
                                                <span class="text-[10px] font-black">{{ cs.total }}</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-6 rounded-xl bg-slate-50/50 p-4">
                                    <p class="text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">Quick Note</p>
                                    <p class="mt-2 text-[12px] font-medium leading-relaxed text-slate-500">
                                        Fokus utama ada di tombol tambah complaint.
                                    </p>
                                </div>
                            </div>
                        </aside>

                        <!-- TABLE AREA -->
                        <div class="min-w-0 space-y-8">
                            <section class="app-table-shell p-0">
                                <div
                                    class="flex flex-col gap-4 border-b border-[var(--app-border)] px-5 py-4 lg:flex-row lg:items-center lg:justify-between"
                                >
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-[var(--app-primary)]">Operational Database</p>
                                        <h2 class="mt-1 text-xl font-black text-slate-900">Current Tickets</h2>
                                        <p class="mt-0.5 text-[13px] font-medium text-slate-500">
                                            Monitoring penuh dengan metrik prioritas real-time.
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-black text-slate-500">
                                            <div class="h-1 w-1 rounded-full bg-slate-400"></div>
                                            <span>SHOWING {{ complaintPage.from || 0 }}-{{ complaintPage.to || 0 }} OF {{ complaintPage.total || 0 }}</span>
                                        </div>
                                        <div
                                            class="rounded-full border px-3 py-1.5 text-[11px] font-black uppercase tracking-wider"
                                            :class="activeFilterCount ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-100 bg-white text-slate-400'"
                                        >
                                            {{ activeFilterCount ? `${activeFilterCount} Active Filters` : 'No Filter Active' }}
                                        </div>
                                    </div>
                                </div>

                            <div class="space-y-4 px-4 py-4 lg:hidden">
                                <article
                                    v-for="item in complaintRows"
                                    :key="`card-${item.id}`"
                                    class="group relative overflow-hidden rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md"
                                >
                                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-slate-50 group-hover:bg-[var(--app-primary-soft)] transition-colors opacity-50"></div>
                                    <div class="relative z-10 flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black tracking-widest text-slate-400">#{{ item.order_id }}</span>
                                                <span class="text-[11px] font-bold text-slate-400">{{ formatDate(item.tanggal_complaint) }}</span>
                                            </div>
                                            <p class="mt-3 text-lg font-black text-[var(--app-ink)]">{{ item.username || 'Unknown Customer' }}</p>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <button
                                                type="button"
                                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 transition-all hover:bg-blue-500 hover:text-white hover:shadow-lg active:scale-95"
                                                @click="openDetail(item)"
                                            >
                                                <Eye class="h-5 w-5" />
                                            </button>
                                            <button
                                                type="button"
                                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 transition-all hover:bg-amber-500 hover:text-white hover:shadow-lg active:scale-95"
                                                @click="openEditModal(item)"
                                            >
                                                <Pencil class="h-5 w-5" />
                                            </button>
                                            <button
                                                type="button"
                                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white hover:shadow-lg active:scale-95"
                                                @click="confirmDelete(item)"
                                            >
                                                <Trash2 class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl bg-[#f9fbff] p-4 ring-1 ring-slate-100/50">
                                            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Source / Platform</p>
                                            <p class="mt-2 text-[13px] font-bold text-slate-700">{{ item.source || '-' }} / {{ item.platform || '-' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-[#f9fbff] p-4 ring-1 ring-slate-100/50">
                                            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Handling Agent</p>
                                            <p class="mt-2 text-[13px] font-bold text-slate-700">{{ item.cs_name || 'UNASSIGNED' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex items-center justify-between gap-2 pt-5 border-t border-slate-50">
                                        <div class="flex gap-2">
                                            <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider shadow-sm" :class="statusClass(item.status)">{{
                                                item.status || 'Pending'
                                            }}</span>
                                            <span
                                                class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider shadow-sm"
                                                :class="priorityClass(item.priority)"
                                            >{{ item.priority || '-' }}</span>
                                        </div>
                                        <span class="text-[11px] font-bold text-slate-300 italic">"{{ item.brand || '-' }}"</span>
                                    </div>
                                </article>
                            </div>

                            <div class="hidden overflow-x-auto lg:block">
                                <table class="w-full min-w-[1080px] table-fixed divide-y divide-[var(--line)]">
                                    <thead class="bg-slate-50/80">
                                        <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            <th class="w-[12%] py-3 pl-5 pr-4">Source</th>
                                            <th class="w-[14%] px-4 py-3">
                                                <button type="button" class="group inline-flex items-center gap-2 transition hover:text-[var(--app-primary)]" @click="sortBy('tanggal_complaint')">
                                                    Tanggal
                                                    <ArrowUpDown class="h-3 w-3 transition-transform group-hover:scale-125" />
                                                </button>
                                            </th>
                                            <th class="w-[15%] px-4 py-3">Order ID</th>
                                            <th class="w-[20%] px-4 py-3">Customer</th>
                                            <th class="w-[12%] px-4 py-3">Agent</th>
                                            <th class="w-[10%] px-4 py-3 text-center">Status</th>
                                            <th class="w-[8%] px-4 py-3 text-center">Prty</th>
                                            <th class="w-[12%] py-3 pl-4 pr-5 text-right">Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-[var(--line)] bg-white">
                                        <tr
                                            v-for="item in complaintRows"
                                            :key="item.id"
                                            class="group align-top transition-colors hover:bg-slate-50/70"
                                        >
                                            <td class="py-3 pl-5 pr-4">
                                                <div class="space-y-0.5">
                                                    <p class="truncate text-[13px] font-bold text-[var(--app-ink)]">{{ item.source || '-' }}</p>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-[10px] font-bold text-slate-400 capitalize">{{ item.brand || '-' }}</span>
                                                        <span class="h-1 w-1 rounded-full bg-slate-200"></span>
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ item.platform || '-' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-[13px] font-medium text-slate-600">{{ formatDate(item.tanggal_complaint) }}</td>
                                            <td class="px-4 py-3">
                                                <div class="space-y-0.5">
                                                    <p class="truncate text-[13px] font-bold text-[var(--app-ink)]">{{ item.order_id || '-' }}</p>
                                                    <p class="truncate text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ item.resi || '-' }}</p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="space-y-0.5">
                                                    <p class="truncate text-[13px] font-bold text-[var(--app-ink)]">{{ item.username || '-' }}</p>
                                                    <p class="line-clamp-1 text-[11px] font-medium text-slate-400">
                                                        {{ item.product_name || item.summary_case || '-' }}
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-[13px] font-bold text-slate-600">{{ item.cs_name || 'UNASSIGNED' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                                    :class="statusClass(item.status)"
                                                >{{ item.status || 'Pending' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                                    :class="priorityClass(item.priority)"
                                                >{{ item.priority || '-' }}</span>
                                            </td>
                                            <td class="py-3 pl-4 pr-5">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button
                                                        type="button"
                                                        class="group inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 shadow-sm transition-all hover:border-blue-100 hover:bg-blue-50/50 hover:text-blue-600"
                                                        title="Detail"
                                                        @click="openDetail(item)"
                                                    >
                                                        <Eye class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="group inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 shadow-sm transition-all hover:border-amber-100 hover:bg-amber-50/50 hover:text-amber-600"
                                                        title="Edit"
                                                        @click="openEditModal(item)"
                                                    >
                                                        <Pencil class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="group inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-100 bg-white text-slate-400 shadow-sm transition-all hover:border-red-100 hover:bg-red-50/50 hover:text-red-600"
                                                        title="Hapus"
                                                        @click="confirmDelete(item)"
                                                    >
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="!complaintRows.length" class="border-t border-slate-50 px-6 py-24 text-center">
                                <div class="mx-auto max-w-sm space-y-5">
                                    <div
                                        class="mx-auto flex h-20 w-20 items-center justify-center rounded-[2.5rem] bg-slate-50 text-slate-300 shadow-inner"
                                    >
                                        <ClipboardList class="h-10 w-10" />
                                    </div>
                                    <div class="space-y-2">
                                        <h3 class="text-2xl font-black tracking-tight text-[var(--app-ink)]">
                                            {{ hasActiveFilters ? 'No Results Found' : 'Clean Slate' }}
                                        </h3>
                                        <p class="text-[13px] font-bold uppercase tracking-wide leading-relaxed text-slate-400">
                                            {{
                                                hasActiveFilters
                                                    ? 'We couldn\'t find any complaints matching your current filters. Try resetting them or adjusting your search.'
                                                    : 'Your workspace is currently empty. Start by adding your first complaint to see it in action.'
                                            }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-center gap-3 pt-4">
                                        <button
                                            type="button"
                                            class="group inline-flex items-center justify-center gap-3 rounded-2xl bg-[var(--app-primary)] px-8 py-4 text-sm font-black text-white shadow-[0_12px_30px_rgba(53,103,232,0.25)] transition-all hover:bg-[var(--app-primary-dark)] hover:-translate-y-1 active:scale-95"
                                            @click="openCreateModal"
                                        >
                                            <Plus class="h-5 w-5 stroke-[3px]" />
                                            <span>Tambah Data Baru</span>
                                        </button>
                                        <button
                                            v-if="hasActiveFilters"
                                            type="button"
                                            class="text-sm font-bold text-slate-500 underline underline-offset-4 transition hovet:text-[var(--app-primary)]"
                                            @click="resetFilters"
                                        >
                                            Reset all filters
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 border-t border-[var(--line)] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-400">
                                    Listing {{ complaintPage.from || 0 }}-{{ complaintPage.to || 0 }} / Total {{ complaintPage.total || 0 }} index
                                </p>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="link in paginationLinks"
                                        :key="`${link.label}-${link.url}`"
                                        type="button"
                                        class="rounded-2xl border px-4 py-2 text-sm font-semibold transition"
                                        :class="
                                            link.active
                                                ? 'border-[var(--accent)] bg-[var(--accent)] text-white'
                                                : 'hover:border-[var(--accent)]/40 border-[var(--line)] bg-white text-slate-600'
                                        "
                                        @click="router.visit(link.url, { preserveScroll: true, preserveState: true, replace: true })"
                                        v-html="link.label"
                                    ></button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

            <transition name="fade">
                <div v-if="detailItem" class="fixed inset-0 z-40 bg-slate-950/20 backdrop-blur-[1px]" @click.self="closeDetail">
                    <aside class="absolute right-0 top-0 h-full w-full max-w-xl overflow-y-auto border-l border-[var(--line)] bg-white shadow-2xl">
                        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white/80 px-5 py-4 backdrop-blur-md">
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">Ticket Detail</p>
                                <h3 class="mt-0.5 text-xl font-black text-[var(--app-ink)]">{{ detailItem.order_id || 'Complaint Detail' }}</h3>
                            </div>
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition hover:bg-rose-50 hover:text-rose-500"
                                @click="closeDetail"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="space-y-8 px-7 py-7">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="relative overflow-hidden rounded-3xl border border-slate-100 bg-slate-50/50 p-5">
                                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-white opacity-40"></div>
                                    <p class="relative z-10 text-[10px] font-bold uppercase tracking-[0.2rem] text-slate-400">Current Status</p>
                                    <div
                                        class="mt-3 inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[11px] font-black uppercase tracking-wider shadow-sm"
                                        :class="statusClass(detailItem.status)"
                                    >
                                        <span class="h-2 w-2 animate-pulse rounded-full" :class="statusDotClass(detailItem.status)"></span>
                                        {{ detailItem.status || 'Pending' }}
                                    </div>
                                </div>
                                <div class="relative overflow-hidden rounded-3xl border border-slate-100 bg-slate-50/50 p-5">
                                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-white opacity-40"></div>
                                    <p class="relative z-10 text-[10px] font-bold uppercase tracking-[0.2rem] text-slate-400">Priority Level</p>
                                    <div
                                        class="mt-3 inline-flex rounded-full px-3.5 py-1.5 text-[11px] font-black uppercase tracking-wider shadow-sm"
                                        :class="priorityClass(detailItem.priority)"
                                    >
                                        {{ detailItem.priority || '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-3xl border border-slate-100 p-5 shadow-sm">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.2rem] text-slate-400">Customer Identity</p>
                                    <p class="mt-3 text-lg font-black text-[var(--app-ink)]">{{ detailItem.username || '-' }}</p>
                                    <div class="mt-1 flex items-center gap-2 text-[13px] font-medium text-slate-500">
                                        <span>{{ detailItem.brand || '-' }}</span>
                                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                        <span>{{ detailItem.platform || '-' }}</span>
                                    </div>
                                </div>
                                <div class="rounded-3xl border border-slate-100 p-5 shadow-sm">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.2rem] text-slate-400">Handled By</p>
                                    <p class="mt-3 text-lg font-black text-[var(--app-ink)]">{{ detailItem.cs_name || 'UNASSIGNED' }}</p>
                                    <p class="mt-1 text-[13px] font-medium text-slate-400 italic">"{{ detailItem.last_step || '-' }}"</p>
                                </div>
                            </div>

                            <div class="space-y-6 rounded-[2rem] border border-slate-100 bg-[#f8fbff]/50 p-7 ring-1 ring-slate-100/50">
                                <div class="grid gap-y-6 gap-x-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Tanggal Complaint</p>
                                        <p class="mt-2 text-[15px] font-bold text-[var(--app-ink)]">{{ formatDate(detailItem.tanggal_complaint) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Terakhir Update</p>
                                        <p class="mt-2 text-[15px] font-bold text-[var(--app-ink)]">{{ formatDate(detailItem.tanggal_update) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Order ID</p>
                                        <p class="mt-2 text-[15px] font-bold text-[var(--app-ink)]">{{ detailItem.order_id || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Nomor Resi</p>
                                        <p class="mt-2 text-[14px] font-medium text-slate-600 font-mono">{{ detailItem.resi || '-' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Product SKU & Name</p>
                                        <p class="mt-2 text-[15px] font-bold text-[var(--app-ink)]">{{ detailItem.sku || '-' }} - {{ detailItem.product_name || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Value of Product</p>
                                        <p class="mt-2 text-[15px] font-bold text-emerald-600">{{ formatCurrency(detailItem.value_of_product) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Issue Category</p>
                                        <p class="mt-2 text-[15px] font-bold text-[var(--app-ink)]">{{ detailItem.sub_case || '-' }}</p>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200/50 pt-5">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Summary Case</p>
                                    <p class="mt-3 text-[15px] leading-relaxed font-medium text-slate-600">{{ detailItem.summary_case || '-' }}</p>
                                </div>

                                <div class="border-t border-slate-200/50 pt-5">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Handling Update</p>
                                    <p class="mt-3 text-[15px] leading-relaxed font-medium text-slate-600">{{ detailItem.update_long_text || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </transition>

            <transition name="fade">
                <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-950/35 backdrop-blur-[2px]">
                    <div class="absolute inset-y-0 right-0 h-full w-full overflow-y-auto bg-[#f8fbff] shadow-2xl 2xl:max-w-[1240px]">
                        <div class="sticky top-0 z-20 flex items-center justify-between border-b px-5 py-6 sm:px-8 transition-all duration-500" :class="modalMode === 'edit' ? 'bg-slate-900 border-slate-800' : 'bg-[#EEF2FF] border-[#E0E7FF]'">
                            <div class="flex items-center gap-5">
                                <button type="button" class="flex h-11 w-11 items-center justify-center rounded-2xl transition-all active:scale-90" :class="modalMode === 'edit' ? 'bg-white/10 text-slate-300 hover:bg-white/20 hover:text-white' : 'bg-slate-900/5 text-slate-500 hover:bg-slate-900/10 hover:text-slate-900'" @click="discardForm">
                                    <X class="h-5 w-5" />
                                </button>
                                <div>
                                    <h2 class="text-2xl font-black tracking-tight transition-colors" :class="modalMode === 'edit' ? 'text-white' : 'text-slate-900'">{{ modalMode === 'edit' ? 'Edit Ticket' : 'Create Ticket' }}</h2>
                                    <p class="mt-0.5 text-[13px] font-medium transition-colors" :class="modalMode === 'edit' ? 'text-slate-400' : 'text-slate-500'">{{ modalMode === 'edit' ? 'Perbarui data complaint untuk akurasi laporan operasional.' : 'Input detail complaint baru ke dalam sistem monitoring.' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div
                                    class="hidden rounded-full border px-4 py-2.5 text-[12px] font-black uppercase tracking-wider lg:block shadow-sm"
                                    :class="modalMode === 'edit' ? 'bg-white/5 border-white/10 text-slate-400' : 'bg-white border-slate-200 text-slate-500'"
                                >
                                    <span :class="modalMode === 'edit' ? 'text-blue-400' : 'text-[var(--app-primary)]'">{{ completionSummary.completed }}</span>/{{ completionSummary.total }} Fields Complete
                                </div>
                                <button
                                    type="button"
                                    class="h-11 rounded-xl px-5 text-sm font-bold transition-all active:scale-95"
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
                                        <span>{{ form.processing ? 'Syncing...' : (modalMode === 'edit' ? 'Save Changes' : 'Submit Ticket') }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <div class="px-5 py-8 sm:px-8">
                            <div class="mx-auto grid max-w-[1160px] gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                                <div class="space-y-7">
                                        <div
                                            v-if="Object.keys(form.errors).length"
                                            class="rounded-[18px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700"
                                        >
                                            <p class="font-semibold">Masih ada field yang perlu dilengkapi:</p>
                                            <ul class="mt-2 list-disc pl-5">
                                                <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                                            </ul>
                                        </div>
                                        
                                        <div
                                            v-if="form.errors.general"
                                            class="rounded-[18px] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-700"
                                        >
                                            <p class="font-bold uppercase tracking-wider">System Error</p>
                                            <p class="mt-1">{{ form.errors.general }}</p>
                                        </div>

                                        <!-- Automation Preview Bar -->
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Automated Classification</div>
                                                <div class="mt-1 text-[13px] font-semibold text-slate-700">{{ computedCycle }} | {{ automationResults.status }} ({{ automationResults.priority }})</div>
                                            </div>
                                            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                                <div class="text-[11px] font-bold uppercase tracking-wider text-blue-400">Inventory & Customer Intel</div>
                                                <div class="mt-1 text-[13px] font-semibold text-blue-700">
                                                    {{ selectedSku.available_qty ?? 0 }} Units | {{ selectedSku.status_qty || 'Normal' }}
                                                    <span v-if="oosPreview" class="ml-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[9px] font-black text-rose-600">OOS ALERT</span>
                                                </div>
                                            </div>
                                            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                                                <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-400">Customer History & Responsible</div>
                                                <div class="mt-1 text-[13px] font-semibold text-indigo-700">
                                                    {{ customerHistoryCount || 'New Customer' }} | By: {{ automationResults.cause_by }}
                                                </div>
                                            </div>
                                        </div>

                                    <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]">
                                        <div
                                            class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between"
                                        >
                                            <div class="flex-1">
                                                <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                                    <span>Section 01</span>
                                                </div>
                                                <h3 class="mt-2 text-lg font-black text-slate-900">Essential Information</h3>
                                                <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                                    Capture the core details of the complaint and related order to ensure accurate tracking.
                                                </p>
                                            </div>
                                            <div class="rounded-2xl bg-indigo-50/50 p-4 ring-1 ring-indigo-100 sm:max-w-xs">
                                                <p class="text-[13px] font-medium leading-relaxed text-indigo-700">
                                                    SKU-specific data will auto-populate to maintain data integrity.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="mb-2 block text-[13px] font-bold uppercase tracking-wide text-slate-700">SOURCE*</label>
                                                <div class="relative">
                                                    <select v-model="form.source" :class="controlClass('source', 'select')">
                                                        <option value="" disabled>Pilih Source</option>
                                                        <option v-for="option in sourceOptions" :key="option" :value="option">
                                                            {{ option }}
                                                        </option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                                </div>
                                                <p v-if="fieldError('source')" class="mt-2 text-xs font-medium text-rose-600">
                                                    {{ fieldError('source') }}
                                                </p>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                                <div class="space-y-1.5">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Tanggal Complaint*</label>
                                                    <input v-model="form.tanggal_complaint" type="date" :class="controlClass('tanggal_complaint')" />
                                                    <p v-if="fieldError('tanggal_complaint')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('tanggal_complaint') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Tanggal Order*</label>
                                                    <input v-model="form.tanggal_order" type="date" :class="controlClass('tanggal_order')" />
                                                    <p v-if="fieldError('tanggal_order')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('tanggal_order') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Jam Customer Complaint*</label>
                                                    <input
                                                        v-model="form.jam_customer_complaint"
                                                        type="time"
                                                        step="1"
                                                        :class="controlClass('jam_customer_complaint')"
                                                    />
                                                    <p v-if="fieldError('jam_customer_complaint')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('jam_customer_complaint') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide uppercase text-slate-700">BRAND*</label>
                                                    <div class="relative">
                                                        <select v-model="form.brand" :class="controlClass('brand', 'select')">
                                                            <option value="" disabled>Pilih Brand</option>
                                                            <option v-for="option in complaintBrandOptions" :key="option" :value="option">{{ option }}</option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('brand')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('brand') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Platform*</label>
                                                    <div class="relative">
                                                        <select v-model="form.platform" :class="controlClass('platform', 'select')">
                                                            <option value="" disabled>Pilih Platform</option>
                                                            <option v-for="option in complaintPlatformOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('platform')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('platform') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Nomor Pesanan*</label>
                                                    <input v-model="form.order_id" type="text" :class="controlClass('order_id')" />
                                                    <p v-if="fieldError('order_id')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('order_id') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">No Resi*</label>
                                                    <input v-model="form.resi" type="text" :class="controlClass('resi')" />
                                                    <p v-if="fieldError('resi')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('resi') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">SKU Code*</label>
                                                    <div class="relative">
                                                        <select v-model="form.sku" :class="controlClass('sku', 'select')">
                                                            <option value="" disabled>Pilih SKU</option>
                                                            <option v-for="option in skuOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('sku')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('sku') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Product Name*</label>
                                                    <input
                                                        v-model="form.product_name"
                                                        type="text"
                                                        readonly
                                                        :class="readonlyInputClass"
                                                    />
                                                </div>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Sub Case*</label>
                                                    <div class="relative">
                                                        <select v-model="form.sub_case" :class="controlClass('sub_case', 'select')">
                                                            <option value="" disabled>Pilih Sub Case</option>
                                                            <option v-for="option in subCaseOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('sub_case')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('sub_case') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="mb-3 flex items-center justify-between gap-3">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">By*</label>
                                                    <span
                                                        v-if="causeByLocked"
                                                        class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--accent)]"
                                                    >Auto from Sub Case</span>
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <button
                                                        v-for="option in causeByOptions"
                                                        :key="option"
                                                        type="button"
                                                        class="rounded-lg border px-4 py-3 text-[15px] font-bold transition disabled:cursor-not-allowed disabled:opacity-60"
                                                        :class="selectButtonClass(form.cause_by, option)"
                                                        :disabled="causeByLocked && form.cause_by !== option"
                                                        @click="form.cause_by = option"
                                                    >
                                                        {{ option }}
                                                    </button>
                                                </div>
                                                <p v-if="fieldError('cause_by')" class="mt-2 text-xs font-medium text-rose-600">
                                                    {{ fieldError('cause_by') }}
                                                </p>
                                            </div>
                                        </div>
                                    </section>

                                    <section class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]">
                                        <div class="mb-6 border-b border-slate-50 pb-5">
                                            <div class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]">
                                                <span>Section 02</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Handling Ticket</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                                Record investigative steps, evidence proof, and final outcomes for this case.
                                            </p>
                                        </div>

                                        <div class="space-y-6">
                                            <div class="grid gap-5 sm:grid-cols-3">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Proof</label>
                                                    <input v-model="form.proof" type="text" :class="controlClass('proof')" />
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Summary Case*</label>
                                                    <input v-model="form.summary_case" type="text" :class="controlClass('summary_case')" />
                                                    <p v-if="fieldError('summary_case')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('summary_case') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Part of Bad</label>
                                                    <div class="relative">
                                                        <select v-model="form.part_of_bad" :class="controlClass('part_of_bad', 'select')">
                                                            <option value="" disabled>Pilih Part of bad</option>
                                                            <option v-for="option in partOfBadOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('part_of_bad')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('part_of_bad') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Update*</label>
                                                <textarea
                                                    v-model="form.update_long_text"
                                                    rows="4"
                                                    :class="controlClass('update_long_text', 'textarea')"
                                                ></textarea>
                                                <p v-if="fieldError('update_long_text')" class="text-xs font-medium text-rose-600">
                                                    {{ fieldError('update_long_text') }}
                                                </p>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">CS Name*</label>
                                                    <div class="relative">
                                                        <select v-model="form.cs_name" :class="controlClass('cs_name', 'select')">
                                                            <option value="" disabled>Pilih CS Name</option>
                                                            <option v-for="option in csNameOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('cs_name')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('cs_name') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Last Step*</label>
                                                    <div class="relative">
                                                        <select v-model="form.last_step" :class="controlClass('last_step', 'select')">
                                                            <option value="" disabled>Pilih Last Step</option>
                                                            <option v-for="option in lastStepOptions" :key="option.value" :value="option.value">
                                                                {{ option.label }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('last_step')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('last_step') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div v-if="showReasonWhitelist" class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Reason Whitelist*</label>
                                                    <div class="relative">
                                                        <select v-model="form.reason_whitelist" :class="controlClass('reason_whitelist', 'select')">
                                                            <option value="" disabled>Pilih reason whitelist</option>
                                                            <option v-for="option in reasonWhitelistOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('reason_whitelist')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('reason_whitelist') }}
                                                    </p>
                                                </div>

                                                <div v-if="showReasonLateRespons" class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Reason Late Respons*</label>
                                                    <div class="relative">
                                                        <select
                                                            v-model="form.reason_late_respons"
                                                            :class="controlClass('reason_late_respons', 'select')"
                                                        >
                                                            <option value="" disabled>Pilih reason late respons</option>
                                                            <option v-for="option in reasonLateResponseOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
                                                        </select>
                                                        <ChevronDown
                                                            class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                                        />
                                                    </div>
                                                    <p v-if="fieldError('reason_late_respons')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('reason_late_respons') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide uppercase text-slate-700">UPDATE AI</label>
                                                    <input v-model="form.update_ai" type="text" :class="controlClass('update_ai')" />
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Tanggal Update*</label>
                                                    <input v-model="form.tanggal_update" type="date" :class="controlClass('tanggal_update')" />
                                                    <p v-if="fieldError('tanggal_update')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('tanggal_update') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="mb-3 block text-[13px] font-bold uppercase tracking-wide uppercase text-slate-700">STEP CS SELESAI?*</label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button
                                                        v-for="option in stepStatusOptions"
                                                        :key="option"
                                                        type="button"
                                                        class="rounded-lg border px-4 py-3 text-[15px] font-bold transition"
                                                        :class="selectButtonClass(form.step_cs_selesai, option)"
                                                        @click="form.step_cs_selesai = option"
                                                    >
                                                        {{ option }}
                                                    </button>
                                                </div>
                                                <p v-if="fieldError('step_cs_selesai')" class="mt-2 text-xs font-medium text-rose-600">
                                                    {{ fieldError('step_cs_selesai') }}
                                                </p>
                                            </div>

                                            <div v-if="showStepCompletedDate" class="space-y-2">
                                                <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Tanggal Step CS Selesai*</label>
                                                <input
                                                    v-model="form.tanggal_step_cs_selesai"
                                                    type="date"
                                                    :class="controlClass('tanggal_step_cs_selesai')"
                                                />
                                                <p v-if="fieldError('tanggal_step_cs_selesai')" class="text-xs font-medium text-rose-600">
                                                    {{ fieldError('tanggal_step_cs_selesai') }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-3 block text-[13px] font-bold uppercase tracking-wide text-slate-700">Complaint power*</label>
                                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                    <button
                                                        v-for="option in complaintPowerOptions"
                                                        :key="option.value"
                                                        type="button"
                                                        class="rounded-lg border px-4 py-3 text-[15px] font-bold uppercase transition"
                                                        :class="selectButtonClass(form.complaint_power, option.value)"
                                                        @click="form.complaint_power = option.value"
                                                    >
                                                        {{ option.label }}
                                                    </button>
                                                </div>
                                                <p v-if="fieldError('complaint_power')" class="mt-2 text-xs font-medium text-rose-600">
                                                    {{ fieldError('complaint_power') }}
                                                </p>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Video Unboxing</label>
                                                    <label
                                                        class="hover:border-[var(--accent)]/50 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:bg-white"
                                                    >
                                                        <Upload class="h-5 w-5 text-slate-400" />
                                                        <span class="text-[12px] font-medium text-slate-500 line-clamp-1">{{ videoLabel }}</span>
                                                        <input type="file" class="hidden" accept="video/*" @change="setVideoFile" />
                                                    </label>
                                                </div>

                                                <div class="space-y-1.5">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Proof Attachment (IMG/PDF/VID)</label>
                                                    <label
                                                        class="hover:border-[var(--accent)]/50 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:bg-white"
                                                    >
                                                        <Upload class="h-5 w-5 text-slate-400" />
                                                        <span class="text-[12px] font-medium text-slate-500 line-clamp-1">{{ proofAttachmentLabel }}</span>
                                                        <input type="file" class="hidden" @change="setProofAttachmentFile" />
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="space-y-1.5">
                                                <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Username*</label>
                                                <input v-model="form.username" type="text" :class="controlClass('username')" />
                                                <p v-if="fieldError('username')" class="text-xs font-medium text-rose-600">
                                                    {{ fieldError('username') }}
                                                </p>
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
                                                    <div
                                                        class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm"
                                                        :class="statusClass(statusPreview)"
                                                    >
                                                        <span class="h-1.5 w-1.5 animate-pulse rounded-full" :class="statusDotClass(statusPreview)"></span>
                                                        {{ statusPreview }}
                                                    </div>
                                                </div>

                                                <div v-if="priorityPreview" class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-slate-400">Priority Level</p>
                                                    <div
                                                        class="mt-1.5 inline-flex rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm"
                                                        :class="priorityClass(priorityPreview)"
                                                    >
                                                        {{ priorityPreview }}
                                                    </div>
                                                </div>

                                                <div class="grid gap-2.5 sm:grid-cols-2">
                                                    <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                        <p class="text-[10px] font-bold text-slate-400">SLA Days</p>
                                                        <p class="mt-0.5 text-base font-black text-slate-900">{{ slaPreview }}d</p>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                        <p class="text-[10px] font-bold text-slate-400">Sync</p>
                                                        <p class="mt-0.5 text-[10px] font-black uppercase text-[var(--app-primary)]">{{ autoSyncSlaPreview }}</p>
                                                    </div>
                                                </div>

                                                <div v-if="reportCategoryPreview" class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-slate-400">Categorization</p>
                                                    <p class="mt-1 text-[13px] font-bold text-slate-700 leading-tight">{{ reportCategoryPreview }}</p>
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
                                                        Start with <span class="font-bold text-white">Order & Customer</span> data first for better flow.
                                                    </p>
                                                </li>
                                                <li class="flex items-start gap-2.5">
                                                    <div class="flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                        <CheckCircle2 class="h-2.5 w-2.5" />
                                                    </div>
                                                    <p class="text-[12px] font-medium leading-tight text-white/80">
                                                        The <span class="font-bold text-white">"By"</span> field will auto-lock based on sub-case mapping.
                                                    </p>
                                                </li>
                                                <li class="flex items-start gap-2.5">
                                                    <div class="flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-white/10 text-white">
                                                        <CheckCircle2 class="h-2.5 w-2.5" />
                                                    </div>
                                                    <p class="text-[12px] font-medium leading-tight text-white/80">
                                                        Whitelist and completion dates only appear if specific steps are met.
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

            <!-- Master Data Style Delete Confirmation -->
            <Dialog v-model:open="isDeleteModalOpen">
                <DialogContent class="max-w-md rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                    <div class="overflow-hidden rounded-[28px] bg-white">
                        <div class="bg-rose-50 px-7 py-8">
                            <DialogHeader>
                                <DialogTitle class="text-3xl font-black text-rose-950">Hapus Tiket</DialogTitle>
                                <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">Tindakan ini tidak bisa dibatalkan secara instan.</DialogDescription>
                            </DialogHeader>
                        </div>
                        <div class="space-y-4 px-5 py-5">
                            <div v-if="complaintToDelete" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Order Information</p>
                                        <p class="text-base font-black text-slate-900">#{{ complaintToDelete.order_id }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Customer</p>
                                        <p class="text-[13px] font-bold text-slate-700">{{ complaintToDelete.username || '-' }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex gap-1.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider" :class="statusClass(complaintToDelete.status)">
                                        {{ complaintToDelete.status || 'Pending' }}
                                    </span>
                                    <span class="inline-flex rounded-full bg-white px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400 ring-1 ring-slate-200">
                                        {{ complaintToDelete.brand || '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                                <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isDeleteModalOpen = false">Cancel</Button>
                                <Button type="button" class="h-11 rounded-xl bg-rose-600 px-6 font-bold text-white shadow-lg shadow-rose-500/20 hover:bg-rose-700" :disabled="deleteForm.processing" @click="submitDelete">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    {{ deleteForm.processing ? 'Deleting...' : 'Delete Ticket' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
