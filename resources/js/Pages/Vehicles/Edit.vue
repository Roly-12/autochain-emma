<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ vehicle: Object });

const toDateInput = (value) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value).slice(0, 10);
    }

    return date.toISOString().slice(0, 10);
};

const form = useForm({
    license_plate: props.vehicle.license_plate,
    brand: props.vehicle.brand,
    model: props.vehicle.model,
    year: props.vehicle.year,
    fuel_type: props.vehicle.fuel_type || 'essence',
    status: props.vehicle.status,
    technical_control_deadline: toDateInput(props.vehicle.technical_control_deadline),
    insurance_expiry: toDateInput(props.vehicle.insurance_expiry),
    next_maintenance_date: toDateInput(props.vehicle.next_maintenance_date),
    next_maintenance_mileage: props.vehicle.next_maintenance_mileage || '',
    photo: null,
});

const preview = ref(props.vehicle.photo_url);

const onPhoto = (e) => {
    const file = e.target.files?.[0] || null;
    form.photo = file;
    preview.value = file ? URL.createObjectURL(file) : props.vehicle.photo_url;
};

const submit = () => {
    form.post(route('vehicles.update', props.vehicle.blockchain_vehicle_id), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
  <Head title="Modifier le véhicule" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Modification</p>
          <h2 class="text-2xl font-semibold text-slate-900">{{ vehicle.license_plate }}</h2>
        </div>
        <Link :href="route('vehicles.show', vehicle.blockchain_vehicle_id)" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Retour</Link>
      </div>
    </template>

    <div class="mx-auto max-w-5xl px-4 py-8">
      <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Le <strong>VIN</strong> et le <strong>kilométrage certifié</strong> ne sont pas modifiables ici (intégrité blockchain). Utilisez le relevé km dédié pour augmenter le compteur.
      </div>

      <form @submit.prevent="submit" class="grid gap-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
        <div class="md:col-span-2">
          <label class="mb-2 block text-sm font-medium">Photo</label>
          <div class="flex flex-wrap items-center gap-4">
            <img v-if="preview" :src="preview" class="h-32 w-48 rounded-2xl object-cover bg-slate-100" alt="" />
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" @change="onPhoto" class="text-sm" />
          </div>
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium">Immatriculation</label>
          <input v-model="form.license_plate" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
          <p v-if="form.errors.license_plate" class="text-sm text-red-600">{{ form.errors.license_plate }}</p>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">VIN (lecture seule)</label>
          <input :value="vehicle.vin" disabled class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-500" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Marque</label>
          <input v-model="form.brand" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Modèle</label>
          <input v-model="form.model" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Année</label>
          <input v-model="form.year" type="number" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Carburant</label>
          <select v-model="form.fuel_type" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
            <option value="essence">Essence</option>
            <option value="diesel">Diesel</option>
            <option value="electrique">Électrique</option>
            <option value="hybride">Hybride</option>
          </select>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Statut</label>
          <select v-model="form.status" class="w-full rounded-2xl border border-slate-200 px-4 py-3">
            <option value="available">Disponible</option>
            <option value="in_mission">En mission</option>
            <option value="maintenance">Maintenance</option>
            <option value="broken">En panne</option>
            <option value="sold">Vendu</option>
          </select>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Km certifié (lecture seule)</label>
          <input :value="(vehicle.last_certified_mileage ?? 0) + ' km'" disabled class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-500" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Échéance CT</label>
          <input v-model="form.technical_control_deadline" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Fin assurance</label>
          <input v-model="form.insurance_expiry" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Prochain entretien</label>
          <input v-model="form.next_maintenance_date" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium">Seuil km entretien</label>
          <input v-model="form.next_maintenance_mileage" type="number" class="w-full rounded-2xl border border-slate-200 px-4 py-3" />
        </div>

        <div class="md:col-span-2">
          <button :disabled="form.processing" class="rounded-full bg-indigo-600 px-6 py-3 font-semibold text-white">Enregistrer les modifications</button>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
