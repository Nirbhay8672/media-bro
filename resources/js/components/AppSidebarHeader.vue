<script setup lang="ts">
import NavbarUser from '@/components/NavbarUser.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAssets } from '@/composables/useAssets';

const { getLogoUrl } = useAssets();
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
                        :src="getLogoUrl()"
                        alt="Media Bro Logo" 
                        class="w-8 h-8 object-contain"
                    />
                </div>
                <div class="text-white">
                    <h1 class="text-lg font-bold">Media Bro</h1>
                </div>
            </div>
        </div>
        
        <!-- Right side of header -->
        <div class="ml-auto flex items-center">
            <!-- User Menu -->
            <NavbarUser />
        </div>
    </header>
</template>
