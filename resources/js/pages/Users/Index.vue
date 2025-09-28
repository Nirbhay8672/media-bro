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
import { MoreHorizontal, Plus, Edit, Trash2, Eye } from 'lucide-vue-next';
import { ref } from 'vue';
import UserFormModal from '@/components/modals/user/UserFormModal.vue';
import ViewUserModal from '@/components/modals/user/ViewUserModal.vue';
import DeleteUserModal from '@/components/modals/user/DeleteUserModal.vue';
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

// Modal state
const showUserFormModal = ref(false);
const showViewModal = ref(false);
const showDeleteModal = ref(false);
const selectedUser = ref<User | null>(null);

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

// Modal handlers
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

const openDeleteModal = (user: User) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
};

const closeModals = () => {
    showUserFormModal.value = false;
    showViewModal.value = false;
    showDeleteModal.value = false;
    selectedUser.value = null;
};

const refreshUsers = () => {
    router.reload();
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
                            <TableHead>Subscription</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead class="w-[50px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>{{ user.username || '-' }}</TableCell>
                            <TableCell>
                                <div v-if="user.subscription_start_date && user.subscription_end_date" class="text-sm">
                                    <div>Start: {{ formatDate(user.subscription_start_date) }}</div>
                                    <div>End: {{ formatDate(user.subscription_end_date) }}</div>
                                </div>
                                <span v-else class="text-muted-foreground">No subscription</span>
                            </TableCell>
                            <TableCell>{{ formatDate(user.created_at) }}</TableCell>
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
                                            @click="openDeleteModal(user)"
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
        
        <DeleteUserModal 
            :is-open="showDeleteModal" 
            :user="selectedUser"
            @close="closeModals"
            @success="refreshUsers"
        />
    </AppLayout>
</template>

