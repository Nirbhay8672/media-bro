<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center" style="background-color: rgba(0, 0, 0, 0.4);"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0 w-10 h-10 mx-auto flex items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
        </div>
        
        <div class="text-center">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Delete User
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            Are you sure you want to delete <strong>{{ user?.name }}</strong>? This action cannot be undone.
          </p>
        </div>

        <div class="flex justify-center gap-4">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="confirmDelete"
            :disabled="isDeleting"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
          >
            {{ isDeleting ? 'Deleting...' : 'Delete User' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import users from '@/routes/users';
import type { User } from '@/types';
import Swal from 'sweetalert2';

interface Props {
  isOpen: boolean;
  user: User | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
  success: [];
}>();

const isDeleting = ref(false);

const closeModal = () => {
  isDeleting.value = false;
  emit('close');
};

const confirmDelete = () => {
  if (!props.user) return;
  
  Swal.fire({
    title: 'Are you sure?',
    text: `You are about to delete ${props.user.name}. This action cannot be undone!`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      isDeleting.value = true;
      
      router.delete(users.destroy.url(props.user.id), {
        onSuccess: () => {
          Swal.fire({
            title: 'Deleted!',
            text: 'User has been deleted successfully.',
            icon: 'success',
            confirmButtonText: 'OK'
          });
          closeModal();
          emit('success');
        },
        onError: () => {
          isDeleting.value = false;
          Swal.fire({
            title: 'Error!',
            text: 'Failed to delete user. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
          });
        },
      });
    }
  });
};
</script>
