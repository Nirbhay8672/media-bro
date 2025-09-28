<template>
    <div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8 text-center">
            <div class="mb-6">
                <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Subscription Expired</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Your subscription has expired. Please contact your administrator to renew your access.
                </p>
            </div>
            
            <div class="space-y-4">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Subscription Details</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <p v-if="user?.subscription_start_date">
                            <strong>Start Date:</strong> {{ formatDate(user.subscription_start_date) }}
                        </p>
                        <p v-if="user?.subscription_end_date">
                            <strong>End Date:</strong> {{ formatDate(user.subscription_end_date) }}
                        </p>
                        <p v-if="user?.subscription_end_date">
                            <strong>Status:</strong> 
                            <span class="text-red-500 font-semibold">Expired</span>
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-center">
                    <button 
                        @click="logout" 
                        class="bg-red-500 hover:bg-red-600 text-white font-medium py-3 px-8 rounded-lg transition-colors"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const logout = () => {
    // Use POST method for logout
    router.post('/logout')
}
</script>


