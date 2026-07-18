<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Mon profil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <img
                        v-if="user.avatar_url"
                        :src="user.avatar_url"
                        alt=""
                        class="h-14 w-14 rounded-full object-cover ring-2 ring-indigo-100"
                    />
                    <div
                        v-else
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-700"
                    >
                        {{ user.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">Compte</p>
                        <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ user.name }}</h2>
                        <p class="text-sm text-slate-500">{{ user.email }}</p>
                    </div>
                </div>
                <img
                    v-if="user.company_logo_url"
                    :src="user.company_logo_url"
                    alt="Logo entreprise"
                    class="h-12 w-auto max-w-[160px] object-contain"
                />
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
