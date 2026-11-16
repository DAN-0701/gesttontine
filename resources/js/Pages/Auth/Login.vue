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

        </div>

        <!-- Right panel - Login form -->
        <div class="w-full lg:w-1/2 bg-[#F5F5F0] flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Connexion</h2>
                <p class="text-gray-500 mb-8">Entrez vos Identifiants.</p>

                <form @submit.prevent="submit">
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-900 mb-1.5">Adresse email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="prenom.nom@exemple.ci"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-900 mb-1.5">Mot de passe</label>
                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 bg-[#1B6B4A] text-white font-semibold rounded-lg hover:bg-[#155A3E] transition-colors disabled:opacity-50"
                    >
                        Se connecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: '',
})

function submit() {
    form.post('/login')
}
</script>
