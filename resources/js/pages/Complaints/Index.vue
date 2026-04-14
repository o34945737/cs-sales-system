<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import {
    AlertCircle,
    ArrowUpDown,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
    Eye,
    Minus,
    Plus,
    Search,
    ShieldAlert,
    Upload,
    Users,
    X,
} from 'lucide-vue-next';

const SOURCE_OPTIONS = ['AFTERSALES', 'B2B', 'PRESALES', 'SOCMED', 'BRAND/OPS'];
const SUB_CASE_OPTIONS = [
    'Bad Quality Product',
    'Bad Service',
    'Change Mind',
    'Damaged Packaging',
    'Damaged Product',
    'Expired',
    'Fake Return',
    'Late Delivery',
    'Under Delivery Product',
    'Misunderstanding of the product',
    'No Reason',
    'Promotion',
    'Wrong Product',
    'OOS',
    'Refund DP',
    'Lost Product',
];
const CAUSE_BY_OPTIONS = [
    '?',
    'CS',
    'KAE',
    'WH',
    'ANTERAJA',
    'CHAT++',
    'CUSTOM LOGISTICS',
    'GOJEK/GRAB',
    'GTL',
    'INDOPAKET',
    'J&T',
    'JNE',
    'KURIR REKOMENDASI',
    'LEX',
    'NINJA',
    'POS',
    'SAP EXPRESS',
    'SICEPAT',
    'SPX',
    'STREAMER',
    'CUSTOMER',
    'BRAND',
    'PROMO',
    'PART',
];
const LAST_STEP_OPTIONS = [
    { label: 'Claim Receive (10x shipping fee)', value: 'Claim Receive (10x shipping fee)' },
    { label: 'Claim Receive (Full)', value: 'Claim Receive (Full)' },
    { label: 'Claim Reject', value: 'Claim Reject' },
    { label: 'Complaint Canceled by buyer/No Respons', value: 'Complaint Canceled by buyer/No Respons' },
    { label: 'FU Courier (MP Non aktif)', value: 'Follow Up Courier (MP Non aktif)' },
    { label: 'Analysis MP (Late Delivery)', value: 'Analysis MP (Late Delivery)' },
    { label: 'Analysis MP (Non Late Delivery)', value: 'Analysis MP (Non Late Delivery)' },
    { label: 'Kingdee Processing (Waiting AWB for replacement product)', value: 'Kingdee Processing (Waiting AWB for replacement product)' },
    { label: 'On the way return & plan banding', value: 'On the way return & plan banding' },
    { label: 'On the way return & plan refund', value: 'On the way return & plan refund' },
    { label: 'On the way return & plan replace', value: 'On the way return & plan replace' },
    { label: 'Pending return & plan banding', value: 'Pending return & plan banding' },
    { label: 'Pending return & plan refund', value: 'Pending return & plan refund' },
    { label: 'Pending return & plan replace', value: 'Pending return & plan replace' },
    { label: 'Pending RGO & plan refund', value: 'Pending RGO & plan refund' },
    { label: 'Product has been delivered (Late Delivery)', value: 'Product has been delivered (Late Delivery)' },
    { label: 'Refund has been transferred by finance (SPF)', value: 'Refund has been transferred by finance (SPF)' },
    { label: 'Refund processing by finance (SPF)', value: 'Refund processing by finance (SPF)' },
    { label: 'Replacement product on the way', value: 'Replacement product on the way' },
    { label: 'Return Refund (Full)', value: 'Return Refund (Full)' },
    { label: 'Return Refund (Partial)', value: 'Return Refund (Partial)' },
    { label: 'Seller Win', value: 'Seller Win' },
    { label: 'The replacement product has been received by the buyer', value: 'The replacement product has been received by the buyer' },
    { label: 'Follow Up to After Sales Team', value: 'Follow Up to After Sales Team' },
    { label: 'Waiting Claim', value: 'Waiting Claim' },
    { label: 'Waiting Money Receive', value: 'Waiting Money Receive' },
    { label: 'Waiting Data From Customer', value: 'Waiting Data From Customer' },
    { label: 'Follow Up KAE to Brand', value: 'Follow Up KAE to Brand' },
    { label: 'Follow Up WH', value: 'Follow Up WH' },
    { label: 'Follow Up KAE to KAM', value: 'Follow Up KAE to KAM' },
    { label: 'Return not authorized', value: 'Return not authorized' },
    { label: 'Return follow-up (No further action)', value: 'Return follow-up (No further action)' },
];
const REASON_WHITELIST_OPTIONS = [
    'No repacking indication',
    'Packing not proper',
    "Customer's evidence is stronger than us",
    'Our evidences are not strong (platform T&C)',
    'CCTV does not show receipt number',
    'Late Respons',
];
const REASON_LATE_RESPONS_OPTIONS = ['CS', 'KAE', 'Finance', 'WH', 'PH'];
const COMPLAINT_POWER_OPTIONS = [
    { label: 'HARD COMPLAINT', value: 'Hard Complaint' },
    { label: 'NORMAL COMPLAINT', value: 'Normal Complaint' },
];

const AUTO_CAUSE_BY_MAP = {
    'Bad Quality Product': 'BRAND',
    Expired: 'BRAND',
    'Misunderstanding of the product': 'CUSTOMER',
    OOS: 'KAE',
    Promotion: 'PROMO',
    'Change Mind': 'CUSTOMER',
};

