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
import { CheckCircle2, ChevronLeft, ChevronRight, Mail, Search, ShieldCheck, ShieldPlus, Trash2, UserCog, Users as UsersIcon, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface ManagedUser { id: number; name: string; email: string; is_active: boolean; force_password_reset: boolean; roles: string[]; created_at: string | null; }
interface PaginatorLink { active: boolean; label: string; url: string | null; }
interface Paginator<T> { current_page: number; data: T[]; from: number | null; last_page: number; links: PaginatorLink[]; path: string; per_page: number; to: number | null; total: number; }
interface ActivityItem { id: number; action: string; target_label: string; actor_name: string; created_at: string | null; metadata: Record<string, unknown>; }

const props = defineProps<{
    users: Paginator<ManagedUser>;
    roles: string[];
    filters: { search?: string | null; role?: string | null; status?: string | null; };
    metrics: { total: number; active: number; inactive: number; super_admin: number; };
    recentActivities: ActivityItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Users', href: '/users' }];
const page = usePage<SharedData>();
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || 'All');
const statusFilter = ref(props.filters.status || 'All');
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const activeUser = ref<ManagedUser | null>(null);

const canCreateUsers = computed(() => page.props.auth.can.create_users);
const canUpdateUsers = computed(() => page.props.auth.can.update_users);
const canDeleteUsers = computed(() => page.props.auth.can.delete_users);

const createForm = useForm({ name: '', email: '', password: '', password_confirmation: '', role: props.roles[0] ?? '', is_active: true, force_password_reset: true });
const editForm = useForm({ name: '', email: '', password: '', password_confirmation: '', role: props.roles[0] ?? '', is_active: true, force_password_reset: false });
const deleteForm = useForm({});

const usersPage = computed(() => props.users);
const userRows = computed(() => props.users.data ?? []);
const paginationLinks = computed(() => props.users.links?.filter((link) => link.url) ?? []);
const summaryCards = computed(() => [
    { label: 'Total User', value: props.metrics.total, icon: UsersIcon, tone: 'bg-[var(--app-primary)] text-white shadow-[0_12px_24px_rgba(53,103,232,0.22)]' },
    { label: 'Active', value: props.metrics.active, icon: CheckCircle2, tone: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' },
    { label: 'Inactive', value: props.metrics.inactive, icon: XCircle, tone: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' },
    { label: 'Super Admin', value: props.metrics.super_admin, icon: ShieldCheck, tone: 'bg-[var(--app-primary-soft)] text-[var(--app-primary)] ring-1 ring-[rgba(53,103,232,0.14)]' },
]);

const visitIndex = (overrides: Record<string, unknown> = {}, replace = true) => {
    router.get(route('users.index'), {
        search: search.value || undefined,
        role: roleFilter.value !== 'All' ? roleFilter.value : undefined,
        status: statusFilter.value !== 'All' ? statusFilter.value : undefined,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace });
};

watch(search, debounce(() => visitIndex({ page: 1 }), 350));
watch(roleFilter, () => visitIndex({ page: 1 }, false));
watch(statusFilter, () => visitIndex({ page: 1 }, false));

const resetCreateForm = () => {
    createForm.defaults({ name: '', email: '', password: '', password_confirmation: '', role: props.roles[0] ?? '', is_active: true, force_password_reset: true });
    createForm.reset();
    createForm.clearErrors();
};

const openCreateModal = () => { resetCreateForm(); isCreateOpen.value = true; };
const openEditModal = (user: ManagedUser) => {
    activeUser.value = user;
    editForm.defaults({ name: user.name, email: user.email, password: '', password_confirmation: '', role: user.roles[0] ?? props.roles[0] ?? '', is_active: user.is_active, force_password_reset: user.force_password_reset });
    editForm.reset();
    editForm.clearErrors();
    isEditOpen.value = true;
};
const openDeleteModal = (user: ManagedUser) => { activeUser.value = user; deleteForm.clearErrors(); isDeleteOpen.value = true; };
const closeDeleteModal = () => { isDeleteOpen.value = false; activeUser.value = null; deleteForm.clearErrors(); };

const submitCreate = () => createForm.post(route('users.store'), { preserveScroll: true, onSuccess: () => { isCreateOpen.value = false; resetCreateForm(); } });
const submitEdit = () => {
    if (!activeUser.value) return;
    editForm.put(route('users.update', activeUser.value.id), { preserveScroll: true, onSuccess: () => { isEditOpen.value = false; activeUser.value = null; editForm.password = ''; editForm.password_confirmation = ''; editForm.clearErrors(); } });
};
const submitDelete = () => {
    if (!activeUser.value) return;
    deleteForm.delete(route('users.destroy', activeUser.value.id), { preserveScroll: true, onSuccess: () => closeDeleteModal() });
};

const formatDate = (value: string | null, withTime = false) => {
    if (!value) return '-';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}) }).format(parsed);
};

