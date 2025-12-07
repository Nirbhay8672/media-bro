<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
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
import { LayoutGrid, Image, File, Users } from 'lucide-vue-next';
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
    ];

    // Get user's template access (default to 'both' for super admins or if not set)
    const templateAccess = user.value?.role === 'super_admin' 
        ? 'both' 
        : (user.value?.template_access || 'both');

    // Add Templates menu item based on template_access
    if (templateAccess === 'image' || templateAccess === 'both') {
        items.push({
            title: 'Templates',
            href: templates.index(),
            icon: Image,
        });
    }

    // Add PDF Templates menu item based on template_access
    if (templateAccess === 'pdf' || templateAccess === 'both') {
        items.push({
            title: 'PDF Templates',
            href: '/pdf-templates',
            icon: File,
        });
    }

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
    <Sidebar collapsible="icon" variant="inset" class="border-r border-slate-200/60 dark:border-slate-700/60 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 mt-16">
        <SidebarContent class="bg-gradient-to-b from-slate-800/90 to-slate-900/90 dark:from-gray-800/90 dark:to-gray-900/90">
            <div class="px-4 py-6 space-y-2">
                <NavMain :items="mainNavItems" />
            </div>
        </SidebarContent>

        <SidebarFooter class="border-t border-slate-700/60 dark:border-gray-600/60 bg-gradient-to-t from-slate-800/90 to-slate-900/90 dark:from-gray-800/90 dark:to-gray-900/90">
            <NavFooter :items="footerNavItems" />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
