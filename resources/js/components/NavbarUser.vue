<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import UserMenuContent from './UserMenuContent.vue';

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button class="flex items-center space-x-3 px-3 py-2 hover:bg-slate-700/60 dark:hover:bg-gray-700/60 transition-all duration-200 group">
                <UserInfo :user="user" />
                <ChevronDown class="h-4 w-4 text-slate-300 dark:text-gray-400 transition-transform duration-200 group-data-[state=open]:rotate-180" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            class="w-80 shadow-2xl border border-slate-700/60 dark:border-gray-600/60 bg-slate-800 dark:bg-gray-900"
            align="end"
            :side-offset="4"
        >
            <UserMenuContent :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
