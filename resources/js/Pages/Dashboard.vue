<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header avec Sélecteur de cycle (Demande 8) -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tableau de bord</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Vue d'ensemble et suivi des cotisations par cycle.
                    </p>
                </div>

                <!-- Sélecteur de cycle (Demande 8: choisir le cycle qu'on veut observer) -->
                <div v-if="availableCycles && availableCycles.length > 0" class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-700 whitespace-nowrap">Cycle observé :</label>
                    <select
                        :value="selectedCycleId"
                        @change="onCycleChange($event.target.value)"
                        class="px-3 py-2 text-xs font-semibold rounded-xl border border-gray-300 bg-white text-gray-900 shadow-2xs focus:ring-2 focus:ring-[#1B6B4A] focus:outline-none cursor-pointer"
                    >
                        <option
                            v-for="c in availableCycles"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.libelle }} ({{ c.statut }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Si un cycle est chargé -->
            <div v-if="cycle" class="space-y-6">
                <!-- Info barre sous le header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-gray-100 shadow-2xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-gray-900 text-sm">{{ cycle.libelle }}</span>
                        <span class="text-gray-300">·</span>
                        <span class="text-xs text-gray-600">
                            Tour {{ cycle.tour_actuel || 1 }} sur {{ cycle.total_tours || 1 }}
                        </span>
                        <template v-if="cycle.date_echeance">
                            <span class="text-gray-300">·</span>
                            <span class="text-xs text-gray-600">
                                Échéance le {{ formatDate(cycle.date_echeance) }}
                            </span>
                        </template>
                        <span
                            :class="[
                                'inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                cycle.statut === 'actif'
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : cycle.statut === 'brouillon'
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-gray-100 text-gray-700'
                            ]"
                        >
                            {{ cycle.statut === 'actif' ? 'En cours' : (cycle.statut === 'brouillon' ? 'Brouillon' : 'Terminé') }}
                        </span>
                    </div>

                    <Link
                        :href="`/cycles/${cycle.id}`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer self-start sm:self-auto"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        <span>Ouvrir le tableau matriciel</span>
                    </Link>
                </div>

                <!-- Top Metric Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Encaissé sur ce tour -->
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-500">Encaissé sur ce tour</p>
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            {{ formatMoney(cycle.encaisse) }} FCFA
                        </p>
                        <div class="w-full bg-gray-200 h-1.5 rounded-full mt-3 overflow-hidden">
                            <div
                                class="bg-[#1B6B4A] h-1.5 rounded-full"
                                :style="{ width: getProgressPercentage() + '%' }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            sur {{ formatMoney(cycle.total_attendu) }} attendus · {{ cycle.membres_payes }} membres sur {{ cycle.total_membres }}
                        </p>
                    </div>

                    <!-- Cotisations en retard (avec tolérance Demande 6) -->
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-500">Cotisations en retard</p>
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            {{ cycle.cotisations_en_retard || 0 }}
                        </p>
                        <div class="h-1.5 mt-3"></div>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ formatMoney(cycle.montant_retard) }} FCFA non réglés
                        </p>
                    </div>

                    <!-- Pénalités dues -->
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-500">Pénalités dues</p>
                            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            {{ formatMoney(cycle.montant_penalite) }} FCFA
                        </p>
                        <div class="h-1.5 mt-3"></div>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ cycle.penalites_dues || 0 }} pénalité(s) due(s)
                        </p>
                    </div>

                    <!-- Cagnotte prévue -->
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-medium text-gray-500">Cagnotte prévue</p>
                            <svg class="w-4 h-4 text-[#1B6B4A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            {{ formatMoney(cycle.cagnotte) }} FCFA
                        </p>
                        <div class="h-1.5 mt-3"></div>
                        <p class="text-xs text-gray-500 mt-2">
                            Moins {{ formatMoney(cycle.frais_gestion) }} de frais de gestion
                        </p>
                    </div>
                </div>

                <!-- Lower Section: Bénéficiaire card & Retards à relancer -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                    <!-- Bénéficiaire du tour -->
                    <div class="md:col-span-5 bg-[#1B6B4A] text-white p-6 rounded-2xl flex flex-col justify-between min-h-[220px]">
                        <div>
                            <p class="text-xs font-medium text-emerald-100 uppercase tracking-wider">
                                Bénéficiaire du tour {{ cycle.tour_actuel || 1 }}
                            </p>
                            <h2 class="text-2xl font-bold mt-2">
                                {{ cycle.beneficiaire?.nom || 'Non défini' }}
                            </h2>
                            <p class="text-sm text-emerald-100 mt-1">
                                Rang {{ cycle.beneficiaire?.rang || 1 }} · reçoit la cagnotte après clôture du tour
                            </p>
                        </div>

                        <p class="text-xs text-emerald-200 mt-6 leading-relaxed">
                            Distribution possible quand le tour est clôturé et que le bénéficiaire n'est pas bloqué.
                        </p>
                    </div>

                    <!-- Retards à relancer -->
                    <div class="md:col-span-7 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900 mb-4">Retards à relancer</h3>

                        <div v-if="cycle.retards && cycle.retards.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="text-gray-400 text-xs border-b border-gray-100">
                                        <th class="pb-3 font-normal">Membre</th>
                                        <th class="pb-3 font-normal">Téléphone</th>
                                        <th class="pb-3 font-normal">Reste dû</th>
                                        <th class="pb-3 font-normal">Pénalité</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr
                                        v-for="(item, index) in cycle.retards"
                                        :key="index"
                                        class="text-gray-800"
                                    >
                                        <td class="py-3 font-semibold text-gray-900">{{ item.membre }}</td>
                                        <td class="py-3 text-gray-600">{{ item.telephone }}</td>
                                        <td class="py-3 font-medium">{{ formatMoney(item.reste_du) }} FCFA</td>
                                        <td class="py-3">
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                                {{ formatMoney(item.penalite) }} FCFA due
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="py-8 text-center text-gray-400 text-sm">
                            Aucune cotisation en retard sur ce tour.
                        </div>
                    </div>
                </div>
            </div>

            <!-- État si aucun cycle en base -->
            <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center space-y-4">
                <h2 class="text-xl font-bold text-gray-900">Bienvenue sur votre gestionnaire de tontine</h2>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    Aucun cycle n'a été créé pour le moment. Créez un cycle pour commencer le suivi de votre tontine.
                </p>
                <div>
                    <Link
                        href="/cycles/create"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Créer un premier cycle</span>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    cycle: Object,
    availableCycles: {
        type: Array,
        default: () => []
    },
    selectedCycleId: [Number, String],
})

function onCycleChange(newCycleId) {
    router.get('/dashboard', { cycle_id: newCycleId }, { preserveState: true })
}

function getProgressPercentage() {
    if (!props.cycle || !props.cycle.total_attendu || props.cycle.total_attendu === 0) return 0
    return Math.min(100, Math.round((props.cycle.encaisse / props.cycle.total_attendu) * 100))
}

function formatMoney(amount) {
    if (amount === undefined || amount === null) return '0'
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}
</script>
