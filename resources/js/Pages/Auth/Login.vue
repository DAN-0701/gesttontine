<template>
    <div class="min-h-screen flex">
        <!-- Left panel - Green with marketing text -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#1B6B4A] flex-col justify-between p-12 text-white">
            <div>
                <h1 class="text-xl font-bold">Tontine</h1>
            </div>
            <div>
                <h2 class="text-4xl font-bold leading-tight mb-6">
                    Fini le cahier. Chaque cotisation, chaque tour, au même endroit.
                </h2>
                <p class="text-white/80 text-lg">
                    Suivez qui a payé, qui est en retard et à qui est le tour,
                    sans refaire les comptes à la main.
                </p>
            </div>
            <div></div>
        </div>

        <!-- Right panel - Login form & Password reset -->
        <div class="w-full lg:w-1/2 bg-[#F5F5F0] flex items-center justify-center p-8">
            <div class="w-full max-w-md">

                <!-- Mode Connexion -->
                <div v-if="!showForgotPassword">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Connexion</h2>
                    <p class="text-gray-500 mb-8">Entrez vos Identifiants.</p>

                    <div v-if="errorMessage" class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                        {{ errorMessage }}
                    </div>

                    <div v-if="successMessage" class="mb-5 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-700">
                        {{ successMessage }}
                    </div>

                    <form @submit.prevent="handleLogin">
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Adresse email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="prenom.nom@exemple.ci"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                        </div>

                        <div class="mb-3">
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Mot de passe</label>
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                        </div>

                        <!-- Lien Mot de passe oublié -->
                        <div class="flex justify-end mb-6">
                            <button
                                type="button"
                                @click="openForgotPassword"
                                class="text-xs font-semibold text-[#1B6B4A] hover:underline"
                            >
                                Mot de passe oublié ?
                            </button>
                        </div>

                        <button
                            type="submit"
                            :disabled="loading"
                            class="w-full py-3 bg-[#1B6B4A] text-white font-semibold rounded-lg hover:bg-[#155A3E] transition-colors disabled:opacity-50"
                        >
                            {{ loading ? 'Connexion en cours...' : 'Se connecter' }}
                        </button>
                    </form>
                </div>

                <!-- Mode Modification / Réinitialisation du mot de passe (utilise controller user::update) -->
                <div v-else>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Modifier le mot de passe</h2>
                    <p class="text-gray-500 mb-8">Entrez votre email et votre nouveau mot de passe (8 caractères minimum).</p>

                    <div v-if="resetError" class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                        {{ resetError }}
                    </div>

                    <form @submit.prevent="handleUpdatePassword">
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Adresse email du compte</label>
                            <input
                                v-model="resetForm.email"
                                type="email"
                                required
                                placeholder="prenom.nom@exemple.ci"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Nouveau mot de passe</label>
                            <input
                                v-model="resetForm.password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="•••••••• (min. 8 caractères)"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="submit"
                                :disabled="resetLoading"
                                class="flex-1 py-3 bg-[#1B6B4A] text-white font-semibold rounded-lg hover:bg-[#155A3E] transition-colors disabled:opacity-50"
                            >
                                {{ resetLoading ? 'Modification...' : 'Modifier le mot de passe' }}
                            </button>
                            <button
                                type="button"
                                @click="showForgotPassword = false"
                                class="px-5 py-3 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const form = ref({
    email: '',
    password: '',
})

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const showForgotPassword = ref(false)
const resetLoading = ref(false)
const resetError = ref('')
const resetForm = ref({
    email: '',
    password: '',
})

function openForgotPassword() {
    resetForm.value.email = form.value.email
    resetForm.value.password = ''
    resetError.value = ''
    showForgotPassword.value = true
}

// Utilise la route /login -> App\Http\Controllers\user::login
async function handleLogin() {
    loading.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await fetch('/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                email: form.value.email,
                password: form.value.password,
            })
        })

        const data = await response.json()

        if (response.ok) {
            // Sauvegarder les infos utilisateur localement si besoin
            if (data.user) {
                localStorage.setItem('auth_user', JSON.stringify(data.user))
            }
            window.location.href = '/dashboard'
        } else {
            errorMessage.value = data.message || 'Identifiants incorrects'
        }
    } catch (e) {
        // En cas de redirection ou fallback
        window.location.href = '/dashboard'
    } finally {
        loading.value = false
    }
}

// Utilise la route /update -> App\Http\Controllers\user::update
async function handleUpdatePassword() {
    resetLoading.value = true
    resetError.value = ''

    try {
        const response = await fetch('/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                email: resetForm.value.email,
                password: resetForm.value.password,
            })
        })

        const data = await response.json()

        if (response.ok) {
            showForgotPassword.value = false
            successMessage.value = data.message || 'Mot de passe modifié avec succès. Vous pouvez vous connecter.'
            form.value.password = ''
        } else {
            resetError.value = data.message || 'Erreur lors de la modification du mot de passe.'
        }
    } catch (e) {
        resetError.value = 'Une erreur est survenue lors de la communication avec le serveur.'
    } finally {
        resetLoading.value = false
    }
}
</script>
