<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({ code: '' });
</script>

<template>
    <Head title="Vérification MFA" />

    <GuestLayout>
        <div class="mx-auto flex min-h-screen max-w-2xl items-center px-4 py-16 sm:px-6 lg:px-8">
            <div class="w-full rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-2xl backdrop-blur">
                <h1 class="text-2xl font-semibold text-white">Vérification en deux étapes</h1>
                <p class="mt-3 text-sm leading-6 text-slate-300">
                    Un code à 6 chiffres a été envoyé à votre adresse email. Saisissez-le pour accéder à votre espace.
                </p>

                <form @submit.prevent="form.post(route('mfa.verify.store'))" class="mt-8 space-y-6">
                    <div>
                        <InputLabel for="code" value="Code de vérification" class="text-slate-200" />
                        <TextInput id="code" v-model="form.code" type="text" inputmode="numeric" autocomplete="one-time-code" class="mt-1 block w-full border-slate-700 bg-slate-800 text-white" required />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <div class="flex items-center justify-between">
                        <PrimaryButton :disabled="form.processing">Valider</PrimaryButton>
                        <Link href="/login" class="text-sm text-slate-400 hover:text-white">Retour à la connexion</Link>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
