<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
// @ts-ignore — no @types/lodash installed
import debounce from 'lodash/debounce';
import { Boxes, CheckCircle2, ChevronLeft, ChevronRight, PencilLine, Plus, Search, Tag, Trash2, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedSkuCode {
    id: number;
    sku: string;
    product_name: string;
    brand: string | null;
    available_qty: number;
    status_qty: string | null;
    default_value_of_product: number | null;
    is_active: boolean;
    created_at: string | null;
}

interface PaginatorLink {
    active: boolean;
    label: string;
    url: string | null;
}
interface Paginator<T> {
    current_page: number;
    data: T[];
    from: number | null;
    last_page: number;
    links: PaginatorLink[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

const props = defineProps<{
    skuCodes: Paginator<ManagedSkuCode>;
    brandOptions: string[];
    filters: { search?: string | null; status?: string | null };
    metrics: { total: number; active: number; inactive: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master SKU Codes', href: '/sku-codes' },
];
const page = usePage<SharedData & Record<string, unknown>>();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeSkuCode = ref<ManagedSkuCode | null>(null);

const canCreateSkuCodes = computed(() => page.props.auth.can.create_sku_codes);
const canUpdateSkuCodes = computed(() => page.props.auth.can.update_sku_codes);
const canDeleteSkuCodes = computed(() => page.props.auth.can.delete_sku_codes);

const createForm = useForm({
    sku: '',
    product_name: '',
    brand: '',
    available_qty: '',
    status_qty: '',
    default_value_of_product: '',
    is_active: true,
});
const editForm = useForm({
    sku: '',
    product_name: '',
    brand: '',
    available_qty: '',
    status_qty: '',
    default_value_of_product: '',
    is_active: true,
});
const deleteForm = useForm({});

const skuCodesPage = computed(() => props.skuCodes);
const skuCodeRows = computed(() => props.skuCodes.data ?? []);
const brandOptions = computed(() => props.brandOptions ?? []);
const paginationLinks = computed(() => props.skuCodes.links?.filter((link) => link.url) ?? []);
const summaryCards = computed(() => [
    {
        label: 'Total SKU',
        value: props.metrics.total,
        icon: Boxes,
        tone: 'bg-[var(--app-primary)] text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]',
    },
    { label: 'Active', value: props.metrics.active, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    { label: 'Inactive', value: props.metrics.inactive, icon: XCircle, tone: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' },
]);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(
        route('sku-codes.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== 'All' ? statusFilter.value : undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace },
    );
};

watch(
    search,
    debounce(() => visitIndex({ page: 1 }), 350),
);
watch(statusFilter, () => visitIndex({ page: 1 }, false));

const toFormValue = (value: number | null | '') => (value === null || value === '' ? '' : String(value));

const resetCreateForm = () => {
    createForm.defaults({ sku: '', product_name: '', brand: '', available_qty: '', status_qty: '', default_value_of_product: '', is_active: true } as any);
    createForm.reset();
    createForm.clearErrors();
};

const openCreateModal = () => {
    resetCreateForm();
    isCreateOpen.value = true;
};

const openEditModal = (skuCode: ManagedSkuCode) => {
    activeSkuCode.value = skuCode;
    editForm.defaults({
        sku: skuCode.sku,
        product_name: skuCode.product_name,
        brand: skuCode.brand || '',
        available_qty: skuCode.available_qty != null ? String(skuCode.available_qty) : '',
        status_qty: skuCode.status_qty || '',
        default_value_of_product: toFormValue(skuCode.default_value_of_product),
        is_active: skuCode.is_active,
    } as any);
    editForm.reset();
    editForm.clearErrors();
    isEditOpen.value = true;
};

const openDeleteModal = (skuCode: ManagedSkuCode) => {
    activeSkuCode.value = skuCode;
    deleteForm.clearErrors();
    isDeleteOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteOpen.value = false;
    activeSkuCode.value = null;
    deleteForm.clearErrors();
};

const normalizePayload = (data: ReturnType<typeof createForm.data>) => ({
    ...data,
    brand: data.brand || null,
    available_qty: data.available_qty === '' ? 0 : Number(data.available_qty),
    status_qty: data.status_qty || null,
    default_value_of_product: data.default_value_of_product === '' ? null : Number(data.default_value_of_product),
});

const submitCreate = () =>
    createForm
        .transform((data) => normalizePayload(data))
        .post(route('sku-codes.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isCreateOpen.value = false;
                resetCreateForm();
            },
        });

const submitEdit = () => {
    if (!activeSkuCode.value) return;

    editForm
        .transform((data) => normalizePayload(data))
        .put(route('sku-codes.update', { id: activeSkuCode.value.id }), {
            preserveScroll: true,
            onSuccess: () => {
                isEditOpen.value = false;
                activeSkuCode.value = null;
                editForm.clearErrors();
            },
        });
};

const submitDelete = () => {
    if (!activeSkuCode.value) return;
    deleteForm.delete(route('sku-codes.destroy', { id: activeSkuCode.value.id }), { preserveScroll: true, onSuccess: () => closeDeleteModal() });
};

const formatCurrency = (value: number | null) => {
    if (value === null) return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
};

const statusBadgeClass = (active: boolean) =>
    active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Master SKU Codes" />

        <div class="space-y-4">
            <div class="mx-auto max-w-7xl space-y-4">
                <section class="app-table-shell overflow-hidden">
                    <div
                        class="grid gap-4 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-5 py-4 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]"
                    >
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Master Data</p>
                            <h1 class="mt-1 text-xl font-black tracking-tight">SKU Code Directory</h1>
                            <p class="mt-1 text-xs font-medium text-slate-500">
                                Kelola detail SKU, brand, dan value produk untuk konsistensi pelaporan operasional.
                            </p>
                        </div>
                        <div v-if="canCreateSkuCodes" class="flex items-center justify-end">
                            <Button
                                type="button"
                                size="sm"
                                class="h-9 rounded-xl bg-[var(--app-primary)] px-5 text-xs font-bold text-white shadow-lg hover:bg-[var(--app-primary-dark)]"
                                @click="openCreateModal"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                Tambah SKU
                            </Button>
                        </div>
                    </div>
                    <div class="grid gap-3.5 px-4 py-3.5 md:grid-cols-3">
                        <article v-for="card in summaryCards" :key="card.label" class="rounded-xl p-3.5 shadow-sm" :class="card.tone">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-[0.2em] opacity-70">{{ card.label }}</p>
                                    <p class="mt-1.5 text-xl font-black">{{ card.value }}</p>
                                </div>
                                <component :is="card.icon" class="h-4 w-4 opacity-80" />
                            </div>
                        </article>
                    </div>
                </section>
                <section class="app-table-shell p-5">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] xl:items-center">
                        <div>
                            <h2 class="text-base font-black text-slate-900 uppercase tracking-wide">SKU List</h2>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:flex lg:justify-end">
                            <div class="relative lg:w-64">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="h-9 pl-9 text-xs" placeholder="Cari SKU..." />
                            </div>
                            <select
                                v-model="statusFilter"
                                class="h-9 min-w-[140px] rounded-xl border border-input bg-background px-3 text-xs font-bold text-slate-600 shadow-sm outline-none transition hover:border-slate-300"
                            >
                                <option value="All">Semua status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-[13px]">
                                <thead class="bg-slate-50/50">
                                    <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        <th class="px-4 py-3">SKU & Product</th>
                                        <th class="px-4 py-3">Brand</th>
                                        <th class="px-4 py-3">Qty</th>
                                        <th class="px-4 py-3">Value</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 bg-white">
                                    <tr v-for="skuCode in skuCodeRows" :key="skuCode.id" class="transition hover:bg-slate-50/50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                                    <Boxes class="h-3.5 w-3.5" />
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900 leading-tight">{{ skuCode.sku }}</p>
                                                    <p class="max-w-[180px] truncate text-[10px] font-medium text-slate-500">{{ skuCode.product_name }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-600">{{ skuCode.brand || '-' }}</td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900">{{ skuCode.available_qty ?? 0 }}</p>
                                            <p class="text-[10px] font-medium text-slate-400 mt-0.5">{{ skuCode.status_qty || '-' }}</p>
                                        </td>
                                        <td class="px-4 py-3 font-bold text-slate-900">{{ formatCurrency(skuCode.default_value_of_product) }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm"
                                                :class="statusBadgeClass(skuCode.is_active)"
                                            >
                                                {{ skuCode.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <Button
                                                    v-if="canUpdateSkuCodes"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 rounded-lg text-slate-400 hover:text-[var(--app-primary)] hover:bg-blue-50"
                                                    @click="openEditModal(skuCode)"
                                                >
                                                    <PencilLine class="h-3.5 w-3.5" />
                                                </Button>
                                                <Button
                                                    v-if="canDeleteSkuCodes"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50"
                                                    @click="openDeleteModal(skuCode)"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="skuCodeRows.length === 0">
                                        <td colspan="6" class="px-4 py-10 text-center text-slate-400 font-bold">Tidak ada data ditemukan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-[11px] font-bold text-slate-400">
                            Showing {{ skuCodesPage.from || 0 }}-{{ skuCodesPage.to || 0 }} of {{ skuCodesPage.total }} entries
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                v-for="link in paginationLinks"
                                :key="link.label + String(link.url)"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="rounded-xl"
                                :class="link.active ? 'border-slate-900 bg-slate-900 text-white hover:bg-slate-800 hover:text-white' : ''"
                                @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })"
                            >
                                <ChevronLeft v-if="link.label.includes('Previous')" class="h-4 w-4" />
                                <ChevronRight v-else-if="link.label.includes('Next')" class="h-4 w-4" />
                                <span v-else v-html="link.label"></span>
                            </Button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <Dialog v-model:open="isCreateOpen">
            <DialogContent class="max-w-2xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-[#EEF2FF] px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-slate-900">Tambah SKU Code</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-500">Masukkan SKU baru agar siap dipakai di flow complaint operasional.</DialogDescription>
                    </DialogHeader>
                </div>

                <form class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitCreate">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="create-sku" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">SKU Code</Label>
                            <Input id="create-sku" v-model="createForm.sku" placeholder="Contoh: PBP246" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.sku" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-brand" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Brand</Label>
                            <select id="create-brand" v-model="createForm.brand" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-sm shadow-sm outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-100">
                                <option value="">Tanpa brand</option>
                                <option v-for="option in brandOptions" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <InputError :message="createForm.errors.brand" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-product-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Product Name</Label>
                        <Input id="create-product-name" v-model="createForm.product_name" placeholder="Nama produk lengkap dan deskriptif" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                        <InputError :message="createForm.errors.product_name" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="create-available-qty" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Available Qty</Label>
                            <Input id="create-available-qty" v-model="createForm.available_qty" type="number" min="0" placeholder="0" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.available_qty" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-status-qty" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Status Qty</Label>
                            <Input id="create-status-qty" v-model="createForm.status_qty" placeholder="Contoh: Normal / Kosong" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.status_qty" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-default-value" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Default Value of Product (IDR)</Label>
                        <Input id="create-default-value" v-model="createForm.default_value_of_product" type="number" min="0" placeholder="0" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                        <InputError :message="createForm.errors.default_value_of_product" />
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                        <input v-model="createForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                        <span class="text-sm font-semibold text-slate-600">SKU aktif dan siap dipakai di modul complaint</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isCreateOpen = false">Cancel</Button>
                        <Button :disabled="createForm.processing" class="h-11 rounded-xl bg-[var(--app-primary)] px-6 font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-[var(--app-primary-dark)]">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Creating...' : 'Create SKU' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-2xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-slate-900 px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-white">Edit SKU Code</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-400">Perbarui detail atau value SKU agar master data tetap sinkron.</DialogDescription>
                    </DialogHeader>
                </div>

                <form v-if="activeSkuCode" class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitEdit">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit-sku" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">SKU Code</Label>
                            <Input id="edit-sku" v-model="editForm.sku" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.sku" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-brand" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Brand</Label>
                            <select id="edit-brand" v-model="editForm.brand" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-sm shadow-sm outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-100">
                                <option value="">Tanpa brand</option>
                                <option v-for="option in brandOptions" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <InputError :message="editForm.errors.brand" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-product-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Product Name</Label>
                        <Input id="edit-product-name" v-model="editForm.product_name" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                        <InputError :message="editForm.errors.product_name" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit-available-qty" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Available Qty</Label>
                            <Input id="edit-available-qty" v-model="editForm.available_qty" type="number" min="0" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.available_qty" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-status-qty" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Status Qty</Label>
                            <Input id="edit-status-qty" v-model="editForm.status_qty" placeholder="Contoh: Normal / Kosong" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.status_qty" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-default-value" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Default Value of Product (IDR)</Label>
                        <Input id="edit-default-value" v-model="editForm.default_value_of_product" type="number" min="0" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                        <InputError :message="editForm.errors.default_value_of_product" />
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                        <input v-model="editForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                        <span class="text-sm font-semibold text-slate-600">SKU aktif</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isEditOpen = false">Cancel</Button>
                        <Button :disabled="editForm.processing" class="h-11 rounded-xl bg-[var(--app-primary)] px-6 font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-[var(--app-primary-dark)]">
                            <PencilLine class="mr-2 h-4 w-4" />
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-lg overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(244,63,94,0.15)]">
                <div class="bg-rose-50 px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-rose-950">Hapus SKU Code</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">Tindakan ini permanen. Pastikan SKU aman untuk dihapus.</DialogDescription>
                    </DialogHeader>
                </div>

                <div v-if="activeSkuCode" class="space-y-6 bg-white px-7 py-7">
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5">
                        <p class="text-sm font-bold text-rose-900 uppercase tracking-tight">Konfirmasi SKU</p>
                        <p class="mt-1 text-lg font-black text-rose-600">{{ activeSkuCode.sku }}</p>
                        <p class="mt-1 text-sm font-medium text-slate-500">{{ activeSkuCode.product_name }}</p>
                        <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white/70 px-3 py-1 ring-1 ring-rose-100">
                            <Tag class="h-3 w-3 text-rose-400" />
                            <span class="text-[10px] font-black uppercase text-rose-600">{{ activeSkuCode.brand || 'No Brand' }}</span>
                        </div>
                    </div>

                    <InputError :message="(deleteForm.errors as Record<string, string>).delete" />

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="closeDeleteModal">Cancel</Button>
                        <Button type="button" class="h-11 rounded-xl bg-rose-600 px-6 font-bold text-white shadow-lg shadow-rose-500/20 hover:bg-rose-700" :disabled="deleteForm.processing" @click="submitDelete">
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete SKU' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
