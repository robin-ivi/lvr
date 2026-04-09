<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles - Edit',
                href: '/roles',
            },
        ],
    },
});


const props = defineProps({
    'role': Object,
    'permissions': Array,
    'rolePermission': Array
})

const form = useForm({
    name: props.role.name,
    permissions: props.rolePermission || []
});

</script>

<template>

    <Head title="Roles" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex justify-between">
            <h1>Edit Roles</h1>
            <Link href="/roles"
                class="mb-4 inline-block rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                Back
            </Link>
        </div>
        <form @submit.prevent="form.put(`/roles/${props.role.id}`)" class="max-w-md space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-white">Name</label>
                <input type="text" id="name" v-model="form.name" class="mt-1 block w-full rounded-md border border-amber-300 focus:border-amber-500 focus:ring focus:ring-amber-200 focus:ring-opacity-50 p-3" placeholder="Enter name">
                <span class="text-red-500">{{ form.errors.name }}</span>
            </div>
            <div>
                <label v-for='permission in permissions' class="block text-sm font-medium text-white flex items-center space-x-2 mb-1">
                    <input :value="permission" v-model="form.permissions" type="checkbox" class="form-checkbox h-5 w-5 text-blue-500 rounded focus:ring-2">
                    <span>{{ permission }}</span>
                </label>
                <span class="text-red-500">{{ form.errors.permissions }}</span>
            </div>
            <button type="submit" class="w-full rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">Create User</button>
        </form>
    </div>
</template>
