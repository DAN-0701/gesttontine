<template>
    <div class="min-h-screen bg-[#F5F5F0] flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 space-y-6 border border-gray-100">
            <div>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 mb-2">
                    Sécurité renforcée
                </span>
                <h1 class="text-2xl font-bold text-gray-900">
                    Changer votre mot de passe
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Vous vous êtes connecté avec un mot de passe temporaire. Vous devez obligatoirement définir votre nouveau mot de passe personnel pour accéder à votre espace.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">
                        Nouveau mot de passe <span class="text-red-500 font-bold">*</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            minlength="8"
                            placeholder="•••••••• (min. 8 caractères)"
                            class="w-full px-4 py-2.5 pr-11 rounded-lg border border-gray-300 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                        >
                            <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">
                        Confirmer le mot de passe <span class="text-red-500 font-bold">*</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            required
                            minlength="8"
                            placeholder="••••••••"
                            class="w-full px-4 py-2.5 pr-11 rounded-lg border border-gray-300 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                        >
                            <svg v-if="!showConfirmPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password_confirmation" class="text-xs text-red-600 mt-1">{{ form.errors.password_confirmation }}</p>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 bg-[#1B6B4A] hover:bg-[#155A3E] text-white font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer mon nouveau mot de passe' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const form = useForm({
    password: '',
    password_confirmation: '',
})

function submit() {
    form.post('/mot-de-passe-obligatoire')
}
</script>
