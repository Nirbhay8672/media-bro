<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NavbarUser from '@/components/NavbarUser.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="fixed top-0 left-0 right-0 z-50 flex h-16 shrink-0 items-center gap-2 border-b border-slate-700/60 dark:border-gray-600/60 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4 bg-gradient-to-r from-slate-800 via-gray-800 to-slate-900 dark:from-gray-800 dark:via-gray-700 dark:to-gray-900 shadow-xl"
    >
        <div class="flex items-center gap-4">
            <!-- Logo and Name -->
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-lg">
                    <img 
                        :src="page.props.logo_url || '/images/logo.png'"
                        alt="Media Bro Logo" 
                        class="w-8 h-8 object-contain"
                    />
                </div>
                <div class="text-white">
                    <h1 class="text-lg font-bold">Media Bro</h1>
                </div>
            </div>
            
            <div class="h-6 w-px bg-slate-600 dark:bg-gray-500"></div>
            
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        
        <!-- Right side of header -->
        <div class="ml-auto flex items-center">
            <!-- User Menu -->
            <NavbarUser />
        </div>
    </header>
</template>
