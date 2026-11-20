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
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Adresse email <span class="text-red-500 font-bold">*</span></label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="prenom.nom@exemple.ci"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                        </div>

                        <div class="mb-3">
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Mot de passe <span class="text-red-500 font-bold">*</span></label>
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="w-full px-4 py-3 pr-11 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                                    :title="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                                >
                                    <!-- Icône œil ouvert -->
                                    <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <!-- Icône œil barré -->
                                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
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

                <!-- Mode Mot de passe oublié (Nouveau comportement sécurisé : email uniquement + message générique) -->
                <div v-else>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Mot de passe oublié</h2>
                    <p class="text-gray-500 mb-6">
                        Saisissez votre adresse email pour recevoir votre mot de passe temporaire par e-mail.
                    </p>

                    <div v-if="resetError" class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                        {{ resetError }}
                    </div>

                    <div v-if="infoMessage" class="mb-5 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 leading-relaxed">
                        {{ infoMessage }}
                    </div>

                    <form @submit.prevent="handleForgotPassword" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-1.5">Adresse email du compte <span class="text-red-500 font-bold">*</span></label>
                            <input
                                v-model="resetEmail"
                                type="email"
                                required
                                placeholder="prenom.nom@exemple.ci"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button
                                type="submit"
                                :disabled="resetLoading"
                                class="flex-1 py-3 bg-[#1B6B4A] text-white font-semibold rounded-lg hover:bg-[#155A3E] transition-colors disabled:opacity-50 cursor-pointer"
                            >
                                {{ resetLoading ? 'Envoi en cours...' : 'Envoyer le mot de passe temporaire' }}
                            </button>
                            <button
                                type="button"
                                @click="cancelReset"
                                class="px-5 py-3 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors cursor-pointer"
                            >
                                Retour
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

const showPassword = ref(false)

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const showForgotPassword = ref(false)
const resetLoading = ref(false)
const resetError = ref('')
const infoMessage = ref('')
const resetEmail = ref('')

function openForgotPassword() {
    resetEmail.value = form.value.email
    resetError.value = ''
    infoMessage.value = ''
    showForgotPassword.value = true
}

function cancelReset() {
    showForgotPassword.value = false
    resetError.value = ''
    infoMessage.value = ''
}

// Demande de mot de passe temporaire envoyé par email
async function handleForgotPassword() {
    resetLoading.value = true
    resetError.value = ''
    infoMessage.value = ''

    try {
        const response = await fetch('/password/forgot', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                email: resetEmail.value,
            })
        })

        const data = await response.json()
        infoMessage.value = data.message || 'Si cette adresse e-mail existe dans notre système, un mot de passe temporaire a été envoyé.'
    } catch (e) {
        infoMessage.value = 'Si cette adresse e-mail existe dans notre système, un mot de passe temporaire a été envoyé.'
    } finally {
        resetLoading.value = false
    }
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
            if (data.user) {
                localStorage.setItem('auth_user', JSON.stringify(data.user))
            }
            if (data.force_password_change || data.redirect === '/mot-de-passe-obligatoire') {
                window.location.href = '/mot-de-passe-obligatoire'
            } else {
                window.location.href = '/dashboard'
            }
        } else {
            errorMessage.value = data.message || 'Identifiants incorrects'
        }
    } catch (e) {
        window.location.href = '/dashboard'
    } finally {
        loading.value = false
    }
}
</script>
