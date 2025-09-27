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
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Image } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
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

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-sidebar-border/50">
        <SidebarHeader class="border-b border-sidebar-border/30 bg-gradient-to-br from-sidebar-background to-sidebar-accent/20">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-sidebar-accent/50 transition-colors">
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="bg-gradient-to-b from-sidebar-background to-sidebar-accent/10">
            <div class="px-3 py-4">
                <NavMain :items="mainNavItems" />
            </div>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/30 bg-gradient-to-t from-sidebar-accent/20 to-sidebar-background">
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
