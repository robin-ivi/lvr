<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Edit, Trash } from 'lucide-vue-next';

const props = defineProps<{
    roles: Array
}>();


defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles',
                href: '/roles',
            },
        ],
    },
});
</script>

<template>

    <Head title="Roles" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex justify-between">
            <h1>All Roles</h1>
            <Link href="/roles/create"
                class="mb-4 inline-block rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                Create Roles
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
                        Permissions
                    </th>
                    <th class="border-b border-sidebar-border/70 px-4 py-2 font-medium dark:border-sidebar-border">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="role in props.roles" :key="role.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                        {{ role.id }}
                    </td>
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                        {{ role.name }}
                    </td>
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                        <span v-for="permission in role.permissions" :key="permission" class="rounded bg-green-400 p-1 m-1">
                            {{ permission.name }}
                        </span>
                    </td>
                    <td class="border-b border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border flex items-center">
                        <!-- Actions like Edit/Delete can go here -->
                        <Link :href="`/roles/${ role.id }/edit`" class="bg-blue-500 hover:underline p-2 rounded-full cursor-pointer flex items-center">
                            <Edit class="w-5 h-5" />
                        </Link>
                        <Link :href="`/roles/${ role.id }`" method="delete" class="bg-red-500 hover:underline ml-2 p-2 rounded-full cursor-pointer flex items-center">
                            <Trash class="w-5 h-5" />
                        </Link>
                    </td>

                </tr>
            </tbody>
        </table>
    </div>
</template>
