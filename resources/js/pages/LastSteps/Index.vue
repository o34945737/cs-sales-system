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

interface ManagedLastStep { id: number; name: string; status_result: string; priority_level: string | null; is_active: boolean; created_at: string | null; }
interface PaginatorLink { active: boolean; label: string; url: string | null; }
interface Paginator<T> { current_page: number; data: T[]; from: number | null; last_page: number; links: PaginatorLink[]; path: string; per_page: number; to: number | null; total: number; }

const props = defineProps<{
    lastSteps: Paginator<ManagedLastStep>;
    statusOptions: string[];
    priorityOptions: string[];
    filters: { search?: string | null; status_filter?: string | null; active_state?: string | null; };
    metrics: { total: number; pending: number; solved: number; whitelist: number; };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Master Last Steps', href: '/last-steps' }];
const page = usePage<SharedData>();
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status_filter || 'All');
const activeState = ref(props.filters.active_state || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeLastStep = ref<ManagedLastStep | null>(null);

const canCreateLastSteps = computed(() => page.props.auth.can.create_last_steps);
const canUpdateLastSteps = computed(() => page.props.auth.can.update_last_steps);
const canDeleteLastSteps = computed(() => page.props.auth.can.delete_last_steps);

const createForm = useForm({ name: '', status_result: props.statusOptions[0] ?? 'Pending', priority_level: '', is_active: true });
const editForm = useForm({ name: '', status_result: props.statusOptions[0] ?? 'Pending', priority_level: '', is_active: true });
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
        active_state: activeState.value !== 'All' ? activeState.value : undefined,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace });
};

watch(search, debounce(() => visitIndex({ page: 1 }), 350));
watch(statusFilter, () => visitIndex({ page: 1 }, false));
watch(activeState, () => visitIndex({ page: 1 }, false));

