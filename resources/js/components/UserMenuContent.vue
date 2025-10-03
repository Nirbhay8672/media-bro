<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';

interface Props {
    user: User;
}

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <div class="p-4">
        <DropdownMenuLabel class="p-0 font-normal">
            <div class="flex items-center gap-3 px-2 py-2 text-left">
                <UserInfo :user="user" :show-email="true" />
            </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator class="my-2" />
        <DropdownMenuGroup>
            <DropdownMenuItem :as-child="true" class="px-3 py-2 hover:bg-slate-700/50 dark:hover:bg-gray-700/50 rounded-lg mx-1">
                <Link class="flex items-center w-full text-slate-200 dark:text-gray-300 hover:text-white dark:hover:text-gray-100" :href="edit()" prefetch as="button">
                    <Settings class="mr-3 h-4 w-4" />
                    Settings
                </Link>
            </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator class="my-2" />
        <DropdownMenuItem :as-child="true" class="px-3 py-2 hover:bg-slate-700/50 dark:hover:bg-gray-700/50 rounded-lg mx-1">
            <Link
                class="flex items-center w-full text-slate-200 dark:text-gray-300 hover:text-white dark:hover:text-gray-100"
                :href="logout()"
                @click="handleLogout"
                as="button"
                data-test="logout-button"
            >
                <LogOut class="mr-3 h-4 w-4" />
                Log out
            </Link>
        </DropdownMenuItem>
    </div>
</template>
