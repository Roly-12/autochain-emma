<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Mot de passe oublié" />

        <section class="mx-auto w-full max-w-md px-4 py-12 sm:py-16">
          <div class="rounded-3xl border border-white/10 bg-white p-6 text-slate-900 shadow-2xl sm:p-8">
            <h1 class="text-2xl font-semibold">Mot de passe oublié</h1>
            <p class="mt-2 text-sm text-slate-600">
                Indiquez votre adresse e-mail. Nous vous enverrons un lien sécurisé pour choisir un nouveau mot de passe.
            </p>

        <div
            v-if="status"
            class="mt-4 text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form class="mt-6" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Envoyer le lien de réinitialisation
                </PrimaryButton>
            </div>
        </form>
          </div>
        </section>
    </GuestLayout>
</template>
