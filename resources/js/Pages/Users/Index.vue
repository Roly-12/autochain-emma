<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({ users: Object, roles: Array });

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'auditeur',
});

const updateRole = (user, role) => {
    router.patch(route('users.update', user.id), { role, is_active: user.is_active });
};

const setGarageCertification = (user, certified) => {
    router.post(route('users.garage-certification', user.id), { certified });
};
</script>

<template>
  <Head title="Utilisateurs" />
  <AuthenticatedLayout>
    <template #header>
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Super Admin</p>
        <h2 class="text-2xl font-semibold text-slate-900">Gestion des comptes</h2>
      </div>
    </template>

    <div class="mx-auto max-w-6xl px-4 py-8 grid gap-6 lg:grid-cols-[1fr_1.2fr]">
      <form class="space-y-3 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm h-fit" @submit.prevent="form.post(route('users.store'))">
        <h3 class="font-semibold text-slate-900">Créer un utilisateur</h3>
        <input v-model="form.name" placeholder="Nom" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        <input v-model="form.email" type="email" placeholder="Email" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        <input v-model="form.password" type="password" placeholder="Mot de passe" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        <select v-model="form.role" class="w-full rounded-2xl border border-slate-200 px-4 py-2">
          <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
        </select>
        <p class="text-xs text-slate-500">Le wallet sera lié par signature MetaMask depuis le compte de l’utilisateur.</p>
        <button class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">Créer</button>
      </form>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-3">
          <div v-for="user in users.data" :key="user.id" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 px-4 py-3">
            <div>
              <p class="font-medium text-slate-900">{{ user.name }}</p>
              <p class="text-sm text-slate-500">{{ user.email }}</p>
              <p class="text-xs text-slate-500">{{ user.wallet_verified_at ? 'Wallet vérifié' : 'Wallet non lié' }}</p>
            </div>
            <button
              v-if="user.role === 'garagiste_agree' && user.wallet_verified_at"
              @click="setGarageCertification(user, !user.is_verified_onchain)"
              class="rounded-full border border-indigo-200 px-3 py-1 text-xs font-semibold text-indigo-700"
            >
              {{ user.is_verified_onchain ? 'Révoquer on-chain' : 'Certifier on-chain' }}
            </button>
            <select :value="user.role" @change="updateRole(user, $event.target.value)" class="rounded-xl border border-slate-200 px-3 py-1 text-sm">
              <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
