<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import templates from '@/routes/templates';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Image, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const mainNavItems = computed((): NavItem[] => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Templates',
            href: templates.index(),
            icon: Image,
        },
    ];

    // Add user management for super admins
    if (user.value?.role === 'super_admin') {
        items.push({
            title: 'Users',
            href: '/users',
            icon: Users,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-slate-200/60 dark:border-slate-700/60 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <SidebarHeader class="border-b border-slate-700/60 dark:border-gray-600/60 bg-gradient-to-br from-slate-800 via-gray-800 to-slate-900 shadow-xl">
            <!-- Empty header - logo moved to navbar -->
        </SidebarHeader>

        <SidebarContent class="bg-gradient-to-b from-slate-800/90 to-slate-900/90 dark:from-gray-800/90 dark:to-gray-900/90">
            <div class="px-4 py-6 space-y-2">
                <NavMain :items="mainNavItems" />
            </div>
        </SidebarContent>

        <SidebarFooter class="border-t border-slate-700/60 dark:border-gray-600/60 bg-gradient-to-t from-slate-800/90 to-slate-900/90 dark:from-gray-800/90 dark:to-gray-900/90">
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
