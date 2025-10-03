<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar class="h-10 w-10 overflow-hidden ring-2 ring-white/20 shadow-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback class="text-white font-semibold bg-gradient-to-br from-blue-500 to-indigo-600">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold text-slate-200 dark:text-white">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-slate-400 dark:text-gray-400">{{
            user.email
        }}</span>
        <span v-else class="truncate text-xs text-slate-400 dark:text-gray-400 capitalize">{{ user.role?.replace('_', ' ') }}</span>
    </div>
</template>
