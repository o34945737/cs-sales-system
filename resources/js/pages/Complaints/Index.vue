<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';
import {
    AlertCircle,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
    Eye,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    ShieldAlert,
    Trash2,
    Upload,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const DEFAULT_SOURCE_OPTIONS = ['After Sales', 'Pre Sales', 'Brand', 'KAE', 'Socmed'];
const DEFAULT_COMPLAINT_POWER_OPTIONS = ['Hard Complaint', 'Normal Complaint'];
const DEFAULT_STEP_STATUS_OPTIONS = ['YES', 'NO'];
const DEFAULT_PRIORITY_OPTIONS = ['Cool', 'Mines', 'P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];

const normalizeOptionValue = (value) => {
    if (typeof value !== 'string') {
        return value;
    }

    return value.trim();
};
const uniqueOptions = (values) => [...new Set(values.map((value) => normalizeOptionValue(value)).filter(Boolean))];
const mergeStringOptions = (...groups) => uniqueOptions(groups.flatMap((group) => (Array.isArray(group) ? group : [])));
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
    subCaseOptions: Array,
    causeByOptions: Array,
    lastStepOptions: Array,
    reasonWhitelistOptions: Array,
    reasonLateResponseOptions: Array,
    priority_summary: Object,
    oosOrderIds: Array,
    autoCauseByMap: Object,
});
const currentEditItem = ref(null);

const complaintPage = computed(() => ({
    ...createEmptyPaginator(),
    ...(props.complaints || {}),
}));
const complaintRows = computed(() => (Array.isArray(complaintPage.value.data) ? complaintPage.value.data : []));
const paginationLinks = computed(() => (Array.isArray(complaintPage.value.links) ? complaintPage.value.links : []));
const filterState = computed(() => (props.filters && !Array.isArray(props.filters) ? props.filters : {}));
const csSummary = computed(() => props.cs_summary || []);
const statusSummary = computed(() => props.status_summary || {});
const prioritySummary = computed(() => (props.priority_summary && !Array.isArray(props.priority_summary) ? props.priority_summary : {}));
const overview = computed(() => props.overview || {});
const sourceOptions = computed(() =>
    mergeStringOptions(
        Array.isArray(props.sourceOptions) && props.sourceOptions.length ? props.sourceOptions : DEFAULT_SOURCE_OPTIONS,
        complaintRows.value.map((item) => item.source),
        [filterState.value.source, form.source, currentEditItem.value?.source],
    ),
);
const complaintPowerOptions = computed(() =>
    mergeStringOptions(
        Array.isArray(props.complaintPowerOptions) && props.complaintPowerOptions.length
            ? props.complaintPowerOptions
            : DEFAULT_COMPLAINT_POWER_OPTIONS,
        complaintRows.value.map((item) => item.complaint_power),
        [form.complaint_power],
    ).map((value) => ({
        label: value.toUpperCase(),
        value,
    })),
);
const stepStatusOptions = computed(() => DEFAULT_STEP_STATUS_OPTIONS);
const csNameOptions = computed(() =>
    mergeStringOptions(
        Array.isArray(props.csNameOptions) ? props.csNameOptions : [],
        complaintRows.value.map((item) => item.cs_name),
        [form.cs_name, currentEditItem.value?.cs_name],
    ),
);
const masterBrandOptions = computed(() => (Array.isArray(props.brandOptions) ? props.brandOptions : []));
const masterPlatformOptions = computed(() => (Array.isArray(props.platformOptions) ? props.platformOptions : []));
const masterSkuCodeOptions = computed(() => (Array.isArray(props.skuCodeOptions) ? props.skuCodeOptions : []));
const lastStepOptions = computed(() =>
    [
        ...(Array.isArray(props.lastStepOptions) && props.lastStepOptions.length
            ? props.lastStepOptions
            : [
                  {
                      label: 'Claim Receive (10x shipping fee)',
                      value: 'Claim Receive (10x shipping fee)',
                      status_result: 'Solved',
                      priority_level: 'Cool',
                  },
                  { label: 'Claim Receive (Full)', value: 'Claim Receive (Full)', status_result: 'Solved', priority_level: 'Cool' },
                  { label: 'Claim Reject', value: 'Claim Reject', status_result: 'Whitelist', priority_level: 'Mines' },
                  {
                      label: 'Complaint Canceled by buyer/No Respons',
                      value: 'Complaint Canceled by buyer/No Respons',
                      status_result: 'Solved',
                      priority_level: 'Cool',
                  },
                  {
                      label: 'Follow Up Courier (MP Non aktif)',
                      value: 'Follow Up Courier (MP Non aktif)',
                      status_result: 'Pending',
                      priority_level: 'P3',
                  },
                  { label: 'Analysis MP (Late Delivery)', value: 'Analysis MP (Late Delivery)', status_result: 'Pending', priority_level: 'P5' },
                  {
                      label: 'Analysis MP (Non Late Delivery)',
                      value: 'Analysis MP (Non Late Delivery)',
                      status_result: 'Pending',
                      priority_level: 'P1',
                  },
                  {
                      label: 'Kingdee Processing (Waiting AWB for replacement product)',
                      value: 'Kingdee Processing (Waiting AWB for replacement product)',
                      status_result: 'Pending',
                      priority_level: 'P6',
                  },
                  {
                      label: 'On the way return & plan banding',
                      value: 'On the way return & plan banding',
                      status_result: 'Pending',
                      priority_level: 'P2',
                  },
                  {
                      label: 'On the way return & plan refund',
                      value: 'On the way return & plan refund',
                      status_result: 'Pending',
                      priority_level: 'P3',
                  },
                  {
                      label: 'On the way return & plan replace',
                      value: 'On the way return & plan replace',
                      status_result: 'Pending',
                      priority_level: 'P4',
                  },
                  { label: 'Pending return & plan banding', value: 'Pending return & plan banding', status_result: 'Pending', priority_level: 'P3' },
                  { label: 'Pending return & plan refund', value: 'Pending return & plan refund', status_result: 'Pending', priority_level: 'P3' },
                  { label: 'Pending return & plan replace', value: 'Pending return & plan replace', status_result: 'Pending', priority_level: 'P4' },
                  { label: 'Pending RGO & plan refund', value: 'Pending RGO & plan refund', status_result: 'Pending', priority_level: 'P3' },
                  {
                      label: 'Product has been delivered (Late Delivery)',
                      value: 'Product has been delivered (Late Delivery)',
                      status_result: 'Solved',
                      priority_level: 'Cool',
                  },
                  {
                      label: 'Refund has been transferred by finance (SPF)',
                      value: 'Refund has been transferred by finance (SPF)',
                      status_result: 'Solved',
                      priority_level: 'Cool',
                  },
                  {
                      label: 'Refund processing by finance (SPF)',
                      value: 'Refund processing by finance (SPF)',
                      status_result: 'Pending',
                      priority_level: 'P6',
                  },
                  {
                      label: 'Replacement product on the way',
                      value: 'Replacement product on the way',
                      status_result: 'Pending',
                      priority_level: 'P6',
                  },
                  { label: 'Return Refund (Full)', value: 'Return Refund (Full)', status_result: 'Solved', priority_level: 'Cool' },
                  { label: 'Return Refund (Partial)', value: 'Return Refund (Partial)', status_result: 'Solved', priority_level: 'Cool' },
                  { label: 'Seller Win', value: 'Seller Win', status_result: 'Solved', priority_level: 'Cool' },
                  {
                      label: 'The replacement product has been received by the buyer',
                      value: 'The replacement product has been received by the buyer',
                      status_result: 'Solved',
                      priority_level: 'Cool',
                  },
                  { label: 'Follow Up to After Sales Team', value: 'Follow Up to After Sales Team', status_result: 'Pending', priority_level: 'P1' },
                  { label: 'Waiting Claim', value: 'Waiting Claim', status_result: 'Pending', priority_level: 'P7' },
                  { label: 'Waiting Money Receive', value: 'Waiting Money Receive', status_result: 'Pending', priority_level: 'P7' },
                  { label: 'Waiting Data From Customer', value: 'Waiting Data From Customer', status_result: 'Pending', priority_level: 'P3' },
                  { label: 'Follow Up KAE to Brand', value: 'Follow Up KAE to Brand', status_result: 'Pending', priority_level: 'P2' },
                  { label: 'Follow Up WH', value: 'Follow Up WH', status_result: 'Pending', priority_level: 'P1' },
                  { label: 'Follow Up KAE to KAM', value: 'Follow Up KAE to KAM', status_result: 'Pending', priority_level: 'P2' },
                  { label: 'Return not authorized', value: 'Return not authorized', status_result: 'Pending', priority_level: 'P5' },
                  {
                      label: 'Return follow-up (No further action)',
                      value: 'Return follow-up (No further action)',
                      status_result: 'Solved',
                      priority_level: 'Cool',
                  },
              ]),
        ...mergeStringOptions(
            complaintRows.value.map((item) => item.last_step),
            [form.last_step, currentEditItem.value?.last_step],
        )
            .filter((value) => value)
            .map((value) => ({
                label: value,
                value,
                status_result: 'Pending',
                priority_level: null,
            })),
    ].filter((option, index, array) => array.findIndex((entry) => entry?.value === option?.value) === index),
);

