<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, watch, onMounted } from 'vue';
import debounce from 'lodash/debounce';
import { 
    Plus, Search, Filter, MoreVertical, 
    AlertCircle, CheckCircle2, Clock, 
    TrendingUp, ShieldAlert, Package,
    Calendar, Minus, X, ArrowUpDown, ChevronLeft, ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
    complaints: Object,
    filters: Object,
});

// --- STATE PENCARIAN & FILTER (DataTable Logic) ---
const search = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || 'All');

// Fungsi Search otomatis (Debounce 500ms agar tidak berisik ke server)
watch(search, debounce((value) => {
    router.get(route('complaints.index'), { ...props.filters, search: value }, { 
        preserveState: true, 
        replace: true 
    });
}, 500));

const setFilterStatus = (status) => {
    activeStatus.value = status;
    router.get(route('complaints.index'), { ...props.filters, status: status, page: 1 }, { preserveState: true });
};

const sortBy = (field) => {
    let order = props.filters.order === 'asc' ? 'desc' : 'asc';
    router.get(route('complaints.index'), { ...props.filters, sort: field, order: order }, { preserveState: true });
};

// --- FORM LOGIC (Modal) ---
const isModalOpen = ref(false);
const openModal = () => isModalOpen.value = true;
const closeModal = () => isModalOpen.value = false;

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
    phoenix: '',
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
    <Head title="Complaints Management" />

    <AppLayout :breadcrumbs="[{title: 'Dashboard', href: '/dashboard'}, {title: 'Complaints', href: '/complaints'}]">
        <!-- HEADER LUX -->
        <div class="px-6 lg:px-8 py-5 bg-white border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">Complaint <span class="text-indigo-600">Console</span></h2>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Real-time Customer Issue Tracking</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative hidden sm:block">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="search" type="text" placeholder="Quick Search..." class="pl-9 pr-4 py-2 border-gray-200 rounded-xl text-sm focus:ring-4 focus:ring-indigo-50 transition-all w-64" />
                </div>
                <button @click="openModal" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-indigo-200 transition-all active:scale-95">
                    <Plus class="w-4 h-4" stroke-width="3" />
                    New Ticket
                </button>
            </div>
        </div>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            
            <!-- FAST FILTERS (Modern Dashboard Style) -->
            <div class="flex flex-wrap items-center gap-2 pb-2 overflow-x-auto no-scrollbar">
                <button v-for="st in ['All', 'Pending', 'Solved', 'Whitelist']" :key="st"
                    @click="setFilterStatus(st)"
                    :class="activeStatus === st ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'bg-white text-gray-500 border-gray-100 hover:bg-gray-50'"
                    class="px-5 py-2 rounded-full text-xs font-black border transition-all truncate uppercase tracking-wider">
                    {{ st }}
                </button>
            </div>

            <!-- TABLE CONTAINER (DataTable Simulation) -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                <th class="px-6 py-5 cursor-pointer hover:text-indigo-600 transition-colors" @click="sortBy('order_id')">
                                    <div class="flex items-center gap-2">Order Info <ArrowUpDown class="w-3 h-3"/></div>
                                </th>
                                <th class="px-6 py-5 cursor-pointer hover:text-indigo-600 transition-colors" @click="sortBy('username')">
                                    <div class="flex items-center gap-2">Customer <ArrowUpDown class="w-3 h-3"/></div>
                                </th>
                                <th class="px-6 py-5">Brand / Platform</th>
                                <th class="px-6 py-5 cursor-pointer hover:text-indigo-600 transition-colors" @click="sortBy('status')">
                                    <div class="flex items-center gap-2">Status <ArrowUpDown class="w-3 h-3"/></div>
                                </th>
                                <th class="px-6 py-5 text-right">Priority</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 uppercase text-[11px] font-bold tracking-wider">
                            <tr v-if="complaints.data.length === 0">
                                <td colspan="5" class="py-20 text-center text-gray-400 italic">No data found matching your filters.</td>
                            </tr>
                            <tr v-for="item in complaints.data" :key="item.id" class="hover:bg-indigo-50/30 transition-colors group cursor-pointer">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-black text-gray-900 group-hover:text-indigo-600 transition-colors">#{{ item.order_id }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ item.tanggal_complaint }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-800">{{ item.username || 'ANONYMOUS' }}</div>
                                    <div class="text-[10px] text-indigo-400/70">{{ item.cs_name || 'NOT ASSIGNED' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-600">{{ item.brand || '-' }}</div>
                                    <div class="text-[10px] text-gray-400 font-medium">{{ item.platform || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border w-fit" 
                                        :class="{
                                            'bg-emerald-50 border-emerald-100 text-emerald-600': item.status === 'Solved',
                                            'bg-amber-50 border-amber-100 text-amber-600': item.status === 'Pending',
                                            'bg-purple-50 border-purple-100 text-purple-600': item.status === 'Whitelist',
                                            'bg-gray-50 border-gray-100 text-gray-400': !item.status
                                        }">
                                        <div class="w-1.5 h-1.5 rounded-full" :class="{
                                            'bg-emerald-500 shadow-[0_0_8px_#10b981]': item.status === 'Solved',
                                            'bg-amber-500 shadow-[0_0_8px_#f59e0b] animate-pulse': item.status === 'Pending',
                                            'bg-purple-500 shadow-[0_0_8px_#a855f7]': item.status === 'Whitelist',
                                            'bg-gray-300': !item.status
                                        }"></div>
                                        {{ item.status || 'DRAFT' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span :class="{
                                        'text-red-500': ['Mines', 'P1', 'P2'].includes(item.priority),
                                        'text-indigo-600': item.priority === 'Cool'
                                    }">{{ item.priority || '-' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION (Laravel Paginated Style) -->
                <div class="p-6 bg-gray-50/50 border-t border-gray-50 flex items-center justify-between">
                    <p class="text-xs text-gray-400 font-bold uppercase">Showing {{ complaints.from }} to {{ complaints.to }} of {{ complaints.total }} Results</p>
                    <div class="flex gap-2">
                        <template v-for="link in complaints.links" :key="link.label">
                            <button v-if="link.url" 
                                @click="router.get(link.url, {}, { preserveState: true })"
                                v-html="link.label"
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
                                class="px-4 py-2 rounded-xl border text-xs font-black transition-all">
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM MODAL (Same as previous, omitted logic remains identical for conciseness but fields added) -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[100] overflow-y-auto px-4 py-6 sm:px-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
            
            <div class="relative bg-white rounded-3xl shadow-2xl max-w-4xl mx-auto overflow-hidden animate-in fade-in zoom-in duration-300">
                <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100 bg-white sticky top-0 z-10">
                    <div class="flex items-center gap-4">
                        <button @click="closeModal" class="p-2 hover:bg-gray-100 rounded-full transition-colors"><X class="w-6 h-6 text-gray-400" /></button>
                        <div>
                            <h3 class="text-xl font-black text-gray-900">COMPLAINT Form</h3>
                            <p class="text-[10px] text-indigo-400 uppercase tracking-widest font-bold">New Ticket Entry</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="px-6 py-2 border-2 border-indigo-50 shadow-sm text-indigo-600 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-50 transition-all active:scale-95">Cancel</button>
                </div>

                <div class="p-10 space-y-10 max-h-[75vh] overflow-y-auto bg-gray-50/30 custom-scrollbar">
                    <!-- SOURCE SELECTOR -->
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">SOURCE <span class="text-red-400">*</span></label>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                            <button v-for="s in sources" :key="s" @click="form.source = s" 
                                :class="form.source === s ? 'bg-purple-700 text-white border-purple-700 shadow-lg shadow-purple-200' : 'bg-white text-gray-400 border-gray-100 hover:border-purple-200 hover:text-purple-400'"
                                class="px-2 py-4 border rounded-2xl text-[10px] font-black transition-all uppercase tracking-tighter">
                                {{ s }}
                            </button>
                        </div>
                    </div>

                    <!-- MAIN GRID -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                        <!-- TANGGAL COMPLAINT -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tanggal Complaint <span class="text-red-400">*</span></label>
                            <div class="relative group">
                                <input type="date" v-model="form.tanggal_complaint" class="w-full pl-5 pr-12 py-3.5 border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all font-bold text-sm" />
                                <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300 group-focus-within:text-purple-500 transition-colors pointer-events-none" />
                            </div>
                        </div>

                        <!-- TANGGAL ORDER -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tanggal Order <span class="text-red-400">*</span></label>
                            <div class="relative group">
                                <input type="date" v-model="form.tanggal_order" class="w-full pl-5 pr-12 py-3.5 border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all font-bold text-sm" />
                                <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300 group-focus-within:text-purple-500 transition-colors pointer-events-none" />
                            </div>
                        </div>

                        <!-- JAM -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Jam Customer Complaint <span class="text-red-400">*</span></label>
                            <div class="relative group">
                                <input type="time" step="1" v-model="form.jam_customer_complaint" class="w-full pl-5 pr-12 py-3.5 border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all font-bold text-sm" />
                                <Clock class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300 group-focus-within:text-purple-500 transition-colors pointer-events-none" />
                            </div>
                        </div>

                        <!-- BRAND -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">BRAND <span class="text-red-400">*</span></label>
                            <select v-model="form.brand" class="w-full py-3.5 px-5 border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all font-bold text-sm appearance-none bg-white">
                                <option value="" disabled>SELECT BRAND</option>
                                <option>PARENTY STORE</option>
                                <option>MAKUKU</option>
                            </select>
                        </div>

                        <!-- NOMOR PESANAN -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nomor Pesanan <span class="text-red-400">*</span></label>
                            <input type="text" v-model="form.order_id" placeholder="12345678" class="w-full px-5 py-3.5 border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all font-bold text-sm" />
                        </div>

                        <!-- NO RESI -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">No Resi <span class="text-red-400">*</span></label>
                            <input type="text" v-model="form.resi" placeholder="12345678" class="w-full px-5 py-3.5 border-gray-100 rounded-2xl focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 transition-all font-bold text-sm text-purple-600" />
                        </div>
                    </div>

                    <!-- VALUE FIELD -->
                    <div class="space-y-2 max-w-sm">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Value Product <span class="text-red-400">*</span></label>
                        <div class="flex items-center border-2 border-indigo-50 rounded-2xl bg-white overflow-hidden focus-within:ring-4 focus-within:ring-purple-500/10 focus-within:border-purple-500 transition-all">
                            <div class="px-5 text-gray-400 font-black text-xs border-r border-indigo-50">Rp</div>
                            <input type="number" v-model="form.value_of_product" class="flex-1 border-none focus:ring-0 py-4 pr-2 font-black text-indigo-600" />
                            <button type="button" class="p-4 text-gray-300 hover:text-purple-600 transition-colors" @click="form.value_of_product--"><Minus class="w-4 h-4" stroke-width="3" /></button>
                            <button type="button" class="p-4 text-gray-300 hover:text-purple-600 transition-colors" @click="form.value_of_product++"><Plus class="w-4 h-4" stroke-width="3" /></button>
                        </div>
                    </div>

                    <!-- BY SELECTOR (Grid) -->
                    <div class="space-y-4">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">By <span class="text-red-400">*</span></label>
                        <div class="grid grid-cols-3 md:grid-cols-6 lg:grid-cols-8 gap-1.5">
                            <button v-for="o in causeByOptions" :key="o" @click="form.cause_by = o" 
                                :class="form.cause_by === o ? 'bg-purple-700 text-white border-purple-700 shadow-md transform scale-105 z-10' : 'bg-white text-gray-300 border-gray-50 hover:bg-gray-50 hover:text-gray-500'"
                                class="px-1 py-3 border rounded-lg text-[9px] font-black transition-all uppercase tracking-tighter">
                                {{ o }}
                            </button>
                        </div>
                    </div>

                    <div class="pt-10 flex justify-end">
                        <button @click="submitForm" class="px-16 py-5 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-2xl shadow-indigo-100 hover:bg-indigo-700 transform hover:-translate-y-1 transition-all active:scale-95 flex items-center gap-3">
                            Save Complaint <ChevronRight class="w-4 h-4" stroke-width="3" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #CBD5E0; }

input:focus, select:focus, textarea:focus { outline: none !important; }
</style>