const SOLVED_STEPS = [
    'Claim Receive (10x shipping fee)',
    'Claim Receive (Full)',
    'Complaint Canceled by buyer/No Respons',
    'Product has been delivered (Late Delivery)',
    'Refund has been transferred by finance (SPF)',
    'Return Refund (Full)',
    'Return Refund (Partial)',
    'Seller Win',
    'The replacement product has been received by the buyer',
    'Return follow-up (No further action)',
];
const PRIORITY_GROUPS = {
    Cool: [
        'Claim Receive (10x shipping fee)',
        'Claim Receive (Full)',
        'Complaint Canceled by buyer/No Respons',
        'Product has been delivered (Late Delivery)',
        'Refund has been transferred by finance (SPF)',
        'Return Refund (Full)',
        'Return Refund (Partial)',
        'Seller Win',
    ],
    P1: ['Analysis MP (Non Late Delivery)', 'Follow Up to After Sales Team', 'Follow Up WH'],
    P2: ['On the way return & plan banding', 'Follow Up KAE to KAM', 'Follow Up KAE to Brand'],
    P3: ['Follow Up Courier (MP Non aktif)', 'On the way return & plan refund', 'Pending return & plan banding', 'Pending return & plan refund', 'Pending RGO & plan refund', 'Waiting Data From Customer'],
    P4: ['On the way return & plan replace', 'Pending return & plan replace'],
    P5: ['Analysis MP (Late Delivery)', 'Return not authorized'],
    P6: ['Kingdee Processing (Waiting AWB for replacement product)', 'Refund processing by finance (SPF)', 'Replacement product on the way'],
    P7: ['Waiting Claim', 'Waiting Money Receive'],
};

const uniqueOptions = (values) => [...new Set(values.filter(Boolean))];
const today = () => {
    const currentDate = new Date();
    const timezoneOffset = currentDate.getTimezoneOffset() * 60000;

    return new Date(currentDate.getTime() - timezoneOffset).toISOString().split('T')[0];
};
const nowTime = () => new Date().toTimeString().slice(0, 8);

const inputClass =
    'w-full rounded-[6px] border border-slate-300 bg-white px-4 py-3 text-[17px] text-slate-900 outline-none transition focus:border-[var(--accent)]';
const readonlyInputClass =
    'w-full rounded-[6px] border border-slate-200 bg-slate-50 px-4 py-3 text-[17px] text-slate-400 outline-none';
const selectClass =
    'w-full appearance-none rounded-[6px] border border-slate-300 bg-white px-4 py-3 pr-12 text-[17px] text-slate-900 outline-none transition focus:border-[var(--accent)]';
const textAreaClass =
    'w-full rounded-[6px] border border-slate-300 bg-white px-4 py-3 text-[17px] text-slate-900 outline-none transition focus:border-[var(--accent)]';

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
const overview = computed(() => props.overview || {});

const search = ref(filterState.value.search || '');
const isModalOpen = ref(false);
const detailItem = ref(null);

const visitIndex = (overrides = {}, options = {}) => {
    router.get(
        route('complaints.index'),
        {
            search: search.value || undefined,
            status: filterState.value.status && filterState.value.status !== 'All' ? filterState.value.status : undefined,
            cs_name: filterState.value.cs_name || undefined,
            sort: filterState.value.sort || 'tanggal_complaint',
            order: filterState.value.order || 'desc',
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true, ...options },
    );
};

watch(search, debounce((value) => visitIndex({ search: value || undefined, page: 1 }), 350));

const setStatus = (status) => visitIndex({ status: status === 'All' ? undefined : status, page: 1 }, { replace: false });
const setCsFilter = (name) => visitIndex({ cs_name: name || undefined, page: 1 }, { replace: false });
const sortBy = (field) => visitIndex({ sort: field, order: filterState.value.sort === field && filterState.value.order === 'asc' ? 'desc' : 'asc' }, { replace: false });

const formatDate = (value) => {
    if (!value) return '-';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parsed);
};

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0);
};

const resolveStatus = (lastStep) => {
    if (lastStep === 'Claim Reject') {
        return 'Whitelist';
    }

    if (SOLVED_STEPS.includes(lastStep)) {
        return 'Solved';
    }

    return 'Pending';
};

const resolvePriority = (lastStep) => {
    if (lastStep === 'Claim Reject') {
        return 'Mines';
    }

    const matchedPriority = Object.entries(PRIORITY_GROUPS).find(([, steps]) => steps.includes(lastStep));
    return matchedPriority ? matchedPriority[0] : 'P3';
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

const currentStatus = computed(() => filterState.value.status || 'All');
const currentCs = computed(() => filterState.value.cs_name || '');
const hasActiveFilters = computed(() => Boolean(search.value || currentStatus.value !== 'All' || currentCs.value));
const activeFilterCount = computed(() => [Boolean(search.value), currentStatus.value !== 'All', Boolean(currentCs.value)].filter(Boolean).length);

const resetFilters = () => {
    search.value = '';
    visitIndex(
        {
            search: undefined,
            status: undefined,
            cs_name: undefined,
            page: 1,
        },
        { replace: false },
    );
};

const brandOptions = computed(() => uniqueOptions(['ANTA', ...complaintRows.value.map((item) => item.brand)]));
const platformOptions = computed(() => uniqueOptions(['SHOPEE', 'TOKOPEDIA', 'TIKTOK', 'LAZADA', 'BLIBLI', ...complaintRows.value.map((item) => item.platform)]));
const skuOptions = computed(() => uniqueOptions(['PBP246', ...complaintRows.value.map((item) => item.sku)]));
const csNameOptions = computed(() => uniqueOptions(['TYAS', ...csSummary.value.map((item) => item.cs_name)]));
const skuCatalog = computed(() => {
    const catalog = {};

    complaintRows.value.forEach((item) => {
        if (!item.sku) {
            return;
        }

        catalog[item.sku] = {
            product_name: item.product_name || '',
            available_qty: item.available_qty ?? item.qty ?? '',
            status_qty: item.status_qty || '',
            value_of_product: item.value_of_product ?? 0,
        };
    });

    return catalog;
});

const createInitialFormState = () => ({
    source: 'AFTERSALES',
    tanggal_complaint: today(),
    tanggal_order: today(),
    jam_customer_complaint: nowTime(),
    brand: 'ANTA',
    platform: 'SHOPEE',
    order_id: '',
    resi: '',
    product_name: '',
    sku: 'PBP246',
    value_of_product: 0,
    sub_case: 'Damaged Packaging',
    cause_by: '?',
    proof: '',
    summary_case: '',
    update_long_text: '',
    part_of_bad: '',
    cs_name: 'TYAS',
    last_step: 'Follow Up Courier (MP Non aktif)',
    tanggal_step_cs_selesai: '',
    update_ai: '',
    step_cs_selesai: 'NO',
    tanggal_update: today(),
    auto_sync_sla: '',
    complaint_power: 'Hard Complaint',
    report_category: '',
    video_unboxing: null,
    username: '',
    available_qty: '',
    status_qty: '',
    category_customer: '',
    oos: '',
    reason_whitelist: '',
    reason_late_respons: '',
});

const form = useForm(createInitialFormState());

watch(
    () => form.sub_case,
    (value) => {
        const nextCause = AUTO_CAUSE_BY_MAP[value];

        if (nextCause) {
            form.cause_by = nextCause;
        }
    },
    { immediate: true },
);

watch(
    () => form.sku,
    (value) => {
        const matchedSku = skuCatalog.value[value];

        if (!matchedSku) {
            return;
        }

        if (matchedSku.product_name) {
            form.product_name = matchedSku.product_name;
        }

        if (matchedSku.available_qty !== '' && matchedSku.available_qty !== null && matchedSku.available_qty !== undefined) {
            form.available_qty = String(matchedSku.available_qty);
        }

        if (matchedSku.status_qty) {
            form.status_qty = matchedSku.status_qty;
        }

        if (Number(matchedSku.value_of_product) > 0 && !Number(form.value_of_product)) {
            form.value_of_product = Number(matchedSku.value_of_product);
        }
    },
    { immediate: true },
);

watch(
    () => form.last_step,
    (value) => {
        if (value !== 'Claim Reject') {
            form.reason_whitelist = '';
            form.reason_late_respons = '';
        }
    },
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

    const hasOosHistory = complaintRows.value.some(
        (item) => item.order_id === form.order_id && (item.oos || item.riwayat_oos === 'Ada Riwayat OOS'),
    );

    return hasOosHistory ? 'Ada Riwayat OOS' : 'Tidak Ada Riwayat OOS';
});

