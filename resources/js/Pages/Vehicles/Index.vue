<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    vehicles: Object,
    canCreate: Boolean,
});
</script>

<template>
  <Head title="Véhicules" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Parc</p>
          <h2 class="text-2xl font-semibold text-slate-900">Gestion des véhicules</h2>
        </div>
        <Link v-if="canCreate" :href="route('vehicles.create')" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Ajouter un véhicule</Link>
      </div>
    </template>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="grid gap-6 lg:grid-cols-2">
        <div v-for="vehicle in vehicles.data" :key="vehicle.blockchain_vehicle_id" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <div class="aspect-[16/9] bg-slate-100">
            <img
              v-if="vehicle.photo_url"
              :src="vehicle.photo_url"
              :alt="vehicle.license_plate"
              class="h-full w-full object-cover"
            />
            <div v-else class="flex h-full items-center justify-center text-sm text-slate-400">
              Pas de photo
            </div>
          </div>
          <div class="p-6">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-medium text-indigo-600">{{ vehicle.brand }} {{ vehicle.model }}</p>
                <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ vehicle.license_plate }}</h3>
              </div>
              <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">{{ vehicle.status }}</span>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
              <div>
                <dt class="text-sm text-slate-500">Année</dt>
                <dd class="font-medium text-slate-900">{{ vehicle.year }}</dd>
              </div>
              <div>
                <dt class="text-sm text-slate-500">Kilométrage</dt>
                <dd class="font-medium text-slate-900">{{ vehicle.last_certified_mileage ?? vehicle.mileage ?? 0 }} km</dd>
              </div>
              <div>
                <dt class="text-sm text-slate-500">Chauffeur</dt>
                <dd class="font-medium text-slate-900">{{ vehicle.current_driver?.name || '—' }}</dd>
              </div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
              <Link :href="route('vehicles.show', vehicle.blockchain_vehicle_id)" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Voir</Link>
              <Link
                v-if="canCreate"
                :href="route('vehicles.edit', vehicle.blockchain_vehicle_id)"
                class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
              >Modifier</Link>
            </div>
          </div>
        </div>
      </div>

      <p v-if="!vehicles.data?.length" class="text-center text-sm text-slate-500">Aucun véhicule sur cette page.</p>

      <div v-if="vehicles.last_page > 1" class="mt-8 flex flex-wrap items-center justify-center gap-2">
        <template v-for="link in vehicles.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            :class="{'bg-indigo-600 text-white border-indigo-600': link.active}"
            v-html="link.label"
          />
          <span v-else class="px-4 py-2 text-sm text-slate-400" v-html="link.label" />
        </template>
      </div>
      <p v-if="vehicles.total" class="mt-3 text-center text-xs text-slate-500">
        Page {{ vehicles.current_page }} / {{ vehicles.last_page }} — {{ vehicles.total }} véhicule(s)
      </p>
    </div>
  </AuthenticatedLayout>
</template>
