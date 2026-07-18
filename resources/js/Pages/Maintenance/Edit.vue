<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { toRefs } from 'vue';

const props = defineProps({ maintenance: Object });
const { maintenance } = toRefs(props);

const form = useForm({ vehicle_id: maintenance.value.vehicle_id, type: maintenance.value.type, garage: maintenance.value.garage, date: maintenance.value.date, details: maintenance.value.details, status: maintenance.value.status });
</script>

<template>
  <Head title="Modifier intervention" />
  <AuthenticatedLayout>
    <template #header>
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Maintenance</p>
        <h2 class="text-2xl font-semibold text-slate-900">Modifier intervention</h2>
      </div>
    </template>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form @submit.prevent="form.patch(route('maintenance.update', maintenance.value.id))" class="grid gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Véhicule (ID)</label>
            <input v-model="form.vehicle_id" class="mt-1 block w-full rounded-md border-gray-300" />
            <p class="text-sm text-red-600" v-if="form.errors.vehicle_id">{{ form.errors.vehicle_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Type d'intervention</label>
            <input v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300" />
            <p class="text-sm text-red-600" v-if="form.errors.type">{{ form.errors.type }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Garage</label>
            <input v-model="form.garage" class="mt-1 block w-full rounded-md border-gray-300" />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Date</label>
            <input type="date" v-model="form.date" class="mt-1 block w-full rounded-md border-gray-300" />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Détails</label>
            <textarea v-model="form.details" class="mt-1 block w-full rounded-md border-gray-300" rows="4"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700">Statut</label>
            <input v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300" />
          </div>

          <div class="flex justify-end gap-3">
            <button :disabled="form.processing" class="rounded-full bg-indigo-600 px-6 py-2 text-white">Sauvegarder</button>
            <form :action="route('maintenance.destroy', maintenance.value.id)" method="post">
              <input type="hidden" name="_method" value="delete" />
              <button class="rounded-full border px-4 py-2 text-sm">Supprimer</button>
            </form>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
