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
import { CheckCircle2, ChevronLeft, ChevronRight, Database, PencilLine, Plus, Search, Trash2, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedJetTrackEntry {
    id: number;
    awb: string;
    kondisi_barang: string;
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
    jetTrackEntries: Paginator<ManagedJetTrackEntry>;
    filters: { search?: string | null; status?: string | null };
    metrics: { total: number; active: number; inactive: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Jet Track', href: '/jet-track-entries' },
];
const page = usePage<SharedData>();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeEntry = ref<ManagedJetTrackEntry | null>(null);

const canCreateEntries = computed(() => page.props.auth.can.create_jet_track_entries);
const canUpdateEntries = computed(() => page.props.auth.can.update_jet_track_entries);
const canDeleteEntries = computed(() => page.props.auth.can.delete_jet_track_entries);

const createForm = useForm({ awb: '', kondisi_barang: '', notes: '', is_active: true });
const editForm = useForm({ awb: '', kondisi_barang: '', notes: '', is_active: true });
const deleteForm = useForm({});

const pageData = computed(() => props.jetTrackEntries);
const rows = computed(() => props.jetTrackEntries.data ?? []);
const paginationLinks = computed(() => props.jetTrackEntries.links?.filter((link) => link.url) ?? []);
const summaryCards = computed(() => [
    { label: 'Total AWB', value: props.metrics.total, icon: Database, tone: 'bg-[var(--app-primary)] text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]' },
    { label: 'Active', value: props.metrics.active, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    { label: 'Inactive', value: props.metrics.inactive, icon: XCircle, tone: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' },
]);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(
        route('jet-track-entries.index'),
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
    createForm.defaults({ awb: '', kondisi_barang: '', notes: '', is_active: true });
    createForm.reset();
    createForm.clearErrors();
};

const openCreateModal = () => {
    resetCreateForm();
    isCreateOpen.value = true;
};

const openEditModal = (entry: ManagedJetTrackEntry) => {
    activeEntry.value = entry;
    editForm.defaults({
        awb: entry.awb,
        kondisi_barang: entry.kondisi_barang,
        notes: entry.notes || '',
        is_active: entry.is_active,
    });
    editForm.reset();
    editForm.clearErrors();
    isEditOpen.value = true;
};

const openDeleteModal = (entry: ManagedJetTrackEntry) => {
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
    createForm.post(route('jet-track-entries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateOpen.value = false;
            resetCreateForm();
        },
    });

const submitEdit = () => {
    if (!activeEntry.value) return;

    editForm.put(route('jet-track-entries.update', activeEntry.value.id), {
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

    deleteForm.delete(route('jet-track-entries.destroy', activeEntry.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const statusBadgeClass = (active: boolean) =>
    active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Jet Track" />

        <div class="space-y-4">
            <div class="mx-auto max-w-7xl space-y-4">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-4 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-5 py-4 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Automation Source</p>
                            <h1 class="mt-1 text-xl font-black tracking-tight">Jet Track Reference</h1>
                            <p class="mt-1 text-xs font-medium text-slate-500">
                                Kelola daftar AWB dan kondisi barang untuk automation track ADA DI JET TRACK.
                            </p>
                        </div>

                        <div v-if="canCreateEntries" class="flex items-center justify-end">
                            <Button type="button" size="sm" class="h-9 rounded-xl bg-[var(--app-primary)] px-5 text-xs font-bold text-white shadow-lg hover:bg-[var(--app-primary-dark)]" @click="openCreateModal">
                                <Plus class="h-3.5 w-3.5" />
                                Tambah AWB
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
                            <h2 class="text-base font-black uppercase tracking-wide text-slate-900">Jet Track List</h2>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:flex lg:justify-end">
                            <div class="relative lg:w-64">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="h-9 pl-9 text-xs" placeholder="Cari AWB / kondisi..." />
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
                                        <th class="px-4 py-3">AWB</th>
                                        <th class="px-4 py-3">Kondisi Barang</th>
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
                                                    <p class="font-bold leading-tight text-slate-900">{{ entry.awb }}</p>
                                                    <p class="text-[10px] font-bold uppercase tracking-tighter text-slate-400">ID: #{{ entry.id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-700">{{ entry.kondisi_barang }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ entry.notes || '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm" :class="statusBadgeClass(entry.is_active)">
                                                {{ entry.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-1.5">
                                                <Button v-if="canUpdateEntries" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:bg-blue-50 hover:text-[var(--app-primary)]" @click="openEditModal(entry)">
                                                    <PencilLine class="h-3.5 w-3.5" />
                                                </Button>
                                                <Button v-if="canDeleteEntries" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600" @click="openDeleteModal(entry)">
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="rows.length === 0">
                                        <td colspan="5" class="px-4 py-10 text-center font-bold text-slate-400">Tidak ada data ditemukan</td>
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
                        <DialogTitle class="text-3xl font-black text-slate-900">Tambah AWB Jet Track</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-500">Masukkan AWB dan kondisi barang yang akan dipakai automation Order Tracking.</DialogDescription>
                    </DialogHeader>
                </div>

                <form class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitCreate">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="create-awb" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">AWB</Label>
                            <Input id="create-awb" v-model="createForm.awb" placeholder="Contoh: JT000123456" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.awb" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-kondisi" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Kondisi Barang</Label>
                            <Input id="create-kondisi" v-model="createForm.kondisi_barang" placeholder="Contoh: Box penyok" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.kondisi_barang" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-notes" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Notes</Label>
                            <Input id="create-notes" v-model="createForm.notes" placeholder="Catatan opsional" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.notes" />
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                            <input v-model="createForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                            <span class="text-sm font-semibold text-slate-600">Entry aktif dan siap dipakai di automation Order Tracking</span>
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
                        <DialogTitle class="text-3xl font-black text-white">Edit Entry Jet Track</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-400">Perbarui AWB, kondisi barang, atau status aktif agar automation tetap akurat.</DialogDescription>
                    </DialogHeader>
                </div>

                <form v-if="activeEntry" class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitEdit">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="edit-awb" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">AWB</Label>
                            <Input id="edit-awb" v-model="editForm.awb" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.awb" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-kondisi" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Kondisi Barang</Label>
                            <Input id="edit-kondisi" v-model="editForm.kondisi_barang" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.kondisi_barang" />
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
                        <DialogTitle class="text-3xl font-black text-rose-950">Hapus Entry Jet Track</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">Tindakan ini tidak bisa dibatalkan secara instan.</DialogDescription>
                    </DialogHeader>
                </div>

                <div class="space-y-6 bg-white px-7 py-7">
                    <div v-if="activeEntry" class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5">
                        <p class="text-sm font-bold uppercase tracking-tight text-rose-900">Konfirmasi AWB</p>
                        <p class="mt-1 text-lg font-black text-rose-600">{{ activeEntry.awb }}</p>
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
    </AppLayout>
</template>
