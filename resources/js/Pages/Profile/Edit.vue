<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <!-- Memakai AppLayout (shell aplikasi) — AuthenticatedLayout bawaan Breeze memanggil route('dashboard') yang tidak ada -->
    <AppLayout title="Profil">
        <Head title="Profil" />

        <h1 class="mb-6 text-2xl font-semibold leading-8 text-gray-900">Profil</h1>

        <div class="max-w-3xl space-y-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <UpdatePasswordForm />
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <DeleteUserForm />
            </div>
        </div>
    </AppLayout>
</template>
