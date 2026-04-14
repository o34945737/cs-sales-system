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
import { CheckCircle2, ChevronLeft, ChevronRight, Mail, Plus, Search, ShieldCheck, ShieldPlus, Trash2, UserCog, Users as UsersIcon, XCircle } from 'lucide-vue-next';
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

        <div class="space-y-6">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="app-table-shell overflow-hidden">
                    <div class="grid gap-6 border-b border-[var(--app-border)] bg-[linear-gradient(135deg,_#eef4ff_0%,_#f8fbff_100%)] px-6 py-7 text-[var(--app-ink)] lg:grid-cols-[1.2fr,0.8fr]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Internal Access</p>
                            <h1 class="mt-3 text-3xl font-extrabold tracking-tight">User Management</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                                Kelola akun internal, role, dan status aktif user dalam workspace admin yang lebih aman, cepat, dan siap scale.
                            </p>
                        </div>

                        <div class="flex items-start justify-end" v-if="canCreateUsers">
                            <Button type="button" size="lg" class="rounded-2xl bg-[var(--app-primary)] text-white shadow-[0_14px_24px_rgba(53,103,232,0.24)] hover:bg-[var(--app-primary-dark)]" @click="openCreateModal">
                                <ShieldPlus class="h-4 w-4" />
                                Tambah User
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

                <div class="space-y-6">
                    <section class="app-table-shell p-6">
                        <div class="grid gap-5 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] xl:items-end">
                            <div class="min-w-0 max-w-xl">
                                <h2 class="text-xl font-semibold text-slate-900">Directory</h2>
                                <p class="mt-1 max-w-md text-sm leading-6 text-slate-500">
                                    Cari user, filter role, lalu edit langsung dari tabel tanpa perlu pindah halaman.
                                </p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,0.8fr)_minmax(0,0.8fr)]">
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input v-model="search" class="pl-10" placeholder="Cari nama atau email..." />
                                </div>

                                <select v-model="roleFilter" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none">
                                    <option value="All">Semua role</option>
                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                </select>

                                <select v-model="statusFilter" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none sm:col-span-2 xl:col-span-1">
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
                                            <th class="px-5 py-4 font-medium">User</th>
                                            <th class="px-5 py-4 font-medium">Role</th>
                                            <th class="px-5 py-4 font-medium">Status</th>
                                            <th class="px-5 py-4 font-medium">Created</th>
                                            <th class="px-5 py-4 text-right font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-for="user in userRows" :key="user.id" class="transition hover:bg-slate-50/70">
                                            <td class="px-5 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                                        <UserCog class="h-5 w-5" />
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-slate-900">{{ user.name }}</p>
                                                        <div class="mt-1 flex items-center gap-2 text-slate-500">
                                                            <Mail class="h-3.5 w-3.5" />
                                                            <span>{{ user.email }}</span>
                                                        </div>
                                                        <p v-if="user.force_password_reset" class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-600">
                                                            Password reset required
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                                    {{ user.roles[0] || 'No role' }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(user.is_active)">
                                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-slate-500">{{ formatDate(user.created_at) }}</td>
                                            <td class="px-5 py-4">
                                                <div class="flex justify-end gap-2">
                                                    <Button v-if="canUpdateUsers" type="button" variant="outline" size="sm" class="rounded-xl" @click="openEditModal(user)">
                                                        <UserCog class="h-4 w-4" />
                                                        Edit
                                                    </Button>
                                                    <Button v-if="canDeleteUsers" type="button" variant="destructive" size="sm" class="rounded-xl" @click="openDeleteModal(user)">
                                                        <Trash2 class="h-4 w-4" />
                                                        Delete
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="userRows.length === 0">
                                            <td colspan="5" class="px-5 py-14 text-center">
                                                <div class="mx-auto max-w-sm space-y-2">
                                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                                        <UsersIcon class="h-5 w-5" />
                                                    </div>
                                                    <p class="font-medium text-slate-900">Tidak ada user yang cocok</p>
                                                    <p class="text-sm text-slate-500">Coba ubah kata kunci pencarian atau filter role dan status.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <p class="text-sm text-slate-500">Menampilkan {{ usersPage.from || 0 }} - {{ usersPage.to || 0 }} dari {{ usersPage.total }} user</p>

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

                    <section class="app-table-shell p-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Audit Trail</p>
                            <h2 class="mt-3 text-xl font-semibold text-slate-900">Recent Admin Activities</h2>
                            <p class="mt-1 text-sm text-slate-500">Jejak perubahan user untuk membantu review akses internal.</p>
                        </div>

                        <div class="mt-6 space-y-4">
                            <article v-for="activity in recentActivities" :key="activity.id" class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ activity.actor_name }} {{ actionLabel(activity.action) }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ activity.target_label }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                                        {{ formatDate(activity.created_at, true) }}
                                    </span>
                                </div>

                                <p v-if="activity.metadata.role" class="mt-3 text-xs uppercase tracking-[0.2em] text-slate-400">Role: {{ activity.metadata.role }}</p>
                                <p v-else-if="activity.metadata.after && typeof activity.metadata.after === 'object'" class="mt-3 text-xs uppercase tracking-[0.2em] text-slate-400">
                                    Updated access details
                                </p>
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
            <DialogContent class="max-w-2xl rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-[linear-gradient(135deg,_#eef4ff_0%,_#dbeafe_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Tambah User Baru</DialogTitle>
                            <DialogDescription class="text-slate-500">Buat akun internal baru dan tetapkan role yang sesuai sejak awal.</DialogDescription>
                        </DialogHeader>
                    </div>

                    <form class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitCreate">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="create-name">Name</Label>
                                <Input id="create-name" v-model="createForm.name" placeholder="Nama lengkap" />
                                <InputError :message="createForm.errors.name" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="create-email">Email</Label>
                                <Input id="create-email" v-model="createForm.email" type="email" placeholder="nama@company.com" />
                                <InputError :message="createForm.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="create-role">Role</Label>
                                <select id="create-role" v-model="createForm.role" class="rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none">
                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                </select>
                                <InputError :message="createForm.errors.role" />
                            </div>
                            <div class="flex items-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="space-y-3">
                                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                        <input v-model="createForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                        Aktifkan akun setelah dibuat
                                    </label>
                                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                        <input v-model="createForm.force_password_reset" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                        Paksa ganti password saat login pertama
                                    </label>
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="create-password">Password</Label>
                                <Input id="create-password" v-model="createForm.password" type="password" />
                                <InputError :message="createForm.errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="create-password-confirmation">Confirm Password</Label>
                                <Input id="create-password-confirmation" v-model="createForm.password_confirmation" type="password" />
                            </div>
                        </div>

                        <DialogFooter class="border-t border-slate-100 pt-5">
                            <Button type="button" variant="outline" class="rounded-xl" @click="isCreateOpen = false">Cancel</Button>
                            <Button :disabled="createForm.processing" class="rounded-xl">
                                <ShieldPlus class="h-4 w-4" />
                                {{ createForm.processing ? 'Creating...' : 'Create User' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-2xl rounded-[28px] border-0 p-0 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="overflow-hidden rounded-[28px]">
                    <div class="bg-[linear-gradient(135deg,_#eef4ff_0%,_#dbeafe_100%)] px-6 py-6 text-[var(--app-ink)]">
                        <DialogHeader>
                            <DialogTitle class="text-2xl">Edit User</DialogTitle>
                            <DialogDescription class="text-slate-500">Perbarui identitas, role, password, dan status akses user.</DialogDescription>
                        </DialogHeader>
                    </div>

                    <form v-if="activeUser" class="space-y-5 bg-white px-6 py-6" @submit.prevent="submitEdit">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="edit-name">Name</Label>
                                <Input id="edit-name" v-model="editForm.name" />
                                <InputError :message="editForm.errors.name" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="edit-email">Email</Label>
                                <Input id="edit-email" v-model="editForm.email" type="email" />
                                <InputError :message="editForm.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-role">Role</Label>
                                <select id="edit-role" v-model="editForm.role" class="rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none">
                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                </select>
                                <InputError :message="editForm.errors.role" />
                            </div>
                            <div class="flex items-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="space-y-3">
                                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                        <input v-model="editForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                        User aktif
                                    </label>
                                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                        <input v-model="editForm.force_password_reset" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                        Paksa ganti password saat login berikutnya
                                    </label>
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-password">New Password</Label>
                                <Input id="edit-password" v-model="editForm.password" type="password" placeholder="Kosongkan jika tidak diubah" />
                                <InputError :message="editForm.errors.password" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-password-confirmation">Confirm New Password</Label>
                                <Input id="edit-password-confirmation" v-model="editForm.password_confirmation" type="password" />
                            </div>
                        </div>

                        <InputError :message="editForm.errors.is_active || editForm.errors.role" />

                        <DialogFooter class="border-t border-slate-100 pt-5">
                            <Button type="button" variant="outline" class="rounded-xl" @click="isEditOpen = false">Cancel</Button>
                            <Button :disabled="editForm.processing" class="rounded-xl">
                                <UserCog class="h-4 w-4" />
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
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
                            <DialogTitle class="text-2xl">Hapus User</DialogTitle>
                            <DialogDescription class="text-rose-500">Tindakan ini tidak bisa dibatalkan. Pastikan akun yang dipilih memang aman untuk dihapus.</DialogDescription>
                        </DialogHeader>
                    </div>

                    <div class="space-y-5 px-6 py-6">
                        <div v-if="activeUser" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="font-medium text-slate-900">{{ activeUser.name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ activeUser.email }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{{ activeUser.roles[0] || 'No role' }}</span>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(activeUser.is_active)">
                                    {{ activeUser.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <InputError :message="deleteForm.errors.delete" />

                        <DialogFooter>
                            <Button type="button" variant="outline" class="rounded-xl" @click="closeDeleteModal">Cancel</Button>
                            <Button type="button" variant="destructive" class="rounded-xl" :disabled="deleteForm.processing" @click="submitDelete">
                                <Trash2 class="h-4 w-4" />
                                {{ deleteForm.processing ? 'Deleting...' : 'Delete User' }}
                            </Button>
                        </DialogFooter>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
