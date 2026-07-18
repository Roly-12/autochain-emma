<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Array,
    fleet: Object,
    recentVehicles: Array,
    openAlerts: Array,
    pendingSales: Array,
    fuelCount: Number,
    blockchainReady: Boolean,
});

const permissions = computed(() => usePage().props.permissions || {});
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">AutoChain Emma+</p>
                    <h2 class="text-2xl font-semibold text-slate-900">Pilotage de la flotte</h2>
                    <p class="mt-1 text-sm text-slate-500">Rôle : {{ permissions.role_label }}</p>
                </div>
                <div
                    class="rounded-full border px-4 py-2 text-sm font-medium"
                    :class="blockchainReady ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                >
                    {{ blockchainReady ? 'Contrat blockchain configuré' : 'Contrat blockchain à déployer' }}
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="stat in stats" :key="stat.label" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">{{ stat.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stat.value }}</p>
                        <p class="mt-1 text-sm text-emerald-600">{{ stat.detail }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-3">
                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">État de la flotte</h3>
                            <Link :href="route('vehicles.index')" class="text-sm font-medium text-indigo-600">Voir tout</Link>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-5">
                            <div v-for="(count, key) in fleet" :key="key" class="rounded-2xl bg-slate-50 p-4 text-center">
                                <p class="text-2xl font-semibold text-slate-900">{{ count }}</p>
                                <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">{{ key.replace('_', ' ') }}</p>
                            </div>
                        </div>
                        <div class="mt-6 space-y-3">
                            <div v-for="v in recentVehicles" :key="v.id" class="flex items-center justify-between rounded-2xl border border-slate-100 px-4 py-3">
                                <div>
                                    <p class="font-medium text-slate-900">{{ v.license_plate }} — {{ v.brand }} {{ v.model }}</p>
                                    <p class="text-sm text-slate-500">{{ v.last_certified_mileage ?? 0 }} km · {{ v.status }}</p>
                                </div>
                                <Link :href="route('vehicles.show', v.blockchain_vehicle_id)" class="text-sm font-semibold text-indigo-600">Détail</Link>
                            </div>
                            <p v-if="!recentVehicles?.length" class="text-sm text-slate-500">Aucun véhicule pour le moment.</p>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">Alertes</h3>
                            <Link v-if="permissions.view_alerts" :href="route('alerts.index')" class="text-sm font-medium text-indigo-600">Toutes</Link>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div v-for="alert in openAlerts" :key="alert.id" class="rounded-2xl border border-slate-100 p-3">
                                <p class="text-sm font-semibold text-slate-900">{{ alert.title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ alert.vehicle?.license_plate }} · {{ alert.due_date }}</p>
                                <span class="mt-2 inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-red-50 text-red-700': alert.severity === 'critical',
                                        'bg-amber-50 text-amber-700': alert.severity === 'warning',
                                        'bg-slate-100 text-slate-600': alert.severity === 'info',
                                    }"
                                >{{ alert.severity }}</span>
                            </div>
                            <p v-if="!openAlerts?.length" class="text-sm text-slate-500">Aucune alerte ouverte.</p>
                        </div>
                        <p v-if="permissions.view_fuel" class="mt-6 text-sm text-slate-500">{{ fuelCount }} pleins carburant enregistrés</p>
                    </section>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <Link v-if="permissions.create_vehicle" :href="route('vehicles.create')" class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Ajouter un véhicule</Link>
                    <Link v-if="permissions.create_maintenance" :href="route('maintenance.create')" class="rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Nouvelle maintenance</Link>
                    <Link v-if="permissions.upload_documents" :href="route('documents.create')" class="rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Ajouter un document</Link>
                    <Link v-if="permissions.view_fuel" :href="route('fuel.create')" class="rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Saisir un plein</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
