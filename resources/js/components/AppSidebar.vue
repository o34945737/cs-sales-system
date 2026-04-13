<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Archive, BookOpen, Folder, LayoutGrid, MessageSquare, Star, Truck, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();

const mainNavItems: NavItem[] = [];

if (page.props.auth.can.view_dashboard) {
    mainNavItems.push({
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    });
}

if (page.props.auth.can.access_complaints) {
    mainNavItems.push({
        title: 'Complaints',
        href: '/complaints',
        icon: MessageSquare,
    });
}

if (page.props.auth.can.access_bad_reviews) {
    mainNavItems.push({
        title: 'Bad Reviews',
        href: '/bad-reviews',
        icon: Star,
    });
}

if (page.props.auth.can.access_order_trackings) {
    mainNavItems.push({
        title: 'Order Tracking',
        href: '/order-trackings',
        icon: Truck,
    });
}

if (page.props.auth.can.access_oos) {
    mainNavItems.push({
        title: 'OOS Data',
        href: '/oos',
        icon: Archive,
    });
}

if (page.props.auth.can.view_users) {
    mainNavItems.push({
        title: 'Users',
        href: '/users',
        icon: Users,
    });
}

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
