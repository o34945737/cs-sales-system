<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import { 
    Plus, Search, Filter, MoreVertical, 
    AlertCircle, CheckCircle2, Clock, 
    TrendingUp, ShieldAlert, Package,
    Calendar, Minus, X, ArrowUpDown, ChevronLeft, ChevronRight,
    Eye, MoreHorizontal, Settings2, Download, RefreshCw, Users, LayoutGrid, Clipboard, Check, ChevronDown, ChevronRight as ChevronRightSmall,
    Menu, SlidersHorizontal, ArrowRightCircle
} from 'lucide-vue-next';

const props = defineProps({
    complaints: Object,
    filters: Object,
    cs_summary: Array,
});

// --- STATE PENCARIAN & FILTER ---
const search = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'All');
const selectedCs = ref(null);
const isSidebarOpen = ref(false); // Mobile Sidebar Toggle

watch(search, debounce((value) => {
    router.get(route('complaints.index'), { ...props.filters, search: value }, { preserveState: true, replace: true });
}, 500));

const setFilterStatus = (status) => {
    activeStatus.value = status;
    router.get(route('complaints.index'), { ...props.filters, status: status, page: 1 }, { preserveState: true });
};

const filterByCs = (name) => {
    selectedCs.value = name;
    isSidebarOpen.value = false;
};

const sortBy = (field) => {
    let order = props.filters.order === 'asc' ? 'desc' : 'asc';
    router.get(route('complaints.index'), { ...props.filters, sort: field, order: order }, { preserveState: true });
};

// --- DATA TABLE COLUMNS (42 Columns!) ---
const columns = ref([
    { key: 'source', label: 'SOURCE', visible: true, sticky: true },
    { key: 'tanggal_complaint', label: 'Tanggal Complaint', visible: true, sticky: false },
    { key: 'tanggal_order', label: 'Tanggal Order', visible: true, sticky: false },
    { key: 'jam_customer_complaint', label: 'Jam Customer Complaint', visible: true, sticky: false },
    { key: 'brand', label: 'BRAND', visible: true, sticky: false },
    { key: 'platform', label: 'Platform', visible: true, sticky: false },
    { key: 'order_id', label: 'Nomor Pesanan', visible: true, sticky: false },
    { key: 'resi', label: 'No Resi', visible: true, sticky: false },
    { key: 'product_name', label: 'Product', visible: true, sticky: false },
    { key: 'sku', label: 'SKU Code', visible: true, sticky: false },
    { key: 'value_of_product', label: 'Value', visible: true, sticky: false },
    { key: 'sub_case', label: 'Sub Case', visible: true, sticky: false },
    { key: 'cause_by', label: 'By', visible: true, sticky: false },
    { key: 'part_of_bad', label: 'Part of Bad', visible: true, sticky: false },
    { key: 'proof', label: 'Proof', visible: true, sticky: false },
    { key: 'summary_case', label: 'Summary Case', visible: true, sticky: false },
    { key: 'update_long_text', label: 'Update', visible: false, sticky: false },
    { key: 'cs_name', label: 'CS Name', visible: true, sticky: false },
    { key: 'last_step', label: 'Last Step', visible: false, sticky: false },
    { key: 'tanggal_step_cs_selesai', label: 'Tanggal Step CS Selesai', visible: false, sticky: false },
    { key: 'update_ai', label: 'Update AI', visible: false, sticky: false },
    { key: 'step_cs_selesai', label: 'Step CS Selesai?', visible: false, sticky: false },
    { key: 'tanggal_update', label: 'Tanggal Update', visible: false, sticky: false },
    { key: 'cycle', label: 'Cycle', visible: false, sticky: false },
    { key: 'external_internal', label: 'External/Internal', visible: false, sticky: false },
    { key: 'sla', label: 'SLA', visible: true, sticky: false },
    { key: 'month', label: 'Month', visible: false, sticky: false },
    { key: 'auto_sync_sla', label: 'Auto Sync SLA', visible: false, sticky: false },
    { key: 'reason_whitelist', label: 'Reason Whitelist', visible: false, sticky: false },
    { key: 'status', label: 'Status', visible: true, sticky: false },
    { key: 'priority', label: 'Priority', visible: true, sticky: false },
    { key: 'complaint_power', label: 'Complaint Power', visible: false, sticky: false },
    { key: 'report_category', label: 'Report Category', visible: false, sticky: false },
    { key: 'reason_late_handling', label: 'Late Handling', visible: false, sticky: false },
    { key: 'username', label: 'Username', visible: true, sticky: false },
    { key: 'ai_template', label: 'AI Template', visible: false, sticky: false },
    { key: 'available_qty', label: 'Avail Qty', visible: false, sticky: false },
    { key: 'status_qty', label: 'Status Qty', visible: false, sticky: false },
    { key: 'kae', label: 'KAE', visible: false, sticky: false },
    { key: 'category_customer', label: 'Category', visible: false, sticky: false },
    { key: 'oos', label: 'OOS', visible: false, sticky: false },
]);

