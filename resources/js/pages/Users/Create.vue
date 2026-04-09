<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    'roles': Array
})

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users - Create',
                href: '/users',
            },
        ],
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    roles: []
});
</script>

<template>

    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex justify-between">
            <h1>Create User</h1>
            <Link href="/users" class="mb-4 inline-block rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                Back
            </Link>
        </div>
        <form @submit.prevent="form.post('/users')" class="max-w-md space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-white">Name</label>
                <input type="text" id="name" v-model="form.name"
                    class="mt-1 block w-full rounded-md border border-amber-300 focus:border-amber-500 focus:ring focus:ring-amber-200 focus:ring-opacity-50 p-3"
                    placeholder="Enter name">
                <span class="text-red-500">{{ form.errors.name }}</span>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-white">Email</label>
                <input type="email" id="email" v-model="form.email"
                    class="mt-1 block w-full rounded-md border border-amber-300 focus:border-amber-500 focus:ring focus:ring-amber-200 focus:ring-opacity-50 p-3"
                    placeholder="Enter email">
                <span class="text-red-500">{{ form.errors.email }}</span>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-white">Password</label>
                <input type="password" id="password" v-model="form.password"
                    class="mt-1 block w-full rounded-md border border-amber-300 focus:border-amber-500 focus:ring focus:ring-amber-200 focus:ring-opacity-50 p-3"
                    placeholder="Enter password">
                <span class="text-red-500">{{ form.errors.password }}</span>
            </div>
            <div>
                <label v-for="role in roles" :key="role" class="flex items-center space-x-2">
                    <input type="checkbox" :value="role" v-model="form.roles">
                    <span>{{ role }}</span>
                </label>
            </div>
            <button type="submit" class="w-full rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">Create
                User</button>
        </form>
    </div>
</template>
