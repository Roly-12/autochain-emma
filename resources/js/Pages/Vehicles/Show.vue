<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    vehicle: Object,
    timeline: Array,
    onchain: Object,
    avgConsumption: Number,
    drivers: Array,
    canManage: Boolean,
    canAssign: Boolean,
    canDelete: Boolean,
    canReportMileage: Boolean,
});

const assignForm = useForm({ driver_id: props.vehicle.current_driver_id || '' });
const mileageForm = useForm({
    odometer: Number(props.vehicle.last_certified_mileage || 0) + 1,
    context: 'trip_end',
    notes: '',
});
const statusForm = useForm({ status: props.vehicle.status });
const photoForm = useForm({ photo: null });
const vehicleKey = props.vehicle.blockchain_vehicle_id;
const showArchiveModal = ref(false);
const archiving = ref(false);

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatDateTime = (value) => {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const onPhoto = (e) => {
    photoForm.photo = e.target.files?.[0] || null;
    if (photoForm.photo) {
        photoForm.post(route('vehicles.photo', vehicleKey), { forceFormData: true, preserveScroll: true });
    }
};

const openArchiveModal = () => {
    showArchiveModal.value = true;
};

const closeArchiveModal = () => {
    if (! archiving.value) {
        showArchiveModal.value = false;
    }
};

const confirmArchive = () => {
    archiving.value = true;
    router.delete(route('vehicles.destroy', vehicleKey), {
        onFinish: () => {
            archiving.value = false;
            showArchiveModal.value = false;
        },
    });
};
</script>

<template>
  <Head :title="`Véhicule ${vehicle.license_plate}`" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="min-w-0">
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600 dark:text-indigo-400">Véhicule</p>
          <h2 class="truncate text-xl font-semibold text-slate-900 dark:text-slate-100 sm:text-2xl">
            {{ vehicle.license_plate }} — {{ vehicle.brand }} {{ vehicle.model }}
          </h2>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link
            v-if="canManage"
            :href="route('vehicles.edit', vehicleKey)"
            class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
          >Modifier</Link>
          <button
            v-if="canDelete"
            type="button"
            @click="openArchiveModal"
            class="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
          >Archiver</button>
          <Link
            :href="route('vehicles.index')"
            class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          >Retour</Link>
        </div>
      </div>
    </template>

    <div class="mx-auto max-w-7xl overflow-x-hidden px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="aspect-[16/9] bg-slate-100 sm:aspect-[21/9] dark:bg-slate-800">
          <img
            v-if="vehicle.photo_url"
            :src="vehicle.photo_url"
            :alt="vehicle.license_plate"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full items-center justify-center text-slate-400">Aucune photo</div>
        </div>
        <div v-if="canManage" class="flex flex-col gap-2 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:gap-3 dark:border-slate-800">
          <label class="shrink-0 text-sm font-medium text-slate-600 dark:text-slate-300">Changer la photo</label>
          <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" class="min-w-0 max-w-full text-sm" @change="onPhoto" />
          <p v-if="photoForm.errors.photo" class="text-sm text-red-600">{{ photoForm.errors.photo }}</p>
        </div>
      </div>

      <div class="grid min-w-0 gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="min-w-0 space-y-6">
          <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Fiche technique</h3>
            <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">VIN (n° de châssis)</dt>
                <dd class="break-all font-mono text-sm font-medium text-slate-900 dark:text-slate-100">{{ vehicle.vin }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Année</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ vehicle.year }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Carburant</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ vehicle.fuel_type }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Km certifié</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ vehicle.mileage_certified_at ? (vehicle.last_certified_mileage ?? 0) + ' km' : 'En attente de certification' }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Statut</dt>
                <dd class="font-medium text-emerald-600 dark:text-emerald-400">{{ vehicle.status }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Chauffeur</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ vehicle.current_driver?.name || '—' }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">CT</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ formatDate(vehicle.technical_control_deadline) }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Assurance</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ formatDate(vehicle.insurance_expiry) }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Conso moyenne</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ avgConsumption ? avgConsumption + ' L/100km' : '—' }}</dd>
              </div>
              <div class="min-w-0 sm:col-span-2">
                <dt class="text-sm text-slate-500 dark:text-slate-400">Tx blockchain</dt>
                <dd class="break-all font-mono text-xs text-slate-700 dark:text-slate-300">{{ vehicle.transaction_hash || 'en attente' }}</dd>
              </div>
              <div class="min-w-0">
                <dt class="text-sm text-slate-500 dark:text-slate-400">État blockchain</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ vehicle.blockchain_status }}</dd>
              </div>
            </dl>
          </div>

          <div v-if="canAssign" class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Affectation chauffeur</h3>
            <form class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap" @submit.prevent="assignForm.post(route('vehicles.assign', vehicleKey))">
              <select v-model="assignForm.driver_id" class="w-full min-w-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 dark:border-slate-700 dark:bg-slate-950 sm:max-w-xs">
                <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
              <button class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">Affecter</button>
            </form>
          </div>

          <div v-if="canAssign" class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Statut certifié</h3>
            <form class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap" @submit.prevent="statusForm.post(route('vehicles.status', vehicleKey))">
              <select v-model="statusForm.status" class="w-full min-w-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 dark:border-slate-700 dark:bg-slate-950 sm:max-w-xs">
                <option value="available">Disponible</option>
                <option value="maintenance">Maintenance</option>
                <option value="broken">En panne</option>
              </select>
              <button class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">Signer le statut</button>
            </form>
          </div>

          <div v-if="canReportMileage" class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Relevé kilométrique</h3>
            <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="mileageForm.post(route('vehicles.mileage', vehicleKey))">
              <input v-model="mileageForm.odometer" type="number" class="w-full min-w-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 dark:border-slate-700 dark:bg-slate-950" placeholder="Kilométrage" />
              <select v-model="mileageForm.context" class="w-full min-w-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 dark:border-slate-700 dark:bg-slate-950">
                <option value="trip_end">Fin de trajet</option>
                <option value="manual">Manuel</option>
                <option value="assignment">Affectation</option>
                <option value="maintenance">Maintenance</option>
              </select>
              <input v-model="mileageForm.notes" class="w-full min-w-0 rounded-2xl border border-slate-200 bg-white px-4 py-2 md:col-span-2 dark:border-slate-700 dark:bg-slate-950" placeholder="Notes" />
              <p class="text-sm text-slate-500 md:col-span-2 dark:text-slate-400">La valeur sera certifiée après signature MetaMask et receipt confirmé.</p>
              <button class="rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white">Enregistrer le km</button>
              <p v-if="mileageForm.errors.odometer" class="text-sm text-red-600 md:col-span-2">{{ mileageForm.errors.odometer }}</p>
            </form>
          </div>
        </div>

        <div class="min-w-0 space-y-6">
          <div class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 p-4 text-white shadow-sm sm:p-6 dark:border-slate-700">
            <h3 class="text-xl font-semibold">Timeline certifiée</h3>
            <p class="mt-1 text-sm text-slate-300">Données blockchain + administratives</p>
            <div class="mt-6 max-h-[640px] space-y-4 overflow-y-auto pr-1">
              <div v-for="(entry, idx) in timeline" :key="idx" class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                  <div class="min-w-0">
                    <p class="font-semibold">{{ entry.title }}</p>
                    <p class="mt-1 break-words text-sm text-slate-300">{{ entry.detail }}</p>
                  </div>
                  <div class="shrink-0 sm:text-right">
                    <p class="text-xs text-slate-400">{{ formatDateTime(entry.at) }}</p>
                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] uppercase"
                      :class="entry.certified ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300'"
                    >{{ entry.source }}</span>
                  </div>
                </div>
              </div>
              <p v-if="!timeline?.length" class="text-sm text-slate-400">Aucun événement pour ce véhicule.</p>
            </div>
          </div>

          <div v-if="vehicle.documents?.length" class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Documents</h3>
            <ul class="mt-4 space-y-2">
              <li v-for="doc in vehicle.documents" :key="doc.id" class="flex min-w-0 flex-col gap-1 text-sm sm:flex-row sm:items-center sm:justify-between">
                <span class="min-w-0 break-words dark:text-slate-200">{{ doc.title }} <span class="text-slate-400">({{ doc.type }})</span></span>
                <Link :href="route('documents.download', doc.id)" class="shrink-0 font-medium text-indigo-600 dark:text-indigo-400">Télécharger</Link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <Modal :show="showArchiveModal" max-width="md" @close="closeArchiveModal">
      <div class="p-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-500">Archivage</p>
        <h3 class="mt-2 text-xl font-semibold text-slate-900">
          Archiver {{ vehicle.license_plate }} ?
        </h3>
        <p class="mt-3 text-sm leading-6 text-slate-600">
          Le véhicule disparaîtra du parc actif (soft delete). Les preuves blockchain
          (kilométrage certifié, maintenances on-chain) restent intactes.
        </p>
        <div class="mt-6 flex flex-wrap justify-end gap-3">
          <button
            type="button"
            class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            :disabled="archiving"
            @click="closeArchiveModal"
          >
            Annuler
          </button>
          <button
            type="button"
            class="rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-60"
            :disabled="archiving"
            @click="confirmArchive"
          >
            {{ archiving ? 'Archivage…' : 'Confirmer l\'archivage' }}
          </button>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
