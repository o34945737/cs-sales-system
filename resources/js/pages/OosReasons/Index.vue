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
import { Archive, CheckCircle2, ChevronLeft, ChevronRight, PencilLine, Plus, Search, Trash2, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedOosReason { id: number; name: string; is_active: boolean; created_at: string | null; }
interface PaginatorLink { active: boolean; label: string; url: string | null; }
interface Paginator<T> { current_page: number; data: T[]; from: number | null; last_page: number; links: PaginatorLink[]; path: string; per_page: number; to: number | null; total: number; }

const props = defineProps<{
    oosReasons: Paginator<ManagedOosReason>;
    filters: { search?: string | null; status?: string | null; };
    metrics: { total: number; active: number; inactive: number; };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'OOS Reasons', href: '/oos-reasons' }];
const page = usePage<SharedData>();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeReason = ref<ManagedOosReason | null>(null);

const canCreateReasons = computed(() => page.props.auth.can.create_oos_reasons);
const canUpdateReasons = computed(() => page.props.auth.can.update_oos_reasons);
const canDeleteReasons = computed(() => page.props.auth.can.delete_oos_reasons);

const createForm = useForm({ name: '', is_active: true });
const editForm = useForm({ name: '', is_active: true });
const deleteForm = useForm({});

const pageData = computed(() => props.oosReasons);
const rows = computed(() => props.oosReasons.data ?? []);
const paginationLinks = computed(() => props.oosReasons.links?.filter((link) => link.url) ?? []);
const summaryCards = computed(() => [
    { label: 'Total Reason', value: props.metrics.total, icon: Archive, tone: 'bg-[var(--app-primary)] text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]' },
    { label: 'Active', value: props.metrics.active, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    { label: 'Inactive', value: props.metrics.inactive, icon: XCircle, tone: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' },
]);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(route('oos-reasons.index'), {
        search: search.value || undefined,
        status: statusFilter.value !== 'All' ? statusFilter.value : undefined,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace });
};

watch(search, debounce(() => visitIndex({ page: 1 }), 350));
watch(statusFilter, () => visitIndex({ page: 1 }, false));

const resetCreateForm = () => {
    createForm.defaults({ name: '', is_active: true });
    createForm.reset();
    createForm.clearErrors();
};

const openCreateModal = () => {
    resetCreateForm();
    isCreateOpen.value = true;
};

const openEditModal = (reason: ManagedOosReason) => {
    activeReason.value = reason;
    editForm.defaults({ name: reason.name, is_active: reason.is_active });
    editForm.reset();
    editForm.clearErrors();
    isEditOpen.value = true;
};

const openDeleteModal = (reason: ManagedOosReason) => {
    activeReason.value = reason;
    deleteForm.clearErrors();
    isDeleteOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteOpen.value = false;
    activeReason.value = null;
    deleteForm.clearErrors();
};

const submitCreate = () => createForm.post(route('oos-reasons.store'), { preserveScroll: true, onSuccess: () => { isCreateOpen.value = false; resetCreateForm(); } });
const submitEdit = () => {
    if (!activeReason.value) return;
    editForm.put(route('oos-reasons.update', activeReason.value.id), { preserveScroll: true, onSuccess: () => { isEditOpen.value = false; activeReason.value = null; editForm.clearErrors(); } });
};
const submitDelete = () => {
    if (!activeReason.value) return;
    deleteForm.delete(route('oos-reasons.destroy', activeReason.value.id), { preserveScroll: true, onSuccess: () => closeDeleteModal() });
};

const formatDate = (value: string | null) => {
    if (!value) return '-';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parsed);
};

