<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
const avatarPreview = ref(user.avatar_url);
const logoPreview = ref(user.company_logo_url);

const form = useForm({
    name: user.name,
    email: user.email,
    phone_number: user.phone_number ?? '',
    company_name: user.company_name ?? '',
    bio: user.bio ?? '',
    theme_preference: user.theme_preference ?? 'system',
    notification_email: user.notification_email ?? true,
    avatar: null,
    company_logo: null,
});

const onAvatar = (e) => {
    const file = e.target.files?.[0];
    form.avatar = file || null;
    avatarPreview.value = file ? URL.createObjectURL(file) : user.avatar_url;
};

const onLogo = (e) => {
    const file = e.target.files?.[0];
    form.company_logo = file || null;
    logoPreview.value = file ? URL.createObjectURL(file) : user.company_logo_url;
};

const submit = () => {
    form.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Informations du profil</h2>
            <p class="mt-1 text-sm text-slate-500">
                Photo, logo entreprise, thème, wallet et coordonnées.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-8 space-y-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <InputLabel value="Photo de profil" />
                    <div class="mt-3 flex items-center gap-4">
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            class="h-20 w-20 rounded-full bg-slate-100 object-cover"
                            alt=""
                            @error="avatarPreview = null"
                        />
                        <div
                            v-else
                            class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-50 text-xl font-bold text-indigo-700"
                        >
                            {{ user.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" @change="onAvatar" class="text-sm" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.avatar" />
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG, GIF ou WebP (pas HEIC iPhone). Max 4 Mo.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                    <InputLabel value="Logo entreprise (navbar)" />
                    <div class="mt-3 flex items-center gap-4">
                        <img
                            v-if="logoPreview"
                            :src="logoPreview"
                            class="h-16 w-auto max-w-[140px] rounded-lg bg-slate-100 object-contain p-1"
                            alt=""
                            @error="logoPreview = null"
                        />
                        <div
                            v-else
                            class="flex h-16 w-28 items-center justify-center rounded-xl border border-dashed border-slate-300 text-xs text-slate-400"
                        >
                            Aucun logo
                        </div>
                        <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" @change="onLogo" class="text-sm" />
                    </div>
                    <InputError class="mt-2" :message="form.errors.company_logo" />
                    <p class="mt-2 text-xs text-slate-500">Préférez un PNG transparent ou un JPG. Evitez HEIC.</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <InputLabel for="name" value="Nom" />
                    <TextInput id="name" class="mt-1 block w-full" v-model="form.name" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                <div>
                    <InputLabel for="phone_number" value="Téléphone" />
                    <TextInput id="phone_number" class="mt-1 block w-full" v-model="form.phone_number" />
                </div>
                <div>
                    <InputLabel for="company_name" value="Entreprise" />
                    <TextInput id="company_name" class="mt-1 block w-full" v-model="form.company_name" />
                </div>
                <div class="md:col-span-2">
                    <InputLabel for="bio" value="Biographie" />
                    <textarea
                        id="bio"
                        v-model="form.bio"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                    />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <InputLabel for="theme_preference" value="Thème d'affichage" />
                    <select
                        id="theme_preference"
                        v-model="form.theme_preference"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                    >
                        <option value="system">Système (auto)</option>
                        <option value="light">Clair</option>
                        <option value="dark">Sombre</option>
                    </select>
                </div>
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                    <input
                        type="checkbox"
                        v-model="form.notification_email"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span class="text-sm text-slate-700 dark:text-slate-300">Notifications email (alertes flotte)</span>
                </label>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-sm text-slate-700">
                    Email non vérifié.
                    <Link :href="route('verification.send')" method="post" as="button" class="underline">
                        Renvoyer le lien
                    </Link>
                </p>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Enregistrer le profil</PrimaryButton>
                <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600">Enregistré.</p>
            </div>
        </form>
    </section>
</template>
