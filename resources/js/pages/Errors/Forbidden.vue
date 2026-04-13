<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, LogOut, ShieldAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    message?: string;
}>();

const page = usePage<SharedData>();
const canGoDashboard = computed(() => page.props.auth?.can?.view_dashboard);
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(30,64,175,0.10),_transparent_25%),linear-gradient(180deg,_#f8fafc_0%,_#e2e8f0_100%)] px-6 py-10">
        <Head title="403 Forbidden" />

        <div class="w-full max-w-3xl overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.12)]">
            <div class="bg-[linear-gradient(135deg,_#0f172a_0%,_#1e293b_45%,_#7f1d1d_100%)] px-8 py-8 text-white">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20">
                        <ShieldAlert class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-300">Access Control</p>
                        <h1 class="mt-2 text-3xl font-semibold">403 Forbidden</h1>
                    </div>
                </div>
            </div>

            <div class="space-y-6 px-8 py-8">
                <div class="space-y-2">
                    <p class="text-lg font-medium text-slate-900">Akses ke halaman ini belum tersedia untuk akun Anda.</p>
                    <p class="text-sm leading-6 text-slate-500">
                        {{ props.message || 'Role atau permission akun Anda belum mencakup modul ini. Jika ini seharusnya bisa diakses, hubungi administrator.' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                    Sistem sekarang memakai permission per modul. Logout lalu login ulang dengan akun admin jika Anda memang ingin mengelola akses.
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button v-if="canGoDashboard" as-child class="rounded-xl">
                        <Link :href="route('dashboard')">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Dashboard
                        </Link>
                    </Button>

                    <Button variant="outline" as-child class="rounded-xl">
                        <Link :href="route('logout')" method="post" as="button">
                            <LogOut class="h-4 w-4" />
                            Log out
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