const visibleColumns = computed(() => columns.value.filter(col => col.visible));
const showColumnSwitcher = ref(false);

// --- MODAL & VIEW DETAILS ---
const isModalOpen = ref(false);
const detailModalItem = ref(null);

const openModal = () => isModalOpen.value = true;
const closeModal = () => isModalOpen.value = false;

const showDetails = (item) => {
    detailModalItem.value = item;
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
};

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val);

const form = useForm({
    source: 'AFTERSALES',
    tanggal_complaint: new Date().toISOString().split('T')[0],
    tanggal_order: '',
    jam_customer_complaint: '',
    brand: '',
    platform: '',
    order_id: '',
    resi: '',
    product_name: '',
    sku: '',
    value_of_product: 0,
    sub_case: '',
    cause_by: 'CS',
    proof: '',
    summary_case: '',
    update_long_text: '',
    cs_name: '',
    last_step: '',
    tanggal_step_cs_selesai: '',
    update_ai: '',
    step_cs_selesai: 'NO',
    tanggal_update: '',
    complaint_power: 'NORMAL COMPLAINT',
    report_category: '',
    video_unboxing: null,
    username: '',
    available_qty: 0,
    status_qty: '',
});

const submitForm = () => {
    form.post(route('complaints.store'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        }
    });
};

const sources = ['AFTERSALES', 'B2B', 'PRESALES', 'SOCMED', 'BRAND/OPS'];
const causeByOptions = ['?', 'CS', 'KAE', 'WH', 'ANTERAJA', 'CHAT++', 'CUSTOM LOGISTICS', 'GOJEK/GRAB', 'GTL', 'INDOPAKET', 'J&T', 'JNE', 'KURIR REKOMENDASI', 'LEX', 'NINJA', 'POS', 'SAP EXPRESS', 'SICEPAT', 'SPX', 'STREAMER', 'CUSTOMER', 'BRAND', 'PROMO', 'PART'];

</script>

