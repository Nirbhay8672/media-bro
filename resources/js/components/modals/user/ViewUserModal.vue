<template>
  <div
    v-if="isOpen && user"
    class="fixed inset-0 z-50 flex items-center justify-center" style="background-color: rgba(0, 0, 0, 0.4);"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">User Details</h2>
          <button
            @click="closeModal"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Basic Information -->
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
            <div class="space-y-4">
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</label>
                <p class="text-sm text-gray-900 dark:text-white">{{ user.name }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                <p class="text-sm text-gray-900 dark:text-white">{{ user.email }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Username</label>
                <p class="text-sm text-gray-900 dark:text-white">{{ user.username || 'Not set' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Mobile</label>
                <p class="text-sm text-gray-900 dark:text-white">{{ user.mobile || 'Not set' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</label>
                <div class="mt-1">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getRoleBadgeClass(user.role)"
                  >
                    {{ user.role.replace('_', ' ').toUpperCase() }}
                  </span>
                </div>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Templates Created</label>
                <p class="text-sm text-gray-900 dark:text-white">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ user.templates_count || 0 }} templates
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- Subscription Information -->
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Subscription Information</h3>
            <div class="space-y-4">
              <div v-if="user.subscription_start_date && user.subscription_end_date">
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</label>
                <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(user.subscription_start_date) }}</p>
              </div>
              <div v-if="user.subscription_start_date && user.subscription_end_date">
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</label>
                <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(user.subscription_end_date) }}</p>
              </div>
              <div v-if="!user.subscription_start_date || !user.subscription_end_date">
                <p class="text-sm text-gray-500 dark:text-gray-400">No subscription information available</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { User } from '@/types';

interface Props {
  isOpen: boolean;
  user: User | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
}>();

const closeModal = () => {
  emit('close');
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const getRoleBadgeClass = (role: string) => {
  switch (role) {
    case 'super_admin':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    case 'admin':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    case 'user':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
  }
};
</script>
