<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

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
                <div class="w-10 h-10 bg-white flex items-center justify-center shadow-lg">
                    <img 
                        src="/images/logo.png" 
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
            <!-- Search bar -->
            <div class="hidden md:flex items-center">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Search templates..."
                        class="w-64 px-4 py-2 pl-10 text-sm border border-slate-600 dark:border-gray-500 bg-slate-700/50 dark:bg-gray-800/50 text-white placeholder-slate-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                    />
                    <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </header>
</template>
