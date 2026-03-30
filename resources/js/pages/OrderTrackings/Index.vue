<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    orderTrackings: Object,
});
</script>

<template>
    <Head title="Order Tracking" />
    <AppLayout :breadcrumbs="[{title: 'Dashboard', href: '/dashboard'}, {title: 'Order Tracking', href: '/order-trackings'}]">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-8 text-center">
                    <h2 class="text-2xl font-bold text-gray-800">Order Tracking Workspace</h2>
                    <p class="mt-2 text-gray-500 mb-6">Modul Halaman Order Tracking (Phase 4.3) segera dirender di sini!</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