const subCaseOptions = computed(() =>
    mergeStringOptions(
        Array.isArray(props.subCaseOptions) && props.subCaseOptions.length
            ? props.subCaseOptions
            : [
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
              ],
        complaintRows.value.map((item) => item.sub_case),
        [filterState.value.sub_case, form.sub_case, currentEditItem.value?.sub_case],
    ),
);

const causeByOptions = computed(() =>
    mergeStringOptions(
        Array.isArray(props.causeByOptions) && props.causeByOptions.length
            ? props.causeByOptions
            : [
                  '?',
                  'J&T',
                  'SAP EXPRESS',
                  'ANTERAJA',
                  'LEX',
                  'POS',
                  'NINJA',
                  'SICEPAT',
                  'KURIR REKOMENDASI',
                  'SPX',
                  'INDOPAKET',
                  'GTL',
                  'CUSTOM LOGISTICS',
                  'GRAB',
                  'JNE',
                  'GOJEK',
                  'CS',
                  'Chat++',
                  'STREAMER',
                  'KAE',
                  'WH',
                  'PART',
              ],
        complaintRows.value.map((item) => item.cause_by),
        [form.cause_by, currentEditItem.value?.cause_by],
    ),
);

const reasonWhitelistOptions = computed(() =>
    mergeStringOptions(
        Array.isArray(props.reasonWhitelistOptions) && props.reasonWhitelistOptions.length
            ? props.reasonWhitelistOptions
            : [
                  'No repacking indication',
                  'Packing not proper',
                  "Customer's evidence is stonger than us",
                  'Our evidences are not strong (platform T&C)',
                  'CCTV does not show receipt number',
                  'Late Respons',
              ],
        complaintRows.value.map((item) => item.reason_whitelist),
        [form.reason_whitelist, currentEditItem.value?.reason_whitelist],
    ),
);

const reasonLateResponseOptions = computed(() =>
    mergeStringOptions(
        Array.isArray(props.reasonLateResponseOptions) && props.reasonLateResponseOptions.length
            ? props.reasonLateResponseOptions
            : ['CS', 'KAE', 'Finance', 'WH', 'PH'],
        complaintRows.value.map((item) => item.reason_late_respons),
        [form.reason_late_respons, currentEditItem.value?.reason_late_respons],
    ),
);

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
const agentSearchQuery = ref('');

const filteredCsSummary = computed(() => {
    let list = [...(props.cs_summary || [])];

    if (agentSearchQuery.value) {
        const query = agentSearchQuery.value.toLowerCase();
        list = list.filter((cs) => cs.cs_name && cs.cs_name.toLowerCase().includes(query));
    }

    return list.sort((a, b) => (b.total || 0) - (a.total || 0));
});

const deleteForm = useForm({});
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const complaintToDelete = ref(null);
const detailItem = ref(null);
const submitError = ref('');

// --- BULK ACTION STATE ---
const selectedIds = ref([]);
const isBulkDeleteModalOpen = ref(false);
const bulkDeleteForm = useForm({
    ids: [],
});

const currentPageIds = computed(() => complaintRows.value.map((row) => row.id));
const isAllCurrentPageSelected = computed(
    () => currentPageIds.value.length > 0 && currentPageIds.value.every((id) => selectedIds.value.includes(id)),
);

const toggleSelectAll = () => {
    if (isAllCurrentPageSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !currentPageIds.value.includes(id));
    } else {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...currentPageIds.value]));
    }
};

