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
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Véhicule</p>
          <h2 class="text-2xl font-semibold text-slate-900">{{ vehicle.license_plate }} — {{ vehicle.brand }} {{ vehicle.model }}</h2>
        </div>
        <div class="flex gap-2">
          <Link
            v-if="canManage"
            :href="route('vehicles.edit', vehicleKey)"
            class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
          >Modifier</Link>
          <button
            v-if="canDelete"
            type="button"
            @click="openArchiveModal"
            class="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
          >Archiver</button>
          <Link :href="route('vehicles.index')" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Retour</Link>
        </div>
      </div>
    </template>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="aspect-[21/9] bg-slate-100">
          <img
            v-if="vehicle.photo_url"
            :src="vehicle.photo_url"
            :alt="vehicle.license_plate"
            class="h-full w-full object-cover"
          />
          <div v-else class="flex h-full items-center justify-center text-slate-400">Aucune photo</div>
        </div>
        <div v-if="canManage" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3">
          <label class="text-sm font-medium text-slate-600">Changer la photo</label>
          <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" class="text-sm" @change="onPhoto" />
          <p v-if="photoForm.errors.photo" class="text-sm text-red-600">{{ photoForm.errors.photo }}</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-xl font-semibold text-slate-900">Fiche technique</h3>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
              <div><dt class="text-sm text-slate-500">VIN (n° de châssis)</dt><dd class="font-medium text-slate-900 font-mono text-sm">{{ vehicle.vin }}</dd></div>
              <div><dt class="text-sm text-slate-500">Année</dt><dd class="font-medium text-slate-900">{{ vehicle.year }}</dd></div>
              <div><dt class="text-sm text-slate-500">Carburant</dt><dd class="font-medium text-slate-900">{{ vehicle.fuel_type }}</dd></div>
              <div><dt class="text-sm text-slate-500">Km certifié</dt><dd class="font-medium text-slate-900">{{ vehicle.mileage_certified_at ? (vehicle.last_certified_mileage ?? 0) + ' km' : 'En attente de certification' }}</dd></div>
              <div><dt class="text-sm text-slate-500">Statut</dt><dd class="font-medium text-emerald-600">{{ vehicle.status }}</dd></div>
              <div><dt class="text-sm text-slate-500">Chauffeur</dt><dd class="font-medium text-slate-900">{{ vehicle.current_driver?.name || '—' }}</dd></div>
              <div><dt class="text-sm text-slate-500">CT</dt><dd class="font-medium text-slate-900">{{ vehicle.technical_control_deadline || '—' }}</dd></div>
              <div><dt class="text-sm text-slate-500">Assurance</dt><dd class="font-medium text-slate-900">{{ vehicle.insurance_expiry || '—' }}</dd></div>
              <div><dt class="text-sm text-slate-500">Conso moyenne</dt><dd class="font-medium text-slate-900">{{ avgConsumption ? avgConsumption + ' L/100km' : '—' }}</dd></div>
              <div><dt class="text-sm text-slate-500">Tx blockchain</dt><dd class="truncate font-mono text-xs text-slate-700">{{ vehicle.transaction_hash || 'en attente' }}</dd></div>
              <div><dt class="text-sm text-slate-500">État blockchain</dt><dd class="font-medium text-slate-900">{{ vehicle.blockchain_status }}</dd></div>
            </dl>
          </div>

          <div v-if="canAssign" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Affectation chauffeur</h3>
            <form class="mt-4 flex flex-wrap gap-3" @submit.prevent="assignForm.post(route('vehicles.assign', vehicleKey))">
              <select v-model="assignForm.driver_id" class="min-w-[220px] rounded-2xl border border-slate-200 px-4 py-2">
                <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
              <button class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">Affecter</button>
            </form>
          </div>

          <div v-if="canAssign" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Statut certifié</h3>
            <form class="mt-4 flex flex-wrap gap-3" @submit.prevent="statusForm.post(route('vehicles.status', vehicleKey))">
              <select v-model="statusForm.status" class="min-w-[220px] rounded-2xl border border-slate-200 px-4 py-2">
                <option value="available">Disponible</option>
                <option value="maintenance">Maintenance</option>
                <option value="broken">En panne</option>
              </select>
              <button class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">Signer le statut</button>
            </form>
          </div>

          <div v-if="canReportMileage" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Relevé kilométrique</h3>
            <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="mileageForm.post(route('vehicles.mileage', vehicleKey))">
              <input v-model="mileageForm.odometer" type="number" class="rounded-2xl border border-slate-200 px-4 py-2" placeholder="Kilométrage" />
              <select v-model="mileageForm.context" class="rounded-2xl border border-slate-200 px-4 py-2">
                <option value="trip_end">Fin de trajet</option>
                <option value="manual">Manuel</option>
                <option value="assignment">Affectation</option>
                <option value="maintenance">Maintenance</option>
              </select>
              <input v-model="mileageForm.notes" class="rounded-2xl border border-slate-200 px-4 py-2 md:col-span-2" placeholder="Notes" />
              <p class="text-sm text-slate-500 md:col-span-2">La valeur sera certifiée après signature MetaMask et receipt confirmé.</p>
              <button class="rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white">Enregistrer le km</button>
              <p v-if="mileageForm.errors.odometer" class="text-sm text-red-600 md:col-span-2">{{ mileageForm.errors.odometer }}</p>
            </form>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-3xl border border-slate-200 bg-slate-900 p-6 text-white shadow-sm">
            <h3 class="text-xl font-semibold">Timeline certifiée</h3>
            <p class="mt-1 text-sm text-slate-300">Données blockchain + administratives</p>
            <div class="mt-6 max-h-[640px] space-y-4 overflow-y-auto pr-1">
              <div v-for="(entry, idx) in timeline" :key="idx" class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="font-semibold">{{ entry.title }}</p>
                    <p class="mt-1 text-sm text-slate-300">{{ entry.detail }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-slate-400">{{ entry.at }}</p>
                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[10px] uppercase"
                      :class="entry.certified ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-500/20 text-slate-300'"
                    >{{ entry.source }}</span>
                  </div>
                </div>
              </div>
              <p v-if="!timeline?.length" class="text-sm text-slate-400">Aucun événement pour ce véhicule.</p>
            </div>
          </div>

          <div v-if="vehicle.documents?.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Documents</h3>
            <ul class="mt-4 space-y-2">
              <li v-for="doc in vehicle.documents" :key="doc.id" class="flex items-center justify-between text-sm">
                <span>{{ doc.title }} <span class="text-slate-400">({{ doc.type }})</span></span>
                <Link :href="route('documents.download', doc.id)" class="font-medium text-indigo-600">Télécharger</Link>
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
