<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
// @ts-ignore - no @types/lodash installed
import debounce from 'lodash/debounce';
import { Boxes, ChevronLeft, ChevronRight, PencilLine, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedSkuCode {
    id: number;
    sku: string;
    product_name: string;
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
    filters: { search?: string | null };
    metrics: { total: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master SKU Codes', href: '/sku-codes' },
];

const page = usePage<SharedData & Record<string, unknown>>();
const search = ref(props.filters.search || '');
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
});

const editForm = useForm({
    sku: '',
    product_name: '',
});

const deleteForm = useForm({});

const skuCodesPage = computed(() => props.skuCodes);
const skuCodeRows = computed(() => props.skuCodes.data ?? []);
const paginationLinks = computed(() => props.skuCodes.links?.filter((link) => link.url) ?? []);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(
        route('sku-codes.index'),
        {
            search: search.value || undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace },
    );
};

watch(
    search,
    debounce(() => visitIndex({ page: 1 }), 350),
);

const resetCreateForm = () => {
    createForm.defaults({ sku: '', product_name: '' } as any);
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

const submitCreate = () =>
    createForm.post(route('sku-codes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            resetCreateForm();
        },
    });

const submitEdit = () => {
    if (!activeSkuCode.value) return;

    editForm.put(route('sku-codes.update', { id: activeSkuCode.value.id }), {
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

    deleteForm.delete(route('sku-codes.destroy', { id: activeSkuCode.value.id }), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
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
                                Kelola master SKU sesederhana mungkin agar mapping produk tetap rapi dan konsisten.
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

                    <div class="px-4 py-3.5">
                        <article class="rounded-xl bg-[var(--app-primary)] p-3.5 text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-[0.2em] opacity-70">Total SKU</p>
                                    <p class="mt-1.5 text-xl font-black">{{ props.metrics.total }}</p>
                                </div>
                                <Boxes class="h-4 w-4 opacity-80" />
                            </div>
                        </article>
                    </div>
                </section>

                <section class="app-table-shell p-5">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] xl:items-center">
                        <div>
                            <h2 class="text-base font-black uppercase tracking-wide text-slate-900">SKU List</h2>
                        </div>
                        <div class="flex justify-end">
                            <div class="relative lg:w-64">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="h-9 pl-9 text-xs" placeholder="Cari SKU..." />
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-[13px]">
                                <thead class="bg-slate-50/50">
                                    <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        <th class="px-4 py-3">SKU & Product</th>
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
                                                    <p class="leading-tight font-bold text-slate-900">{{ skuCode.sku }}</p>
                                                    <p class="max-w-[220px] truncate text-[10px] font-medium text-slate-500">{{ skuCode.product_name }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <Button
                                                    v-if="canUpdateSkuCodes"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 rounded-lg text-slate-400 hover:bg-blue-50 hover:text-[var(--app-primary)]"
                                                    @click="openEditModal(skuCode)"
                                                >
                                                    <PencilLine class="h-3.5 w-3.5" />
                                                </Button>
                                                <Button
                                                    v-if="canDeleteSkuCodes"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                                    @click="openDeleteModal(skuCode)"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="skuCodeRows.length === 0">
                                        <td colspan="2" class="px-4 py-10 text-center font-bold text-slate-400">Tidak ada data ditemukan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-[11px] font-bold text-slate-400">
                            Showing {{ skuCodesPage.from || 0 }}-{{ skuCodesPage.to || 0 }} of {{ skuCodesPage.total }} SKU
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
                        <DialogDescription class="mt-2 text-base font-medium text-slate-500">
                            Masukkan SKU dan nama produk agar siap dipakai di modul operasional.
                        </DialogDescription>
                    </DialogHeader>
                </div>

                <form class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitCreate">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="create-sku" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">SKU Code</Label>
                            <Input
                                id="create-sku"
                                v-model="createForm.sku"
                                placeholder="Contoh: PBP246"
                                class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white"
                            />
                            <InputError :message="createForm.errors.sku" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-product-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Product Name</Label>
                            <Input
                                id="create-product-name"
                                v-model="createForm.product_name"
                                placeholder="Nama produk lengkap dan deskriptif"
                                class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white"
                            />
                            <InputError :message="createForm.errors.product_name" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isCreateOpen = false">
                            Cancel
                        </Button>
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
                        <DialogDescription class="mt-2 text-base font-medium text-slate-400">
                            Perbarui SKU dan nama produk agar master data tetap sinkron.
                        </DialogDescription>
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
                            <Label for="edit-product-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Product Name</Label>
                            <Input
                                id="edit-product-name"
                                v-model="editForm.product_name"
                                class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white"
                            />
                            <InputError :message="editForm.errors.product_name" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isEditOpen = false">
                            Cancel
                        </Button>
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
                        <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">
                            Tindakan ini permanen. Pastikan SKU aman untuk dihapus.
                        </DialogDescription>
                    </DialogHeader>
                </div>

                <div v-if="activeSkuCode" class="space-y-6 bg-white px-7 py-7">
                    <div class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5">
                        <p class="text-sm font-bold uppercase tracking-tight text-rose-900">Konfirmasi SKU</p>
                        <p class="mt-1 text-lg font-black text-rose-600">{{ activeSkuCode.sku }}</p>
                        <p class="mt-1 text-sm font-medium text-slate-500">{{ activeSkuCode.product_name }}</p>
                    </div>

                    <InputError :message="(deleteForm.errors as Record<string, string>).delete" />

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="closeDeleteModal">
                            Cancel
                        </Button>
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