const toggleSelect = (id) => {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
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
    bulkDeleteForm.post(route('complaints.bulk-delete'), {
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
        route('complaints.index'),
        {
            search: search.value || undefined,
            status: filterState.value.status && filterState.value.status !== 'All' ? filterState.value.status : undefined,
            cs_name: filterState.value.cs_name || undefined,
            brand: filterState.value.brand && filterState.value.brand !== 'All' ? filterState.value.brand : undefined,
            priority: filterState.value.priority && filterState.value.priority !== 'All' ? filterState.value.priority : undefined,
            source: filterState.value.source && filterState.value.source !== 'All' ? filterState.value.source : undefined,
            platform: filterState.value.platform && filterState.value.platform !== 'All' ? filterState.value.platform : undefined,
            history: filterState.value.history && filterState.value.history !== 'All' ? filterState.value.history : undefined,
            sub_case: filterState.value.sub_case && filterState.value.sub_case !== 'All' ? filterState.value.sub_case : undefined,
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
const setSourceFilter = (source) => visitIndex({ source: source === 'All' ? undefined : source, page: 1 }, { replace: false });
const setPlatformFilter = (platform) => visitIndex({ platform: platform === 'All' ? undefined : platform, page: 1 }, { replace: false });
const setHistoryFilter = (history) => visitIndex({ history: history === 'All' ? undefined : history, page: 1 }, { replace: false });
const setSubCaseFilter = (subCase) => visitIndex({ sub_case: subCase === 'All' ? undefined : subCase, page: 1 }, { replace: false });
const sortBy = (field) => {
    selectedIds.value = []; // Clear selection on sort
    visitIndex({ sort: field, order: filterState.value.sort === field && filterState.value.order === 'asc' ? 'desc' : 'asc' }, { replace: false });
};

const formatDate = (value) => {
    if (!value) return '-';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parsed);
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
    { key: 'All', label: 'Any Priority', value: overview.value.total || 0 },
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
const currentSource = computed(() => filterState.value.source || 'All');
const currentPlatform = computed(() => filterState.value.platform || 'All');
const currentHistory = computed(() => filterState.value.history || 'All');
const currentSubCase = computed(() => filterState.value.sub_case || 'All');

const hasActiveFilters = computed(() =>
    Boolean(
        search.value ||
            currentStatus.value !== 'All' ||
            currentCs.value ||
            currentBrand.value !== 'All' ||
            currentPriority.value !== 'All' ||
            currentSource.value !== 'All' ||
            currentPlatform.value !== 'All' ||
            currentHistory.value !== 'All' ||
            currentSubCase.value !== 'All',
    ),
);
const activeFilterCount = computed(
    () =>
        [
            Boolean(search.value),
            currentStatus.value !== 'All',
            Boolean(currentCs.value),
            currentBrand.value !== 'All',
            currentPriority.value !== 'All',
            currentSource.value !== 'All',
            currentPlatform.value !== 'All',
            currentHistory.value !== 'All',
            currentSubCase.value !== 'All',
        ].filter(Boolean).length,
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
            source: undefined,
            platform: undefined,
            history: undefined,
            sub_case: undefined,
            page: 1,
        },
        { replace: false },
    );
};

const complaintBrandOptions = computed(() =>
    mergeStringOptions(
        masterBrandOptions.value.length ? masterBrandOptions.value : complaintRows.value.map((item) => item.brand),
        complaintRows.value.map((item) => item.brand),
        [filterState.value.brand, form.brand, currentEditItem.value?.brand],
    ),
);
const complaintBrandFilterOptions = computed(() => ['All', ...complaintBrandOptions.value]);

const complaintPlatformOptions = computed(() =>
    mergeStringOptions(
        masterPlatformOptions.value.length ? masterPlatformOptions.value : complaintRows.value.map((item) => item.platform),
        complaintRows.value.map((item) => item.platform),
        [filterState.value.platform, form.platform, currentEditItem.value?.platform],
    ),
);

const skuOptions = computed(() =>
    mergeStringOptions(
        masterSkuCodeOptions.value.map((item) => item?.sku),
        complaintRows.value.map((item) => item.sku),
        [form.sku, currentEditItem.value?.sku],
    ),
);

const skuCatalog = computed(() => {
    const catalog = {};
    masterSkuCodeOptions.value.forEach((item) => {
        if (!item?.sku) return;
        catalog[item.sku] = {
            product_name: item.product_name || '',
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
    qty: null,
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
    step_cs_selesai: 'NO',
    tanggal_update: today(),
    auto_sync_sla: '',
    status: '',
    priority: '',
    complaint_power: 'Normal Complaint',
    report_category: '',
    video_unboxing: null,
    username: '',
    history: '',
    oos: '',
    reason_whitelist: '',
    reason_late_respons: '',
    proof_attachment: null,
});

const form = useForm(createInitialFormState());
const complaintCount = ref(0);
const existingVideoPath = ref('');
const existingProofAttachmentPath = ref('');

const selectedSku = computed(() => skuCatalog.value[form.sku] || {});
const resolvedAutoCauseBy = computed(() => autoCauseByMap.value[form.sub_case] || null);
const isHydratingEditForm = ref(false);

watch(
    () => form.sub_case,
    () => {
        if (isHydratingEditForm.value) {
            return;
        }

        if (resolvedAutoCauseBy.value) {
            form.cause_by = resolvedAutoCauseBy.value;
        } else {
            form.cause_by = '?';
        }
    },
);

watch(
    selectedSku,
    (matchedSku) => {
        if (isHydratingEditForm.value) {
            return;
        }

        form.product_name = matchedSku?.product_name || '';
    },
    { immediate: true, deep: true },
);

// Real-time History Check from Server
const fetchCustomerHistory = debounce(async (username: string) => {
    if (!username) {
        form.history = '';
        complaintCount.value = 0;
        return;
    }

    try {
        const response = await axios.get(route('complaints.history', username));
        if (response.data) {
            form.history = response.data.label || '';
            complaintCount.value = response.data.count ?? 0;
        } else {
            form.history = '';
            complaintCount.value = 0;
        }
    } catch (error) {
        console.error('Failed to fetch customer history:', error);
    }
}, 500);

watch(
    () => form.username,
    (newUsername) => {
        if (isHydratingEditForm.value) {
            return;
        }

        fetchCustomerHistory(newUsername);
    },
);

watch(
    () => form.last_step,
    (val) => {
        if (isHydratingEditForm.value) {
            return;
        }

        form.status = automationResults.value.status;
        form.priority = automationResults.value.priority;

        if (val !== 'Claim Reject') {
            form.reason_whitelist = '';
            form.reason_late_respons = '';
        }
    },
);

watch(
    () => form.reason_whitelist,
    (value) => {
        if (isHydratingEditForm.value) {
            return;
        }

        if (value !== 'Late Respons') {
            form.reason_late_respons = '';
        }
    },
);

watch(
    () => form.step_cs_selesai,
    (value) => {
        if (isHydratingEditForm.value) {
            return;
        }

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

const historyLabelPreview = computed(() => {
    if (!form.username) return '';
    // Edit mode: complaintCount tidak di-fetch, pakai form.history langsung
    if (complaintCount.value === 0 && form.history) return form.history;
    const count = complaintCount.value || 0;
    if (count === 0) return '';
    if (count === 1) return 'Customer ini complaint ke 2';
    return `Customer ini complaint ke ${count + 1}x`;
});

const oosPreview = computed(() => {
    if (!form.order_id) return '';
    const hasOosHistory = oosOrderIds.value.includes(form.order_id);
    return hasOosHistory ? 'Ada Riwayat OOS' : '';
});

const causeByLocked = computed(() => Boolean(resolvedAutoCauseBy.value));

const reportCategoryPreview = computed(() => resolvedAutoCauseBy.value);
const fileNameFromPath = (value) => {
    if (!value) return '';
    const normalizedValue = String(value).replace(/\\/g, '/');
    return normalizedValue.split('/').pop() || normalizedValue;
};
const storageAssetUrl = (value) => {
    if (!value) return '';
    const normalizedValue = String(value);
    if (/^https?:\/\//i.test(normalizedValue) || normalizedValue.startsWith('/')) {
        return normalizedValue;
    }
    return `/storage/${normalizedValue.replace(/^\/+/, '')}`;
};
const currentVideoUrl = computed(() => storageAssetUrl(existingVideoPath.value));
const currentProofAttachmentUrl = computed(() => storageAssetUrl(existingProofAttachmentPath.value));
const videoLabel = computed(() => form.video_unboxing?.name || fileNameFromPath(existingVideoPath.value) || 'Upload video unboxing');

const modalMode = ref('create'); // 'create' or 'edit'
const editId = ref(null);

const setVideoFile = (event) => {
    const [file] = event.target.files || [];
    form.video_unboxing = file || null;
};

const proofAttachmentLabel = computed(
    () => form.proof_attachment?.name || fileNameFromPath(existingProofAttachmentPath.value) || 'Upload proof attachment',
);

const setProofAttachmentFile = (event) => {
    const [file] = event.target.files || [];
    form.proof_attachment = file || null;
};

const syncComplaintDerivedFields = () => {
    form.product_name = form.product_name || selectedSku.value?.product_name || '';
    form.status = automationResults.value.status;
    form.priority = automationResults.value.priority;

    if (resolvedAutoCauseBy.value) {
        form.cause_by = resolvedAutoCauseBy.value;
    } else if (!form.cause_by) {
        form.cause_by = '?';
    }

    if (form.last_step !== 'Claim Reject') {
        form.reason_whitelist = '';
        form.reason_late_respons = '';
    }

    if (form.reason_whitelist !== 'Late Respons') {
        form.reason_late_respons = '';
    }

    if (form.step_cs_selesai !== 'YES') {
        form.tanggal_step_cs_selesai = '';
    }
};

const discardForm = () => {
    submitError.value = '';
    fetchCustomerHistory.cancel();
    isHydratingEditForm.value = false;
    currentEditItem.value = null;
    complaintCount.value = 0;
    existingVideoPath.value = '';
    existingProofAttachmentPath.value = '';
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
    fetchCustomerHistory.cancel();
    isHydratingEditForm.value = false;
    currentEditItem.value = null;
    complaintCount.value = 0;
    existingVideoPath.value = '';
    existingProofAttachmentPath.value = '';
    form.defaults(createInitialFormState());
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (item) => {
    submitError.value = '';
    modalMode.value = 'edit';
    editId.value = item.id;
    fetchCustomerHistory.cancel();
    detailItem.value = null;

    // Use flag to prevent watchers from firing during hydration
    isHydratingEditForm.value = true;
    isModalOpen.value = true;

    nextTick(async () => {
        const initialState = createInitialFormState();
        const hydratedState = { ...initialState };

        // 1. First populate from available row data (Immediate)
        Object.keys(initialState).forEach((key) => {
            if (['video_unboxing', 'proof_attachment'].includes(key)) {
                hydratedState[key] = null;
                return;
            }

            if (item[key] !== undefined) {
                hydratedState[key] = normalizeOptionValue(item[key]);
            }
        });

        existingVideoPath.value = item.video_unboxing || '';
        existingProofAttachmentPath.value = item.proof_attachment || '';

        // Apply first hydration
        form.defaults(hydratedState);
        form.reset();
        form.clearErrors();

        // 2. Fetch full data from database for potentially missing fields
        try {
            const response = await axios.get(route('complaints.show', item.id));
            const complaint = response.data.complaint || (response.data && response.data.id ? response.data : {});

            if (complaint && Object.keys(complaint).length > 0) {
                currentEditItem.value = complaint;
                existingVideoPath.value = complaint.video_unboxing || '';
                existingProofAttachmentPath.value = complaint.proof_attachment || '';

                const updatedState = { ...hydratedState };
                Object.keys(initialState).forEach((key) => {
                    if (['video_unboxing', 'proof_attachment'].includes(key)) return;

                    if (Object.prototype.hasOwnProperty.call(complaint, key)) {
                        updatedState[key] = normalizeOptionValue(complaint[key]);
                    }
                });

                form.defaults(updatedState);
                form.reset();

                // Sync derived fields after full load
                form.product_name = form.product_name || selectedSku.value?.product_name || '';
                form.status = resolveStatus(form.last_step);
                form.priority = resolvePriority(form.last_step) || 'P3';
            }
        } catch (error) {
            console.error('Background fetch failed:', error);
            // Non-blocking error, we already have table data
        } finally {
            await nextTick();
            setTimeout(() => {
                isHydratingEditForm.value = false;
            }, 150);
        }
    });
};

const confirmDelete = (item) => {
    complaintToDelete.value = item;
    isDeleteModalOpen.value = true;
};

const submitDelete = () => {
    if (!complaintToDelete.value) return;

    deleteForm.delete(route('complaints.destroy', complaintToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false;
            complaintToDelete.value = null;
        },
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
            history: data.history || null,
            complaint_power,
            oos: oosValue,
            reason_whitelist: showReasonWhitelist.value ? data.reason_whitelist : null,
            reason_late_respons: showReasonLateRespons.value ? data.reason_late_respons : null,
            tanggal_step_cs_selesai: showStepCompletedDate.value ? data.tanggal_step_cs_selesai || data.tanggal_update : null,
            proof: data.proof || null,
            proof_attachment: data.proof_attachment || null,
            _method: modalMode.value === 'edit' ? 'PUT' : 'POST',
        };
    }).post(modalMode.value === 'edit' ? route('complaints.update', editId.value) : route('complaints.store'), {
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

const fieldError = (field: string) => (form.errors as Record<string, string>)[field];

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

const automationResults = computed(() => {
    return {
        cause_by: resolvedAutoCauseBy.value || 'Manual',
        status: resolveStatus(form.last_step),
        priority: resolvePriority(form.last_step) ?? 'P3',
    };
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
                            class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div class="bg-[var(--app-primary)]/5 absolute -right-4 -top-4 h-24 w-24 rounded-full blur-2xl"></div>
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

                    <!-- 2. Independent Filter Bar (Return to Original Position) -->
                    <div class="rounded-3xl border border-slate-100 bg-slate-50/50 p-2 shadow-sm ring-1 ring-slate-100/10">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3 px-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-blue-500 shadow-sm ring-1 ring-slate-200/50"
                                >
                                    <AlertCircle class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase leading-none tracking-widest text-slate-400">Global Filters</p>
                                    <p class="mt-1 text-[13px] font-black leading-none text-slate-900">Refine Workspace</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pr-1">
                                <!-- Brand Select -->
                                <div class="relative min-w-[140px]">
                                    <select
                                        :value="currentBrand"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setBrandFilter($event.target.value)"
                                    >
                                        <option v-for="option in complaintBrandFilterOptions" :key="option" :value="option">
                                            {{ option === 'All' ? 'ANY BRAND' : option }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <!-- Source Select -->
                                <div class="relative min-w-[130px]">
                                    <select
                                        :value="currentSource"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setSourceFilter($event.target.value)"
                                    >
                                        <option value="All">ANY SOURCE</option>
                                        <option v-for="source in sourceOptions" :key="source" :value="source">
                                            {{ source }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <!-- Platform Select -->
                                <div class="relative min-w-[130px]">
                                    <select
                                        :value="currentPlatform"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setPlatformFilter($event.target.value)"
                                    >
                                        <option value="All">ANY PLATFORM</option>
                                        <option v-for="platform in complaintPlatformOptions" :key="platform" :value="platform">
                                            {{ platform }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <!-- History/Repeat Select -->
                                <div class="relative min-w-[150px]">
                                    <select
                                        :value="currentHistory"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-blue-600 shadow-sm outline-none ring-1 ring-blue-100 transition-all hover:border-blue-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setHistoryFilter($event.target.value)"
                                    >
                                        <option value="All">ALL CUSTOMERS</option>
                                        <option value="Repeat">REPEAT CUSTOMERS ONLY</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-blue-400" />
                                </div>

                                <!-- Sub Case Select -->
                                <div class="relative min-w-[150px]">
                                    <select
                                        :value="currentSubCase"
                                        class="h-10 w-full appearance-none rounded-xl border border-slate-200/60 bg-white pl-4 pr-10 text-[11px] font-black uppercase tracking-wider text-slate-600 shadow-sm outline-none transition-all hover:border-slate-300 focus:ring-4 focus:ring-blue-50/50"
                                        @change="setSubCaseFilter($event.target.value)"
                                    >
                                        <option value="All">ANY SUB CASE</option>
                                        <option v-for="sc in subCaseOptions" :key="sc" :value="sc">
                                            {{ sc }}
                                        </option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                </div>

                                <!-- Reset Filters Button -->
                                <transition name="fade">
                                    <button
                                        v-if="hasActiveFilters"
                                        type="button"
                                        @click="resetFilters"
                                        class="flex h-10 items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 text-[11px] font-black uppercase tracking-wider text-rose-600 shadow-sm transition-all hover:border-rose-300 hover:bg-rose-100"
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" />
                                        <span>Reset</span>
                                        <span
                                            class="flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white"
                                        >
                                            {{ activeFilterCount }}
                                        </span>
                                    </button>
                                </transition>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Priority Tab Strip -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-100 bg-white p-2 shadow-sm ring-1 ring-slate-100/10">
                        <div class="flex min-w-max items-center gap-1.5">
                            <button
                                v-for="p in priorityCards"
                                :key="p.key"
                                @click="setPriorityFilter(p.key)"
                                class="flex items-center gap-2 whitespace-nowrap rounded-xl px-3.5 py-2 text-[11px] font-black uppercase tracking-wider transition-all"
                                :class="
                                    currentPriority === p.key
                                        ? 'bg-[var(--app-primary)] text-white shadow-md shadow-blue-500/20'
                                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
                                "
                            >
                                <span>{{ p.label }}</span>
                                <span
                                    class="rounded-lg px-1.5 py-0.5 text-[9px] font-black"
                                    :class="currentPriority === p.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'"
                                    >{{ p.value }}</span
                                >
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[320px,1fr]">
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
                                    <div class="space-y-4">
                                        <button
                                            @click="setCsFilter('')"
                                            class="flex h-10 w-full items-center justify-between rounded-xl px-4 text-[13px] font-black transition-all"
                                            :class="
                                                !currentCs
                                                    ? 'bg-[var(--app-primary)] text-white shadow-lg shadow-blue-500/20'
                                                    : 'bg-slate-50 text-slate-500 hover:bg-slate-100'
                                            "
                                        >
                                            <div class="flex items-center gap-2">
                                                <Activity class="h-3.5 w-3.5" />
                                                <span>All Active Agents</span>
                                            </div>
                                            <span class="text-[10px] font-black opacity-60">{{
                                                csSummary.reduce((acc, curr) => acc + curr.total, 0)
                                            }}</span>
                                        </button>

                                        <!-- NEW: Agent Search Bar for Scalability -->
                                        <div class="group relative">
                                            <Search
                                                class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-[var(--app-primary)]"
                                            />
                                            <input
                                                v-model="agentSearchQuery"
                                                type="text"
                                                placeholder="Search agent name..."
                                                class="h-9 w-full rounded-xl border border-slate-100 bg-slate-50/50 pl-9 pr-4 text-[11px] font-bold text-slate-700 outline-none transition-all placeholder:font-medium placeholder:text-slate-400 focus:border-[var(--app-primary)] focus:bg-white focus:ring-4 focus:ring-blue-50/50"
                                            />
                                        </div>
                                    </div>

                                    <!-- Design Change: Enhanced Agent Sidebar with Progress & Breakdown -->
                                    <div
                                        class="custom-scrollbar mt-4 max-h-[480px] space-y-3 overflow-y-auto border-b border-dashed border-slate-100 pb-5 pr-1.5"
                                    >
                                        <p class="mb-3 flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">
                                            <Users class="h-3 w-3" />
                                            Agent Workload Desk
                                        </p>

                                        <button
                                            v-for="cs in filteredCsSummary"
                                            :key="cs.cs_name"
                                            @click="setCsFilter(cs.cs_name)"
                                            class="group relative flex w-full flex-col gap-3 overflow-hidden rounded-[20px] border p-3.5 text-left transition-all"
                                            :class="
                                                currentCs === cs.cs_name
                                                    ? 'ring-[var(--app-primary)]/10 border-[var(--app-primary)] bg-blue-50/40 ring-2'
                                                    : 'border-slate-50 bg-white hover:border-slate-200 hover:shadow-sm'
                                            "
                                        >
                                            <!-- Decorative bg for active -->
                                            <div
                                                v-if="currentCs === cs.cs_name"
                                                class="bg-[var(--app-primary)]/5 absolute -right-2 -top-2 h-12 w-12 rounded-full"
                                            ></div>

                                            <div class="flex items-start justify-between">
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-[13px] font-black leading-tight text-slate-900 transition-colors group-hover:text-[var(--app-primary)]"
                                                    >
                                                        {{ cs.cs_name }}
                                                    </p>
                                                    <p class="mt-0.5 text-[10px] font-bold text-slate-400">{{ cs.total }} Active Tickets</p>
                                                </div>
                                                <div
                                                    class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-[10px] font-black text-slate-500 transition-colors group-hover:bg-white"
                                                >
                                                    {{ cs.total }}
                                                </div>
                                            </div>

                                            <!-- Visual Workload Indicator -->
                                            <div class="w-full space-y-1.5">
                                                <div class="flex items-center justify-between text-[8px] font-black uppercase tracking-tighter">
                                                    <span class="text-amber-500">Pending</span>
                                                    <span class="text-emerald-500">Solved</span>
                                                </div>
                                                <div class="flex h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                                    <!-- Simple visualization: we use dummy width here as summary doesn't have detailed breakdown yet,
                                                         but we'll show the potential by using dummy logic or total -->
                                                    <div class="h-full bg-amber-400/80" :style="{ width: '40%' }"></div>
                                                    <div class="h-full flex-1 bg-emerald-400/80"></div>
                                                </div>
                                            </div>
                                        </button>

                                        <!-- No Result State -->
                                        <div v-if="filteredCsSummary.length === 0" class="py-10 text-center">
                                            <Users class="mx-auto h-8 w-8 text-slate-200 opacity-50" />
                                            <p class="mt-2 text-[11px] font-bold uppercase tracking-widest text-slate-400">No agent found</p>
                                        </div>
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
                                <div class="flex flex-col gap-6 border-b border-slate-100 px-6 py-7 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <div
                                            class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]"
                                        >
                                            Operational Database
                                        </div>
                                        <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">Current Tickets</h2>
                                        <div class="mt-2.5 flex items-center gap-2">
                                            <div
                                                class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500 ring-1 ring-slate-200/50"
                                            >
                                                <span
                                                    >SHOWING {{ complaintPage.from || 0 }}-{{ complaintPage.to || 0 }} OF
                                                    {{ complaintPage.total || 0 }}</span
                                                >
                                            </div>
                                            <div
                                                class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                                                :class="
                                                    activeFilterCount
                                                        ? 'border-amber-200 bg-amber-50 text-amber-600 shadow-sm shadow-amber-500/5'
                                                        : 'border-slate-100 bg-white text-slate-400'
                                                "
                                            >
                                                {{ activeFilterCount ? `${activeFilterCount} Active Filters` : 'No Filter' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-1 flex-col gap-4 lg:max-w-xl lg:flex-row lg:items-center lg:justify-end">
                                        <div class="group relative flex-1">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                <Search
                                                    class="h-4.5 w-4.5 text-slate-400 transition-colors group-focus-within:text-[var(--app-primary)]"
                                                />
                                            </div>
                                            <input
                                                v-model="search"
                                                type="text"
                                                class="focus:ring-[var(--app-primary)]/10 h-11 w-full rounded-2xl border border-slate-200 bg-slate-50/50 pl-11 pr-4 text-[13px] font-medium text-slate-900 outline-none transition-all focus:border-[var(--app-primary)] focus:bg-white focus:ring-4"
                                                placeholder="Search SKU, Order ID, Resi, or Customer..."
                                            />
                                        </div>

                                        <button
                                            v-if="selectedIds.length > 0"
                                            type="button"
                                            class="flex h-12 items-center justify-center gap-2 rounded-2xl bg-rose-500 px-6 text-[13px] font-black text-white shadow-lg shadow-rose-500/20 transition-all hover:-translate-y-1 hover:bg-rose-600 active:scale-[0.98]"
                                            @click="confirmBulkDelete"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            <span>Delete Selected ({{ selectedIds.length }})</span>
                                        </button>

                                        <button
                                            type="button"
                                            class="group flex h-12 items-center justify-center gap-2 rounded-2xl bg-[var(--app-primary)] px-6 text-[14px] font-black text-white shadow-[0_15px_30px_rgba(53,103,232,0.25)] transition-all hover:-translate-y-1 hover:bg-[var(--app-primary-dark)] hover:shadow-[0_20px_40px_rgba(53,103,232,0.35)] active:scale-[0.98]"
                                            @click="openCreateModal"
                                        >
                                            <Plus class="h-5 w-5 stroke-[4px]" />
                                            <span class="hidden xl:inline">Create Ticket</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-4 px-4 py-4 lg:hidden">
                                    <article
                                        v-for="item in complaintRows"
                                        :key="`card-${item.id}`"
                                        class="group relative overflow-hidden rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md"
                                    >
                                        <div
                                            class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-slate-50 opacity-50 transition-colors group-hover:bg-[var(--app-primary-soft)]"
                                        ></div>
                                        <div class="relative z-10 flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black tracking-widest text-slate-400"
                                                        >#{{ item.order_id }}</span
                                                    >
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
                                                    @click.stop="openEditModal(item)"
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
                                                <p class="mt-2 text-[13px] font-bold text-slate-700">
                                                    {{ item.source || '-' }} / {{ item.platform || '-' }}
                                                </p>
                                            </div>
                                            <div class="rounded-2xl bg-[#f9fbff] p-4 ring-1 ring-slate-100/50">
                                                <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Handling Agent</p>
                                                <p class="mt-2 text-[13px] font-bold text-slate-700">{{ item.cs_name || 'UNASSIGNED' }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-5 flex items-center justify-between gap-2 border-t border-slate-50 pt-5">
                                            <div class="flex gap-2">
                                                <span
                                                    class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider shadow-sm"
                                                    :class="statusClass(item.status)"
                                                    >{{ item.status || 'Pending' }}</span
                                                >
                                                <span
                                                    class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider shadow-sm"
                                                    :class="priorityClass(item.priority)"
                                                    >{{ item.priority || '-' }}</span
                                                >
                                            </div>
                                            <span class="text-[11px] font-bold italic text-slate-300">"{{ item.brand || '-' }}"</span>
                                        </div>
                                    </article>
                                </div>

                                <div class="hidden overflow-x-auto lg:block">
                                    <table class="w-full min-w-[1080px] table-fixed divide-y divide-[var(--line)]">
                                        <thead class="bg-slate-50/80">
                                            <tr class="text-left text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">
                                                <th class="w-14 py-3 pl-5">
                                                    <div class="flex items-center justify-center">
                                                        <input
                                                            type="checkbox"
                                                            class="h-4 w-4 cursor-pointer rounded-md border-slate-300 text-[var(--app-primary)] transition-all focus:ring-[var(--app-primary)]"
                                                            :checked="isAllCurrentPageSelected"
                                                            @change="toggleSelectAll"
                                                        />
                                                    </div>
                                                </th>
                                                <th class="w-12 px-2 py-3 text-center">No</th>
                                                <th class="w-[8%] px-4 py-3">Source</th>
                                                <th class="w-[12%] px-4 py-3">Complaint Date</th>
                                                <th class="w-[10%] px-4 py-3">Order Date</th>
                                                <th class="w-[10%] px-4 py-3">Order ID</th>
                                                <th class="w-[15%] px-4 py-3">Customer</th>
                                                <th class="w-[10%] px-4 py-3">Agent</th>
                                                <th class="w-[8%] px-4 py-3 text-center">Status</th>
                                                <th class="w-[6%] px-4 py-3 text-center">Prty</th>
                                                <th class="w-[10%] py-3 pl-4 pr-5 text-right">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-[var(--line)] bg-white">
                                            <tr
                                                v-for="(item, index) in complaintRows"
                                                :key="item.id"
                                                class="group align-top transition-colors hover:bg-slate-50/70"
                                                :class="selectedIds.includes(item.id) ? 'bg-blue-50/30' : ''"
                                            >
                                                <td class="w-14 py-3 pl-5">
                                                    <div class="flex items-center justify-center">
                                                        <input
                                                            type="checkbox"
                                                            class="checkbox-row h-4 w-4 cursor-pointer rounded-md border-slate-300 text-[var(--app-primary)] transition-all focus:ring-[var(--app-primary)]"
                                                            :checked="selectedIds.includes(item.id)"
                                                            @change="toggleSelect(item.id)"
                                                        />
                                                    </div>
                                                </td>
                                                <td class="w-12 px-2 py-3 text-center align-middle">
                                                    <span class="text-[10px] font-black text-slate-400">
                                                        {{ (complaintPage.current_page - 1) * complaintPage.per_page + index + 1 }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex min-w-0 flex-col gap-0.5">
                                                        <span class="text-[11px] font-black uppercase tracking-tight text-slate-800">{{
                                                            item.source || '-'
                                                        }}</span>
                                                        <span class="text-[10px] font-bold text-slate-400">{{ item.platform || '-' }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex flex-col gap-0.5">
                                                        <span class="text-[11px] font-black leading-none text-slate-900">{{
                                                            formatDate(item.tanggal_complaint)
                                                        }}</span>
                                                        <span v-if="item.jam_customer_complaint" class="text-[10px] font-bold italic text-slate-400">
                                                            {{ item.jam_customer_complaint.slice(0, 5) }} WIB
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-[11px] font-bold text-slate-500">{{ formatDate(item.tanggal_order) }}</span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex min-w-0 flex-col gap-1">
                                                        <span class="truncate text-[11px] font-black uppercase tracking-tight text-slate-700">{{
                                                            item.order_id || '-'
                                                        }}</span>
                                                        <div
                                                            v-if="item.history"
                                                            class="inline-flex w-fit items-center rounded-lg bg-blue-50 px-2 py-0.5 ring-1 ring-blue-100"
                                                        >
                                                            <span class="text-[8px] font-black uppercase tracking-wider text-blue-600">{{
                                                                item.history
                                                            }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex min-w-0 items-center gap-2.5">
                                                        <div
                                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-[10px] font-black text-slate-400"
                                                        >
                                                            {{ (item.username || '?').charAt(0).toUpperCase() }}
                                                        </div>
                                                        <span class="truncate text-[12px] font-black text-slate-900">{{
                                                            item.username || 'Unknown'
                                                        }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-[12px] font-black text-[var(--app-primary)]">{{
                                                        item.cs_name || 'UNASSIGNED'
                                                    }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="inline-flex rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                                        :class="statusClass(item.status)"
                                                        >{{ item.status || 'Pending' }}</span
                                                    >
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                                        :class="priorityClass(item.priority)"
                                                        >{{ item.priority || '-' }}</span
                                                    >
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
                                                            @click.stop="openEditModal(item)"
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
                                        </div>
                                        <div class="flex flex-col items-center gap-3 pt-4">
                                            <button
                                                type="button"
                                                class="group inline-flex items-center justify-center gap-3 rounded-2xl bg-[var(--app-primary)] px-8 py-4 text-sm font-black text-white shadow-[0_12px_30px_rgba(53,103,232,0.25)] transition-all hover:-translate-y-1 hover:bg-[var(--app-primary-dark)] active:scale-95"
                                                @click="openCreateModal"
                                            >
                                                <Plus class="h-5 w-5 stroke-[3px]" />
                                                <span>Tambah Data Baru</span>
                                            </button>
                                            <button
                                                v-if="hasActiveFilters"
                                                type="button"
                                                class="hovet:text-[var(--app-primary)] text-sm font-bold text-slate-500 underline underline-offset-4 transition"
                                                @click="resetFilters"
                                            >
                                                Reset all filters
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-5 border-t border-slate-100 bg-slate-50/30 px-6 py-6 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <p class="text-[13px] font-bold text-slate-400">
                                        <span class="text-slate-900">Listing {{ complaintPage.from || 0 }} - {{ complaintPage.to || 0 }}</span>
                                        <span class="mx-2 text-slate-300">/</span>
                                        Total {{ complaintPage.total || 0 }} Tiket
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
                                                          ? 'bg-white text-slate-600 ring-1 ring-slate-200/60 hover:border-slate-300 hover:bg-slate-50'
                                                          : 'cursor-not-allowed bg-slate-50 text-slate-300'
                                                "
                                                :disabled="!link.url"
                                                @click="
                                                    link.url && router.visit(link.url, { preserveScroll: true, preserveState: true, replace: true })
                                                "
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

            <transition name="fade">
                <div v-if="detailItem" class="fixed inset-0 z-40 bg-slate-950/20 backdrop-blur-[1px]" @click.self="closeDetail">
                    <aside class="absolute right-0 top-0 h-full w-full max-w-xl overflow-y-auto border-l border-[var(--line)] bg-white shadow-2xl">
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white/80 px-5 py-4 backdrop-blur-md"
                        >
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
                                    <p v-if="detailItem.history" class="mt-1 text-[11px] font-black uppercase tracking-wider text-blue-600">
                                        {{ detailItem.history }}
                                    </p>
                                    <div class="mt-1 flex items-center gap-2 text-[13px] font-medium text-slate-500">
                                        <span>{{ detailItem.brand || '-' }}</span>
                                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                        <span>{{ detailItem.platform || '-' }}</span>
                                    </div>
                                </div>
                                <div class="rounded-3xl border border-slate-100 p-5 shadow-sm">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.2rem] text-slate-400">Handled By</p>
                                    <p class="mt-3 text-lg font-black text-[var(--app-ink)]">{{ detailItem.cs_name || 'UNASSIGNED' }}</p>
                                    <p class="mt-1 text-[13px] font-medium italic text-slate-400">"{{ detailItem.last_step || '-' }}"</p>
                                </div>
                            </div>

                            <div class="space-y-6 rounded-[2rem] border border-slate-100 bg-[#f8fbff]/50 p-7 ring-1 ring-slate-100/50">
                                <div class="grid gap-x-4 gap-y-6 sm:grid-cols-2">
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
                                        <p class="mt-2 font-mono text-[14px] font-medium text-slate-600">{{ detailItem.resi || '-' }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Product SKU & Name</p>
                                        <p class="mt-2 text-[15px] font-bold text-[var(--app-ink)]">
                                            {{ detailItem.sku || '-' }} - {{ detailItem.product_name || '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Issue Category</p>
                                        <p class="mt-2 text-[15px] font-bold text-[var(--app-ink)]">{{ detailItem.sub_case || '-' }}</p>
                                    </div>
                                </div>

                                <div class="border-t border-slate-200/50 pt-5">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Summary Case</p>
                                    <p class="mt-3 text-[15px] font-medium leading-relaxed text-slate-600">{{ detailItem.summary_case || '-' }}</p>
                                </div>

                                <div class="border-t border-slate-200/50 pt-5">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Handling Update</p>
                                    <p class="mt-3 text-[15px] font-medium leading-relaxed text-slate-600">
                                        {{ detailItem.update_long_text || '-' }}
                                    </p>
                                </div>

                                <div class="border-t border-slate-200/50 pt-5">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Evidence & Attachments</p>
                                    <p class="mt-3 text-[15px] font-medium leading-relaxed text-slate-600">{{ detailItem.proof || '-' }}</p>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                                            <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Video Unboxing</p>
                                            <p class="mt-2 truncate text-[13px] font-semibold text-slate-600">
                                                {{ fileNameFromPath(detailItem.video_unboxing) || 'Tidak ada file' }}
                                            </p>
                                            <a
                                                v-if="storageAssetUrl(detailItem.video_unboxing)"
                                                :href="storageAssetUrl(detailItem.video_unboxing)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="mt-3 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-[11px] font-black uppercase tracking-wide text-white transition hover:bg-slate-800"
                                            >
                                                <Eye class="h-3.5 w-3.5" />
                                                Lihat File
                                            </a>
                                        </div>

                                        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                                            <p class="text-[10px] font-bold uppercase tracking-[0.15rem] text-slate-400">Proof Attachment</p>
                                            <p class="mt-2 truncate text-[13px] font-semibold text-slate-600">
                                                {{ fileNameFromPath(detailItem.proof_attachment) || 'Tidak ada file' }}
                                            </p>
                                            <a
                                                v-if="storageAssetUrl(detailItem.proof_attachment)"
                                                :href="storageAssetUrl(detailItem.proof_attachment)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="mt-3 inline-flex items-center gap-2 rounded-xl bg-[var(--app-primary)] px-3 py-2 text-[11px] font-black uppercase tracking-wide text-white transition hover:bg-[var(--app-primary-dark)]"
                                            >
                                                <Eye class="h-3.5 w-3.5" />
                                                Lihat File
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </transition>

            <transition name="fade">
                <div v-show="isModalOpen" class="fixed inset-0 z-50 bg-slate-950/35 backdrop-blur-[2px]">
                    <div class="absolute inset-y-0 right-0 h-full w-full overflow-y-auto bg-[#f8fbff] shadow-2xl 2xl:max-w-[1240px]">
                        <div
                            class="sticky top-0 z-20 flex items-center justify-between border-b px-5 py-6 transition-all duration-500 sm:px-8"
                            :class="modalMode === 'edit' ? 'border-slate-800 bg-slate-900' : 'border-[#E0E7FF] bg-[#EEF2FF]'"
                        >
                            <div class="flex items-center gap-5">
                                <button
                                    type="button"
                                    class="flex h-11 w-11 items-center justify-center rounded-2xl transition-all active:scale-90"
                                    :class="
                                        modalMode === 'edit'
                                            ? 'bg-white/10 text-slate-300 hover:bg-white/20 hover:text-white'
                                            : 'bg-slate-900/5 text-slate-500 hover:bg-slate-900/10 hover:text-slate-900'
                                    "
                                    @click="discardForm"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                                <div>
                                    <h2
                                        class="text-2xl font-black tracking-tight transition-colors"
                                        :class="modalMode === 'edit' ? 'text-white' : 'text-slate-900'"
                                    >
                                        {{ modalMode === 'edit' ? 'Edit Ticket' : 'Create Ticket' }}
                                    </h2>
                                    <p
                                        class="mt-0.5 text-[13px] font-medium transition-colors"
                                        :class="modalMode === 'edit' ? 'text-slate-400' : 'text-slate-500'"
                                    >
                                        {{
                                            modalMode === 'edit'
                                                ? 'Perbarui data complaint untuk akurasi laporan operasional.'
                                                : 'Input detail complaint baru ke dalam sistem monitoring.'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div
                                    class="hidden rounded-full border px-4 py-2.5 text-[12px] font-black uppercase tracking-wider shadow-sm lg:block"
                                    :class="
                                        modalMode === 'edit'
                                            ? 'border-white/10 bg-white/5 text-slate-400'
                                            : 'border-slate-200 bg-white text-slate-500'
                                    "
                                >
                                    <span :class="modalMode === 'edit' ? 'text-blue-400' : 'text-[var(--app-primary)]'">{{
                                        completionSummary.completed
                                    }}</span
                                    >/{{ completionSummary.total }} Fields Complete
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
                                    :class="
                                        modalMode === 'edit'
                                            ? 'bg-blue-600 shadow-blue-500/20 hover:bg-blue-500'
                                            : 'bg-[var(--app-primary)] shadow-indigo-500/20 hover:bg-[var(--app-primary-dark)]'
                                    "
                                    :disabled="form.processing"
                                    @click="submitForm"
                                >
                                    <div class="flex items-center gap-2">
                                        <Plus v-if="!form.processing && modalMode === 'create'" class="h-4 w-4 stroke-[3px]" />
                                        <CheckCircle2 v-else-if="!form.processing && modalMode === 'edit'" class="h-4 w-4 stroke-[3px]" />
                                        <span>{{ form.processing ? 'Syncing...' : modalMode === 'edit' ? 'Save Changes' : 'Submit Ticket' }}</span>
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
                                        v-if="(form.errors as any).general"
                                        class="rounded-[18px] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-700"
                                    >
                                        <p class="font-bold uppercase tracking-wider">System Error</p>
                                        <p class="mt-1">{{ (form.errors as any).general }}</p>
                                    </div>

                                    <!-- Automation Preview Bar -->
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Automated Classification</div>
                                            <div class="mt-1 text-[13px] font-semibold text-slate-700">
                                                {{ computedCycle }} | {{ automationResults.status }} ({{ automationResults.priority }})
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-blue-400">Product & Customer Intel</div>
                                            <div class="mt-1 text-[13px] font-semibold text-blue-700">
                                                {{ form.product_name || 'Menunggu SKU dipilih' }}
                                                <span
                                                    v-if="oosPreview"
                                                    class="ml-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[9px] font-black text-rose-600"
                                                    >OOS ALERT</span
                                                >
                                            </div>
                                        </div>
                                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                                            <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-400">
                                                Customer History & Responsible
                                            </div>
                                            <div class="mt-1 text-[13px] font-semibold text-indigo-700">
                                                {{ historyLabelPreview || 'New Customer' }} | By: {{ automationResults.cause_by }}
                                            </div>
                                        </div>
                                    </div>

                                    <section
                                        class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]"
                                    >
                                        <div
                                            class="mb-6 flex flex-col gap-4 border-b border-slate-50 pb-5 sm:flex-row sm:items-start sm:justify-between"
                                        >
                                            <div class="flex-1">
                                                <div
                                                    class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]"
                                                >
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
                                                    <ChevronDown
                                                        class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                                    />
                                                </div>
                                                <p v-if="fieldError('source')" class="mt-2 text-xs font-medium text-rose-600">
                                                    {{ fieldError('source') }}
                                                </p>
                                            </div>

                                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                                <div class="space-y-1.5">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Tanggal Complaint*</label
                                                    >
                                                    <input v-model="form.tanggal_complaint" type="date" :class="controlClass('tanggal_complaint')" />
                                                    <p v-if="fieldError('tanggal_complaint')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('tanggal_complaint') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Tanggal Order*</label
                                                    >
                                                    <input v-model="form.tanggal_order" type="date" :class="controlClass('tanggal_order')" />
                                                    <p v-if="fieldError('tanggal_order')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('tanggal_order') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Jam Customer Complaint*</label
                                                    >
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">BRAND*</label>
                                                    <div class="relative">
                                                        <select v-model="form.brand" :class="controlClass('brand', 'select')">
                                                            <option value="" disabled>Pilih Brand</option>
                                                            <option v-for="option in complaintBrandOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </option>
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Platform*</label
                                                    >
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Nomor Pesanan*</label
                                                    >
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

                                            <div class="grid gap-5 sm:grid-cols-3">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >SKU Code*</label
                                                    >
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Product Name*</label
                                                    >
                                                    <input v-model="form.product_name" type="text" readonly :class="readonlyInputClass" />
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Qty</label>
                                                    <input
                                                        v-model="form.qty"
                                                        type="number"
                                                        min="0"
                                                        :class="controlClass('qty')"
                                                        placeholder="Jumlah item complaint"
                                                    />
                                                </div>
                                            </div>

                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Sub Case*</label
                                                    >
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
                                                        >Auto from Sub Case</span
                                                    >
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <!-- Locked: tampilkan hanya value auto-fill dari Sub Case -->
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
                                                    <!-- Unlocked: tampilkan semua opsi manual -->
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
                                                <p v-if="fieldError('cause_by')" class="mt-2 text-xs font-medium text-rose-600">
                                                    {{ fieldError('cause_by') }}
                                                </p>
                                            </div>
                                        </div>
                                    </section>

                                    <section
                                        class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_15px_40px_rgba(15,23,42,0.03)] transition-all hover:shadow-[0_20px_50px_rgba(15,23,42,0.05)]"
                                    >
                                        <div class="mb-6 border-b border-slate-50 pb-5">
                                            <div
                                                class="inline-flex items-center gap-2 rounded-full bg-[var(--app-primary-soft)] px-2.5 py-0.5 text-[9px] font-black uppercase tracking-widest text-[var(--app-primary)]"
                                            >
                                                <span>Section 02</span>
                                            </div>
                                            <h3 class="mt-2 text-lg font-black text-slate-900">Handling Ticket</h3>
                                            <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">
                                                Record investigative steps, evidence proof, and final outcomes for this case.
                                            </p>
                                        </div>

                                        <div class="space-y-6">
                                            <div class="grid gap-5 sm:grid-cols-2">
                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Proof</label>
                                                    <input v-model="form.proof" type="text" :class="controlClass('proof')" />
                                                </div>

                                                <div class="space-y-2">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Part of Bad</label
                                                    >
                                                    <input
                                                        v-model="form.part_of_bad"
                                                        type="text"
                                                        :class="controlClass('part_of_bad')"
                                                        placeholder="Isi manual part of bad"
                                                    />
                                                    <p v-if="fieldError('part_of_bad')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('part_of_bad') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                    >Summary Case*</label
                                                >
                                                <textarea
                                                    v-model="form.summary_case"
                                                    rows="4"
                                                    :class="controlClass('summary_case', 'textarea')"
                                                ></textarea>
                                                <p v-if="fieldError('summary_case')" class="text-xs font-medium text-rose-600">
                                                    {{ fieldError('summary_case') }}
                                                </p>
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Last Step*</label
                                                    >
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Reason Whitelist*</label
                                                    >
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Reason Late Respons*</label
                                                    >
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Tanggal Update*</label
                                                    >
                                                    <input v-model="form.tanggal_update" type="date" :class="controlClass('tanggal_update')" />
                                                    <p v-if="fieldError('tanggal_update')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('tanggal_update') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="mb-3 block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                    >STEP CS SELESAI?*</label
                                                >
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
                                                <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                    >Tanggal Step CS Selesai*</label
                                                >
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
                                                <label class="mb-3 block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                    >Complaint power*</label
                                                >
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
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Video Unboxing</label
                                                    >
                                                    <label
                                                        class="hover:border-[var(--accent)]/50 flex w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:bg-white"
                                                    >
                                                        <Upload class="h-5 w-5 text-slate-400" />
                                                        <span class="w-full overflow-hidden truncate px-1 text-[12px] font-medium text-slate-500">{{
                                                            videoLabel
                                                        }}</span>
                                                        <input type="file" class="hidden" accept="video/*" @change="setVideoFile" />
                                                    </label>
                                                    <div
                                                        v-if="modalMode === 'edit' && currentVideoUrl && !form.video_unboxing"
                                                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                                                    >
                                                        <span class="truncate text-[11px] font-semibold text-slate-600">
                                                            Existing: {{ fileNameFromPath(existingVideoPath) }}
                                                        </span>
                                                        <a
                                                            :href="currentVideoUrl"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="shrink-0 text-[11px] font-black uppercase tracking-wide text-[var(--app-primary)] hover:underline"
                                                        >
                                                            Lihat
                                                        </a>
                                                    </div>
                                                    <p v-if="fieldError('video_unboxing')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('video_unboxing') }}
                                                    </p>
                                                </div>

                                                <div class="space-y-1.5">
                                                    <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700"
                                                        >Proof Attachment<br /><span class="text-[11px]">(IMG/PDF/VID)</span></label
                                                    >
                                                    <label
                                                        class="hover:border-[var(--accent)]/50 flex w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:bg-white"
                                                    >
                                                        <Upload class="h-5 w-5 text-slate-400" />
                                                        <span class="w-full overflow-hidden truncate px-1 text-[12px] font-medium text-slate-500">{{
                                                            proofAttachmentLabel
                                                        }}</span>
                                                        <input
                                                            type="file"
                                                            class="hidden"
                                                            accept=".jpg,.jpeg,.png,.pdf,.mp4,.mov,.ogg,.qt,image/*,video/*,application/pdf"
                                                            @change="setProofAttachmentFile"
                                                        />
                                                    </label>
                                                    <div
                                                        v-if="modalMode === 'edit' && currentProofAttachmentUrl && !form.proof_attachment"
                                                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                                                    >
                                                        <span class="truncate text-[11px] font-semibold text-slate-600">
                                                            Existing: {{ fileNameFromPath(existingProofAttachmentPath) }}
                                                        </span>
                                                        <a
                                                            :href="currentProofAttachmentUrl"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="shrink-0 text-[11px] font-black uppercase tracking-wide text-[var(--app-primary)] hover:underline"
                                                        >
                                                            Lihat
                                                        </a>
                                                    </div>
                                                    <p v-if="fieldError('proof_attachment')" class="text-xs font-medium text-rose-600">
                                                        {{ fieldError('proof_attachment') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="space-y-1.5">
                                                <label class="block text-[13px] font-bold uppercase tracking-wide text-slate-700">Username*</label>
                                                <input
                                                    v-model="form.username"
                                                    type="text"
                                                    :class="controlClass('username')"
                                                    placeholder="Gunakan username marketplace..."
                                                />
                                                <div
                                                    v-if="form.history"
                                                    class="mt-2 flex items-center gap-2 rounded-xl bg-blue-50/50 px-3 py-2 ring-1 ring-blue-100"
                                                >
                                                    <Users class="h-3.5 w-3.5 text-blue-500" />
                                                    <span class="text-[11px] font-black uppercase tracking-wider text-blue-600">{{
                                                        form.history
                                                    }}</span>
                                                </div>
                                                <p v-if="fieldError('username')" class="mt-2 text-xs font-medium text-rose-600">
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
                                                        <span
                                                            class="h-1.5 w-1.5 animate-pulse rounded-full"
                                                            :class="statusDotClass(statusPreview)"
                                                        ></span>
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
                                                        <p class="mt-0.5 text-[10px] font-black uppercase text-[var(--app-primary)]">
                                                            {{ autoSyncSlaPreview }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div v-if="reportCategoryPreview" class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-slate-400">Categorization</p>
                                                    <p class="mt-1 text-[13px] font-bold leading-tight text-slate-700">{{ reportCategoryPreview }}</p>
                                                </div>

                                                <div class="rounded-xl border border-slate-50 bg-slate-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-slate-400">Work Cycle</p>
                                                    <p class="mt-0.5 text-[11px] font-bold text-slate-700">{{ computedCycle }}</p>
                                                </div>

                                                <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-blue-400">Product Snapshot</p>
                                                    <p class="mt-0.5 text-[12px] font-bold text-blue-700">
                                                        {{ form.product_name || 'Waiting SKU selection' }}
                                                    </p>
                                                </div>

                                                <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-3.5">
                                                    <p class="text-[10px] font-bold text-amber-400">OOS History</p>
                                                    <p class="mt-0.5 text-[11px] font-bold text-amber-700">
                                                        {{ oosPreview || 'No OOS history' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-2xl border border-slate-100 bg-[var(--app-ink)] p-6 text-white shadow-xl">
                                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-white/40">Quick Guidelines</p>
                                            <ul class="mt-4 space-y-3">
                                                <li class="flex items-start gap-2.5">
                                                    <div
                                                        class="h-4.5 w-4.5 flex shrink-0 items-center justify-center rounded-full bg-white/10 text-white"
                                                    >
                                                        <CheckCircle2 class="h-2.5 w-2.5" />
                                                    </div>
                                                    <p class="text-[12px] font-medium leading-tight text-white/80">
                                                        Start with <span class="font-bold text-white">Order & Customer</span> data first for better
                                                        flow.
                                                    </p>
                                                </li>
                                                <li class="flex items-start gap-2.5">
                                                    <div
                                                        class="h-4.5 w-4.5 flex shrink-0 items-center justify-center rounded-full bg-white/10 text-white"
                                                    >
                                                        <CheckCircle2 class="h-2.5 w-2.5" />
                                                    </div>
                                                    <p class="text-[12px] font-medium leading-tight text-white/80">
                                                        The <span class="font-bold text-white">"By"</span> field will auto-lock based on sub-case
                                                        mapping.
                                                    </p>
                                                </li>
                                                <li class="flex items-start gap-2.5">
                                                    <div
                                                        class="h-4.5 w-4.5 flex shrink-0 items-center justify-center rounded-full bg-white/10 text-white"
                                                    >
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

            <!-- Standardized Delete Confirmation Modal (Robust Pattern) -->
            <transition name="fade">
                <div
                    v-if="isDeleteModalOpen"
                    class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm"
                    @click.self="isDeleteModalOpen = false"
                >
                    <div
                        class="w-full max-w-md scale-100 transform overflow-hidden rounded-[32px] bg-white shadow-[0_40px_100px_rgba(15,23,42,0.3)] transition-all duration-300"
                    >
                        <div class="bg-rose-50 px-8 py-10">
                            <div
                                class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-rose-600 shadow-sm ring-1 ring-rose-100"
                            >
                                <Trash2 class="h-6 w-6" />
                            </div>
                            <h3 class="text-3xl font-black tracking-tight text-rose-950">Hapus Tiket</h3>
                            <p class="mt-2 text-[15px] font-medium leading-relaxed text-rose-600/80">
                                Tiket akan dipindahkan dari daftar aktif ke arsip dan tidak lagi muncul di dashboard utama.
                            </p>
                        </div>

                        <div class="space-y-5 p-6">
                            <div v-if="complaintToDelete" class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 ring-1 ring-slate-200/10">
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Order ID</p>
                                        <p class="text-lg font-black text-slate-900">#{{ complaintToDelete.order_id }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Customer</p>
                                        <p class="text-[13px] font-bold text-slate-700">{{ complaintToDelete.username || '-' }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider shadow-sm"
                                        :class="statusClass(complaintToDelete.status)"
                                    >
                                        {{ complaintToDelete.status || 'Pending' }}
                                    </span>
                                    <span
                                        class="inline-flex rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-wider text-slate-400 ring-1 ring-slate-100"
                                    >
                                        {{ complaintToDelete.brand || '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    class="h-12 flex-1 rounded-2xl bg-slate-50 px-6 text-[14px] font-black text-slate-500 transition hover:bg-slate-100 active:scale-95"
                                    @click="isDeleteModalOpen = false"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    class="h-12 flex-[2] rounded-2xl bg-rose-600 px-6 text-[14px] font-black text-white shadow-lg shadow-rose-500/20 transition hover:-translate-y-1 hover:bg-rose-700 active:scale-[0.98] disabled:opacity-50"
                                    :disabled="deleteForm.processing"
                                    @click="submitDelete"
                                >
                                    {{ deleteForm.processing ? 'Mengarsipkan...' : 'Ya, Arsipkan Tiket' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
            <!-- NOBEL: Bulk Delete Confirmation Modal -->
            <transition name="fade">
                <div
                    v-if="isBulkDeleteModalOpen"
                    class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 px-4 backdrop-blur-sm"
                    @click.self="isBulkDeleteModalOpen = false"
                >
                    <div
                        class="w-full max-w-md scale-100 transform overflow-hidden rounded-[32px] bg-white shadow-[0_40px_100px_rgba(15,23,42,0.3)] transition-all duration-300"
                    >
                        <div class="bg-rose-50 px-8 py-10 text-center">
                            <div
                                class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-rose-600 shadow-sm ring-1 ring-rose-100"
                            >
                                <Trash2 class="h-7 w-7" />
                            </div>
                            <h3 class="text-2xl font-black tracking-tight text-rose-950">Hapus Massal</h3>
                            <p class="mt-2 text-[14px] font-medium leading-relaxed text-rose-600/80">
                                Anda akan memindahkan
                                <span class="font-black text-rose-700 underline decoration-rose-300 underline-offset-4"
                                    >{{ selectedIds.length }} tiket</span
                                >
                                sekaligus ke arsip agar tidak muncul lagi di daftar aktif.
                            </p>
                        </div>

                        <div class="p-6">
                            <div class="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    class="h-12 flex-1 rounded-2xl bg-slate-50 px-6 text-[14px] font-black text-slate-500 transition hover:bg-slate-100 active:scale-95"
                                    @click="isBulkDeleteModalOpen = false"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    class="h-12 flex-[2] rounded-2xl bg-rose-600 px-6 text-[14px] font-black text-white shadow-lg shadow-rose-500/20 transition hover:-translate-y-1 hover:bg-rose-700 active:scale-[0.98] disabled:opacity-50"
                                    :disabled="bulkDeleteForm.processing"
                                    @click="submitBulkDelete"
                                >
                                    {{ bulkDeleteForm.processing ? 'Sedang Mengarsipkan...' : 'Ya, Arsipkan Semua' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Floating Action Bar moved here -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-20 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-20 opacity-0"
            >
                <div v-if="selectedIds.length > 0" class="fixed bottom-10 left-1/2 z-40 -translate-x-1/2">
                    <div
                        class="flex items-center gap-6 rounded-[28px] bg-[var(--app-ink)] p-3 pl-6 pr-3 shadow-[0_20px_50px_rgba(0,0,0,0.3)] ring-1 ring-white/10 backdrop-blur-xl"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="shadow-[var(--app-primary)]/40 flex h-8 w-8 items-center justify-center rounded-full bg-[var(--app-primary)] text-[11px] font-black text-white shadow-lg"
                            >
                                {{ selectedIds.length }}
                            </div>
                            <span class="text-[13px] font-bold tracking-wide text-white">Tiket terpilih</span>
                        </div>

                        <div class="h-8 w-px bg-white/10"></div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="selectedIds = []"
                                class="h-11 rounded-2xl px-5 text-[13px] font-bold text-slate-400 transition-all hover:bg-white/5 hover:text-white"
                            >
                                Batalkan
                            </button>
                            <button
                                @click="confirmBulkDelete"
                                class="flex h-11 items-center gap-2.5 rounded-[18px] bg-rose-500 px-6 text-[13px] font-black text-white shadow-lg shadow-rose-500/30 transition-all hover:bg-rose-600 hover:shadow-rose-600/40 active:scale-95"
                            >
                                <Trash2 class="h-4 w-4" />
                                <span>Hapus Semua</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
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

.fade-leave-active {
    pointer-events: none;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
