<template>
    <div class="min-h-screen bg-[#F8F9FA] flex flex-col">
        <!-- Barre de progression globale requêtes POST/PUT/DELETE -->
        <ProgressBar />

        <!-- 1. Top Navbar -->
        <header class="h-16 bg-white border-b border-gray-200 px-6 flex items-center justify-between sticky top-0 z-40">
            <!-- Logo / Brand & Titre -->
            <div class="flex items-center gap-6">
                <Link href="/dashboard" class="flex items-center gap-2 text-xl font-bold text-[#1B6B4A]">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Tontine</span>
                </Link>

                <!-- Champ de recherche menus Navbar (Demande 2) -->
                <div class="relative w-72 md:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="navSearch"
                        @focus="showSearchDropdown = true"
                        type="text"
                        placeholder="Rechercher un menu ou une action..."
                        class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:bg-white transition-all"
                    />

                    <!-- Dropdown résultats de recherche menus -->
                    <div
                        v-if="showSearchDropdown && filteredMenuItems.length > 0"
                        class="absolute left-0 right-0 mt-1.5 bg-white border border-gray-100 rounded-xl shadow-lg z-50 py-1 overflow-hidden animate-in fade-in duration-100"
                    >
                        <button
                            v-for="item in filteredMenuItems"
                            :key="item.href"
                            type="button"
                            @click="selectMenuItem(item)"
                            class="w-full text-left px-4 py-2.5 text-xs text-gray-700 hover:bg-[#E8F0EC] hover:text-[#1B6B4A] flex items-center justify-between transition-colors"
                        >
                            <span class="font-semibold">{{ item.label }}</span>
                            <span class="text-[10px] text-gray-400 capitalize">{{ item.category || 'Navigation' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Partie Droite: Notifications + Profil utilisateur -->
            <div class="flex items-center gap-3">

                <!-- Cloche Notifications (Demande 3) -->
                <div class="relative" ref="notifDropdownRef">
                    <button
                        type="button"
                        @click="toggleNotifDropdown"
                        class="relative p-2 rounded-full border border-gray-200 bg-gray-50 hover:bg-white hover:border-[#1B6B4A] transition-all cursor-pointer"
                        title="Notifications"
                    >
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <!-- Badge compteur non lues -->
                        <span
                            v-if="notifNonLues > 0"
                            class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 leading-none"
                        >
                            {{ notifNonLues > 99 ? '99+' : notifNonLues }}
                        </span>
                    </button>

                    <!-- Dropdown notifications -->
                    <Transition name="dropdown-fade">
                        <div
                            v-if="showNotifDropdown"
                            class="absolute right-0 mt-2 w-96 bg-white border border-gray-100 rounded-2xl shadow-xl z-50 overflow-hidden"
                        >
                            <!-- Header dropdown -->
                            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-900">Notifications</span>
                                <button
                                    v-if="notifNonLues > 0"
                                    type="button"
                                    @click="marquerToutesLues"
                                    class="text-xs text-[#1B6B4A] hover:underline font-semibold cursor-pointer"
                                >
                                    Tout marquer comme lu
                                </button>
                            </div>

                            <!-- Liste notifications -->
                            <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                                <div
                                    v-if="notifications.length === 0"
                                    class="py-10 text-center text-sm text-gray-400"
                                >
                                    <svg class="w-8 h-8 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    Aucune notification
                                </div>
                                <div
                                    v-for="notif in notifications"
                                    :key="notif.id"
                                    @click="marquerLue(notif)"
                                    :class="[
                                        'px-4 py-3 cursor-pointer transition-colors',
                                        !notif.lue ? 'bg-[#E8F5EE] hover:bg-[#D6EEE0]' : 'hover:bg-gray-50'
                                    ]"
                                >
                                    <div class="flex items-start gap-3">
                                        <!-- Icône type -->
                                        <div :class="[
                                            'mt-0.5 w-8 h-8 rounded-full flex items-center justify-center shrink-0',
                                            notif.type === 'cycle_demarre' ? 'bg-green-100'
                                            : notif.type === 'tour_ouvert' ? 'bg-blue-100'
                                            : notif.type === 'tour_cloture' ? 'bg-orange-100'
                                            : 'bg-gray-100'
                                        ]">
                                            <svg v-if="notif.type === 'cycle_demarre'" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg v-else-if="notif.type === 'tour_ouvert'" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <svg v-else-if="notif.type === 'cycle_termine'" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-xs font-bold text-gray-900 truncate">{{ notif.titre }}</p>
                                                <span v-if="!notif.lue" class="shrink-0 w-2 h-2 bg-[#1B6B4A] rounded-full"></span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5 line-clamp-2">{{ notif.message }}</p>
                                            <p class="text-[10px] text-gray-400 mt-1">{{ notif.created_at }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>

                <button
                    type="button"
                    @click="showProfileModal = true"
                    class="flex items-center gap-2.5 p-1.5 pr-3 rounded-full border border-gray-200 bg-gray-50 hover:bg-white hover:border-[#1B6B4A] transition-all cursor-pointer group"
                    title="Mon compte"
                >
                    <!-- Icône User -->
                    <div class="w-8 h-8 rounded-full bg-[#1B6B4A] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        {{ userInitials }}
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="text-xs font-bold text-gray-900 group-hover:text-[#1B6B4A] leading-tight">
                            {{ currentUser.name }}
                        </p>
                        <p class="text-[10px] text-gray-500 capitalize leading-tight">
                            {{ currentUser.role }}
                        </p>
                    </div>
                    <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#1B6B4A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </header>

        <div class="flex flex-1">
            <!-- 2. Sidebar Navigation (Demande 5: zone un peu plus foncée bg-[#EAEBE6]) -->
            <aside class="w-60 bg-[#EAEBE6] border-r border-gray-300 flex flex-col justify-between p-4 shrink-0">
                <div class="space-y-6">
                    <div>
                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider px-3 mb-2">
                            Menu principal
                        </p>
                        <nav class="space-y-1">
                            <Link
                                v-for="item in menuItems"
                                :key="item.href"
                                :href="item.href"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all',
                                    isActive(item.href)
                                        ? 'bg-[#1B6B4A] text-white shadow-xs'
                                        : 'text-gray-700 hover:bg-[#DFE1DC] hover:text-[#1B6B4A]'
                                ]"
                            >
                                <!-- Icônes SVG par menu (Demande 11: icônes conservées en plus des boutons) -->
                                <svg v-if="item.icon === 'dashboard'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <svg v-else-if="item.icon === 'members'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-else-if="item.icon === 'cycles'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg v-else-if="item.icon === 'users'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <svg v-else-if="item.icon === 'admin'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>

                                <span>{{ item.label }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- Bas de la barre latérale -->
                <div class="pt-4 border-t border-gray-300/80">
                    <p class="text-[11px] text-gray-500 text-center">
                        Tontine v2.0 · Modules Nwidart
                    </p>
                </div>
            </aside>

            <!-- 3. Zone de contenu principal -->
            <main class="flex-1 p-8 overflow-y-auto">
                <slot />
            </main>
        </div>

        <!-- 4. Modal Détails & Édition Utilisateur (Demande 2) -->
        <div
            v-if="showProfileModal"
            class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50 backdrop-blur-xs animate-in fade-in duration-150"
            @click.self="showProfileModal = false"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5">
                <!-- En-tête -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-900">
                        {{ isEditingProfile ? 'Modifier mes informations' : 'Mon profil utilisateur' }}
                    </h3>
                    <button
                        @click="showProfileModal = false"
                        class="text-gray-400 hover:text-gray-600 text-sm font-bold p-1 cursor-pointer"
                    >
                        &times;
                    </button>
                </div>

                <!-- Mode Consultation du Profil -->
                <div v-if="!isEditingProfile" class="space-y-4">
                    <div>
                        <span class="text-xs text-gray-400 font-medium">Nom complet</span>
                        <p class="text-sm font-bold text-gray-900">{{ currentUser.name }}</p>
                    </div>

                    <div>
                        <span class="text-xs text-gray-400 font-medium">Adresse email</span>
                        <p class="text-sm font-medium text-gray-800">{{ currentUser.email }}</p>
                    </div>

                    <div>
                        <span class="text-xs text-gray-400 font-medium">Rôle</span>
                        <div class="mt-1">
                            <span
                                :class="[
                                    'inline-block px-3 py-0.5 rounded-full text-xs font-semibold',
                                    currentUser.role === 'administrateur'
                                        ? 'bg-purple-100 text-purple-700'
                                        : currentUser.role === 'gestionnaire'
                                        ? 'bg-[#155A3E] text-white'
                                        : 'bg-blue-100 text-blue-700'
                                ]"
                            >
                                {{ currentUser.role === 'administrateur' ? 'Super Administrateur' : (currentUser.role === 'gestionnaire' ? 'Gestionnaire' : 'Trésorier') }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
                        <button
                            type="button"
                            @click="startEditProfile"
                            class="w-full py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Modifier mes informations
                        </button>

                        <button
                            type="button"
                            @click="handleLogout"
                            :disabled="isLoggingOut"
                            class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            {{ isLoggingOut ? 'Déconnexion...' : 'Se déconnecter' }}
                        </button>
                    </div>
                </div>

                <!-- Mode Édition du Profil (Demande 2: updateall + déconnexion auto) -->
                <form v-else @submit.prevent="submitProfileUpdate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1">Nom complet</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1">Adresse email</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            required
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-800 mb-1">Nouveau mot de passe (facultatif)</label>
                        <input
                            v-model="profileForm.password"
                            type="password"
                            placeholder="Laisser vide pour conserver le mot de passe actuel"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                        <p class="text-[11px] text-gray-500 mt-1">Au moins 8 caractères si modifié.</p>
                    </div>

                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                        Attention : après la mise à jour, vous serez automatiquement déconnecté et redirigé vers la page de connexion.
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="flex-1 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50 cursor-pointer"
                        >
                            {{ profileForm.processing ? 'Enregistrement...' : 'Enregistrer et se reconnecter' }}
                        </button>
                        <button
                            type="button"
                            @click="isEditingProfile = false"
                            class="px-4 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors cursor-pointer"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage, useForm } from '@inertiajs/vue3'
import ProgressBar from '@/Components/ProgressBar.vue'

const page = usePage()

const currentUser = computed(() => {
    if (page.props.auth?.user) {
        return page.props.auth.user
    }
    return {
        id: 1,
        name: 'Aya Konan',
        email: 'aya.konan@mail.ci',
        role: 'gestionnaire',
    }
})

const userInitials = computed(() => {
    const name = currentUser.value.name || 'U'
    const parts = name.split(' ')
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase()
    }
    return name.slice(0, 2).toUpperCase()
})

const menuItems = computed(() => {
    const items = [
        { href: '/dashboard', label: 'Tableau de bord', icon: 'dashboard', category: 'Navigation' },
        { href: '/membres', label: 'Membres', icon: 'members', category: 'Gestion' },
        { href: '/cycles', label: 'Cycles', icon: 'cycles', category: 'Gestion' },
    ]
    if (currentUser.value.role === 'administrateur' || currentUser.value.role === 'gestionnaire') {
        items.push({ href: '/utilisateurs', label: 'Utilisateurs', icon: 'users', category: 'Administration' })
    }
    if (currentUser.value.role === 'administrateur') {
        items.push({ href: '/administration', label: 'Permissions', icon: 'admin', category: 'Administration' })
    }
    return items
})

function isActive(href) {
    const url = page.url
    if (href === '/dashboard') return url === '/dashboard' || url.startsWith('/dashboard')
    return url.startsWith(href)
}

// Recherche dans la Navbar (Demande 2)
const navSearch = ref('')
const showSearchDropdown = ref(false)

const filteredMenuItems = computed(() => {
    if (!navSearch.value.trim()) return []
    const q = navSearch.value.toLowerCase().trim()
    return menuItems.value.filter(item =>
        item.label.toLowerCase().includes(q) || (item.category && item.category.toLowerCase().includes(q))
    )
})

function selectMenuItem(item) {
    showSearchDropdown.value = false
    navSearch.value = ''
    router.visit(item.href)
}

// Profil & Modal
const showProfileModal = ref(false)
const isEditingProfile = ref(false)
const isLoggingOut = ref(false)

const profileForm = useForm({
    name: '',
    email: '',
    password: '',
})

function startEditProfile() {
    profileForm.name = currentUser.value.name
    profileForm.email = currentUser.value.email
    profileForm.password = ''
    isEditingProfile.value = true
}

function submitProfileUpdate() {
    profileForm.post(`/users/${currentUser.value.id}/updateall`, {
        onSuccess: () => {
            showProfileModal.value = false
            isEditingProfile.value = false
            window.location.href = '/'
        }
    })
}

async function handleLogout() {
    isLoggingOut.value = true
    try {
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
    } catch (e) {
    } finally {
        isLoggingOut.value = false
        window.location.href = '/'
    }
}

// ======================================================
// Notifications (Demande 3 — cloche navbar)
// ======================================================
const showNotifDropdown = ref(false)
const notifications = ref([])
const notifNonLues = ref(0)
const notifDropdownRef = ref(null)

async function fetchNotifications() {
    try {
        const res = await fetch('/notifications', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        if (res.ok) {
            const data = await res.json()
            notifications.value = data.notifications || []
            notifNonLues.value = data.non_lues || 0
        }
    } catch (e) {}
}

function toggleNotifDropdown() {
    showNotifDropdown.value = !showNotifDropdown.value
    if (showNotifDropdown.value) {
        fetchNotifications()
    }
}

async function marquerLue(notif) {
    if (!notif.lue) {
        notif.lue = true
        notifNonLues.value = Math.max(0, notifNonLues.value - 1)
        try {
            await fetch(`/notifications/${notif.id}/lue`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                }
            })
        } catch (e) {}
    }
    // Si la notification concerne un cycle, naviguer dessus
    if (notif.cycle_id) {
        showNotifDropdown.value = false
        router.visit(`/cycles/${notif.cycle_id}`)
    }
}

async function marquerToutesLues() {
    notifications.value.forEach(n => { n.lue = true })
    notifNonLues.value = 0
    try {
        await fetch('/notifications/lire-toutes', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }
        })
    } catch (e) {}
}

// Fermer le dropdown notifications si clic en dehors
function handleClickOutsideNotif(e) {
    if (notifDropdownRef.value && !notifDropdownRef.value.contains(e.target)) {
        showNotifDropdown.value = false
    }
}

// Polling des notifications toutes les 60 secondes
let notifInterval = null
onMounted(() => {
    fetchNotifications()
    notifInterval = setInterval(fetchNotifications, 60000)
    document.addEventListener('click', handleClickOutsideNotif)
})
onUnmounted(() => {
    if (notifInterval) clearInterval(notifInterval)
    document.removeEventListener('click', handleClickOutsideNotif)
})
</script>

<style scoped>
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
