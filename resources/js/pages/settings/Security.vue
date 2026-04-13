<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Activity, LaptopMinimal, ShieldCheck, Smartphone } from 'lucide-vue-next';

interface SessionItem {
    id: string;
    ip_address: string | null;
    user_agent: string | null;
    last_activity: string | null;
    is_current: boolean;
}

interface LoginActivityItem {
    id: number;
    status: string;
    ip_address: string | null;
    user_agent: string | null;
    logged_in_at: string | null;
    logged_out_at: string | null;
    failure_reason: string | null;
}

defineProps<{
    sessions: SessionItem[];
    loginActivities: LoginActivityItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Security settings',
        href: '/settings/security',
    },
];

const revokeForm = useForm({
    password: '',
});

const submit = () => {
    revokeForm.delete(route('security.sessions.destroy-other'), {
        preserveScroll: true,
        onSuccess: () => revokeForm.reset(),
    });
};

const sessionIcon = (userAgent: string | null) => {
    const agent = (userAgent || '').toLowerCase();
    return /mobile|android|iphone/.test(agent) ? Smartphone : LaptopMinimal;
};

const activityClass = (status: string) =>
    status === 'success'
        ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
        : 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Security settings" />

        <SettingsLayout>
            <div class="space-y-8">
                <div class="space-y-3">
                    <HeadingSmall title="Security overview" description="Monitor active sessions and recent login activity for your account" />
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Halaman ini membantu Anda mengaudit perangkat yang masih aktif dan meninjau riwayat login terbaru.
                    </div>
                </div>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <ShieldCheck class="h-5 w-5 text-slate-500" />
                        <h3 class="text-base font-semibold text-slate-900">Active Sessions</h3>
                    </div>

                    <div class="space-y-3">
                        <article
                            v-for="session in sessions"
                            :key="session.id"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm"
                        >
                            <div class="flex items-start gap-3">
                                <component :is="sessionIcon(session.user_agent)" class="mt-1 h-5 w-5 text-slate-400" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-slate-900">{{ session.ip_address || 'Unknown IP' }}</p>
                                        <span
                                            v-if="session.is_current"
                                            class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200"
                                        >
                                            Current Session
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ session.user_agent || 'Unknown device' }}</p>
                                    <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-400">
                                        Last activity: {{ session.last_activity || '-' }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <form class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="security-password">Confirm Password</Label>
                            <Input
                                id="security-password"
                                v-model="revokeForm.password"
                                type="password"
                                placeholder="Masukkan password untuk logout sesi lain"
                            />
                            <InputError :message="revokeForm.errors.password" />
                        </div>

                        <Button :disabled="revokeForm.processing">Log out other sessions</Button>
                    </form>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <Activity class="h-5 w-5 text-slate-500" />
                        <h3 class="text-base font-semibold text-slate-900">Recent Login Activity</h3>
                    </div>

                    <div class="space-y-3">
                        <article
                            v-for="activity in loginActivities"
                            :key="activity.id"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="activityClass(activity.status)">
                                            {{ activity.status }}
                                        </span>
                                        <p class="font-medium text-slate-900">{{ activity.ip_address || 'Unknown IP' }}</p>
                                    </div>
                                    <p class="mt-2 truncate text-sm text-slate-500">{{ activity.user_agent || 'Unknown device' }}</p>
                                    <p v-if="activity.failure_reason" class="mt-2 text-xs uppercase tracking-[0.2em] text-rose-500">
                                        Reason: {{ activity.failure_reason }}
                                    </p>
                                </div>
                                <div class="text-right text-xs uppercase tracking-[0.2em] text-slate-400">
                                    <p>Login: {{ activity.logged_in_at || '-' }}</p>
                                    <p class="mt-2">Logout: {{ activity.logged_out_at || '-' }}</p>
                                </div>
                            </div>
                        </article>

                        <div v-if="loginActivities.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                            Belum ada riwayat login untuk akun ini.
                        </div>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
