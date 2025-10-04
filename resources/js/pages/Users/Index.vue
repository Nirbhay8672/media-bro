<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { MoreHorizontal, Plus, Edit, Trash2, Eye, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import UserFormModal from '@/components/modals/user/UserFormModal.vue';
import ViewUserModal from '@/components/modals/user/ViewUserModal.vue';
import usersRoutes from '@/routes/users';
import Swal from 'sweetalert2';

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const props = defineProps<Props>();

const showUserFormModal = ref(false);
const showViewModal = ref(false);
const selectedUser = ref<User | null>(null);
const togglingUsers = ref<Set<number>>(new Set());

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'User Management',
        href: '/users',
    },
];

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case 'super_admin':
            return 'destructive';
        case 'admin':
            return 'default';
        case 'user':
            return 'secondary';
        default:
            return 'outline';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const openCreateModal = () => {
    selectedUser.value = null;
    showUserFormModal.value = true;
};

const openEditModal = (user: User) => {
    selectedUser.value = user;
    showUserFormModal.value = true;
};

const openViewModal = (user: User) => {
    selectedUser.value = user;
    showViewModal.value = true;
};

const deleteUser = (user: User) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete ${user.name}. This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(usersRoutes.destroy.url(user.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'User has been deleted successfully.',
                        icon: 'success',
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        toast: true
                    });
                },
                onError: () => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to delete user. Please try again.',
                        icon: 'error',
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        toast: true
                    });
                },
            });
        }
    });
};

const closeModals = () => {
    showUserFormModal.value = false;
    showViewModal.value = false;
    selectedUser.value = null;
};

const refreshUsers = () => {
    router.reload();
};

const toggleUserStatus = async (user: User) => {
    // Add user to loading set
    togglingUsers.value.add(user.id);
    
    try {
        const response = await fetch(`/users/${user.id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (data.success) {
            // Update the user's status in the local data
            user.is_active = data.is_active;
            
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                toast: true
            });
        } else {
            throw new Error(data.error || 'Failed to toggle user status');
        }
    } catch (error) {
        Swal.fire({
            title: 'Error!',
            text: error instanceof Error ? error.message : 'Failed to toggle user status',
            icon: 'error',
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            toast: true
        });
    } finally {
        // Remove user from loading set
        togglingUsers.value.delete(user.id);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="User Management" />

        <div class="space-y-6 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">User Management</h1>
                    <p class="text-muted-foreground">
                        Manage users and their roles in the system.
                    </p>
                </div>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Username</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Template Limit</TableHead>
                            <TableHead>Subscription</TableHead>
                            <TableHead>Templates</TableHead>
                            <TableHead class="w-[50px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>{{ user.username || '-' }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            :class="[
                                                'w-2 h-2 rounded-full',
                                                user.is_active ? 'bg-green-500' : 'bg-gray-400'
                                            ]"
                                        ></div>
                                        <span 
                                            :class="[
                                                'text-sm font-medium',
                                                user.is_active ? 'text-green-700' : 'text-gray-500'
                                            ]"
                                        >
                                            {{ user.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    
                                    <button
                                        v-if="user.role !== 'super_admin'"
                                        @click="toggleUserStatus(user)"
                                        :disabled="togglingUsers.has(user.id)"
                                        :title="user.is_active ? 'Click to deactivate account' : 'Click to activate account'"
                                        :class="[
                                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                                            user.is_active ? 'bg-indigo-600' : 'bg-gray-200',
                                            togglingUsers.has(user.id) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:opacity-80'
                                        ]"
                                    >
                                        <span
                                            v-if="!togglingUsers.has(user.id)"
                                            :class="[
                                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                                user.is_active ? 'translate-x-6' : 'translate-x-1'
                                            ]"
                                        />
                                        <Loader2
                                            v-else
                                            class="h-3 w-3 animate-spin text-white mx-auto"
                                        />
                                    </button>
                                    
                                    <span 
                                        v-else 
                                        class="text-xs text-gray-400 italic"
                                    >
                                        Super Admin
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm">
                                    <div v-if="user.template_limit === -1" class="font-medium text-green-600">
                                        Unlimited
                                    </div>
                                    <div v-else class="font-medium">
                                        {{ user.template_limit }} templates
                                    </div>
                                    <div v-if="user.template_limit !== -1" class="text-muted-foreground">
                                        {{ user.templates_count || 0 }}/{{ user.template_limit }} used
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div v-if="user.subscription_start_date && user.subscription_end_date" class="text-sm">
                                    <div>Start: {{ formatDate(user.subscription_start_date) }}</div>
                                    <div>End: {{ formatDate(user.subscription_end_date) }}</div>
                                </div>
                                <span v-else class="text-muted-foreground">No subscription</span>
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary">
                                    {{ user.templates_count || 0 }} templates
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="openViewModal(user)">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="openEditModal(user)">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="user.role !== 'super_admin'"
                                            @click="deleteUser(user)"
                                            class="text-destructive"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination would go here -->
            <div v-if="users.last_page > 1" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ (users.current_page - 1) * users.per_page + 1 }} to 
                    {{ Math.min(users.current_page * users.per_page, users.total) }} of 
                    {{ users.total }} results
                </div>
                <!-- Add pagination component here if needed -->
            </div>
        </div>

        <!-- Modals -->
        <UserFormModal 
            :is-open="showUserFormModal" 
            :user="selectedUser"
            @close="closeModals"
            @success="refreshUsers"
        />
        
        <ViewUserModal 
            :is-open="showViewModal" 
            :user="selectedUser"
            @close="closeModals"
        />
    </AppLayout>
</template>