const causeByLocked = computed(() => Boolean(AUTO_CAUSE_BY_MAP[form.sub_case]));
const videoLabel = computed(() => form.video_unboxing?.name || 'Upload video unboxing');

const setVideoFile = (event) => {
    const [file] = event.target.files || [];
    form.video_unboxing = file || null;
};

const adjustValue = (delta) => {
    const currentValue = Number(form.value_of_product || 0);
    form.value_of_product = Math.max(0, currentValue + delta);
};

const discardForm = () => {
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = false;
};

const openCreateModal = () => {
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openDetail = (item) => {
    detailItem.value = item;
};

const closeDetail = () => {
    detailItem.value = null;
};

const submitForm = () => {
    form.transform((data) => {
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
            tanggal_step_cs_selesai: showStepCompletedDate.value ? data.tanggal_step_cs_selesai || data.tanggal_update : null,
            proof: data.proof || null,
            available_qty: data.available_qty || null,
            status_qty: data.status_qty || null,
        };
    }).post(route('complaints.store'), {
        forceFormData: true,
        onSuccess: () => discardForm(),
    });
};

const selectButtonClass = (currentValue, expectedValue) =>
    currentValue === expectedValue
        ? 'border-[var(--accent)] bg-[var(--accent)] text-white shadow-sm'
        : 'border-slate-300 bg-white text-slate-600 hover:border-[var(--accent)]/40';

const statusClass = (status) =>
    status === 'Solved'
        ? 'bg-emerald-50 text-emerald-700'
        : status === 'Whitelist'
          ? 'bg-rose-50 text-rose-700'
          : 'bg-amber-50 text-amber-700';

const priorityClass = (priority) =>
    ['Mines', 'P1', 'P2'].includes(priority)
        ? 'bg-rose-50 text-rose-700'
        : priority === 'Cool'
          ? 'bg-emerald-50 text-emerald-700'
          : 'bg-slate-100 text-slate-700';

const statusDotClass = (status) =>
    status === 'Solved'
        ? 'bg-emerald-500'
        : status === 'Whitelist'
          ? 'bg-rose-500'
          : 'bg-amber-500';

const fieldError = (field) => form.errors[field];

const controlClass = (field, variant = 'input') => {
    const baseClass = variant === 'select' ? selectClass : variant === 'textarea' ? textAreaClass : inputClass;

    return fieldError(field) ? `${baseClass} border-rose-300 bg-rose-50/60 focus:border-rose-400` : baseClass;
};

