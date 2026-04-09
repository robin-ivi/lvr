<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Edit, Trash } from 'lucide-vue-next';
import { can } from '@/lib/can'
const props = defineProps<{
    users: Array<{
        id: number;
        name: string;
        email: string;

    }>;
}>();


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: '/users',
            },
        ],
    },
});
</script>

<template>

    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex justify-between">
            <h1>All Users</h1>
            <Link href="/users/create"
                class="mb-4 inline-block rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                Create User
            </Link>
        </div>
        <table class="w-full table-auto border-collapse text-left">
            <thead>
                <tr>
                    <th class="border-b border-sidebar-border/70 px-4 py-2 font-medium dark:border-sidebar-border">
                        ID
                    </th>
                    <th class="border-b border-sidebar-border/70 px-4 py-2 font-medium dark:border-sidebar-border">
                        Name
                    </th>
                    <th class="border-b border-sidebar-border/70 px-4 py-2 font-medium dark:border-sidebar-border">
                        Email
                    </th>
                    <th class="border-b border-sidebar-border/70 px-4 py-2 font-medium dark:border-sidebar-border">
                        Permissions
                    </th>
                    <th class="border-b border-sidebar-border/70 px-4 py-2 font-medium dark:border-sidebar-border">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in props.users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                        {{ user.id }}
                    </td>
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                        {{ user.name }}
                    </td>
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                        {{ user.email }}
                    </td>
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                         <span v-for="role in user.roles" :key="role" class="rounded bg-green-400 p-1 m-1">
                            {{ role.name }}
                        </span>
                    </td>
                    <td
                        class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border flex items-center">
                        <!-- Actions like Edit/Delete can go here -->
                        <Link :href="`/users/${user.id}/edit`"
                            class="bg-blue-500 hover:underline p-2 rounded-full cursor-pointer flex items-center">
                            <Edit class="w-5 h-5" />
                        </Link>
                        <Link v-if="can('user.delete')"  :href="`/users/${user.id}`" method="delete"
                            class="bg-red-500 hover:underline ml-2 p-2 rounded-full cursor-pointer flex items-center">
                            <Trash class="w-5 h-5" />
                        </Link>
                    </td>

                </tr>
            </tbody>
        </table>
    </div>
</template>
