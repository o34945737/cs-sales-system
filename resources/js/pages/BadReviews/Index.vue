<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    badReviews: Object,
});
</script>

<template>
    <Head title="Bad Reviews" />
    <AppLayout :breadcrumbs="[{title: 'Dashboard', href: '/dashboard'}, {title: 'Bad Reviews', href: '/bad-reviews'}]">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 p-8 text-center">
                    <h2 class="text-2xl font-bold text-gray-800">Bad Reviews Workspace</h2>
                    <p class="mt-2 text-gray-500 mb-6">Modul Halaman Bad Review (Phase 4.2) segera dirender di sini!</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
