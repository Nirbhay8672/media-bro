<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();
</script>

<template>
    <SidebarGroup class="px-0 py-0">
        <SidebarMenu class="space-y-1">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="urlIsActive(item.href, page.url)"
                    :tooltip="item.title"
                    class="group relative overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.02] data-[active=true]:bg-gradient-to-r data-[active=true]:from-blue-500 data-[active=true]:to-indigo-600 data-[active=true]:text-white data-[active=true]:shadow-xl data-[active=true]:scale-[1.02] hover:bg-slate-700/60 dark:hover:bg-gray-700/60 text-slate-200 dark:text-gray-300 hover:text-white dark:hover:text-gray-100"
                >
                    <Link :href="item.href" class="flex items-center space-x-3 px-4 py-3">
                        <div class="flex-shrink-0">
                            <component :is="item.icon" class="h-5 w-5 transition-all duration-300 group-data-[active=true]:text-white group-hover:scale-110" />
                        </div>
                        <span class="font-medium transition-all duration-300 group-data-[active=true]:text-white group-hover:text-white dark:group-hover:text-gray-100 group-data-[collapsible=icon]/sidebar-wrapper:hidden">{{ item.title }}</span>
                        <div class="ml-auto h-2 w-2 bg-blue-500 opacity-0 transition-all duration-300 group-data-[active=true]:opacity-100 group-data-[active=true]:scale-110 group-data-[collapsible=icon]/sidebar-wrapper:hidden"></div>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