const statusBadgeClass = (active: boolean) => active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="OOS Reasons" />

        <div class="space-y-4">
            <div class="mx-auto max-w-7xl space-y-4">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-4 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-5 py-4 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Master Data</p>
                            <h1 class="mt-1 text-xl font-black tracking-tight">OOS Reason Directory</h1>
                            <p class="mt-1 text-xs font-medium text-slate-500">
                                Kelola daftar alasan OOS untuk standarisasi pencatatan stok kosong.
                            </p>
                        </div>
 
                        <div v-if="canCreateReasons" class="flex items-center justify-end">
                            <Button type="button" size="sm" class="h-9 rounded-xl bg-[var(--app-primary)] px-5 text-xs font-bold text-white shadow-lg hover:bg-[var(--app-primary-dark)]" @click="openCreateModal">
                                <Plus class="h-3.5 w-3.5" />
                                Tambah Reason
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
                            <h2 class="text-base font-black text-slate-900 uppercase tracking-wide">Reason List</h2>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:flex lg:justify-end">
                            <div class="relative lg:w-64">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="h-9 pl-9 text-xs" placeholder="Cari..." />
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
                                        <th class="px-4 py-3">OOS Reason</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 bg-white">
                                    <tr v-for="reason in rows" :key="reason.id" class="transition hover:bg-slate-50/50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                                    <Archive class="h-3.5 w-3.5" />
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900 leading-tight">{{ reason.name }}</p>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">ID: #{{ reason.id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm" :class="statusBadgeClass(reason.is_active)">
                                                {{ reason.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-1.5">
                                                <Button v-if="canUpdateReasons" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-[var(--app-primary)] hover:bg-blue-50" @click="openEditModal(reason)">
                                                    <PencilLine class="h-3.5 w-3.5" />
                                                </Button>
                                                <Button v-if="canDeleteReasons" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50" @click="openDeleteModal(reason)">
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="rows.length === 0">
                                        <td colspan="3" class="px-4 py-10 text-center text-slate-400 font-bold">Tidak ada data ditemukan</td>
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
                        <DialogTitle class="text-3xl font-black text-slate-900">Tambah Reason</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-500">Masukkan alasan OOS baru untuk standarisasi pencatatan stok kosong.</DialogDescription>
                    </DialogHeader>
                </div>

                <form class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitCreate">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="create-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Nama Reason</Label>
                            <Input id="create-name" v-model="createForm.name" placeholder="Contoh: Inventory mismatch" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.name" />
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                            <input v-model="createForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                            <span class="text-sm font-semibold text-slate-600">Reason aktif dan siap dipakai di modul OOS</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isCreateOpen = false">Cancel</Button>
                        <Button :disabled="createForm.processing" class="h-11 rounded-xl bg-[var(--app-primary)] px-6 font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-[var(--app-primary-dark)]">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Creating...' : 'Create Reason' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-slate-900 px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-white">Edit Reason</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-400">Perbarui alasan OOS agar data inventaris tetap akurat.</DialogDescription>
                    </DialogHeader>
                </div>

                <form v-if="activeReason" class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitEdit">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="edit-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Nama Reason</Label>
                            <Input id="edit-name" v-model="editForm.name" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.name" />
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                            <input v-model="editForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                            <span class="text-sm font-semibold text-slate-600">Reason aktif</span>
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
                        <DialogTitle class="text-3xl font-black text-rose-950">Hapus Reason</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">Tindakan ini tidak bisa dibatalkan secara instan.</DialogDescription>
                    </DialogHeader>
                </div>

                <div class="space-y-6 bg-white px-7 py-7">
                    <div v-if="activeReason" class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5">
                        <p class="text-sm font-bold text-rose-900 uppercase tracking-tight">Konfirmasi Reason</p>
                        <p class="mt-1 text-lg font-black text-rose-600">{{ activeReason.name }}</p>
                    </div>

                    <InputError :message="deleteForm.errors.delete" />

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="closeDeleteModal">Cancel</Button>
                        <Button type="button" class="h-11 rounded-xl bg-rose-600 px-6 font-bold text-white shadow-lg shadow-rose-500/20 hover:bg-rose-700" :disabled="deleteForm.processing" @click="submitDelete">
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete Reason' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