<template>
    <Head title="Complaints" />

    <AppLayout :breadcrumbs="[{title: 'Dashboard', href: '/dashboard'}, {title: 'Complaint Desk', href: '/complaints'}]">
        <div class="flex flex-col lg:flex-row h-[calc(100vh-70px)] w-full max-w-full overflow-hidden bg-slate-50/50 relative">
            
            <!-- MOBILE SIDEBAR OVERLAY -->
            <div v-if="isSidebarOpen" class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden backdrop-blur-sm" @click="isSidebarOpen = false"></div>

            <!-- SIDEBAR KIRI: ADAPTIVE DESKTOP & MOBILE -->
            <aside :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                class="fixed lg:relative inset-y-0 left-0 w-72 bg-white border-r border-slate-100 flex flex-col shadow-2xl lg:shadow-none z-50 transition-transform duration-300 ease-in-out overflow-y-auto custom-scrollbar">
                
                <div class="p-8 border-b border-slate-50 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] flex items-center gap-3">
                        <Users class="w-4 h-4 text-indigo-500" /> CS Groupings
                    </h3>
                    <button @click="isSidebarOpen = false" class="lg:hidden p-2 text-slate-400 hover:bg-slate-50 rounded-full">
                        <X class="w-5 h-5"/>
                    </button>
                    <button class="hidden lg:block p-2 hover:bg-indigo-50 rounded-xl text-indigo-400 transition-all"><RefreshCw class="w-3.5 h-3.5"/></button>
                </div>
                
                <div class="p-6 space-y-1.5 flex-1">
                    <button @click="filterByCs(null)"
                        :class="!selectedCs ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-100 font-bold px-6' : 'text-slate-500 hover:bg-slate-50 px-4 ring-1 ring-transparent'"
                        class="w-full flex items-center justify-between py-4 rounded-2xl text-xs transition-all duration-300">
                        <span>All Active Agents</span>
                        <span class="text-[10px] opacity-70 font-black">{{ props.complaints.total }}</span>
                    </button>
                    
                    <div class="mt-8 pt-8 border-t border-slate-50 space-y-1">
                        <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest px-4 mb-4">Select Agent</p>
                        <button v-for="cs in props.cs_summary" :key="cs.cs_name" 
                            @click="filterByCs(cs.cs_name)"
                            :class="selectedCs === cs.cs_name ? 'bg-white ring-2 ring-indigo-500 text-indigo-600 font-black translate-x-2' : 'text-slate-500 hover:bg-white hover:shadow-md hover:ring-1 hover:ring-slate-100'"
                            class="w-full flex items-center justify-between px-4 py-4 rounded-2xl text-xs transition-all group overflow-hidden">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-slate-50 border border-slate-100 shadow-sm text-[11px] font-black group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-all uppercase">
                                    {{ cs.cs_name ? cs.cs_name.charAt(0) : '?' }}
                                </div>
                                <span class="truncate max-w-[140px] tracking-tight uppercase">{{ cs.cs_name || 'Unassigned' }}</span>
                            </div>
                            <ArrowRightCircle v-if="selectedCs === cs.cs_name" class="w-4 h-4" />
                            <span v-else class="text-[9px] text-slate-300 font-bold">{{ cs.total }}</span>
                        </button>
                    </div>
                </div>
            </aside>

            <!-- KONTEN UTAMA: RESPONSIVE SHELL -->
            <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-white lg:bg-transparent relative">
                <!-- TOP HEADER PRO -->
                <header class="p-4 lg:p-8 bg-white border-b lg:border-none lg:bg-transparent flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <button @click="isSidebarOpen = true" class="lg:hidden p-3 bg-slate-50 border border-slate-100 rounded-2xl text-slate-400">
                            <Menu class="w-5 h-5" />
                        </button>
                        <div class="flex-1">
                            <h2 class="text-xl lg:text-3xl font-black text-slate-900 tracking-tighter leading-none"><span class="text-indigo-600">Complaints</span></h2>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-2 hidden sm:block">Industrial Grade Customer Support Engine</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 lg:gap-4 w-full md:w-auto">
                        <div class="relative flex-1 md:w-64 lg:w-96">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                            <input v-model="search" type="text" placeholder="Search data..." 
                                class="w-full pl-11 pr-4 py-3.5 bg-white border-slate-100 rounded-[1.25rem] text-sm font-semibold shadow-sm focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all border outline-none" />
                        </div>
                        <button @click="openModal" class="p-4 bg-indigo-600 text-white rounded-[1.25rem] shadow-xl shadow-indigo-100 hover:bg-slate-900 transition-all active:scale-95 group">
                            <Plus class="w-5 h-5 group-hover:rotate-90 transition-transform" stroke-width="4" />
                        </button>
                    </div>
                </header>

                <!-- TOOLBAR & FILTERS -->
                <div class="px-4 lg:px-8 pb-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-1.5 p-1.5 bg-slate-100/50 rounded-2xl border border-white/50 backdrop-blur-sm overflow-x-auto no-scrollbar max-w-full">
                        <button v-for="st in ['All', 'Pending', 'Solved', 'Whitelist']" :key="st"
                            @click="setFilterStatus(st)"
                            :class="activeStatus === st ? 'bg-white text-indigo-600 shadow-md ring-1 ring-indigo-50 font-black' : 'text-slate-400 hover:text-slate-600 px-3'"
                            class="px-5 py-2.5 rounded-xl text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">
                            {{ st }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button @click="showColumnSwitcher = !showColumnSwitcher" 
                            class="px-5 py-3 bg-white border border-slate-100 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest hover:border-indigo-200 hover:text-indigo-600 transition-all flex items-center gap-3">
                            <Settings2 class="w-4 h-4" /> <span class="hidden sm:inline">Columns</span>
                        </button>
                        <button class="p-3 bg-white border border-slate-100 rounded-2xl text-slate-400 hover:text-indigo-600 group transition-all">
                             <Download class="w-5 h-5 group-hover:-translate-y-1 transition-transform" />
                        </button>
                    </div>
                </div>

                <!-- TABLE/GRID CONTAINER -->
                <div class="flex-1 p-4 lg:p-8 bg-slate-50/50 flex flex-col min-w-0">
                    <div class="flex-1 bg-white rounded-[2.5rem] border border-slate-200/60 shadow-xl shadow-slate-100 flex flex-col relative min-w-0 overflow-hidden">
                        
                        <!-- DESKTOP TABLE VIEW (Absolute positioning prevents page blow-out) -->
                        <div class="hidden lg:block flex-1 relative min-w-0">
                            <div class="absolute inset-0 overflow-x-auto overflow-y-auto custom-scrollbar">
                                <table class="w-full text-left border-separate border-spacing-0 min-w-max">
                                <thead>
                                    <tr class="bg-slate-50/70 text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] sticky top-0 z-10 backdrop-blur-md">
                                        <th v-for="col in visibleColumns" :key="col.key"
                                            :class="{'sticky left-0 z-30 bg-white shadow-[10px_0_15px_-10px_rgba(0,0,0,0.1)] border-r border-slate-100': col.sticky}"
                                            class="px-8 py-7 cursor-pointer hover:text-slate-900 transition-colors border-b border-slate-50"
                                            @click="sortBy(col.key)">
                                            <div class="flex items-center gap-3">
                                                {{ col.label }}
                                                <ArrowUpDown v-if="['order_id','tanggal_complaint','status'].includes(col.key)" class="w-3 h-3 opacity-20" />
                                            </div>
                                        </th>
                                        <th class="px-8 py-7 text-right sticky right-0 bg-white z-30 border-b border-l border-slate-100 shadow-[-10px_0_15px_-10px_rgba(0,0,0,0.1)]">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-[11px] font-bold text-slate-600">
                                    <tr v-for="item in complaints.data" :key="item.id" class="hover:bg-slate-50 transition-all group">
                                        <td v-for="col in visibleColumns" :key="col.key"
                                            :class="{'sticky left-0 z-20 bg-white group-hover:bg-slate-100 shadow-[10px_0_15px_-10px_rgba(0,0,0,0.1)] border-r border-slate-100': col.sticky}"
                                            class="px-8 py-5 border-b border-slate-100/30 whitespace-nowrap">
                                            
                                            <template v-if="col.key === 'order_id'">
                                                <div class="flex items-center gap-4">
                                                    <div class="text-indigo-600 font-black text-xs">#{{ item[col.key] }}</div>
                                                    <button @click="copyToClipboard(item[col.key])" class="p-1.5 rounded-lg bg-slate-100 text-slate-300 hover:text-indigo-500 opacity-0 group-hover:opacity-100 transition-all"><Clipboard class="w-3 h-3"/></button>
                                                </div>
                                            </template>
                                            <template v-else-if="col.key === 'status'">
                                                <div class="px-4 py-2 rounded-xl border-2 w-fit font-black text-[9px] uppercase tracking-widest"
                                                    :class="{
                                                        'border-emerald-50 bg-emerald-50/30 text-emerald-600': item[col.key] === 'Solved',
                                                        'border-amber-50 bg-amber-50/30 text-amber-600': item[col.key] === 'Pending',
                                                    }">
                                                    {{ item[col.key] || 'DRAFT' }}
                                                </div>
                                            </template>
                                            <template v-else-if="col.key === 'value_of_product'">
                                                <span class="font-black text-slate-900 tracking-tighter">{{ formatCurrency(item[col.key]) }}</span>
                                            </template>
                                            <template v-else>
                                                {{ item[col.key] || '--' }}
                                            </template>
                                        </td>
                                        <td class="px-8 py-5 text-right sticky right-0 bg-white group-hover:bg-slate-100 shadow-[-10px_0_15px_-10px_rgba(0,0,0,0.1)] border-l border-b border-slate-100/30">
                                            <div class="flex justify-end gap-2">
                                                <button @click="showDetails(item)" class="p-2.5 bg-slate-50 rounded-xl text-slate-300 hover:text-indigo-600 hover:bg-white hover:shadow-md transition-all"><Eye class="w-4 h-4"/></button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>

                        <!-- MOBILE CARD VIEW (Shown on Mobile screens) -->
                        <div class="lg:hidden flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-slate-50/30">
                            <div v-for="item in complaints.data" :key="item.id" 
                                class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm active:scale-95 transition-all">
                                <div class="flex justify-between items-start mb-6">
                                    <div>
                                        <div class="text-xs font-black text-indigo-600 uppercase">#{{ item.order_id }}</div>
                                        <div class="text-[10px] text-slate-300 font-bold mt-1 uppercase">{{ item.tanggal_complaint }} • {{ item.platform }}</div>
                                    </div>
                                    <div class="px-3 py-1.5 rounded-xl border text-[9px] font-black uppercase"
                                        :class="item.status === 'Solved' ? 'border-emerald-100 text-emerald-600' : 'border-amber-100 text-amber-600'">
                                        {{ item.status || 'Pending' }}
                                    </div>
                                </div>
                                <div class="space-y-3 mb-6">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-400 font-bold uppercase tracking-widest text-[9px]">Customer</span>
                                        <span class="font-black text-slate-900">{{ item.username || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-400 font-bold uppercase tracking-widest text-[9px]">SLA</span>
                                        <span class="font-black" :class="item.sla > 2 ? 'text-red-500' : 'text-slate-900'">{{ item.sla }} Days</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-400 font-bold uppercase tracking-widest text-[9px]">Amount</span>
                                        <span class="font-black text-slate-900 italic font-serif">{{ formatCurrency(item.value_of_product) }}</span>
                                    </div>
                                </div>
                                <button @click="showDetails(item)" class="w-full py-4 bg-slate-50 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-400 active:bg-indigo-600 active:text-white transition-all">
                                    View Full Console Data
                                </button>
                            </div>
                            <div v-if="complaints.data.length === 0" class="py-20 text-center px-6">
                                <Package class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                                <p class="text-slate-400 font-bold text-sm tracking-tight uppercase">Database is silent as the ocean.</p>
                            </div>
                        </div>

                        <!-- RESPONSIVE FOOTER -->
                        <footer class="p-6 lg:p-8 bg-slate-50 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="text-center md:text-left">
                                <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em]">Listing {{ props.complaints.from }}-{{ props.complaints.to }} / Total {{ props.complaints.total }} Index</p>
                            </div>
                            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar max-w-full pb-2 md:pb-0">
                                <template v-for="(link, i) in props.complaints.links" :key="i">
                                    <button v-if="link.url"
                                        @click="router.get(link.url, {}, { preserveState: true })"
                                        v-html="link.label"
                                        :class="link.active ? 'bg-slate-900 text-white shadow-2xl shadow-slate-300 border-slate-900' : 'bg-white text-slate-400 border-slate-100 hover:text-indigo-600'"
                                        class="h-12 min-w-[48px] px-4 flex items-center justify-center rounded-2xl border text-[10px] font-black uppercase transition-all shrink-0">
                                    </button>
                                </template>
                            </div>
                        </footer>
                    </div>
                </div>
            </main>
        </div>

        <!-- COLUMN SWITCHER DRAWER (Mobile-friendly) -->
        <div v-if="showColumnSwitcher" class="fixed inset-0 z-[100] flex justify-end">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showColumnSwitcher = false"></div>
            <div class="relative w-80 bg-white shadow-2xl flex flex-col animate-in slide-in-from-right duration-300">
                <div class="p-8 border-b border-slate-50 flex items-center justify-between">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-3"><Settings2 class="w-4 h-4"/> Visibility</h4>
                    <button @click="showColumnSwitcher = false" class="p-2 text-slate-300 hover:text-red-500"><X class="w-5 h-5"/></button>
                </div>
                <div class="flex-1 overflow-y-auto p-6 space-y-1.5 custom-scrollbar bg-slate-50/20">
                    <label v-for="col in columns" :key="col.key" 
                        class="flex items-center gap-4 p-4 rounded-2xl cursor-pointer transition-all border border-transparent"
                        :class="col.visible ? 'bg-white shadow-sm border-slate-100 font-bold' : 'opacity-40 grayscale group'">
                        <input type="checkbox" v-model="col.visible" class="w-5 h-5 rounded-lg border-slate-200 text-indigo-600 focus:ring-0" />
                        <span class="text-xs text-slate-600 transition-colors">{{ col.label }}</span>
                    </label>
                </div>
                <div class="p-6 bg-white border-t border-slate-50">
                    <button @click="showColumnSwitcher = false" class="w-full py-5 bg-slate-900 text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-xl shadow-slate-200 active:scale-95 transition-all">Apply Data View</button>
                </div>
            </div>
        </div>

        <!-- FULL DETAILS DRAWER (BEST PRACTICE FOR 42 COLS) -->
        <div v-if="detailModalItem" class="fixed inset-0 z-[100] flex justify-end">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" @click="detailModalItem = null"></div>
            <div class="relative w-full max-w-2xl bg-white shadow-2xl flex flex-col animate-in slide-in-from-right duration-500">
                <div class="p-10 border-b border-slate-50 flex items-center justify-between bg-white z-10 sticky top-0">
                    <div>
                        <h4 class="text-h1 font-black text-slate-900 text-3xl tracking-tighter">DATA<span class="text-indigo-600">CONSOLE</span></h4>
                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] mt-2">Node Identity 001-{{ detailModalItem.id }}</p>
                    </div>
                    <button @click="detailModalItem = null" class="p-4 bg-slate-50 text-slate-300 hover:text-red-500 rounded-3xl transition-all"><X class="w-6 h-6"/></button>
                </div>
                <div class="flex-1 overflow-y-auto p-12 space-y-12 bg-slate-50/30 custom-scrollbar">
                    <div class="grid grid-cols-2 gap-x-12 gap-y-10">
                        <div v-for="col in columns" :key="col.key" class="space-y-3 pb-6 border-b border-slate-100/50 group">
                            <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] group-hover:text-indigo-400 transition-colors">{{ col.label }}</p>
                            <p class="text-sm font-black text-slate-700 tracking-tight lowercase first-letter:uppercase">{{ detailModalItem[col.key] || 'Not Defined' }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-8 bg-white border-t border-slate-100 flex gap-4">
                    <button @click="detailModalItem = null" class="flex-1 py-5 bg-slate-900 text-white rounded-[2rem] text-[10px] font-black uppercase tracking-widest shadow-2xl active:scale-95 transition-all">Back to Dashboard</button>
                </div>
            </div>
        </div>

        <!-- FORM ENTRY MODAL -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md" @click="closeModal"></div>
            <div class="relative bg-white rounded-[3rem] shadow-2xl max-w-4xl w-full p-12 max-h-[85vh] overflow-y-auto custom-scrollbar animate-in zoom-in-95 duration-500 border border-slate-100">
                <div class="flex items-start justify-between mb-16">
                    <div>
                        <h3 class="text-4xl font-black text-slate-900 tracking-tighter">NEW <span class="text-indigo-600">ENTRY</span></h3>
                        <p class="text-[10px] text-slate-300 font-black uppercase tracking-[0.4em] mt-3 underline decoration-indigo-400 decoration-4 underline-offset-8">Authorized Data Submission</p>
                    </div>
                    <button @click="closeModal" class="p-4 bg-slate-50 text-slate-300 rounded-2xl"><X class="w-6 h-6"/></button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                    <!-- SOURCE SELECTOR -->
                    <div class="col-span-1 md:col-span-2 space-y-3">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">SOURCE <span class="text-red-500 text-[9px] ml-1 font-black">*</span></label>
                        <div class="flex flex-wrap gap-2.5">
                            <button v-for="s in sources" :key="s" @click="form.source = s" 
                                :class="form.source === s ? 'bg-purple-700 text-white shadow-lg shadow-purple-200' : 'bg-slate-50 text-slate-400 border border-slate-100 hover:bg-white'"
                                class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                                {{ s }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tanggal Complaint <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <div class="group relative">
                            <input type="date" v-model="form.tanggal_complaint" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 pl-5 pr-10 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                            <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 pointer-events-none" />
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tanggal Order <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <div class="group relative">
                            <input type="date" v-model="form.tanggal_order" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 pl-5 pr-10 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                            <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 pointer-events-none" />
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jam Customer Complaint <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <div class="group relative">
                            <input type="time" step="1" v-model="form.jam_customer_complaint" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 pl-5 pr-10 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                            <Clock class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 pointer-events-none" />
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">BRAND <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <select v-model="form.brand" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none">
                            <option value="" disabled>Select Brand</option>
                            <option>PARENTY STORE</option>
                            <option>MAKUKU</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Platform <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <select v-model="form.platform" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none">
                            <option value="" disabled>Select Platform</option>
                            <option>SHOPEE</option>
                            <option>TOKOPEDIA</option>
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nomor Pesanan <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <input type="text" v-model="form.order_id" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Username <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <input type="text" v-model="form.username" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">No Resi <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <input type="text" v-model="form.resi" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Value Product <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <div class="flex items-center bg-slate-50 border border-transparent rounded-2xl overflow-hidden focus-within:bg-white focus-within:ring-8 focus-within:ring-indigo-600/5 focus-within:border-indigo-600 transition-all">
                            <div class="px-4 text-slate-400 font-black text-xs">Rp</div>
                            <input type="number" v-model="form.value_of_product" class="flex-1 bg-transparent border-none focus:ring-0 py-3 font-black text-slate-700 focus:outline-none" />
                        </div>
                    </div>
                    
                    <!-- BY GRID -->
                    <div class="col-span-1 md:col-span-2 space-y-3 mt-4">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">BY (Cause By) <span class="text-red-500 text-[9px] ml-1 font-black">*</span></label>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="o in causeByOptions" :key="o" @click="form.cause_by = o" 
                                :class="form.cause_by === o ? 'bg-purple-700 text-white shadow-md' : 'bg-slate-50 text-slate-400 hover:bg-white'"
                                class="px-3 py-2 border border-slate-100 rounded-lg text-[9px] font-black uppercase transition-all">
                                {{ o }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Product <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <textarea v-model="form.product_name" rows="2" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-medium text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none italic"></textarea>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Summary Case <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <textarea v-model="form.summary_case" rows="2" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-medium text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none italic"></textarea>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Update AI</label>
                        <input type="text" v-model="form.update_ai" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-medium text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Available QTY</label>
                        <input type="number" v-model="form.available_qty" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status Qty</label>
                        <input type="text" v-model="form.status_qty" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none" />
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Step CS Selesai? <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <div class="flex border border-slate-100 rounded-2xl overflow-hidden bg-slate-50">
                            <button @click="form.step_cs_selesai = 'YES'" 
                                :class="form.step_cs_selesai === 'YES' ? 'bg-purple-700 text-white' : 'text-slate-400 hover:bg-slate-100'"
                                class="flex-1 py-3 font-bold text-xs transition-all border-r border-slate-100">
                                YES
                            </button>
                            <button @click="form.step_cs_selesai = 'NO'" 
                                :class="form.step_cs_selesai === 'NO' ? 'bg-purple-700 text-white' : 'text-slate-400 hover:bg-slate-100'"
                                class="flex-1 py-3 font-bold text-xs transition-all">
                                NO
                            </button>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">CS Name <span class="text-red-500 ml-1 italic font-black">*</span></label>
                        <input type="text" v-model="form.cs_name" class="w-full bg-slate-50 border-transparent rounded-2xl py-3 px-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-8 focus:ring-indigo-600/5 focus:border-indigo-600 transition-all focus:outline-none uppercase" />
                    </div>
                </div>

                <div class="mt-16 border-t border-slate-100 pt-8 flex flex-col sm:flex-row justify-end gap-4">
                    <button @click="closeModal" class="px-12 py-5 bg-slate-50 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">Discard</button>
                    <button @click="submitForm" class="px-16 py-5 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-slate-900 transition-all active:scale-95">LOG DATA</button>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style>
/* PREMIUM SCROLLBARS */
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 40px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #818CF8; }

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* FIXING BORDERS FOR STICKY THEME */
table { border-collapse: separate; }
th, td { border-bottom-width: 1px; }

/* ANIMATION UTILS */
@keyframes drawer-in {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
.animate-in.slide-in-from-right {
    animation: drawer-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

/* TYPOGRAPHY */
body {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
