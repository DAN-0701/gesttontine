<template>
    <div class="min-h-screen bg-[#F5F5F0] flex">
        <!-- Sidebar -->
        <aside class="w-60 flex flex-col justify-between border-r border-gray-200 bg-[#F5F5F0] min-h-screen fixed">
            <div>
                <div class="px-6 py-6">
                    <Link href="/dashboard" class="text-xl font-bold text-[#1B6B4A]">Tontine</Link>
                </div>
                <nav class="mt-2">
                    <Link
                        v-for="item in menuItems"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'block px-6 py-2.5 text-sm transition-colors',
                            isActive(item.href)
                                ? 'text-[#1B6B4A] font-semibold bg-[#E8F0EC] border-l-4 border-[#1B6B4A]'
                                : 'text-gray-700 hover:text-[#1B6B4A] hover:bg-gray-100'
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>

            <!-- Bloc Utilisateur connecté (Cliquable pour voir les détails et se déconnecter) -->
            <div class="p-3 border-t border-gray-200">
                <button
                    type="button"
                    @click="showProfileModal = true"
                    class="w-full text-left p-3 rounded-xl hover:bg-white hover:shadow-xs transition-all flex items-center justify-between group cursor-pointer"
                >
                    <div class="overflow-hidden">
                        <p class="text-sm font-semibold text-gray-900 group-hover:text-[#1B6B4A] truncate">
                            {{ currentUser.name }}
                        </p>
                        <p class="text-xs text-gray-500 capitalize truncate">
                            {{ currentUser.role }}
                        </p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-[#1B6B4A] shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 ml-60 p-8">
            <slot />
        </main>

        <!-- Modal Informations Utilisateur & Déconnexion -->
        <div
            v-if="showProfileModal"
            class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50 backdrop-blur-xs"
            @click.self="showProfileModal = false"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-5 animate-in fade-in duration-150">
                <!-- En-tête du modal -->
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-900">Compte connecté</h3>
                    <button
                        @click="showProfileModal = false"
                        class="text-gray-400 hover:text-gray-600 text-sm font-bold p-1"
                    >
                        &times;
                    </button>
                </div>

                <!-- Détails utilisateur -->
                <div class="space-y-3">
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
                                    currentUser.role === 'gestionnaire'
                                        ? 'bg-[#155A3E] text-white'
                                        : 'bg-blue-100 text-blue-700'
                                ]"
                            >
                                {{ currentUser.role === 'gestionnaire' ? 'Gestionnaire' : 'Trésorier' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bouton Se déconnecter (Appel user::logout) -->
                <div class="pt-2 border-t border-gray-100 flex flex-col gap-2">
                    <button
                        type="button"
                        @click="handleLogout"
                        :disabled="isLoggingOut"
                        class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        {{ isLoggingOut ? 'Déconnexion...' : 'Se déconnecter' }}
                    </button>
                    <button
                        type="button"
                        @click="showProfileModal = false"
                        class="w-full py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const menuItems = [
    { href: '/dashboard', label: 'Tableau de bord' },
    { href: '/membres', label: 'Membres' },
    { href: '/cycles', label: 'Cycles' },
    { href: '/utilisateurs', label: 'Utilisateurs' },
]

function isActive(href) {
    const url = page.url
    if (href === '/dashboard') return url === '/dashboard'
    return url.startsWith(href)
}

const showProfileModal = ref(false)
const isLoggingOut = ref(false)

// Récupère l'utilisateur depuis Inertia auth ou localStorage ou fallback maquette
const currentUser = computed(() => {
    if (page.props.auth?.user) {
        return page.props.auth.user
    }
    try {
        const stored = localStorage.getItem('auth_user')
        if (stored) {
            return JSON.parse(stored)
        }
    } catch (e) {}

    return {
        name: 'Aya Konan',
        email: 'aya.konan@mail.ci',
        role: 'gestionnaire',
    }
})

// Déconnexion qui appelle la méthode logout de App\Http\Controllers\user.php
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
        // Ignorer si la session est invalidée
    } finally {
        localStorage.removeItem('auth_user')
        isLoggingOut.value = false
        window.location.href = '/'
    }
}
</script>
