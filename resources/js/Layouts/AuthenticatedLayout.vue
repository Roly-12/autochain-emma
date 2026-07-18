<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);
const permissions = computed(() => page.props.permissions || {});
const branding = computed(() => page.props.branding || {});
const prefersDark = () => typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;
const isDark = computed(() => user.value?.theme_preference === 'dark' || (user.value?.theme_preference === 'system' && prefersDark()));

const logoBroken = ref(false);
const avatarBroken = ref(false);

const showLogo = computed(() => branding.value.logo_url && !logoBroken.value);
const showAvatar = computed(() => user.value?.avatar_url && !avatarBroken.value);

const themeLabel = computed(() => {
    const t = user.value?.theme_preference || 'system';
    return { light: 'Clair', dark: 'Sombre', system: 'Auto' }[t] || 'Auto';
});

const cycleTheme = () => {
    const order = ['light', 'dark', 'system'];
    const current = user.value?.theme_preference || 'system';
    const next = order[(order.indexOf(current) + 1) % order.length];
    router.post(route('profile.theme'), { theme_preference: next }, { preserveScroll: true });
};
</script>

<template>
    <div :class="isDark ? 'dark' : ''">
        <div class="min-h-screen bg-gray-100 text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
            <nav class="border-b border-gray-100 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex min-w-0">
                            <div class="flex shrink-0 items-center gap-2">
                                <Link :href="route('dashboard')" class="flex items-center gap-2">
                                    <img
                                        v-if="showLogo"
                                        :src="branding.logo_url"
                                        alt="Logo"
                                        class="h-9 w-auto max-w-[140px] object-contain"
                                        @error="logoBroken = true"
                                    />
                                    <ApplicationLogo
                                        v-else
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-white"
                                    />
                                    <span class="hidden text-sm font-semibold tracking-tight text-slate-800 dark:text-white sm:inline">
                                        {{ branding.app_name || 'AutoChain' }}
                                    </span>
                                </Link>
                            </div>
                            <div class="hidden space-x-5 lg:-my-px lg:ms-6 lg:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</NavLink>
                                <NavLink :href="route('vehicles.index')" :active="route().current('vehicles.*')">Véhicules</NavLink>
                                <NavLink :href="route('maintenance.index')" :active="route().current('maintenance.*')">Maintenance</NavLink>
                                <div class="flex items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button type="button" class="inline-flex items-center gap-1 rounded-full px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                                                Plus <span aria-hidden="true">⌄</span>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('documents.index')">Documents</DropdownLink>
                                            <DropdownLink v-if="permissions.view_fuel" :href="route('fuel.index')">Carburant</DropdownLink>
                                            <DropdownLink v-if="permissions.view_alerts" :href="route('alerts.index')">Alertes</DropdownLink>
                                            <DropdownLink v-if="permissions.view_sales" :href="route('sales.index')">Ventes</DropdownLink>
                                            <DropdownLink v-if="permissions.manage_users" :href="route('users.index')">Utilisateurs</DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>

                        <div class="hidden items-center gap-2 lg:ms-4 lg:flex">
                            <button
                                type="button"
                                @click="cycleTheme"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                title="Changer le thème"
                            >
                                <span aria-hidden="true">{{ isDark ? '☾' : '☀' }}</span>
                                {{ themeLabel }}
                            </button>

                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ permissions.role_label }}
                            </span>

                            <div class="relative ms-1">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-full border border-transparent bg-white py-1 pe-3 ps-1 text-sm font-medium text-gray-600 transition hover:text-gray-800 focus:outline-none dark:bg-slate-900 dark:text-slate-300"
                                            >
                                                <img
                                                    v-if="showAvatar"
                                                    :src="user.avatar_url"
                                                    alt=""
                                                    class="h-8 w-8 rounded-full object-cover bg-slate-200"
                                                    @error="avatarBroken = true"
                                                />
                                                <span
                                                    v-else
                                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700"
                                                >
                                                    {{ user.name?.charAt(0)?.toUpperCase() }}
                                                </span>
                                                <span class="max-w-[120px] truncate">{{ user.name }}</span>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Mon profil</DropdownLink>
                                        <DropdownLink :href="route('wallet.show')">Wallet MetaMask</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Déconnexion</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center gap-2 lg:hidden">
                            <button
                                type="button"
                                @click="cycleTheme"
                                class="rounded-full border border-slate-200 px-2.5 py-1 text-xs dark:border-slate-700"
                            >
                                {{ isDark ? '☾' : '☀' }}
                            </button>
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="lg:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('vehicles.index')" :active="route().current('vehicles.*')">Véhicules</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('maintenance.index')" :active="route().current('maintenance.*')">Maintenance</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('documents.index')" :active="route().current('documents.*')">Documents</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="permissions.view_fuel" :href="route('fuel.index')" :active="route().current('fuel.*')">Carburant</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="permissions.view_alerts" :href="route('alerts.index')" :active="route().current('alerts.*')">Alertes</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="permissions.view_sales" :href="route('sales.index')" :active="route().current('sales.*')">Ventes</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="permissions.manage_users" :href="route('users.index')" :active="route().current('users.*')">Utilisateurs</ResponsiveNavLink>
                    </div>
                    <div class="border-t border-gray-200 pb-1 pt-4 dark:border-slate-800">
                        <div class="flex items-center gap-3 px-4">
                            <img
                                v-if="showAvatar"
                                :src="user.avatar_url"
                                class="h-10 w-10 rounded-full object-cover bg-slate-200"
                                alt=""
                                @error="avatarBroken = true"
                            />
                            <div
                                v-else
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700"
                            >
                                {{ user.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div>
                                <div class="text-base font-medium text-gray-800 dark:text-slate-100">{{ user.name }}</div>
                                <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Mon profil</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('wallet.show')">Wallet MetaMask</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Déconnexion</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-white shadow dark:bg-slate-900 dark:shadow-none" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <div v-if="$page.props.flash?.success" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                        {{ $page.props.flash.success }}
                    </div>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
