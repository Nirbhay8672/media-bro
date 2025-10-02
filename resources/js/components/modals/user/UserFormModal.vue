<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center" style="background-color: rgba(0, 0, 0, 0.4);"
    @click.self="closeModal"
  >
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ isEditMode ? 'Edit User' : 'Create New User' }}
          </h2>
          <button
            @click="closeModal"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Name *
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-sm text-red-500">
                {{ form.errors.name }}
              </p>
            </div>

            <div class="space-y-2">
              <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Username *
              </label>
              <input
                id="username"
                v-model="form.username"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.username }"
              />
              <p v-if="form.errors.username" class="text-sm text-red-500">
                {{ form.errors.username }}
              </p>
            </div>

            <div class="space-y-2">
              <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Email *
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.email }"
              />
              <p v-if="form.errors.email" class="text-sm text-red-500">
                {{ form.errors.email }}
              </p>
            </div>

            <div class="space-y-2">
              <label for="mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Mobile
              </label>
              <input
                id="mobile"
                v-model="form.mobile"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.mobile }"
              />
              <p v-if="form.errors.mobile" class="text-sm text-red-500">
                {{ form.errors.mobile }}
              </p>
            </div>

            <div class="space-y-2">
              <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Password {{ isEditMode ? '(leave blank to keep current)' : '*' }}
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                :required="!isEditMode"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.password }"
              />
              <p v-if="form.errors.password" class="text-sm text-red-500">
                {{ form.errors.password }}
              </p>
            </div>

            <div class="space-y-2">
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Confirm Password {{ isEditMode ? '' : '*' }}
              </label>
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                :required="!isEditMode"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.password_confirmation }"
              />
              <p v-if="form.errors.password_confirmation" class="text-sm text-red-500">
                {{ form.errors.password_confirmation }}
              </p>
            </div>

            <div class="space-y-2">
              <label for="subscription_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Subscription Start Date *
              </label>
              <input
                id="subscription_start_date"
                v-model="form.subscription_start_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.subscription_start_date }"
              />
              <p v-if="form.errors.subscription_start_date" class="text-sm text-red-500">
                {{ form.errors.subscription_start_date }}
              </p>
            </div>

            <div class="space-y-2">
              <label for="subscription_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Subscription End Date *
              </label>
              <input
                id="subscription_end_date"
                v-model="form.subscription_end_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                :class="{ 'border-red-500': form.errors.subscription_end_date }"
              />
              <p v-if="form.errors.subscription_end_date" class="text-sm text-red-500">
                {{ form.errors.subscription_end_date }}
              </p>
            </div>

          </div>

          <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-600">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {{ form.processing ? (isEditMode ? 'Updating...' : 'Creating...') : (isEditMode ? 'Update User' : 'Create User') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch, computed } from 'vue';
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

const isEditMode = computed(() => !!props.user);

const form = useForm({
  name: '',
  username: '',
  email: '',
  mobile: '',
  password: '',
  password_confirmation: '',
  subscription_start_date: '',
  subscription_end_date: '',
  role: 'admin' as 'super_admin' | 'admin' | 'user',
});

const closeModal = () => {
  form.reset();
  form.clearErrors();
  emit('close');
};

const submit = () => {
  if (isEditMode.value && props.user) {
    // Edit mode - use PUT request
    form.post(users.update.url(props.user.id), {
      onSuccess: () => {
        Swal.fire({
          title: 'Success!',
          text: 'User updated successfully.',
          icon: 'success',
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          toast: true
        });
        closeModal();
        emit('success');
      },
      onError: () => {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to update user. Please try again.',
          icon: 'error',
          position: 'top-end',
          showConfirmButton: false,
          timer: 4000,
          timerProgressBar: true,
          toast: true
        });
      }
    });
  } else {
    // Create mode - use POST request
    form.post(users.store.url(), {
      onSuccess: () => {
        Swal.fire({
          title: 'Success!',
          text: 'User created successfully.',
          icon: 'success',
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          toast: true
        });
        closeModal();
        emit('success');
      },
      onError: () => {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to create user. Please try again.',
          icon: 'error',
          position: 'top-end',
          showConfirmButton: false,
          timer: 4000,
          timerProgressBar: true,
          toast: true
        });
      }
    });
  }
};

// Initialize form when modal opens
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    if (props.user) {
      // Edit mode - populate with user data
      form.name = props.user.name;
      form.username = props.user.username || '';
      form.email = props.user.email;
      form.mobile = props.user.mobile || '';
      form.password = '';
      form.password_confirmation = '';
      form.subscription_start_date = props.user.subscription_start_date ? new Date(props.user.subscription_start_date).toISOString().split('T')[0] : '';
      form.subscription_end_date = props.user.subscription_end_date ? new Date(props.user.subscription_end_date).toISOString().split('T')[0] : '';
      form.role = 'admin'; // Always set as admin
    } else {
      // Create mode - reset form
      form.reset();
      form.role = 'admin'; // Always set as admin
    }
    form.clearErrors();
  }
});
</script>
