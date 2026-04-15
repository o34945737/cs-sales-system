<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Boxes, CheckCircle2, ChevronLeft, ChevronRight, PackageSearch, PencilLine, Plus, Search, Trash2, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedSkuCode {
    id: number;
    sku: string;
    product_name: string;
    brand: string | null;
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
const page = usePage<SharedData>();
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
    default_value_of_product: '',
    is_active: true,
});
const editForm = useForm({
    sku: '',
    product_name: '',
    brand: '',
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
    createForm.defaults({ sku: '', product_name: '', brand: '', default_value_of_product: '', is_active: true });
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
        default_value_of_product: toFormValue(skuCode.default_value_of_product),
        is_active: skuCode.is_active,
    });
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

const normalizePayload = (data: typeof createForm.data) => ({
    ...data,
    brand: data.brand || null,
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
        .put(route('sku-codes.update', activeSkuCode.value.id), {
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
    deleteForm.delete(route('sku-codes.destroy', activeSkuCode.value.id), { preserveScroll: true, onSuccess: () => closeDeleteModal() });
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

        <div class="space-y-6">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="app-table-shell overflow-hidden">
                    <div
                        class="grid gap-6 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-6 py-7 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]"
                    >
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Master Data</p>
                            <h1 class="mt-3 text-3xl font-extrabold tracking-tight">SKU Code Directory</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                                Kelola daftar SKU agar complaint bisa mengambil SKU, product name, brand, dan value default dari master yang sama.
                            </p>
                        </div>

                        <div v-if="canCreateSkuCodes" class="flex items-start justify-end">
                            <Button
                                type="button"
                                size="lg"
                                class="rounded-2xl bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)] hover:bg-[var(--app-primary-dark)]"
                                @click="openCreateModal"
                            >
                                <Plus class="h-4 w-4" />
                                Tambah SKU Code
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-4 px-6 py-5 md:grid-cols-3">
                        <article v-for="card in summaryCards" :key="card.label" class="rounded-3xl p-5 shadow-sm" :class="card.tone">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] opacity-70">{{ card.label }}</p>
                                    <p class="mt-4 text-3xl font-semibold">{{ card.value }}</p>
                                </div>
                                <component :is="card.icon" class="h-5 w-5 opacity-80" />
                            </div>
                        </article>
                    </div>
                </section>

                <section class="app-table-shell p-6">
                    <div class="grid gap-5 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] xl:items-end">
                        <div class="min-w-0 max-w-xl">
                            <h2 class="text-xl font-semibold text-slate-900">SKU List</h2>
                            <p class="mt-1 max-w-md text-sm leading-6 text-slate-500">
                                Master ini disiapkan untuk flow complaint agar tim tidak perlu input ulang detail SKU secara manual di setiap case.
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,0.8fr)]">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="pl-10" placeholder="Cari SKU, product, atau brand..." />
                            </div>

                            <select
                                v-model="statusFilter"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none"
                            >
                                <option value="All">Semua status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50/90">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-5 py-4 font-medium">SKU</th>
                                        <th class="px-5 py-4 font-medium">Product</th>
                                        <th class="px-5 py-4 font-medium">Brand</th>
                                        <th class="px-5 py-4 font-medium">Default Value</th>
                                        <th class="px-5 py-4 font-medium">Status</th>
                                        <th class="px-5 py-4 text-right font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="skuCode in skuCodeRows" :key="skuCode.id" class="transition hover:bg-slate-50/70">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                                    <Boxes class="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <p class="font-medium text-slate-900">{{ skuCode.sku }}</p>
                                                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Master SKU</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700">{{ skuCode.product_name }}</td>
                                        <td class="px-5 py-4 text-slate-500">{{ skuCode.brand || '-' }}</td>
                                        <td class="px-5 py-4 text-slate-500">{{ formatCurrency(skuCode.default_value_of_product) }}</td>
                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                                :class="statusBadgeClass(skuCode.is_active)"
                                            >
                                                {{ skuCode.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2">
                                                <Button
                                                    v-if="canUpdateSkuCodes"
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    class="rounded-xl"
                                                    @click="openEditModal(skuCode)"
                                                >
                                                    <PencilLine class="h-4 w-4" />
                                                    Edit
                                                </Button>
                                                <Button
                                                    v-if="canDeleteSkuCodes"
                                                    type="button"
                                                    variant="destructive"
                                                    size="sm"
                                                    class="rounded-xl"
                                                    @click="openDeleteModal(skuCode)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    Delete
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="skuCodeRows.length === 0">
                                        <td colspan="6" class="px-5 py-14 text-center">
                                            <div class="mx-auto max-w-sm space-y-2">
                                                <div
                                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500"
                                                >
                                                    <PackageSearch class="h-5 w-5" />
                                                </div>
                                                <p class="font-medium text-slate-900">Tidak ada SKU yang cocok</p>
                                                <p class="text-sm text-slate-500">Coba ubah pencarian atau status filter untuk melihat data lain.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-sm text-slate-500">
                            Menampilkan {{ skuCodesPage.from || 0 }} - {{ skuCodesPage.to || 0 }} dari {{ skuCodesPage.total }} SKU
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
            <DialogContent class="max-w-xl rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-[linear-gradient(135deg,_#eef4ff_0%,_#dbeafe_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Tambah SKU Code</DialogTitle>
                            <DialogDescription class="text-slate-500">Masukkan SKU baru agar siap dipakai di flow complaint.</DialogDescription>
                        </DialogHeader>
                    </div>

                    <form class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitCreate">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="create-sku">SKU</Label>
                                <Input id="create-sku" v-model="createForm.sku" placeholder="Contoh: PBP246" />
                                <InputError :message="createForm.errors.sku" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="create-brand">Brand</Label>
                                <select
                                    id="create-brand"
                                    v-model="createForm.brand"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none"
                                >
                                    <option value="">Tanpa brand</option>
                                    <option v-for="option in brandOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <InputError :message="createForm.errors.brand" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-product-name">Product Name</Label>
                            <Input id="create-product-name" v-model="createForm.product_name" placeholder="Nama produk lengkap" />
                            <InputError :message="createForm.errors.product_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-default-value">Default Value of Product</Label>
                            <Input id="create-default-value" v-model="createForm.default_value_of_product" type="number" min="0" placeholder="0" />
                            <InputError :message="createForm.errors.default_value_of_product" />
                        </div>

                        <label class="flex items-center gap-3 text-sm text-slate-600">
                            <input
                                v-model="createForm.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                            SKU aktif dan siap dipakai di complaint
                        </label>

                        <DialogFooter class="border-t border-slate-100 pt-5">
                            <Button type="button" variant="outline" class="rounded-xl" @click="isCreateOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing" class="rounded-xl">
                                <Plus class="h-4 w-4" />
                                {{ createForm.processing ? 'Creating...' : 'Create SKU' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-xl rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-[linear-gradient(135deg,_#0f172a_0%,_#1e293b_100%)] px-6 py-6 text-white">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Edit SKU Code</DialogTitle>
                            <DialogDescription class="text-slate-200">Perbarui detail SKU yang dipakai flow complaint.</DialogDescription>
                        </DialogHeader>
                    </div>

                    <form v-if="activeSkuCode" class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitEdit">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="edit-sku">SKU</Label>
                                <Input id="edit-sku" v-model="editForm.sku" />
                                <InputError :message="editForm.errors.sku" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="edit-brand">Brand</Label>
                                <select
                                    id="edit-brand"
                                    v-model="editForm.brand"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none"
                                >
                                    <option value="">Tanpa brand</option>
                                    <option v-for="option in brandOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <InputError :message="editForm.errors.brand" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-product-name">Product Name</Label>
                            <Input id="edit-product-name" v-model="editForm.product_name" />
                            <InputError :message="editForm.errors.product_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-default-value">Default Value of Product</Label>
                            <Input id="edit-default-value" v-model="editForm.default_value_of_product" type="number" min="0" />
                            <InputError :message="editForm.errors.default_value_of_product" />
                        </div>

                        <label class="flex items-center gap-3 text-sm text-slate-600">
                            <input
                                v-model="editForm.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                            SKU aktif
                        </label>

                        <DialogFooter class="border-t border-slate-100 pt-5">
                            <Button type="button" variant="outline" class="rounded-xl" @click="isEditOpen = false">Cancel</Button>
                            <Button :disabled="editForm.processing" class="rounded-xl">
                                <PencilLine class="h-4 w-4" />
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-lg rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-rose-50 px-6 py-6 text-rose-700">
                        <DialogHeader>
                            <DialogTitle class="text-2xl text-rose-700">Hapus SKU Code</DialogTitle>
                            <DialogDescription class="text-rose-600">Pastikan SKU ini memang tidak lagi dipakai di flow complaint.</DialogDescription>
                        </DialogHeader>
                    </div>

                    <div v-if="activeSkuCode" class="space-y-5 bg-white px-6 py-6">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="font-medium text-slate-900">{{ activeSkuCode.sku }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ activeSkuCode.product_name }}</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-slate-400">{{ activeSkuCode.brand || 'Tanpa brand' }}</p>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" class="rounded-xl" @click="closeDeleteModal">Cancel</Button>
                            <Button type="button" variant="destructive" class="rounded-xl" :disabled="deleteForm.processing" @click="submitDelete">
                                <Trash2 class="h-4 w-4" />
                                {{ deleteForm.processing ? 'Deleting...' : 'Delete SKU' }}
                            </Button>
                        </DialogFooter>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
