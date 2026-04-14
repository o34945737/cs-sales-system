<script setup lang="ts">
import GlobalFlashToasts from '@/components/GlobalFlashToasts.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';
import { ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-transparent">
        <GlobalFlashToasts />

        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-950/35 backdrop-blur-[1px] lg:hidden"
            @click="sidebarOpen = false"
        />

        <div class="mx-auto flex min-h-screen max-w-[1600px]">
            <AppSidebar :mobile-open="sidebarOpen" @close="sidebarOpen = false" />

            <div class="flex min-w-0 flex-1 flex-col">
                <AppSidebarHeader :breadcrumbs="breadcrumbs" @open-sidebar="sidebarOpen = true" />

                <main class="flex-1 px-4 pb-6 pt-2 sm:px-6 lg:px-8 lg:pb-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