const isFilled = (value) => value !== null && value !== undefined && value !== '';

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
        complete: [form.summary_case, form.update_long_text, form.cs_name, form.last_step, form.step_cs_selesai, form.tanggal_update, form.complaint_power].every(isFilled),
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
    <Head title="Complaints" />

    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }, { title: 'Complaints', href: '/complaints' }]">
        <div class="complaints-shell bg-[var(--canvas)]">
            <div class="mx-auto flex max-w-7xl flex-col gap-6">
                <section class="grid gap-6 xl:grid-cols-[244px_minmax(0,1fr)]">
                    <aside class="space-y-5 xl:sticky xl:top-24 xl:h-fit">
                        <div class="rounded-[28px] border border-[var(--line)] bg-[var(--panel)] p-5 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">CS Groupings</p>
                                    <h2 class="mt-2 text-xl font-semibold text-[var(--ink)]">Complaint Desk</h2>
                                </div>
                                <div class="rounded-2xl bg-[var(--accent-soft)] p-3 text-[var(--accent)]">
                                    <Users class="h-5 w-5" />
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3">
                                <button
                                    type="button"
                                    class="flex items-center justify-between rounded-[22px] border px-4 py-4 text-left transition"
                                    :class="currentCs === '' ? 'border-[var(--accent)] bg-[var(--accent)] text-white shadow-lg shadow-[var(--accent)]/20' : 'border-[var(--line)] bg-white text-slate-700 hover:border-[var(--accent)]/40'"
                                    @click="setCsFilter('')"
                                >
                                    <span class="text-sm font-semibold">All Active Agents</span>
                                    <span class="text-sm">{{ overview.agents || 0 }}</span>
                                </button>

                                <div class="space-y-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Select Agent</p>

                                    <button
                                        v-for="agent in csSummary"
                                        :key="agent.cs_name || 'unassigned'"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-[20px] border px-4 py-4 text-left transition"
                                        :class="currentCs === agent.cs_name ? 'border-[var(--accent)] bg-[var(--accent)] text-white shadow-lg shadow-[var(--accent)]/20' : 'border-[var(--line)] bg-white text-slate-700 hover:border-[var(--accent)]/40'"
                                        @click="setCsFilter(agent.cs_name)"
                                    >
                                        <div>
                                            <p class="text-sm font-semibold">{{ agent.cs_name || 'UNASSIGNED' }}</p>
                                            <p class="mt-1 text-xs" :class="currentCs === agent.cs_name ? 'text-white/80' : 'text-slate-400'">
                                                {{ agent.total }} complaint
                                            </p>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="currentCs === agent.cs_name ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">
                                            {{ agent.total }}
                                        </span>
                                    </button>

                                    <div v-if="!csSummary.length" class="rounded-[20px] border border-dashed border-[var(--line)] bg-[var(--panel-soft)] px-4 py-5 text-center">
                                        <p class="text-sm font-semibold text-slate-700">Belum ada agent aktif</p>
                                        <p class="mt-2 text-xs leading-5 text-slate-500">Filter CS akan muncul otomatis setelah complaint memiliki assignment agent.</p>
                                    </div>
                                </div>

                                <div class="rounded-[20px] bg-slate-50 px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Quick note</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan filter agent saat volume case mulai tinggi. Untuk kondisi kosong, fokus utama ada di tombol tambah complaint.</p>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="space-y-5">
                        <section class="rounded-[32px] border border-[var(--line)] bg-[var(--panel)] p-6 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                            <div class="space-y-6">
                                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)] xl:items-start">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Complaint Workspace</p>
                                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-[var(--ink)]">Complaints</h1>
                                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                                            Entry dan monitoring complaint dirapikan agar lebih cepat dipakai tim CS, terutama saat kondisi kosong, volume tinggi, atau butuh follow-up cepat.
                                        </p>
                                    </div>

                                    <div class="rounded-[28px] border border-[var(--line)] bg-[var(--panel-soft)] p-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Quick Actions</p>
                                        <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                            <label class="relative flex-1">
                                                <Search class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                <input
                                                    v-model="search"
                                                    type="text"
                                                    placeholder="Cari order ID, resi, username, atau brand"
                                                    class="w-full rounded-[22px] border border-[var(--line)] bg-white py-3 pl-12 pr-4 text-sm text-slate-700 outline-none transition focus:border-[var(--accent)]"
                                                />
                                            </label>

                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center gap-2 rounded-[22px] bg-[var(--accent)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[var(--accent)]/20 transition hover:bg-[var(--accent-dark)]"
                                                @click="openCreateModal"
                                            >
                                                <Plus class="h-4 w-4" />
                                                Add Complaint
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <article v-for="card in overviewCards" :key="card.label" class="rounded-[24px] border border-[var(--line)] bg-white px-5 py-5">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">{{ card.label }}</p>
                                        <p class="mt-4 text-[2.15rem] font-bold tracking-tight text-[var(--ink)]">{{ card.value }}</p>
                                    </article>
                                </div>

                                <div class="rounded-[28px] border border-[var(--line)] bg-white p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Filter Status</p>
                                            <p class="mt-1 text-sm text-slate-500">Pilih status untuk memfokuskan daftar complaint.</p>
                                        </div>
                                        <span class="w-fit rounded-full bg-[var(--accent-soft)] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--accent)]">
                                            {{ currentStatus }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                        <button
                                            v-for="status in statusCards"
                                            :key="status.key"
                                            type="button"
                                            class="flex items-center justify-between rounded-[20px] border px-4 py-3 text-left transition"
                                            :class="currentStatus === status.key ? 'border-[var(--accent)] bg-[var(--accent)] text-white shadow-lg shadow-[var(--accent)]/20' : 'border-[var(--line)] bg-white text-slate-700 hover:border-[var(--accent)]/40'"
                                            @click="setStatus(status.key)"
                                        >
                                            <div class="flex items-center gap-3">
                                                <component :is="status.icon" class="h-4 w-4" />
                                                <span class="text-sm font-semibold">{{ status.label }}</span>
                                            </div>
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="currentStatus === status.key ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">
                                                {{ status.value }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-[32px] border border-[var(--line)] bg-[var(--panel)] shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                            <div class="flex flex-col gap-4 border-b border-[var(--line)] px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Complaint Index</p>
                                    <h2 class="mt-2 text-xl font-semibold text-[var(--ink)]">Daftar Complaint</h2>
                                    <p class="mt-1 text-sm text-slate-500">Daftar utama untuk monitoring cepat, detail per row, dan pengecekan status serta priority.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-400">
                                    <span class="rounded-full bg-slate-50 px-3 py-1.5">Showing {{ complaintPage.from || 0 }}-{{ complaintPage.to || 0 }} dari {{ complaintPage.total || 0 }} data</span>
                                    <span class="rounded-full border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ activeFilterCount ? `${activeFilterCount} active filter` : 'No active filter' }}
                                    </span>
                                    <span v-if="currentCs" class="rounded-full bg-[var(--accent-soft)] px-3 py-1.5 text-[var(--accent)]">CS: {{ currentCs }}</span>
                                    <span v-if="currentStatus !== 'All'" class="rounded-full bg-[var(--accent-soft)] px-3 py-1.5 text-[var(--accent)]">Status: {{ currentStatus }}</span>
                                    <button
                                        v-if="hasActiveFilters"
                                        type="button"
                                        class="rounded-full border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 transition hover:border-[var(--accent)]/40 hover:text-[var(--accent)]"
                                        @click="resetFilters"
                                    >
                                        Reset Filter
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-hidden">
                                <table class="w-full table-fixed divide-y divide-[var(--line)]">
                                    <thead class="bg-[var(--panel-soft)]">
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">
                                            <th class="w-[12%] px-5 py-4">Source</th>
                                            <th class="w-[14%] px-5 py-4">
                                                <button type="button" class="inline-flex items-center gap-2" @click="sortBy('tanggal_complaint')">
                                                    Tanggal Complaint
                                                    <ArrowUpDown class="h-4 w-4" />
                                                </button>
                                            </th>
                                            <th class="w-[16%] px-5 py-4">Order ID</th>
                                            <th class="w-[22%] px-5 py-4">Customer</th>
                                            <th class="w-[12%] px-5 py-4">CS Name</th>
                                            <th class="w-[10%] px-5 py-4">Status</th>
                                            <th class="w-[8%] px-5 py-4">Priority</th>
                                            <th class="w-[12%] px-5 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--line)] bg-white">
                                        <tr v-for="item in complaintRows" :key="item.id" class="align-top">
                                            <td class="px-5 py-4">
                                                <div class="space-y-1">
                                                    <p class="truncate text-sm font-semibold text-[var(--ink)]">{{ item.source || '-' }}</p>
                                                    <p class="text-xs text-slate-400">{{ item.brand || '-' }} · {{ item.platform || '-' }}</p>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ formatDate(item.tanggal_complaint) }}</td>
                                            <td class="px-5 py-4">
                                                <div class="space-y-1">
                                                    <p class="truncate text-sm font-semibold text-[var(--ink)]">{{ item.order_id || '-' }}</p>
                                                    <p class="truncate text-xs text-slate-400">{{ item.resi || '-' }}</p>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="space-y-1">
                                                    <p class="truncate text-sm font-semibold text-[var(--ink)]">{{ item.username || '-' }}</p>
                                                    <p class="line-clamp-2 text-xs text-slate-400">{{ item.product_name || item.summary_case || '-' }}</p>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ item.cs_name || 'UNASSIGNED' }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(item.status)">{{ item.status || 'Pending' }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="priorityClass(item.priority)">{{ item.priority || '-' }}</span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-2 rounded-2xl border border-[var(--line)] px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-[var(--accent)]/40 hover:text-[var(--accent)]"
                                                    @click="openDetail(item)"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                    Detail
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="!complaintRows.length" class="border-t border-[var(--line)] px-6 py-16 text-center">
                                <div class="mx-auto max-w-md space-y-3">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--accent-soft)] text-[var(--accent)]">
                                        <ClipboardList class="h-6 w-6" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-[var(--ink)]">{{ hasActiveFilters ? 'Tidak ada complaint untuk filter ini' : 'Belum ada complaint' }}</h3>
                                    <p class="text-sm text-slate-400">
                                        {{ hasActiveFilters ? 'Coba reset filter agar seluruh data tampil lagi, atau tambahkan complaint baru jika memang belum ada data yang sesuai.' : 'Mulai dari tambah complaint pertama agar dashboard dan filter agent mulai terisi.' }}
                                    </p>
                                    <div class="flex flex-wrap justify-center gap-3 pt-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center gap-2 rounded-[18px] bg-[var(--accent)] px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-[var(--accent)]/20 transition hover:bg-[var(--accent-dark)]"
                                            @click="openCreateModal"
                                        >
                                            <Plus class="h-4 w-4" />
                                            Add Complaint
                                        </button>
                                        <button
                                            v-if="hasActiveFilters"
                                            type="button"
                                            class="rounded-[18px] border border-[var(--line)] bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-[var(--accent)]/40 hover:text-[var(--accent)]"
                                            @click="resetFilters"
                                        >
                                            Reset Filter
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
                                        :class="link.active ? 'border-[var(--accent)] bg-[var(--accent)] text-white' : 'border-[var(--line)] bg-white text-slate-600 hover:border-[var(--accent)]/40'"
                                        @click="router.visit(link.url, { preserveScroll: true, preserveState: true, replace: true })"
                                        v-html="link.label"
                                    ></button>
                                </div>
                            </div>
                        </section>
                    </div>
                </section>
            </div>

            <transition name="fade">
                <div v-if="detailItem" class="fixed inset-0 z-40 bg-slate-950/20 backdrop-blur-[1px]" @click.self="closeDetail">
                    <aside class="absolute right-0 top-0 h-full w-full max-w-xl overflow-y-auto border-l border-[var(--line)] bg-white shadow-2xl">
                        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-[var(--line)] bg-white px-6 py-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Complaint Detail</p>
                                <h3 class="mt-2 text-xl font-semibold text-[var(--ink)]">{{ detailItem.order_id || 'Complaint Detail' }}</h3>
                            </div>
                            <button
                                type="button"
                                class="rounded-2xl border border-[var(--line)] p-2 text-slate-500 transition hover:border-[var(--accent)]/40 hover:text-[var(--accent)]"
                                @click="closeDetail"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="space-y-6 px-6 py-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-[24px] border border-[var(--line)] bg-[var(--panel-soft)] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Status</p>
                                    <div class="mt-3 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(detailItem.status)">
                                        <span class="h-2 w-2 rounded-full" :class="statusDotClass(detailItem.status)"></span>
                                        {{ detailItem.status || 'Pending' }}
                                    </div>
                                </div>
                                <div class="rounded-[24px] border border-[var(--line)] bg-[var(--panel-soft)] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Priority</p>
                                    <div class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="priorityClass(detailItem.priority)">
                                        {{ detailItem.priority || '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-[24px] border border-[var(--line)] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Customer</p>
                                    <p class="mt-3 text-sm font-semibold text-[var(--ink)]">{{ detailItem.username || '-' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ detailItem.brand || '-' }} / {{ detailItem.platform || '-' }}</p>
                                </div>
                                <div class="rounded-[24px] border border-[var(--line)] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">CS Name</p>
                                    <p class="mt-3 text-sm font-semibold text-[var(--ink)]">{{ detailItem.cs_name || 'UNASSIGNED' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ detailItem.last_step || '-' }}</p>
                                </div>
                            </div>

                            <div class="space-y-4 rounded-[28px] border border-[var(--line)] p-5">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tanggal Complaint</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ formatDate(detailItem.tanggal_complaint) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tanggal Update</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ formatDate(detailItem.tanggal_update) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Order ID</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ detailItem.order_id || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Resi</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ detailItem.resi || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Product</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ detailItem.product_name || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Value</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ formatCurrency(detailItem.value_of_product) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sub Case</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ detailItem.sub_case || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">By</p>
                                        <p class="mt-2 text-sm text-[var(--ink)]">{{ detailItem.cause_by || '-' }}</p>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Summary Case</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ detailItem.summary_case || '-' }}</p>
                                </div>

                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Update</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ detailItem.update_long_text || '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </transition>

            <transition name="fade">
                <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-950/35 backdrop-blur-[2px]">
                    <div class="absolute inset-y-0 right-0 h-full w-full overflow-y-auto bg-[#f8fbff] shadow-2xl 2xl:max-w-[1240px]">
                        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 sm:px-8">
                            <div class="flex items-start gap-4">
                                <button type="button" class="text-slate-500 transition hover:text-slate-900" @click="discardForm">
                                    <X class="h-7 w-7" />
                                </button>
                                <div>
                                    <h2 class="text-[20px] font-medium text-slate-900">COMPLAINT Form</h2>
                                    <p class="mt-1 text-sm text-slate-500">Urutan penting dari sistem lama tetap dipertahankan, lalu dirapikan per section supaya lebih cepat diisi dan lebih mudah dicek.</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="hidden rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-600 lg:block">
                                    {{ completionSummary.completed }}/{{ completionSummary.total }} field inti terisi
                                </div>
                                <button
                                    type="button"
                                    class="rounded-[6px] border border-[var(--accent)]/35 px-5 py-2 text-sm font-medium text-[var(--accent)] transition hover:bg-[var(--accent-soft)]"
                                    @click="discardForm"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="rounded-[6px] bg-[var(--accent)] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[var(--accent-dark)] disabled:cursor-not-allowed disabled:opacity-70"
                                    :disabled="form.processing"
                                    @click="submitForm"
                                >
                                    {{ form.processing ? 'Saving...' : 'Save' }}
                                </button>
                            </div>
                        </div>

                        <div class="px-5 py-8 sm:px-8">
                            <div class="mx-auto grid max-w-[1160px] gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                                <div class="space-y-7">
                                <div v-if="Object.keys(form.errors).length" class="rounded-[18px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                                    <p class="font-semibold">Masih ada field yang perlu dilengkapi:</p>
                                    <ul class="mt-2 list-disc pl-5">
                                        <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                                    </ul>
                                </div>

                                <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                                    <div class="mb-6 flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--accent)]">Section 1</p>
                                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Informasi Utama</h3>
                                            <p class="mt-2 text-sm text-slate-500">Mulai dari identitas complaint dan order terlebih dahulu. Ini bagian yang paling sering dipakai tim saat entry cepat.</p>
                                        </div>
                                        <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                            Field otomatis dari SKU dan sub case akan dikunci agar input tetap konsisten.
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <div>
                                            <label class="mb-4 block text-[15px] font-medium text-slate-700">SOURCE*</label>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="option in SOURCE_OPTIONS"
                                                    :key="option"
                                                    type="button"
                                                    class="min-w-[120px] rounded-[8px] border px-5 py-4 text-[17px] font-medium uppercase transition"
                                                    :class="selectButtonClass(form.source, option)"
                                                    @click="form.source = option"
                                                >
                                                    {{ option }}
                                                </button>
                                            </div>
                                            <p v-if="fieldError('source')" class="mt-2 text-xs font-medium text-rose-600">{{ fieldError('source') }}</p>
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Tanggal Complaint*</label>
                                                <input v-model="form.tanggal_complaint" type="date" :class="controlClass('tanggal_complaint')" />
                                                <p v-if="fieldError('tanggal_complaint')" class="text-xs font-medium text-rose-600">{{ fieldError('tanggal_complaint') }}</p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Tanggal Order*</label>
                                                <input v-model="form.tanggal_order" type="date" :class="controlClass('tanggal_order')" />
                                                <p v-if="fieldError('tanggal_order')" class="text-xs font-medium text-rose-600">{{ fieldError('tanggal_order') }}</p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Jam Customer Complaint*</label>
                                                <input v-model="form.jam_customer_complaint" type="time" step="1" :class="controlClass('jam_customer_complaint')" />
                                                <p v-if="fieldError('jam_customer_complaint')" class="text-xs font-medium text-rose-600">{{ fieldError('jam_customer_complaint') }}</p>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium uppercase text-slate-700">BRAND*</label>
                                                <div class="relative">
                                                    <select v-model="form.brand" :class="controlClass('brand', 'select')">
                                                        <option v-for="option in brandOptions" :key="option" :value="option">{{ option }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                </div>
                                                <p v-if="fieldError('brand')" class="text-xs font-medium text-rose-600">{{ fieldError('brand') }}</p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Platform*</label>
                                                <div class="relative">
                                                    <select v-model="form.platform" :class="controlClass('platform', 'select')">
                                                        <option v-for="option in platformOptions" :key="option" :value="option">{{ option }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                </div>
                                                <p v-if="fieldError('platform')" class="text-xs font-medium text-rose-600">{{ fieldError('platform') }}</p>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Nomor Pesanan*</label>
                                                <input v-model="form.order_id" type="text" :class="controlClass('order_id')" />
                                                <p v-if="fieldError('order_id')" class="text-xs font-medium text-rose-600">{{ fieldError('order_id') }}</p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Username*</label>
                                                <input v-model="form.username" type="text" :class="controlClass('username')" />
                                                <p v-if="fieldError('username')" class="text-xs font-medium text-rose-600">{{ fieldError('username') }}</p>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">No Resi*</label>
                                                <input v-model="form.resi" type="text" :class="controlClass('resi')" />
                                                <p v-if="fieldError('resi')" class="text-xs font-medium text-rose-600">{{ fieldError('resi') }}</p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Product*</label>
                                                <input v-model="form.product_name" type="text" :class="controlClass('product_name')" />
                                            </div>
                                        </div>

                                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_220px]">
                                            <div class="space-y-5">
                                                <div class="grid gap-5 sm:grid-cols-2">
                                                    <div class="space-y-2">
                                                        <label class="block text-[15px] font-medium text-slate-700">SKU Code*</label>
                                                        <div class="relative">
                                                            <select v-model="form.sku" :class="controlClass('sku', 'select')">
                                                                <option v-for="option in skuOptions" :key="option" :value="option">{{ option }}</option>
                                                            </select>
                                                            <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                        </div>
                                                        <p v-if="fieldError('sku')" class="text-xs font-medium text-rose-600">{{ fieldError('sku') }}</p>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <label class="block text-[15px] font-medium text-slate-700">Value*</label>
                                                        <div class="relative">
                                                            <input v-model="form.value_of_product" type="number" min="0" :class="`${controlClass('value_of_product')} pr-24`" />
                                                            <div class="absolute right-3 top-1/2 flex -translate-y-1/2 items-center gap-2">
                                                                <button type="button" class="rounded-full p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" @click="adjustValue(-1)">
                                                                    <Minus class="h-5 w-5" />
                                                                </button>
                                                                <button type="button" class="rounded-full p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" @click="adjustValue(1)">
                                                                    <Plus class="h-5 w-5" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[15px] font-medium text-slate-700">Sub Case*</label>
                                                    <div class="relative">
                                                        <select v-model="form.sub_case" :class="controlClass('sub_case', 'select')">
                                                            <option v-for="option in SUB_CASE_OPTIONS" :key="option" :value="option">{{ option }}</option>
                                                        </select>
                                                        <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                    </div>
                                                    <p v-if="fieldError('sub_case')" class="text-xs font-medium text-rose-600">{{ fieldError('sub_case') }}</p>
                                                </div>
                                            </div>

                                            <div class="rounded-[20px] border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">SKU Snapshot</p>
                                                <div class="mt-4 space-y-3">
                                                    <div>
                                                        <p class="text-xs text-slate-400">Available Qty</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ form.available_qty || '-' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-slate-400">Status Qty</p>
                                                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ form.status_qty || '-' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <label class="block text-[15px] font-medium text-slate-700">By*</label>
                                                <span v-if="causeByLocked" class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--accent)]">Auto from Sub Case</span>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="option in CAUSE_BY_OPTIONS"
                                                    :key="option"
                                                    type="button"
                                                    class="rounded-[8px] border px-5 py-4 text-[17px] font-medium transition disabled:cursor-not-allowed disabled:opacity-60"
                                                    :class="selectButtonClass(form.cause_by, option)"
                                                    :disabled="causeByLocked && form.cause_by !== option"
                                                    @click="form.cause_by = option"
                                                >
                                                    {{ option }}
                                                </button>
                                            </div>
                                            <p v-if="fieldError('cause_by')" class="mt-2 text-xs font-medium text-rose-600">{{ fieldError('cause_by') }}</p>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                                    <div class="mb-6 border-b border-slate-100 pb-5">
                                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--accent)]">Section 2</p>
                                        <h3 class="mt-2 text-xl font-semibold text-slate-900">Handling Complaint</h3>
                                        <p class="mt-2 text-sm text-slate-500">Bagian ini fokus ke tindak lanjut CS, kualitas evidence, dan kondisi khusus yang memengaruhi status complaint.</p>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Proof</label>
                                                <input v-model="form.proof" type="text" :class="controlClass('proof')" />
                                                <p class="text-xs text-slate-400">Bisa berupa nomor evidence, link, atau catatan proof singkat.</p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Part of Bad</label>
                                                <input v-model="form.part_of_bad" type="text" :class="controlClass('part_of_bad')" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[15px] font-medium text-slate-700">Summary Case*</label>
                                            <input v-model="form.summary_case" type="text" :class="controlClass('summary_case')" />
                                            <p v-if="fieldError('summary_case')" class="text-xs font-medium text-rose-600">{{ fieldError('summary_case') }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[15px] font-medium text-slate-700">Update*</label>
                                            <textarea v-model="form.update_long_text" rows="4" :class="controlClass('update_long_text', 'textarea')"></textarea>
                                            <p v-if="fieldError('update_long_text')" class="text-xs font-medium text-rose-600">{{ fieldError('update_long_text') }}</p>
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">CS Name*</label>
                                                <div class="relative">
                                                    <select v-model="form.cs_name" :class="controlClass('cs_name', 'select')">
                                                        <option v-for="option in csNameOptions" :key="option" :value="option">{{ option }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                </div>
                                                <p v-if="fieldError('cs_name')" class="text-xs font-medium text-rose-600">{{ fieldError('cs_name') }}</p>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Last Step*</label>
                                                <div class="relative">
                                                    <select v-model="form.last_step" :class="controlClass('last_step', 'select')">
                                                        <option v-for="option in LAST_STEP_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                </div>
                                                <p v-if="fieldError('last_step')" class="text-xs font-medium text-rose-600">{{ fieldError('last_step') }}</p>
                                            </div>
                                        </div>

                                        <div v-if="showReasonWhitelist" class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Reason Whitelist*</label>
                                                <div class="relative">
                                                    <select v-model="form.reason_whitelist" :class="controlClass('reason_whitelist', 'select')">
                                                        <option value="" disabled>Pilih reason whitelist</option>
                                                        <option v-for="option in REASON_WHITELIST_OPTIONS" :key="option" :value="option">{{ option }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                </div>
                                                <p v-if="fieldError('reason_whitelist')" class="text-xs font-medium text-rose-600">{{ fieldError('reason_whitelist') }}</p>
                                            </div>

                                            <div v-if="showReasonLateRespons" class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Reason Late Respons*</label>
                                                <div class="relative">
                                                    <select v-model="form.reason_late_respons" :class="controlClass('reason_late_respons', 'select')">
                                                        <option value="" disabled>Pilih reason late respons</option>
                                                        <option v-for="option in REASON_LATE_RESPONS_OPTIONS" :key="option" :value="option">{{ option }}</option>
                                                    </select>
                                                    <ChevronDown class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                                </div>
                                                <p v-if="fieldError('reason_late_respons')" class="text-xs font-medium text-rose-600">{{ fieldError('reason_late_respons') }}</p>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium uppercase text-slate-700">UPDATE AI</label>
                                                <input v-model="form.update_ai" type="text" :class="controlClass('update_ai')" />
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[15px] font-medium text-slate-700">Tanggal Update*</label>
                                                <input v-model="form.tanggal_update" type="date" :class="controlClass('tanggal_update')" />
                                                <p v-if="fieldError('tanggal_update')" class="text-xs font-medium text-rose-600">{{ fieldError('tanggal_update') }}</p>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="mb-3 block text-[15px] font-medium uppercase text-slate-700">STEP CS SELESAI?*</label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <button
                                                    v-for="option in ['YES', 'NO']"
                                                    :key="option"
                                                    type="button"
                                                    class="rounded-[8px] border px-5 py-4 text-[17px] font-medium transition"
                                                    :class="selectButtonClass(form.step_cs_selesai, option)"
                                                    @click="form.step_cs_selesai = option"
                                                >
                                                    {{ option }}
                                                </button>
                                            </div>
                                            <p v-if="fieldError('step_cs_selesai')" class="mt-2 text-xs font-medium text-rose-600">{{ fieldError('step_cs_selesai') }}</p>
                                        </div>

                                        <div v-if="showStepCompletedDate" class="space-y-2">
                                            <label class="block text-[15px] font-medium text-slate-700">Tanggal Step CS Selesai*</label>
                                            <input v-model="form.tanggal_step_cs_selesai" type="date" :class="controlClass('tanggal_step_cs_selesai')" />
                                            <p v-if="fieldError('tanggal_step_cs_selesai')" class="text-xs font-medium text-rose-600">{{ fieldError('tanggal_step_cs_selesai') }}</p>
                                        </div>

                                        <div>
                                            <label class="mb-3 block text-[15px] font-medium text-slate-700">Complaint power*</label>
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                <button
                                                    v-for="option in COMPLAINT_POWER_OPTIONS"
                                                    :key="option.value"
                                                    type="button"
                                                    class="rounded-[8px] border px-5 py-4 text-[17px] font-medium uppercase transition"
                                                    :class="selectButtonClass(form.complaint_power, option.value)"
                                                    @click="form.complaint_power = option.value"
                                                >
                                                    {{ option.label }}
                                                </button>
                                            </div>
                                            <p v-if="fieldError('complaint_power')" class="mt-2 text-xs font-medium text-rose-600">{{ fieldError('complaint_power') }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-[15px] font-medium text-slate-700">Video Unboxing</label>
                                            <label class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-[10px] border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center transition hover:border-[var(--accent)]/50 hover:bg-white">
                                                <Upload class="h-8 w-8 text-slate-400" />
                                                <span class="text-sm text-slate-500">{{ videoLabel }}</span>
                                                <input type="file" class="hidden" accept="video/*" @change="setVideoFile" />
                                            </label>
                                        </div>
                                    </div>
                                </section>
                                </div>

                                <aside class="space-y-5 xl:sticky xl:top-24 xl:h-fit">
                                    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--accent)]">Progress</p>
                                        <div class="mt-4">
                                            <div class="flex items-center justify-between text-sm text-slate-500">
                                                <span>Readiness</span>
                                                <span>{{ completionSummary.percent }}%</span>
                                            </div>
                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-[var(--accent)]" :style="{ width: `${completionSummary.percent}%` }"></div>
                                            </div>
                                            <p class="mt-3 text-sm text-slate-500">{{ completionSummary.completed }} dari {{ completionSummary.total }} field inti sudah terisi.</p>
                                        </div>

                                        <div class="mt-5 space-y-3">
                                            <div v-for="item in sectionChecks" :key="item.label" class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                                <span class="font-medium text-slate-700">{{ item.label }}</span>
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="item.complete ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                                    {{ item.complete ? 'Ready' : 'Pending' }}
                                                </span>
                                            </div>
                                        </div>
                                    </section>

                                    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--accent)]">Auto Summary</p>

                                        <div class="mt-5 space-y-4">
                                            <div class="rounded-[20px] bg-slate-50 p-4">
                                                <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Status</p>
                                                <div class="mt-3 inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(statusPreview)">
                                                    <span class="h-2 w-2 rounded-full" :class="statusDotClass(statusPreview)"></span>
                                                    {{ statusPreview }}
                                                </div>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                                                <div class="rounded-[20px] bg-slate-50 p-4">
                                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Priority</p>
                                                    <div class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="priorityClass(priorityPreview)">
                                                        {{ priorityPreview }}
                                                    </div>
                                                </div>
                                                <div class="rounded-[20px] bg-slate-50 p-4">
                                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Cycle</p>
                                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ cyclePreview }}</p>
                                                </div>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                                                <div class="rounded-[20px] bg-slate-50 p-4">
                                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">SLA</p>
                                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ slaPreview }} hari</p>
                                                    <p class="mt-1 text-xs text-slate-400">{{ autoSyncSlaPreview }}</p>
                                                </div>
                                                <div class="rounded-[20px] bg-slate-50 p-4">
                                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Report Category</p>
                                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ reportCategoryPreview || '-' }}</p>
                                                </div>
                                            </div>

                                            <div class="grid gap-4">
                                                <div class="rounded-[20px] bg-slate-50 p-4">
                                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Category Customer</p>
                                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ categoryCustomerPreview || '-' }}</p>
                                                </div>
                                                <div class="rounded-[20px] bg-slate-50 p-4">
                                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">OOS</p>
                                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ oosPreview || '-' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <section class="rounded-[28px] border border-slate-200 bg-[var(--accent-soft)] p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--accent)]">Input Guide</p>
                                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                                            <p>Isi identitas complaint terlebih dulu, lalu lanjut ke handling agar minim bolak-balik scroll.</p>
                                            <p>`By` otomatis terkunci untuk sub case tertentu seperti `OOS`, `Promotion`, dan `Change Mind`.</p>
                                            <p>Field whitelist dan tanggal selesai hanya muncul saat memang dibutuhkan, supaya form tetap ringkas.</p>
                                        </div>
                                    </section>
                                </aside>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </AppLayout>
</template>

<style>
.complaints-shell {
    --canvas: transparent;
    --panel: #ffffff;
    --panel-soft: #f8fbff;
    --line: #e2e8f0;
    --ink: #111827;
    --muted: #667085;
    --accent: #3567e8;
    --accent-dark: #2452cb;
    --accent-soft: #eef4ff;
}

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
