<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Cycles</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Chaque cycle réunit un groupe de membres, un montant et une périodicité.
                    </p>
                </div>
                <Link
                    href="/cycles/create"
                    class="inline-flex items-center px-4 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
                >
                    Nouveau cycle
                </Link>
            </div>

            <!-- Cycles Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="cycle in displayedCycles"
                    :key="cycle.id"
                    :class="[
                        'bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between',
                        cycle.statut === 'termine' || cycle.statut === 'cloture' ? 'md:col-span-2' : ''
                    ]"
                >
                    <div>
                        <!-- Title & Status Badge -->
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-bold text-gray-900">{{ cycle.libelle }}</h2>
                            <span
                                :class="[
                                    'inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                    cycle.statut === 'actif' || cycle.statut === 'en_cours'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : cycle.statut === 'brouillon'
                                        ? 'bg-amber-50 text-amber-700'
                                        : 'bg-gray-100 text-gray-600'
                                ]"
                            >
                                {{ getStatusLabel(cycle.statut) }}
                            </span>
                        </div>

                        <!-- Description & Timeline -->
                        <p class="text-sm text-gray-600 mt-3 font-medium">
                            {{ cycle.membres_count || 0 }} membres · {{ formatMoney(cycle.montant_cotisation) }} FCFA par {{ cycle.periodicite || 'mois' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            <template v-if="cycle.statut === 'actif' || cycle.statut === 'en_cours'">
                                Début le {{ formatDate(cycle.date_debut) }} · Tour {{ cycle.tour_actuel || 1 }} sur {{ cycle.total_tours || 8 }}
                            </template>
                            <template v-else-if="cycle.statut === 'brouillon'">
                                Début prévu le {{ formatDate(cycle.date_debut) }}
                            </template>
                            <template v-else>
                                Du {{ formatDate(cycle.date_debut) }} au {{ formatDate(cycle.date_fin) }} · {{ cycle.tours_distribues || cycle.total_tours }} tours distribués
                            </template>
                        </p>

                        <!-- Progress Bar (for active cycles) -->
                        <div
                            v-if="cycle.statut === 'actif' || cycle.statut === 'en_cours'"
                            class="w-full bg-gray-100 h-1.5 rounded-full mt-4 overflow-hidden"
                        >
                            <div
                                class="bg-[#1B6B4A] h-1.5 rounded-full"
                                :style="{ width: ((cycle.tour_actuel || 3) / (cycle.total_tours || 8) * 100) + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="mt-6 pt-2">
                        <!-- Active Cycle: Ouvrir le tableau du cycle -->
                        <div v-if="cycle.statut === 'actif' || cycle.statut === 'en_cours'">
                            <Link
                                :href="`/cycles/${cycle.id}/tableau`"
                                class="w-full inline-flex justify-center items-center py-2.5 px-4 border border-[#1B6B4A] text-[#1B6B4A] hover:bg-emerald-50 text-sm font-semibold rounded-lg transition-colors"
                            >
                                Ouvrir le tableau du cycle
                            </Link>
                        </div>

                        <!-- Brouillon: Modifier + Démarrer -->
                        <div v-else-if="cycle.statut === 'brouillon'" class="flex gap-3">
                            <Link
                                :href="`/cycles/${cycle.id}/edit`"
                                class="flex-1 inline-flex justify-center items-center py-2.5 px-4 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold rounded-lg transition-colors"
                            >
                                Modifier
                            </Link>
                            <button
                                @click="demarrerCycle(cycle.id)"
                                class="flex-1 inline-flex justify-center items-center py-2.5 px-4 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg transition-colors"
                            >
                                Démarrer
                            </button>
                        </div>

                        <!-- Terminé / Clôturé: Consulter -->
                        <div v-else>
                            <Link
                                :href="`/cycles/${cycle.id}/tableau`"
                                class="w-full inline-flex justify-center items-center py-2.5 px-4 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-semibold rounded-lg transition-colors"
                            >
                                Consulter
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer note -->
            <p class="text-xs text-gray-500">
                Démarrer un cycle est réservé au gestionnaire : le trésorier ne voit pas ce bouton, et le serveur répond 403 s'il tente l'action.
            </p>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    cycles: Array
})

const defaultCycles = [
    {
        id: 1,
        libelle: 'Tontine Famille 2026',
        statut: 'actif',
        montant_cotisation: 20000,
        periodicite: 'mois',
        date_debut: '2026-09-05',
        membres_count: 8,
        total_tours: 8,
        tour_actuel: 3,
    },
    {
        id: 2,
        libelle: 'Tontine Quartier 2027',
        statut: 'brouillon',
        montant_cotisation: 10000,
        periodicite: 'mois',
        date_debut: '2027-01-05',
        membres_count: 6,
        total_tours: 6,
    },
    {
        id: 3,
        libelle: 'Tontine Collègues 2025',
        statut: 'termine',
        montant_cotisation: 15000,
        periodicite: 'mois',
        date_debut: '2025-01-05',
        date_fin: '2025-06-05',
        membres_count: 6,
        total_tours: 6,
        tours_distribues: 6,
    }
]

const displayedCycles = computed(() => {
    return (props.cycles && props.cycles.length > 0) ? props.cycles : defaultCycles
})

function getStatusLabel(statut) {
    switch (statut) {
        case 'actif':
        case 'en_cours':
            return 'En cours'
        case 'brouillon':
            return 'Brouillon'
        case 'termine':
        case 'cloture':
            return 'Clôturé'
        default:
            return statut
    }
}

function demarrerCycle(id) {
    if (confirm('Êtes-vous sûr de vouloir démarrer ce cycle ? Cette action est irréversible.')) {
        router.post(`/cycles/${id}/demarrer`)
    }
}

function formatMoney(amount) {
    if (amount === undefined || amount === null) return '0'
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function formatDate(dateStr) {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    })
}
</script>