const actionLabel = (action: string) => action === 'user.created' ? 'membuat user' : action === 'user.updated' ? 'memperbarui user' : action === 'user.deleted' ? 'menghapus user' : action;
const statusBadgeClass = (active: boolean) => active ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="User Management" />

        <div class="space-y-4">
            <div class="mx-auto max-w-7xl space-y-4">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-4 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-5 py-4 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Internal Access</p>
                            <h1 class="mt-1 text-xl font-black tracking-tight">User Management</h1>
                            <p class="mt-1 text-xs font-medium text-slate-500">
                                Kelola akun internal, role, dan status aktif user secara real-time.
                            </p>
                        </div>
 
                        <div class="flex items-center justify-end" v-if="canCreateUsers">
                            <Button type="button" size="sm" class="h-9 rounded-xl bg-[var(--app-primary)] px-5 text-xs font-bold text-white shadow-lg hover:bg-[var(--app-primary-dark)]" @click="openCreateModal">
                                <ShieldPlus class="h-3.5 w-3.5" />
                                Tambah User
                            </Button>
                        </div>
                    </div>
 
                    <div class="grid gap-3.5 px-4 py-4 md:grid-cols-2 xl:grid-cols-4">
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
 
                <div class="space-y-4">
                    <section class="app-table-shell p-5">
                        <div class="grid gap-4 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] xl:items-center">
                            <div>
                                <h2 class="text-base font-black text-slate-900 uppercase tracking-wide">Directory</h2>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,0.8fr)_minmax(0,0.8fr)]">
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input v-model="search" class="h-9 pl-9 text-xs" placeholder="Cari user/email..." />
                                </div>
 
                                <select v-model="roleFilter" class="h-9 rounded-xl border border-input bg-background px-3 text-xs font-bold text-slate-600 shadow-sm outline-none transition hover:border-slate-300">
                                    <option value="All">Semua role</option>
                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                </select>
 
                                <select v-model="statusFilter" class="h-9 rounded-xl border border-input bg-background px-3 text-xs font-bold text-slate-600 shadow-sm outline-none transition hover:border-slate-300 sm:col-span-2 xl:col-span-1">
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
                                            <th class="px-4 py-3">User</th>
                                            <th class="px-4 py-3">Role</th>
                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 bg-white">
                                        <tr v-for="user in userRows" :key="user.id" class="transition hover:bg-slate-50/50">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400">
                                                        <UserCog class="h-4 w-4" />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-slate-900 leading-tight truncate">{{ user.name }}</p>
                                                        <p class="text-[11px] font-medium text-slate-500 truncate">{{ user.email }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-slate-50 px-2.5 py-0.5 text-[10px] font-bold text-slate-600 ring-1 ring-slate-100 shadow-sm">
                                                    {{ user.roles[0] || 'No role' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm" :class="statusBadgeClass(user.is_active)">
                                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex justify-end gap-1.5">
                                                    <Button v-if="canUpdateUsers" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-[var(--app-primary)] hover:bg-blue-50" @click="openEditModal(user)">
                                                        <UserCog class="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button v-if="canDeleteUsers" type="button" variant="ghost" size="sm" class="h-8 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50" @click="openDeleteModal(user)">
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="userRows.length === 0">
                                            <td colspan="4" class="px-4 py-10 text-center text-slate-400 font-bold">
                                                Tidak ada user ditemukan
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
 
                        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <p class="text-[11px] font-bold text-slate-400">Showing {{ usersPage.from || 0 }}-{{ usersPage.to || 0 }} of {{ usersPage.total }} entries</p>

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

                    <section class="app-table-shell p-5">
                        <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Audit Trail</p>
                                <h2 class="mt-1 text-base font-black text-slate-900 uppercase tracking-wide">Recent Admin Activities</h2>
                            </div>
                        </div>
 
                        <div class="mt-4 space-y-2">
                            <article v-for="activity in recentActivities" :key="activity.id" class="rounded-xl border border-slate-100 bg-slate-50/30 p-3 transition hover:bg-slate-50/60">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-slate-900 leading-tight">
                                            {{ activity.actor_name }} <span class="font-medium text-slate-500">{{ actionLabel(activity.action) }}</span>
                                        </p>
                                        <p class="mt-1 text-[11px] font-bold text-[var(--app-primary)] truncate">{{ activity.target_label }}</p>
                                    </div>
                                    <span class="shrink-0 text-[10px] font-black uppercase text-slate-400">
                                        {{ formatDate(activity.created_at, true) }}
                                    </span>
                                </div>
                                <div v-if="activity.metadata.role" class="mt-2 text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400 bg-white/50 rounded-md px-2 py-0.5 inline-block">
                                    Role: {{ activity.metadata.role }}
                                </div>
                                <div v-else-if="activity.metadata.after && typeof activity.metadata.after === 'object'" class="mt-2 text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400 bg-white/50 rounded-md px-2 py-0.5 inline-block">
                                    Updated access details
                                </div>
                            </article>

                            <div v-if="recentActivities.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                                Belum ada aktivitas admin yang tercatat.
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <Dialog v-model:open="isCreateOpen">
            <DialogContent class="max-w-2xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-[#EEF2FF] px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-slate-900">Tambah User Baru</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-500">Buat akun internal baru dan tetapkan role yang sesuai sejak awal.</DialogDescription>
                    </DialogHeader>
                </div>

                <form class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitCreate">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="create-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Display Name</Label>
                            <Input id="create-name" v-model="createForm.name" placeholder="Nama lengkap karyawan" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="create-email" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Email Address</Label>
                            <Input id="create-email" v-model="createForm.email" type="email" placeholder="nama@perusahaan.com" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-role" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Access Role</Label>
                            <select id="create-role" v-model="createForm.role" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-sm shadow-sm outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-100">
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                            <InputError :message="createForm.errors.role" />
                        </div>
                        <div class="flex items-center justify-center rounded-2xl border border-slate-100 bg-slate-50/50 px-4 transition hover:bg-slate-50">
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                                    <input v-model="createForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)]" />
                                    Aktifkan akun segera
                                </label>
                                <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                                    <input v-model="createForm.force_password_reset" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)]" />
                                    Paksa ganti password
                                </label>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-password" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Set Password</Label>
                            <Input id="create-password" v-model="createForm.password" type="password" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="createForm.errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-password-confirmation" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Confirm Password</Label>
                            <Input id="create-password-confirmation" v-model="createForm.password_confirmation" type="password" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isCreateOpen = false">Cancel</Button>
                        <Button :disabled="createForm.processing" class="h-11 rounded-xl bg-[var(--app-primary)] px-6 font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-[var(--app-primary-dark)]">
                            <ShieldPlus class="mr-2 h-4 w-4" />
                            {{ createForm.processing ? 'Creating...' : 'Create User' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-2xl overflow-hidden rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="bg-slate-900 px-7 py-8">
                    <DialogHeader>
                        <DialogTitle class="text-3xl font-black text-white">Edit User Access</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-slate-400">Perbarui identitas, role, password, dan status akses user.</DialogDescription>
                    </DialogHeader>
                </div>

                <form v-if="activeUser" class="space-y-6 bg-white px-7 py-7" @submit.prevent="submitEdit">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="edit-name" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Display Name</Label>
                            <Input id="edit-name" v-model="editForm.name" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.name" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="edit-email" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Email Address</Label>
                            <Input id="edit-email" v-model="editForm.email" type="email" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-role" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Access Role</Label>
                            <select id="edit-role" v-model="editForm.role" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-sm shadow-sm outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-100">
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                            <InputError :message="editForm.errors.role" />
                        </div>
                        <div class="flex items-center justify-center rounded-2xl border border-slate-100 bg-slate-50/50 px-4 transition hover:bg-slate-50">
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                                    <input v-model="editForm.is_active" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)]" />
                                    Akun aktif
                                </label>
                                <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
                                    <input v-model="editForm.force_password_reset" type="checkbox" class="h-5 w-5 rounded-md border-slate-300 text-[var(--app-primary)]" />
                                    Wajib ganti password
                                </label>
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-password" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">New Password</Label>
                            <Input id="edit-password" v-model="editForm.password" type="password" placeholder="Kosongkan jika tidak diubah" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                            <InputError :message="editForm.errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-password-confirmation" class="text-[13px] font-bold uppercase tracking-wide text-slate-700">Confirm New Password</Label>
                            <Input id="edit-password-confirmation" v-model="editForm.password_confirmation" type="password" class="h-12 rounded-xl border-slate-200 bg-slate-50/50 px-4 text-sm transition focus:bg-white" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="isEditOpen = false">Cancel</Button>
                        <Button :disabled="editForm.processing" class="h-11 rounded-xl bg-[var(--app-primary)] px-6 font-bold text-white shadow-lg shadow-blue-500/20 hover:bg-[var(--app-primary-dark)]">
                            <UserCog class="mr-2 h-4 w-4" />
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
                        <DialogTitle class="text-3xl font-black text-rose-950">Hapus Akun</DialogTitle>
                        <DialogDescription class="mt-2 text-base font-medium text-rose-600/80">Tindakan ini permanen. Pastikan akun ini memang tidak digunakan lagi.</DialogDescription>
                    </DialogHeader>
                </div>

                <div class="space-y-6 bg-white px-7 py-7">
                    <div v-if="activeUser" class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5">
                        <p class="text-sm font-bold text-rose-900 uppercase tracking-tight">Konfirmasi User</p>
                        <p class="mt-1 text-lg font-black text-rose-600 leading-tight">{{ activeUser.name }}</p>
                        <p class="text-sm font-medium text-slate-500">{{ activeUser.email }}</p>
                        <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1 ring-1 ring-rose-100 shadow-sm">
                            <ShieldCheck class="h-3 w-3 text-rose-400" />
                            <span class="text-[10px] font-black uppercase text-rose-600">{{ activeUser.roles[0] || 'No Role' }}</span>
                        </div>
                    </div>

                    <InputError :message="deleteForm.errors.delete" />

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                        <Button type="button" variant="ghost" class="h-11 rounded-xl px-6 font-bold text-slate-500 hover:bg-slate-50" @click="closeDeleteModal">Cancel</Button>
                        <Button type="button" class="h-11 rounded-xl bg-rose-600 px-6 font-bold text-white shadow-lg shadow-rose-500/20 hover:bg-rose-700" :disabled="deleteForm.processing" @click="submitDelete">
                            <Trash2 class="mr-2 h-4 w-4" />
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete User' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
