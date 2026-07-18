<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ vehicles: Array });

const form = useForm({
    vehicle_id: '',
    type: 'carte_grise',
    title: '',
    file: null,
    is_public: false,
    expires_at: '',
});

const onFile = (e) => {
    form.file = e.target.files[0];
};
</script>

<template>
  <Head title="Nouveau document" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-slate-900">Ajouter un document</h2>
        <Link :href="route('documents.index')" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Retour</Link>
      </div>
    </template>

    <div class="mx-auto max-w-3xl px-4 py-8">
      <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="form.post(route('documents.store'), { forceFormData: true })">
        <div>
          <label class="mb-1 block text-sm font-medium">Véhicule</label>
          <select v-model="form.vehicle_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2">
            <option value="">Sélectionner</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }} — {{ v.brand }} {{ v.model }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Type</label>
          <select v-model="form.type" class="w-full rounded-2xl border border-slate-200 px-4 py-2">
            <option value="carte_grise">Carte grise</option>
            <option value="assurance">Assurance</option>
            <option value="facture">Facture</option>
            <option value="controle_technique">Contrôle technique</option>
            <option value="certificat_inspection">Certificat d'inspection (IPFS)</option>
            <option value="autre">Autre</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Titre</label>
          <input v-model="form.title" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Fichier</label>
          <input type="file" @change="onFile" class="w-full text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Expiration</label>
          <input v-model="form.expires_at" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_public" type="checkbox" /> Publier sur IPFS (certificats publics)
        </label>
        <button :disabled="form.processing" class="rounded-full bg-indigo-600 px-6 py-2 font-semibold text-white">Enregistrer</button>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
