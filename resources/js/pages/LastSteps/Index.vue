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
import { AlertCircle, CheckCircle2, ChevronLeft, ChevronRight, ListChecks, PencilLine, Plus, Search, ShieldAlert, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedLastStep { id: number; name: string; status_result: string; priority_level: string | null; type: string | null; is_active: boolean; created_at: string | null; }
interface PaginatorLink { active: boolean; label: string; url: string | null; }
interface Paginator<T> { current_page: number; data: T[]; from: number | null; last_page: number; links: PaginatorLink[]; path: string; per_page: number; to: number | null; total: number; }
interface LastStepFormData { name: string; status_result: string; priority_level: string; type: string; is_active: boolean; }

const props = defineProps<{
    lastSteps: Paginator<ManagedLastStep>;
    statusOptions: string[];
    priorityOptions: string[];
    typeOptions: string[];
    filters: { search?: string | null; status_filter?: string | null; type_filter?: string | null; active_state?: string | null; };
    metrics: { total: number; pending: number; solved: number; whitelist: number; };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Master Last Steps', href: '/last-steps' }];
const page = usePage<SharedData>();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status_filter || 'All');
const typeFilter = ref(props.filters.type_filter || 'All');
const activeState = ref(props.filters.active_state || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeLastStep = ref<ManagedLastStep | null>(null);

const canCreateLastSteps = computed(() => page.props.auth.can.create_last_steps);
const canUpdateLastSteps = computed(() => page.props.auth.can.update_last_steps);
const canDeleteLastSteps = computed(() => page.props.auth.can.delete_last_steps);

const createForm = useForm<LastStepFormData>({ name: '', status_result: props.statusOptions[0] ?? 'Pending', priority_level: '', type: '', is_active: true });
const editForm = useForm<LastStepFormData>({ name: '', status_result: props.statusOptions[0] ?? 'Pending', priority_level: '', type: '', is_active: true });
const deleteForm = useForm({});

const lastStepsPage = computed(() => props.lastSteps);
const lastStepRows = computed(() => props.lastSteps.data ?? []);
const paginationLinks = computed(() => props.lastSteps.links?.filter((link) => link.url) ?? []);
const summaryCards = computed(() => [
    { label: 'Total Last Step', value: props.metrics.total, icon: ListChecks, tone: 'bg-[var(--app-primary)] text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]' },
    { label: 'Pending', value: props.metrics.pending, icon: AlertCircle, tone: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' },
    { label: 'Solved', value: props.metrics.solved, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    { label: 'Whitelist', value: props.metrics.whitelist, icon: ShieldAlert, tone: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' },
]);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(route('last-steps.index'), {
        search: search.value || undefined,
        status_filter: statusFilter.value !== 'All' ? statusFilter.value : undefined,
        type_filter: typeFilter.value !== 'All' ? typeFilter.value : undefined,
        active_state: activeState.value !== 'All' ? activeState.value : undefined,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace });
};

watch(search, debounce(() => visitIndex({ page: 1 }), 350));
watch(statusFilter, () => visitIndex({ page: 1 }, false));
watch(typeFilter, () => visitIndex({ page: 1 }, false));
watch(activeState, () => visitIndex({ page: 1 }, false));

const resetCreateForm = () => {
    createForm.defaults({ name: '', status_result: props.statusOptions[0] ?? 'Pending', priority_level: '', type: '', is_active: true });
    createForm.reset();
    createForm.clearErrors();
};

const openCreateModal = () => {
    resetCreateForm();
    isCreateOpen.value = true;
};

const openEditModal = (lastStep: ManagedLastStep) => {
    activeLastStep.value = lastStep;
    editForm.defaults({
        name: lastStep.name,
        status_result: lastStep.status_result,
        priority_level: lastStep.priority_level || '',
        type: lastStep.type || '',
        is_active: lastStep.is_active,
    });
    editForm.reset();
    editForm.clearErrors();
    isEditOpen.value = true;
};

const openDeleteModal = (lastStep: ManagedLastStep) => {
    activeLastStep.value = lastStep;
    deleteForm.clearErrors();
    isDeleteOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteOpen.value = false;
    activeLastStep.value = null;
    deleteForm.clearErrors();
};

const normalizePayload = (data: LastStepFormData) => ({
    ...data,
    priority_level: data.priority_level || null,
    type: data.status_result === 'Pending' ? (data.type || null) : null,
});

const submitCreate = () => createForm.transform((data) => normalizePayload(data)).post(route('last-steps.store'), { preserveScroll: true, onSuccess: () => { isCreateOpen.value = false; resetCreateForm(); } });
const submitEdit = () => {
    if (!activeLastStep.value) return;
    editForm.transform((data) => normalizePayload(data)).put(route('last-steps.update', activeLastStep.value.id), { preserveScroll: true, onSuccess: () => { isEditOpen.value = false; activeLastStep.value = null; editForm.clearErrors(); } });
};
const submitDelete = () => {
    if (!activeLastStep.value) return;
    deleteForm.delete(route('last-steps.destroy', activeLastStep.value.id), { preserveScroll: true, onSuccess: () => closeDeleteModal() });
};

const formatDate = (value: string | null) => {
    if (!value) return '-';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parsed);
};

const statusClass = (status: string) => {
    if (status === 'Solved') return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
    if (status === 'Whitelist') return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
    return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
};

const activeBadgeClass = (active: boolean) => active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
const typeBadgeClass = (type: string | null) => type === 'External'
    ? 'bg-orange-100 text-orange-700 ring-1 ring-orange-200'
    : type === 'Internal'
        ? 'bg-violet-100 text-violet-700 ring-1 ring-violet-200'
        : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Master Last Steps" />

        <div class="space-y-4">
            <div class="mx-auto max-w-7xl space-y-4">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-4 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-5 py-4 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Master Data</p>
                            <h1 class="mt-1 text-xl font-black tracking-tight">Last Step Directory</h1>
                            <p class="mt-1 text-xs font-medium text-slate-500">
                                Kelola last step, hasil status, dan prioritas untuk standarisasi rule operasional.
                            </p>
                        </div>
 
                        <div v-if="canCreateLastSteps" class="flex items-center justify-end">
                            <Button type="button" size="sm" class="h-9 rounded-xl bg-[var(--app-primary)] px-5 text-xs font-bold text-white shadow-lg hover:bg-[var(--app-primary-dark)]" @click="openCreateModal">
                                <Plus class="h-3.5 w-3.5" />
                                Tambah Last Step
                            </Button>
                        </div>
                    </div>
 
                    <div class="grid gap-3.5 px-4 py-3.5 md:grid-cols-4">
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
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)] xl:items-center">
                        <div>
                            <h2 class="text-base font-black text-slate-900 uppercase tracking-wide">Last Step List</h2>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-4 lg:flex lg:justify-end">
                            <div class="relative lg:w-48">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="h-9 pl-9 text-xs" placeholder="Search..." />
                            </div>
                            <select v-model="statusFilter" class="h-9 min-w-[120px] rounded-xl border border-input bg-background px-3 text-xs font-bold text-slate-600 shadow-sm outline-none transition hover:border-slate-300">
                                <option value="All">Semua status</option>
                                <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <select v-model="typeFilter" class="h-9 min-w-[120px] rounded-xl border border-input bg-background px-3 text-xs font-bold text-slate-600 shadow-sm outline-none transition hover:border-slate-300">
                                <option value="All">Semua tipe</option>
                                <option v-for="option in typeOptions" :key="option" :value="option">{{ option }}</option>
                                <option value="None">Tanpa tipe</option>
                            </select>
                            <select v-model="activeState" class="h-9 min-w-[120px] rounded-xl border border-input bg-background px-3 text-xs font-bold text-slate-600 shadow-sm outline-none transition hover:border-slate-300">
                                <option value="All">Semua state</option>
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
                                        <th class="px-4 py-3">Last Step</th>
                                        <th class="px-4 py-3">Status Result</th>
                                        <th class="px-4 py-3">Priority</th>
                                        <th class="px-4 py-3">Type</th>
                                        <th class="px-4 py-3">State</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 bg-white">
                                    <tr v-for="lastStep in lastStepRows" :key="lastStep.id" class="transition hover:bg-slate-50/50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                                    <ListChecks class="h-3.5 w-3.5" />
                                                </div>
                                                <div>
                                                    <p class="font-bold text-slate-900 leading-tight">{{ lastStep.name }}</p>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">ID: #{{ lastStep.id }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider" :class="statusClass(lastStep.status_result)">{{ lastStep.status_result }}</span></td>
                                        <td class="px-4 py-3"><span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-700 ring-1 ring-slate-200">{{ lastStep.priority_level || '-' }}</span></td>
                                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider" :class="typeBadgeClass(lastStep.type)">{{ lastStep.type || '-' }}</span></td>
                                        <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider" :class="activeBadgeClass(lastStep.is_active)">{{ lastStep.is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-1.5">
                                                <Button v-if="canUpdateLastSteps" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-[var(--app-primary)] hover:bg-blue-50" @click="openEditModal(lastStep)"><PencilLine class="h-3.5 w-3.5" /></Button>
                                                <Button v-if="canDeleteLastSteps" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50" @click="openDeleteModal(lastStep)"><Trash2 class="h-3.5 w-3.5" /></Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="lastStepRows.length === 0">
                                        <td colspan="6" class="px-4 py-10 text-center text-slate-400 font-bold">Tidak ada data ditemukan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
 
                    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-[11px] font-bold text-slate-400">Showing {{ lastStepsPage.from || 0 }}-{{ lastStepsPage.to || 0 }} of {{ lastStepsPage.total }} entries</p>
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
                        <DialogTitle class="text-3xl font-black text-slate-900">Tambah Last Step</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-500">Masukkan last step baru beserta hasil status dan priority yang seharusnya.</DialogDescription>
                    </DialogHeader>
                </div>

                <form class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitCreate">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="create-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Nama Last Step</Label>
                            <Input id="create-name" v-model="createForm.name" placeholder="Contoh: Follow Up WH" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.name" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="create-status" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Status Result</Label>
                                <select id="create-status" v-model="createForm.status_result" class="h-12 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <InputError :message="createForm.errors.status_result" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="create-priority" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Priority Level</Label>
                                <select id="create-priority" v-model="createForm.priority_level" class="h-12 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">Tidak ada priority</option>
                                    <option v-for="option in priorityOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <InputError :message="createForm.errors.priority_level" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-type" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Type</Label>
                            <select id="create-type" v-model="createForm.type" :disabled="createForm.status_result !== 'Pending'" class="h-12 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:bg-slate-100">
                                <option value="">Tanpa tipe</option>
                                <option v-for="option in typeOptions" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <InputError :message="createForm.errors.type" />
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                            <input v-model="createForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                            <span class="text-sm font-semibold text-slate-600">Last step aktif dan siap dipakai di modul lain</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isCreateOpen = false">Cancel</Button>
                        <Button :disabled="createForm.processing" class="h-11 rounded-xl bg-[var(--app-primary)] px-6 font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-[var(--app-primary-dark)]">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Creating...' : 'Create Last Step' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-slate-900 px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-white">Edit Last Step</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-400">Perbarui last step, status result, dan priority agar mapping bisnis tetap rapi.</DialogDescription>
                    </DialogHeader>
                </div>

                <form v-if="activeLastStep" class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitEdit">
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="edit-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Nama Last Step</Label>
                            <Input id="edit-name" v-model="editForm.name" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.name" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="edit-status" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Status Result</Label>
                                <select id="edit-status" v-model="editForm.status_result" class="h-12 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <InputError :message="editForm.errors.status_result" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-priority" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Priority Level</Label>
                                <select id="edit-priority" v-model="editForm.priority_level" class="h-12 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">Tidak ada priority</option>
                                    <option v-for="option in priorityOptions" :key="option" :value="option">{{ option }}</option>
                                </select>
                                <InputError :message="editForm.errors.priority_level" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-type" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Type</Label>
                            <select id="edit-type" v-model="editForm.type" :disabled="editForm.status_result !== 'Pending'" class="h-12 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-bold text-slate-700 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:bg-slate-100">
                                <option value="">Tanpa tipe</option>
                                <option v-for="option in typeOptions" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <InputError :message="editForm.errors.type" />
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-4 transition hover:bg-slate-50">
                            <input v-model="editForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)] focus:ring-[var(--app-primary)]" />
                            <span class="text-sm font-semibold text-slate-600">Last step aktif</span>
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
                        <DialogTitle class="text-3xl font-black text-rose-950">Hapus Step</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">Tindakan ini tidak bisa dibatalkan secara instan.</DialogDescription>
                    </DialogHeader>
                </div>

                <div class="space-y-6 bg-white px-7 py-7">
                    <div v-if="activeLastStep" class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5">
                        <p class="text-sm font-bold text-rose-900 uppercase tracking-tight">Konfirmasi Step</p>
                        <p class="mt-1 text-lg font-black text-rose-600">{{ activeLastStep.name }}</p>
                    </div>

                    <InputError :message="deleteForm.errors.delete" />

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="closeDeleteModal">Cancel</Button>
                        <Button type="button" class="h-11 rounded-xl bg-rose-600 px-6 font-bold text-white shadow-lg shadow-rose-500/20 hover:bg-rose-700" :disabled="deleteForm.processing" @click="submitDelete">
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete Step' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
