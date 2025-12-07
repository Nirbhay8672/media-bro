<script setup lang="ts">
import { ref } from 'vue';
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
import { LogOut, Settings, Loader2 } from 'lucide-vue-next';

interface Props {
    user: User;
}

const isLoggingOut = ref(false);

const handleLogout = () => {
    isLoggingOut.value = true;
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
                class="flex items-center w-full text-slate-200 dark:text-gray-300 hover:text-white dark:hover:text-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                :href="logout()"
                @click="handleLogout"
                :disabled="isLoggingOut"
                as="button"
                data-test="logout-button"
            >
                <Loader2 v-if="isLoggingOut" class="mr-3 h-4 w-4 animate-spin" />
                <LogOut v-else class="mr-3 h-4 w-4" />
                {{ isLoggingOut ? 'Logging out...' : 'Log out' }}
            </Link>
        </DropdownMenuItem>
    </div>
</template>
