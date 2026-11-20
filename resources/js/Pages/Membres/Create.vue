<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Back link & Header -->
            <div>
                <Link href="/membres" class="text-sm font-semibold text-[#1B6B4A] hover:underline inline-flex items-center gap-1 mb-2">
                    &larr; Retour aux membres
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">
                    Nouveau membre
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Le téléphone doit être unique dans toute la tontine.
                </p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Row 1: Nom & Prénom -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1.5">Nom <span class="text-red-500 font-bold">*</span></label>
                            <input
                                v-model="form.nom"
                                type="text"
                                placeholder="ex: Gnahoré"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                            <p v-if="form.errors.nom" class="mt-1 text-xs text-red-600">{{ form.errors.nom }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1.5">Prénom <span class="text-red-500 font-bold">*</span></label>
                            <input
                                v-model="form.prenom"
                                type="text"
                                placeholder="ex: Estelle"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                            <p v-if="form.errors.prenom" class="mt-1 text-xs text-red-600">{{ form.errors.prenom }}</p>
                        </div>
                    </div>

                    <!-- Row 2: Téléphone & Email -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1.5">Téléphone <span class="text-red-500 font-bold">*</span></label>
                            <input
                                v-model="form.telephone"
                                type="text"
                                placeholder="ex: 0700123456"
                                :class="[
                                    'w-full px-4 py-2.5 rounded-lg border text-sm text-gray-900 focus:outline-none',
                                    form.errors.telephone
                                        ? 'border-red-500 focus:ring-2 focus:ring-red-400'
                                        : 'border-gray-300 bg-white focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent'
                                ]"
                            />
                            <p v-if="form.errors.telephone" class="mt-1.5 text-xs text-red-600 font-medium leading-relaxed">
                                {{ form.errors.telephone }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1.5">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="ex: estelle.gnahore@mail.ci"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <!-- Row 3: Adresse -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-900 mb-1.5">Adresse <span class="text-red-500 font-bold">*</span></label>
                        <input
                            v-model="form.adresse"
                            type="text"
                            placeholder="ex: Quartier, commune"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                        />
                        <p v-if="form.errors.adresse" class="mt-1 text-xs text-red-600">{{ form.errors.adresse }}</p>
                    </div>

                    <!-- Row 4: Date d'adhésion & Statut -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1.5">Date d'adhésion <span class="text-red-500 font-bold">*</span></label>
                            <input
                                v-model="form.date_adhesion"
                                type="date"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            />
                            <p v-if="form.errors.date_adhesion" class="mt-1 text-xs text-red-600">{{ form.errors.date_adhesion }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1.5">Statut <span class="text-red-500 font-bold">*</span></label>
                            <select
                                v-model="form.statut"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A] focus:border-transparent"
                            >
                                <option value="actif">Actif</option>
                                <option value="desactive">Désactivé</option>
                            </select>
                            <p v-if="form.errors.statut" class="mt-1 text-xs text-red-600">{{ form.errors.statut }}</p>
                        </div>
                    </div>

                    <!-- Actions Buttons -->
                    <div class="flex items-center gap-4 pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                        >
                            Enregistrer le membre
                        </button>
                        <Link
                            href="/membres"
                            class="px-5 py-2.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors"
                        >
                            Annuler
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// Champs vides avec placeholders explicites
const form = useForm({
    nom: '',
    prenom: '',
    telephone: '',
    email: '',
    adresse: '',
    date_adhesion: new Date().toISOString().substring(0, 10),
    statut: 'actif',
})

function submit() {
    form.post('/members')
}
</script>
