<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { AlertTriangle, CheckCircle2, ChevronLeft, ChevronRight, Database, FileCheck2, PencilLine, Plus, Search, Trash2, Upload, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedOrderTrackingRgoEntry {
    id: number;
    order_id: string;
    notes: string | null;
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
    orderTrackingRgoEntries: Paginator<ManagedOrderTrackingRgoEntry>;
    filters: { search?: string | null; status?: string | null };
    metrics: { total: number; active: number; inactive: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tracking RGO List', href: '/order-tracking-rgo-entries' },
];
const page = usePage<SharedData>();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeEntry = ref<ManagedOrderTrackingRgoEntry | null>(null);

const canCreateEntries = computed(() => page.props.auth.can.create_order_tracking_rgo_entries);
const canUpdateEntries = computed(() => page.props.auth.can.update_order_tracking_rgo_entries);
const canDeleteEntries = computed(() => page.props.auth.can.delete_order_tracking_rgo_entries);
const canImportEntries = computed(() => page.props.auth.can.import_order_tracking_rgo_entries);

const isImportOpen = ref(false);
const importFile = ref<File | null>(null);
const importFileInput = ref<HTMLInputElement | null>(null);
const importResult = computed(() => page.props.flash?.import_result ?? null);
const importForm = useForm({ file: null as File | null });

const openImportModal = () => {
    importFile.value = null;
    importForm.clearErrors();
    isImportOpen.value = true;
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    importFile.value = target.files?.[0] ?? null;
};

const submitImport = () => {
    if (!importFile.value) return;
    importForm.file = importFile.value;
    importForm.post(route('order-tracking-rgo-entries.import'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isImportOpen.value = false;
            importFile.value = null;
            if (importFileInput.value) importFileInput.value.value = '';
        },
    });
};

const createForm = useForm({ order_id: '', notes: '', is_active: true });
const editForm = useForm({ order_id: '', notes: '', is_active: true });
const deleteForm = useForm({});

const pageData = computed(() => props.orderTrackingRgoEntries);
const rows = computed(() => props.orderTrackingRgoEntries.data ?? []);
const paginationLinks = computed(() => props.orderTrackingRgoEntries.links?.filter((link) => link.url) ?? []);
const summaryCards = computed(() => [
    { label: 'Total Order ID', value: props.metrics.total, icon: Database, tone: 'bg-[var(--app-primary)] text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]' },
    { label: 'Active', value: props.metrics.active, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    { label: 'Inactive', value: props.metrics.inactive, icon: XCircle, tone: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' },
]);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(
        route('order-tracking-rgo-entries.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== 'All' ? statusFilter.value : undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace }
    );
};

watch(search, debounce(() => visitIndex({ page: 1 }), 350));
watch(statusFilter, () => visitIndex({ page: 1 }, false));

const resetCreateForm = () => {
    createForm.defaults({ order_id: '', notes: '', is_active: true });
    createForm.reset();
    createForm.clearErrors();
};

const openCreateModal = () => {
    resetCreateForm();
    isCreateOpen.value = true;
};

const openEditModal = (entry: ManagedOrderTrackingRgoEntry) => {
    activeEntry.value = entry;
    editForm.defaults({
        order_id: entry.order_id,
        notes: entry.notes || '',
        is_active: entry.is_active,
    });
    editForm.reset();
    editForm.clearErrors();
    isEditOpen.value = true;
};

const openDeleteModal = (entry: ManagedOrderTrackingRgoEntry) => {
    activeEntry.value = entry;
    deleteForm.clearErrors();
    isDeleteOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteOpen.value = false;
    activeEntry.value = null;
    deleteForm.clearErrors();
};

const submitCreate = () =>
    createForm.post(route('order-tracking-rgo-entries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            resetCreateForm();
        },
    });

const submitEdit = () => {
    if (!activeEntry.value) return;

    editForm.put(route('order-tracking-rgo-entries.update', activeEntry.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditOpen.value = false;
            activeEntry.value = null;
            editForm.clearErrors();
        },
    });
};

const submitDelete = () => {
    if (!activeEntry.value) return;

    deleteForm.delete(route('order-tracking-rgo-entries.destroy', activeEntry.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const statusBadgeClass = (active: boolean) =>
    active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tracking RGO List" />

        <div class="space-y-4">
            <div class="mx-auto max-w-7xl space-y-4">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-4 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-5 py-4 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Automation Source</p>
                            <h1 class="mt-1 text-xl font-black tracking-tight">Tracking RGO List</h1>
                            <p class="mt-1 text-xs font-medium text-slate-500">
                                Kelola daftar Order ID yang harus memicu automation track menjadi Sudah diRGO.
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <Button
                                v-if="canImportEntries"
                                type="button"
                                size="sm"
                                class="h-9 rounded-xl border border-slate-300 bg-white px-4 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50"
                                @click="openImportModal"
                            >
                                <Upload class="h-3.5 w-3.5" />
                                Import RGO
                            </Button>
                            <Button
                                v-if="canCreateEntries"
                                type="button"
                                size="sm"
                                class="h-9 rounded-xl bg-[var(--app-primary)] px-5 text-xs font-bold text-white shadow-lg hover:bg-[var(--app-primary-dark)]"
                                @click="openCreateModal"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                Tambah Order ID
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
                            <h2 class="text-base font-black text-slate-900 uppercase tracking-wide">RGO Reference List</h2>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:flex lg:justify-end">
                            <div class="relative lg:w-64">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="h-9 pl-9 text-xs" placeholder="Cari order ID / notes..." />
                            </div>

                            <select v-model="statusFilter" class="h-9 min-w-[140px] rounded-xl border border-input bg-background px-3 text-xs font-bold text-slate-600 shadow-sm outline-none transition hover:border-slate-300">
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
                                        <th class="px-4 py-3">Order ID</th>
                                        <th class="px-4 py-3">Notes</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 bg-white">
                                    <tr v-for="entry in rows" :key="entry.id" class="transition hover:bg-slate-50/50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                                    <Database class="h-3.5 w-3.5" />
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900 leading-tight">{{ entry.order_id }}</p>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">ID: #{{ entry.id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ entry.notes || '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm" :class="statusBadgeClass(entry.is_active)">
                                                {{ entry.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-1.5">
                                                <Button v-if="canUpdateEntries" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-[var(--app-primary)] hover:bg-blue-50" @click="openEditModal(entry)">
                                                    <PencilLine class="h-3.5 w-3.5" />
                                                </Button>
                                                <Button v-if="canDeleteEntries" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50" @click="openDeleteModal(entry)">
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="rows.length === 0">
                                        <td colspan="4" class="px-4 py-10 text-center font-bold text-slate-400">Tidak ada data ditemukan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-[11px] font-bold text-slate-400">Showing {{ pageData.from || 0 }}-{{ pageData.to || 0 }} of {{ pageData.total }} entries</p>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button v-for="link in paginationLinks" :key="link.label + String(link.url)" type="button" variant="outline" size="sm" class="rounded-xl" :class="link.active ? 'border-slate-900 bg-slate-900 text-white hover:bg-slate-800 hover:text-white' : ''" @click="link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })">
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
            <DialogContent class="max-w-xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-[#EEF2FF] px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-slate-900">Tambah Order ID RGO</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-500">Masukkan Order ID yang harus ditandai Sudah diRGO saat muncul di Order Tracking.</DialogDescription>
                    </DialogHeader>
                </div>

                <form class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitCreate">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="create-order-id" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Order ID</Label>
                            <Input id="create-order-id" v-model="createForm.order_id" placeholder="Contoh: 1234567890" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.order_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-notes" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Notes</Label>
                            <Input id="create-notes" v-model="createForm.notes" placeholder="Catatan opsional" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.notes" />
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                            <input v-model="createForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                            <span class="text-sm font-semibold text-slate-600">Data aktif dan siap dipakai di automation Order Tracking</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isCreateOpen = false">Cancel</Button>
                        <Button :disabled="createForm.processing" class="h-11 rounded-xl bg-[var(--app-primary)] px-6 font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-[var(--app-primary-dark)]">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Creating...' : 'Create Entry' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-slate-900 px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-white">Edit Entry RGO</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-400">Perbarui Order ID atau status aktif agar automation tetap akurat.</DialogDescription>
                    </DialogHeader>
                </div>

                <form v-if="activeEntry" class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitEdit">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="edit-order-id" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Order ID</Label>
                            <Input id="edit-order-id" v-model="editForm.order_id" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.order_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-notes" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Notes</Label>
                            <Input id="edit-notes" v-model="editForm.notes" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.notes" />
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                            <input v-model="editForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                            <span class="text-sm font-semibold text-slate-600">Entry aktif</span>
                        </div>
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
            <DialogContent class="max-w-md overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(244,63,94,0.15)]">
                <div class="bg-rose-50 px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-rose-950">Hapus Entry RGO</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">Tindakan ini tidak bisa dibatalkan secara instan.</DialogDescription>
                    </DialogHeader>
                </div>

                <div class="space-y-6 bg-white px-7 py-7">
                    <div v-if="activeEntry" class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5">
                        <p class="text-sm font-bold uppercase tracking-tight text-rose-900">Konfirmasi Order ID</p>
                        <p class="mt-1 text-lg font-black text-rose-600">{{ activeEntry.order_id }}</p>
                    </div>

                    <InputError :message="deleteForm.errors.delete" />

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="closeDeleteModal">Cancel</Button>
                        <Button type="button" class="h-11 rounded-xl bg-rose-600 px-6 font-bold text-white shadow-lg shadow-rose-500/20 hover:bg-rose-700" :disabled="deleteForm.processing" @click="submitDelete">
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete Entry' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Import Result Alert -->
        <div
            v-if="importResult"
            class="fixed bottom-6 right-6 z-50 w-80 rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl"
        >
            <div class="mb-3 flex items-center gap-2">
                <FileCheck2 class="h-5 w-5 text-emerald-500" />
                <p class="text-sm font-black text-slate-900">Hasil Import RGO</p>
            </div>
            <div class="space-y-1 text-xs font-semibold text-slate-600">
                <p v-if="importResult.created" class="text-emerald-600">✓ {{ importResult.created }} order ID baru ditambahkan</p>
                <p v-if="importResult.updated" class="text-blue-600">↻ {{ importResult.updated }} order ID diperbarui (duplikat)</p>
                <p v-if="importResult.failed" class="text-rose-600">✗ {{ importResult.failed }} baris gagal</p>
            </div>
            <ul v-if="importResult.errors?.length" class="mt-2 max-h-28 overflow-y-auto space-y-0.5">
                <li v-for="err in importResult.errors" :key="err" class="text-[10px] text-rose-500">{{ err }}</li>
            </ul>
        </div>

        <!-- Import Modal -->
        <Dialog v-model:open="isImportOpen">
            <DialogContent class="max-w-md overflow-hidden rounded-[28px] border-0 p-0 shadow-2xl">
                <div class="bg-gradient-to-br from-blue-50 to-white px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-2xl font-black text-slate-900">Import RGO</DialogTitle>
                        <DialogDescription class="mt-1 text-sm font-medium text-slate-500">
                            Upload file <strong>Excel (.xlsx)</strong> atau CSV berisi kolom <strong>no, order_id, notes, is_active</strong>. Duplikat order_id akan diperbarui & automation tracking di-recompute.
                        </DialogDescription>
                    </DialogHeader>
                </div>

                <div class="space-y-5 bg-white px-7 py-6">
                    <div class="flex items-center justify-between rounded-xl border border-dashed border-blue-200 bg-blue-50/40 px-4 py-3">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <AlertTriangle class="h-4 w-4 text-amber-500" />
                            Download template sebelum mengisi data
                        </div>
                        <a
                            :href="route('order-tracking-rgo-entries.template')"
                            class="rounded-lg bg-[var(--app-primary)] px-3 py-1.5 text-xs font-bold text-white hover:bg-[var(--app-primary-dark)]"
                        >
                            Download
                        </a>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-xs font-black uppercase tracking-wide text-slate-700">File Excel / CSV *</Label>
                        <input
                            ref="importFileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv,.txt"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-[var(--app-primary)] file:px-3 file:py-1 file:text-xs file:font-bold file:text-white"
                            @change="onFileChange"
                        />
                        <InputError :message="importForm.errors.file" />
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <Button
                            type="button"
                            variant="ghost"
                            class="h-10 rounded-xl px-5 text-sm font-bold text-slate-500"
                            @click="isImportOpen = false"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            :disabled="!importFile || importForm.processing"
                            class="h-10 rounded-xl bg-[var(--app-primary)] px-5 text-sm font-bold text-white shadow-md disabled:opacity-40"
                            @click="submitImport"
                        >
                            <Upload class="mr-1.5 h-4 w-4" />
                            {{ importForm.processing ? 'Uploading...' : 'Import Sekarang' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