const resetCreateForm = () => {
    createForm.defaults({ name: '', status_result: props.statusOptions[0] ?? 'Pending', priority_level: '', is_active: true });
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

const submitCreate = () => createForm.transform((data) => ({ ...data, priority_level: data.priority_level || null })).post(route('last-steps.store'), { preserveScroll: true, onSuccess: () => { isCreateOpen.value = false; resetCreateForm(); } });
const submitEdit = () => {
    if (!activeLastStep.value) return;
    editForm.transform((data) => ({ ...data, priority_level: data.priority_level || null })).put(route('last-steps.update', activeLastStep.value.id), { preserveScroll: true, onSuccess: () => { isEditOpen.value = false; activeLastStep.value = null; editForm.clearErrors(); } });
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Master Last Steps" />

        <div class="space-y-6">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-6 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-6 py-7 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Master Data</p>
                            <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Last Step Directory</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                                Kelola last step beserta hasil status dan prioritas agar rule automation complaint dan order tracking siap dipindahkan dari hardcoded ke master data.
                            </p>
                        </div>

                        <div v-if="canCreateLastSteps" class="flex items-start justify-end">
                            <Button type="button" size="lg" class="rounded-2xl bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)] hover:bg-[var(--app-primary-dark)]" @click="openCreateModal">
                                <Plus class="h-4 w-4" />
                                Tambah Last Step
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-4 px-6 py-5 md:grid-cols-2 xl:grid-cols-4">
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
                            <h2 class="text-xl font-semibold text-slate-900">Last Step List</h2>
                            <p class="mt-1 max-w-md text-sm leading-6 text-slate-500">
                                Di sini kita menjaga pemetaan `last step -> status -> priority` tetap konsisten agar tidak perlu diulang di banyak file kode.
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="relative sm:col-span-3 xl:col-span-1">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input v-model="search" class="pl-10" placeholder="Cari last step, status, atau priority..." />
                            </div>
                            <select v-model="statusFilter" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none">
                                <option value="All">Semua status</option>
                                <option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option>
                            </select>
                            <select v-model="activeState" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none">
                                <option value="All">Semua state</option>
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
                                        <th class="px-5 py-4 font-medium">Last Step</th>
                                        <th class="px-5 py-4 font-medium">Status Result</th>
                                        <th class="px-5 py-4 font-medium">Priority</th>
                                        <th class="px-5 py-4 font-medium">State</th>
                                        <th class="px-5 py-4 font-medium">Created</th>
                                        <th class="px-5 py-4 text-right font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="lastStep in lastStepRows" :key="lastStep.id" class="transition hover:bg-slate-50/70">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                                    <ListChecks class="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <p class="font-medium text-slate-900">{{ lastStep.name }}</p>
                                                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Master Last Step</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(lastStep.status_result)">{{ lastStep.status_result }}</span></td>
                                        <td class="px-5 py-4"><span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ lastStep.priority_level || '-' }}</span></td>
                                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="activeBadgeClass(lastStep.is_active)">{{ lastStep.is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="px-5 py-4 text-slate-500">{{ formatDate(lastStep.created_at) }}</td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2">
                                                <Button v-if="canUpdateLastSteps" type="button" variant="outline" size="sm" class="rounded-xl" @click="openEditModal(lastStep)"><PencilLine class="h-4 w-4" />Edit</Button>
                                                <Button v-if="canDeleteLastSteps" type="button" variant="destructive" size="sm" class="rounded-xl" @click="openDeleteModal(lastStep)"><Trash2 class="h-4 w-4" />Delete</Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="lastStepRows.length === 0">
                                        <td colspan="6" class="px-5 py-14 text-center">
                                            <div class="mx-auto max-w-sm space-y-2">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500"><ListChecks class="h-5 w-5" /></div>
                                                <p class="font-medium text-slate-900">Tidak ada last step yang cocok</p>
                                                <p class="text-sm text-slate-500">Coba ubah pencarian atau filter untuk melihat data lain.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-sm text-slate-500">Menampilkan {{ lastStepsPage.from || 0 }} - {{ lastStepsPage.to || 0 }} dari {{ lastStepsPage.total }} last step</p>
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
                            <DialogTitle class="text-2xl">Tambah Last Step</DialogTitle>
                            <DialogDescription class="text-slate-500">Masukkan last step baru beserta hasil status dan priority yang seharusnya.</DialogDescription>
                        </DialogHeader>
                    </div>
                    <form class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitCreate">
                        <div class="grid gap-5">
                            <div class="grid gap-2"><Label for="create-name">Nama Last Step</Label><Input id="create-name" v-model="createForm.name" placeholder="Contoh: Follow Up WH" /><InputError :message="createForm.errors.name" /></div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="grid gap-2"><Label for="create-status">Status Result</Label><select id="create-status" v-model="createForm.status_result" class="rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none"><option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option></select><InputError :message="createForm.errors.status_result" /></div>
                                <div class="grid gap-2"><Label for="create-priority">Priority Level</Label><select id="create-priority" v-model="createForm.priority_level" class="rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none"><option value="">Tidak ada priority</option><option v-for="option in priorityOptions" :key="option" :value="option">{{ option }}</option></select><InputError :message="createForm.errors.priority_level" /></div>
                            </div>
                            <div class="flex items-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"><label class="flex items-center gap-3 text-sm font-medium text-slate-700"><input v-model="createForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />Last step aktif dan siap dipakai di modul lain</label></div>
                        </div>
                        <DialogFooter class="border-t border-slate-100 pt-5"><Button type="button" variant="outline" class="rounded-xl" @click="isCreateOpen = false">Cancel</Button><Button :disabled="createForm.processing" class="rounded-xl"><Plus class="h-4 w-4" />{{ createForm.processing ? 'Creating...' : 'Create Last Step' }}</Button></DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-xl rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-[linear-gradient(135deg,_#eef4ff_0%,_#dbeafe_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Edit Last Step</DialogTitle>
                            <DialogDescription class="text-slate-500">Perbarui last step, status result, dan priority agar mapping bisnis tetap rapi.</DialogDescription>
                        </DialogHeader>
                    </div>
                    <form v-if="activeLastStep" class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitEdit">
                        <div class="grid gap-5">
                            <div class="grid gap-2"><Label for="edit-name">Nama Last Step</Label><Input id="edit-name" v-model="editForm.name" /><InputError :message="editForm.errors.name" /></div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="grid gap-2"><Label for="edit-status">Status Result</Label><select id="edit-status" v-model="editForm.status_result" class="rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none"><option v-for="option in statusOptions" :key="option" :value="option">{{ option }}</option></select><InputError :message="editForm.errors.status_result" /></div>
                                <div class="grid gap-2"><Label for="edit-priority">Priority Level</Label><select id="edit-priority" v-model="editForm.priority_level" class="rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none"><option value="">Tidak ada priority</option><option v-for="option in priorityOptions" :key="option" :value="option">{{ option }}</option></select><InputError :message="editForm.errors.priority_level" /></div>
                            </div>
                            <div class="flex items-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"><label class="flex items-center gap-3 text-sm font-medium text-slate-700"><input v-model="editForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />Last step aktif</label></div>
                        </div>
                        <DialogFooter class="border-t border-slate-100 pt-5"><Button type="button" variant="outline" class="rounded-xl" @click="isEditOpen = false">Cancel</Button><Button :disabled="editForm.processing" class="rounded-xl"><PencilLine class="h-4 w-4" />{{ editForm.processing ? 'Saving...' : 'Save Changes' }}</Button></DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-md rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px] bg-white">
                    <div class="bg-[linear-gradient(135deg,_#fff1f2_0%,_#ffe4e6_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Hapus Last Step</DialogTitle>
                            <DialogDescription class="text-rose-500">Tindakan ini tidak bisa dibatalkan. Pastikan last step memang aman untuk dihapus dari master data.</DialogDescription>
                        </DialogHeader>
                    </div>
                    <div class="space-y-5 px-6 py-6">
                        <div v-if="activeLastStep" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="font-medium text-slate-900">{{ activeLastStep.name }}</p>
                            <div class="mt-3 flex flex-wrap gap-2"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(activeLastStep.status_result)">{{ activeLastStep.status_result }}</span><span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ activeLastStep.priority_level || '-' }}</span></div>
                        </div>
                        <InputError :message="deleteForm.errors.delete" />
                        <DialogFooter><Button type="button" variant="outline" class="rounded-xl" @click="closeDeleteModal">Cancel</Button><Button type="button" variant="destructive" class="rounded-xl" :disabled="deleteForm.processing" @click="submitDelete"><Trash2 class="h-4 w-4" />{{ deleteForm.processing ? 'Deleting...' : 'Delete Last Step' }}</Button></DialogFooter>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
