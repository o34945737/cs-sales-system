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
import { CheckCircle2, ChevronLeft, ChevronRight, PencilLine, Plus, Search, ShieldCheck, Trash2, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedCauseBy { id: number; name: string; is_active: boolean; created_at: string | null; }
interface PaginatorLink { active: boolean; label: string; url: string | null; }
interface Paginator<T> { current_page: number; data: T[]; from: number | null; last_page: number; links: PaginatorLink[]; path: string; per_page: number; to: number | null; total: number; }

const props = defineProps<{
    causeBys: Paginator<ManagedCauseBy>;
    filters: { search?: string | null; status?: string | null; };
    metrics: { total: number; active: number; inactive: number; };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Cause By', href: '/cause-bys' }];
const page = usePage<SharedData>();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeCauseBy = ref<ManagedCauseBy | null>(null);

const canCreateCauseBys = computed(() => page.props.auth.can.create_cause_bys);
const canUpdateCauseBys = computed(() => page.props.auth.can.update_cause_bys);
const canDeleteCauseBys = computed(() => page.props.auth.can.delete_cause_bys);

const createForm = useForm({ name: '', is_active: true });
const editForm = useForm({ name: '', is_active: true });
const deleteForm = useForm({});

const pageData = computed(() => props.causeBys);
const rows = computed(() => props.causeBys.data ?? []);
const paginationLinks = computed(() => props.causeBys.links?.filter((link) => link.url) ?? []);
const summaryCards = computed(() => [
    { label: 'Total Cause By', value: props.metrics.total, icon: ShieldCheck, tone: 'bg-[var(--app-primary)] text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]' },
    { label: 'Active', value: props.metrics.active, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    { label: 'Inactive', value: props.metrics.inactive, icon: XCircle, tone: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' },
]);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(route('cause-bys.index'), {
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

const openEditModal = (causeBy: ManagedCauseBy) => {
    activeCauseBy.value = causeBy;
    editForm.defaults({ name: causeBy.name, is_active: causeBy.is_active });
    editForm.reset();
    editForm.clearErrors();
    isEditOpen.value = true;
};

const openDeleteModal = (causeBy: ManagedCauseBy) => {
    activeCauseBy.value = causeBy;
    deleteForm.clearErrors();
    isDeleteOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteOpen.value = false;
    activeCauseBy.value = null;
    deleteForm.clearErrors();
};

const submitCreate = () => createForm.post(route('cause-bys.store'), { preserveScroll: true, onSuccess: () => { isCreateOpen.value = false; resetCreateForm(); } });
const submitEdit = () => {
    if (!activeCauseBy.value) return;
    editForm.put(route('cause-bys.update', activeCauseBy.value.id), { preserveScroll: true, onSuccess: () => { isEditOpen.value = false; activeCauseBy.value = null; editForm.clearErrors(); } });
};
const submitDelete = () => {
    if (!activeCauseBy.value) return;
    deleteForm.delete(route('cause-bys.destroy', activeCauseBy.value.id), { preserveScroll: true, onSuccess: () => closeDeleteModal() });
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
        <Head title="Cause By" />

        <div class="space-y-6">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-6 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-6 py-7 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Master Data</p>
                            <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Cause By Directory</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                                Kelola daftar cause by agar pilihan penanggung jawab penyebab case tetap konsisten di seluruh modul.
                            </p>
                        </div>

                        <div v-if="canCreateCauseBys" class="flex items-start justify-end">
                            <Button type="button" size="lg" class="rounded-2xl bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)] hover:bg-[var(--app-primary-dark)]" @click="openCreateModal">
                                <Plus class="h-4 w-4" />
                                Tambah Cause By
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
                            <h2 class="text-xl font-semibold text-slate-900">Cause By List</h2>
                            <p class="mt-1 max-w-md text-sm leading-6 text-slate-500">
                                Master data ini dipakai untuk menjaga pilihan cause by tetap sinkron antara sub case dan complaint.
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,0.8fr)]">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="pl-10" placeholder="Cari cause by..." />
                            </div>

                            <select v-model="statusFilter" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none">
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
                                        <th class="px-5 py-4 font-medium">Cause By</th>
                                        <th class="px-5 py-4 font-medium">Status</th>
                                        <th class="px-5 py-4 font-medium">Created</th>
                                        <th class="px-5 py-4 text-right font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="causeBy in rows" :key="causeBy.id" class="transition hover:bg-slate-50/70">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                                    <ShieldCheck class="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <p class="font-medium text-slate-900">{{ causeBy.name }}</p>
                                                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Master Cause By</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(causeBy.is_active)">
                                                {{ causeBy.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-slate-500">{{ formatDate(causeBy.created_at) }}</td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2">
                                                <Button v-if="canUpdateCauseBys" type="button" variant="outline" size="sm" class="rounded-xl" @click="openEditModal(causeBy)">
                                                    <PencilLine class="h-4 w-4" />
                                                    Edit
                                                </Button>
                                                <Button v-if="canDeleteCauseBys" type="button" variant="destructive" size="sm" class="rounded-xl" @click="openDeleteModal(causeBy)">
                                                    <Trash2 class="h-4 w-4" />
                                                    Delete
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="rows.length === 0">
                                        <td colspan="4" class="px-5 py-14 text-center">
                                            <div class="mx-auto max-w-sm space-y-2">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                                    <ShieldCheck class="h-5 w-5" />
                                                </div>
                                                <p class="font-medium text-slate-900">Tidak ada cause by yang cocok</p>
                                                <p class="text-sm text-slate-500">Coba ubah pencarian atau status filter untuk melihat data lain.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-sm text-slate-500">Menampilkan {{ pageData.from || 0 }} - {{ pageData.to || 0 }} dari {{ pageData.total }} cause by</p>

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
            <DialogContent class="max-w-xl rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-[linear-gradient(135deg,_#eef4ff_0%,_#dbeafe_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Tambah Cause By</DialogTitle>
                            <DialogDescription class="text-slate-500">Masukkan cause by baru agar siap dipakai sebagai master data.</DialogDescription>
                        </DialogHeader>
                    </div>
                    <form class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitCreate">
                        <div class="grid gap-5">
                            <div class="grid gap-2">
                                <Label for="create-name">Nama Cause By</Label>
                                <Input id="create-name" v-model="createForm.name" placeholder="Contoh: BRAND" />
                                <InputError :message="createForm.errors.name" />
                            </div>
                            <div class="flex items-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                    <input v-model="createForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                    Cause by aktif dan siap dipakai di modul lain
                                </label>
                            </div>
                        </div>
                        <DialogFooter class="border-t border-slate-100 pt-5">
                            <Button type="button" variant="outline" class="rounded-xl" @click="isCreateOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing" class="rounded-xl"><Plus class="h-4 w-4" />{{ createForm.processing ? 'Creating...' : 'Create Cause By' }}</Button>
                        </DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-xl rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-[linear-gradient(135deg,_#eef4ff_0%,_#dbeafe_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Edit Cause By</DialogTitle>
                            <DialogDescription class="text-slate-500">Perbarui nama atau status aktif cause by agar master data tetap rapi.</DialogDescription>
                        </DialogHeader>
                    </div>
                    <form v-if="activeCauseBy" class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitEdit">
                        <div class="grid gap-5">
                            <div class="grid gap-2">
                                <Label for="edit-name">Nama Cause By</Label>
                                <Input id="edit-name" v-model="editForm.name" />
                                <InputError :message="editForm.errors.name" />
                            </div>
                            <div class="flex items-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                    <input v-model="editForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                    Cause by aktif
                                </label>
                            </div>
                        </div>
                        <DialogFooter class="border-t border-slate-100 pt-5">
                            <Button type="button" variant="outline" class="rounded-xl" @click="isEditOpen = false">Cancel</Button>
                            <Button :disabled="editForm.processing" class="rounded-xl"><PencilLine class="h-4 w-4" />{{ editForm.processing ? 'Saving...' : 'Save Changes' }}</Button>
                        </DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-md rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px] bg-white">
                    <div class="bg-[linear-gradient(135deg,_#fff1f2_0%,_#ffe4e6_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Hapus Cause By</DialogTitle>
                            <DialogDescription class="text-rose-500">Tindakan ini tidak bisa dibatalkan. Pastikan cause by memang aman untuk dihapus dari master data.</DialogDescription>
                        </DialogHeader>
                    </div>
                    <div class="space-y-5 px-6 py-6">
                        <div v-if="activeCauseBy" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="font-medium text-slate-900">{{ activeCauseBy.name }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(activeCauseBy.is_active)">{{ activeCauseBy.is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>
                        <InputError :message="deleteForm.errors.delete" />
                        <DialogFooter>
                            <Button type="button" variant="outline" class="rounded-xl" @click="closeDeleteModal">Cancel</Button>
                            <Button type="button" variant="destructive" class="rounded-xl" :disabled="deleteForm.processing" @click="submitDelete"><Trash2 class="h-4 w-4" />{{ deleteForm.processing ? 'Deleting...' : 'Delete Cause By' }}</Button>
                        </DialogFooter>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
