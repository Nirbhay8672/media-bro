<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const user = page.props.auth.user;
const { isMobile, state } = useSidebar();
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="group relative overflow-hidden transition-all duration-300 hover:shadow-lg hover:scale-[1.02] data-[state=open]:bg-gradient-to-r data-[state=open]:from-slate-700 data-[state=open]:to-slate-600 dark:data-[state=open]:from-gray-700 dark:data-[state=open]:to-gray-600 data-[state=open]:shadow-xl data-[state=open]:scale-[1.02] hover:bg-slate-700/60 dark:hover:bg-gray-700/60 text-slate-200 dark:text-gray-300 hover:text-white dark:hover:text-gray-100"
                    >
                        <UserInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4 transition-all duration-300 group-data-[state=open]:rotate-180 group-hover:scale-110" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 shadow-2xl border border-slate-700/60 dark:border-gray-600/60 bg-slate-800/95 dark:bg-gray-900/95 backdrop-blur-sm"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'left'
                              : 'bottom'
                    "
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
