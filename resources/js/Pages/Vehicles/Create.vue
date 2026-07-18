<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    license_plate: '',
    vin: '',
    brand: '',
    model: '',
    year: '',
    fuel_type: 'essence',
    photo: null,
    technical_control_deadline: '',
    insurance_expiry: '',
    next_maintenance_date: '',
    next_maintenance_mileage: '',
});

const preview = ref(null);

const onPhoto = (e) => {
    const file = e.target.files?.[0] || null;
    form.photo = file;
    preview.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
    form.post(route('vehicles.store'), { forceFormData: true });
};
</script>

<template>
  <Head title="Ajouter un véhicule" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Nouveau</p>
          <h2 class="text-2xl font-semibold text-slate-900">Ajouter un véhicule</h2>
        </div>
        <Link :href="route('vehicles.index')" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Retour</Link>
      </div>
    </template>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form @submit.prevent="submit" class="grid gap-6 md:grid-cols-2">
          <div class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 p-4">
            <label class="mb-2 block text-sm font-medium text-slate-700">Photo du véhicule</label>
            <div class="flex flex-wrap items-center gap-4">
              <img
                v-if="preview"
                :src="preview"
                alt="Aperçu"
                class="h-36 w-56 rounded-2xl bg-slate-100 object-cover"
              />
              <div
                v-else
                class="flex h-36 w-56 items-center justify-center rounded-2xl bg-slate-50 text-sm text-slate-400"
              >
                Aucune photo
              </div>
              <div>
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp"
                  class="text-sm"
                  @change="onPhoto"
                />
                <p class="mt-2 text-xs text-slate-500">JPG, PNG, GIF ou WebP — max 5 Mo (pas HEIC).</p>
                <p class="text-sm text-red-600" v-if="form.errors.photo">{{ form.errors.photo }}</p>
              </div>
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Immatriculation</label>
            <input v-model="form.license_plate" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
            <p class="text-sm text-red-600" v-if="form.errors.license_plate">{{ form.errors.license_plate }}</p>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">VIN</label>
            <input v-model="form.vin" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
            <p class="text-sm text-red-600" v-if="form.errors.vin">{{ form.errors.vin }}</p>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Marque</label>
            <input v-model="form.brand" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Modèle</label>
            <input v-model="form.model" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Année</label>
            <input v-model="form.year" type="number" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Carburant</label>
            <select v-model="form.fuel_type" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
              <option value="essence">Essence</option>
              <option value="diesel">Diesel</option>
              <option value="electrique">Électrique</option>
              <option value="hybride">Hybride</option>
            </select>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Échéance CT</label>
            <input v-model="form.technical_control_deadline" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Fin assurance</label>
            <input v-model="form.insurance_expiry" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Prochain entretien</label>
            <input v-model="form.next_maintenance_date" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
          </div>
          <div class="md:col-span-2">
            <button :disabled="form.processing" class="rounded-full bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Enregistrer le véhicule</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
