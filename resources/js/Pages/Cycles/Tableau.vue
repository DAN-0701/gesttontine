<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <Link href="/cycles" class="text-sm font-semibold text-[#1B6B4A] hover:underline inline-flex items-center gap-1 mb-2">
                    &larr; Retour aux cycles
                </Link>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ cycle?.libelle }}</h1>
                    <span
                        :class="[
                            'inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold',
                            cycle?.statut === 'actif'
                                ? 'bg-emerald-50 text-emerald-700'
                                : cycle?.statut === 'brouillon'
                                ? 'bg-amber-50 text-amber-700'
                                : 'bg-gray-100 text-gray-600'
                        ]"
                    >
                        {{ cycle?.statut === 'actif' ? 'En cours' : cycle?.statut }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    {{ membres.length }} membre(s) · {{ formatMoney(cycle?.montant_cotisation) }} FCFA par {{ cycle?.periodicite || 'mois' }}
                </p>
            </div>

            <!-- Grille matricielle dynamique -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 overflow-hidden">
                <div v-if="membres.length > 0 && tours.length > 0" class="overflow-x-auto pb-4">
                    <table class="w-full border-separate border-spacing-2 text-center text-xs">
                        <thead>
                            <tr>
                                <th class="text-left font-semibold text-gray-500 pb-3 pr-4 w-44">
                                    Membre (rang)
                                </th>
                                <th v-for="t in tours" :key="t.id" class="pb-3 px-1 min-w-[90px]">
                                    <div class="font-bold text-gray-900 text-sm">T{{ t.numero }}</div>
                                    <div class="text-[11px] text-gray-400 font-normal">{{ formatDate(t.date_prevue) }}</div>
                                    <div class="text-[11px] text-gray-500 font-medium capitalize">{{ t.statut }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in membres" :key="m.id">
                                <!-- Membre Row Label -->
                                <td class="text-left font-medium text-gray-800 pr-4 whitespace-nowrap">
                                    <span class="text-gray-400 font-semibold mr-2">{{ m.rang }}</span>
                                    <span class="font-bold text-gray-900">{{ m.nom }}</span>
                                </td>

                                <!-- Cases pour chaque tour -->
                                <td v-for="t in tours" :key="t.id" class="p-0.5">
                                    <!-- Si ce membre est le bénéficiaire de ce tour -->
                                    <div
                                        v-if="t.beneficiaire && t.beneficiaire.id === m.id"
                                        :class="[
                                            'w-full py-3.5 rounded-lg font-bold text-xs flex items-center justify-center transition-colors',
                                            t.statut === 'ouvert' || t.statut === 'ferme' || t.statut === 'distribue'
                                                ? 'bg-[#155A3E] text-white shadow-2xs'
                                                : 'border-2 border-dashed border-gray-300 text-gray-500 bg-gray-50/50'
                                        ]"
                                    >
                                        Bénéficiaire
                                    </div>

                                    <!-- Sinon case de cotisation -->
                                    <div
                                        v-else
                                        @click="handleCellClick(m, t)"
                                        :class="[
                                            'w-full py-3.5 px-1 rounded-lg text-xs font-semibold flex flex-col items-center justify-center transition-all cursor-pointer',
                                            getCotisationClass(m, t)
                                        ]"
                                    >
                                        <span>{{ getCotisationStatusText(m, t) }}</span>
                                        <span
                                            v-if="getCotisationReste(m, t) > 0 && t.statut === 'ouvert'"
                                            class="text-[10px] font-normal opacity-90 mt-0.5"
                                        >
                                            {{ formatMoney(getCotisationReste(m, t)) }} dû
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-12 text-center text-gray-400 text-sm">
                    Ce cycle n'a pas encore de tours générés. Veuillez démarrer le cycle pour voir le tableau matriciel.
                </div>

                <p v-if="tours.length > 0" class="text-xs text-gray-400 mt-2">
                    Cliquer une case ouvre la fenêtre d'enregistrement de paiement.
                </p>
            </div>

            <!-- Bottom Section: Actions du tour & Légende -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <!-- Colonne Gauche: Tour en cours & actions -->
                <div class="md:col-span-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <h3 class="text-base font-bold text-gray-900">
                        {{ tourOuvert ? `Tour ${tourOuvert.numero} · bénéficiaire : ${tourOuvert.beneficiaire || 'En cours'}` : 'Aucun tour actuellement ouvert' }}
                    </h3>

                    <!-- Boutons (Demande 5: Réservé au gestionnaire) -->
                    <div v-if="isGestionnaire && tourOuvert" class="flex items-center gap-4 pt-1">
                        <button
                            type="button"
                            @click="cloturerTour"
                            class="px-5 py-2.5 border border-gray-800 hover:bg-gray-50 text-gray-900 text-sm font-semibold rounded-lg transition-colors"
                        >
                            Clôturer le tour {{ tourOuvert.numero }}
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 pt-1">
                        La distribution et la clôture des tours sont réservées au gestionnaire.
                    </p>
                </div>

                <!-- Colonne Droite: Légende -->
                <div class="md:col-span-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-3">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Légende</h3>

                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="px-3 py-1 rounded-md font-semibold bg-[#E2F2E9] text-[#1B6B4A]">
                            Payé
                        </span>
                        <span class="px-3 py-1 rounded-md font-semibold bg-blue-100 text-blue-700">
                            Partiel
                        </span>
                        <span class="px-3 py-1 rounded-md font-semibold bg-red-100 text-red-600">
                            En retard / Dû
                        </span>
                        <span class="px-3 py-1 rounded-md font-semibold bg-gray-100 text-gray-600">
                            En attente
                        </span>
                        <span class="px-3 py-1 rounded-md font-semibold bg-[#155A3E] text-white">
                            Bénéficiaire
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Paiement relié à Modules/Cotisation/app/Http/Controllers/PaiementController.php -->
        <div
            v-if="showPaymentModal"
            class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50 backdrop-blur-xs"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Enregistrer un paiement</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ activeModalMember?.nom }} · Tour {{ activeModalTour?.numero }}
                    </p>
                </div>

                <!-- Info Badges Attendue & Reste dû -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[#F8F9FA] p-3.5 rounded-xl border border-gray-100">
                        <p class="text-[11px] text-gray-500 font-medium">Attendu</p>
                        <p class="text-base font-bold text-gray-900 mt-1">
                            {{ formatMoney(activeCotisation?.montant_attendu || cycle?.montant_cotisation) }} FCFA
                        </p>
                    </div>
                    <div class="bg-red-50 p-3.5 rounded-xl border border-red-100">
                        <p class="text-[11px] text-red-600 font-medium">Reste dû</p>
                        <p class="text-base font-bold text-red-700 mt-1">
                            {{ formatMoney(activeCotisation?.reste_du ?? cycle?.montant_cotisation) }} FCFA
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1">Montant (FCFA)</label>
                            <input
                                v-model.number="paymentForm.montant"
                                type="number"
                                placeholder="ex: 10000"
                                required
                                min="1"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1">Mode</label>
                            <select
                                v-model="paymentForm.mode"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                            >
                                <option value="especes">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="virement">Virement</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-3">
                        <button
                            type="submit"
                            :disabled="paymentLoading"
                            class="flex-1 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ paymentLoading ? 'Enregistrement...' : 'Enregistrer le paiement' }}
                        </button>
                        <button
                            type="button"
                            @click="showPaymentModal = false"
                            class="px-5 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors"
                        >
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    cycle: Object,
    membres: {
        type: Array,
        default: () => []
    },
    tours: {
        type: Array,
        default: () => []
    },
    tourOuvert: Object
})

const page = usePage()

const isGestionnaire = computed(() => {
    return page.props.auth?.user?.role !== 'tresorier'
})

function getCotisation(membre, tour) {
    if (!tour.cotisations) return null
    return tour.cotisations.find(c => c.membre_id === membre.id)
}

function getCotisationStatusText(membre, tour) {
    const c = getCotisation(membre, tour)
    if (!c) {
        return tour.statut === 'ouvert' ? 'En retard' : 'En attente'
    }
    if (c.statut === 'paye' || c.statut === 'payee') return 'Payé'
    if (c.statut === 'partielle' || c.statut === 'partiel') return 'Partiel'
    if (tour.statut === 'ouvert' && c.reste_du > 0) return 'En retard'
    return 'En attente'
}

function getCotisationReste(membre, tour) {
    const c = getCotisation(membre, tour)
    return c ? (c.reste_du ?? (c.montant_attendu - c.montant_paye)) : (props.cycle?.montant_cotisation || 0)
}

function getCotisationClass(membre, tour) {
    const c = getCotisation(membre, tour)
    if (c && (c.statut === 'paye' || c.statut === 'payee')) {
        return 'bg-[#E2F2E9] text-[#1B6B4A]'
    }
    if (c && (c.statut === 'partielle' || c.statut === 'partiel')) {
        return 'bg-blue-50 text-blue-700 border border-blue-200'
    }
    if (tour.statut === 'ouvert') {
        return 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 shadow-2xs'
    }
    return 'bg-[#F0F0EC] text-gray-600'
}

// Modal Paiement
const showPaymentModal = ref(false)
const activeModalMember = ref(null)
const activeModalTour = ref(null)
const activeCotisation = ref(null)
const paymentLoading = ref(false)

const paymentForm = ref({
    montant: null,
    mode: 'especes',
})

function handleCellClick(membre, tour) {
    const c = getCotisation(membre, tour)
    activeModalMember.value = membre
    activeModalTour.value = tour
    activeCotisation.value = c
    paymentForm.value.montant = c ? c.reste_du : props.cycle?.montant_cotisation
    showPaymentModal.value = true
}

function submitPayment() {
    if (!activeCotisation.value?.id) {
        alert("Cotisation introuvable pour ce membre sur ce tour.")
        return
    }

    paymentLoading.value = true
    router.post('/paiements', {
        cotisation_id: activeCotisation.value.id,
        montant: paymentForm.value.montant,
        mode: paymentForm.value.mode,
    }, {
        onSuccess: () => {
            showPaymentModal.value = false
        },
        onFinish: () => {
            paymentLoading.value = false
        }
    })
}

function cloturerTour() {
    if (!props.tourOuvert?.id) return
    if (confirm(`Êtes-vous sûr de vouloir clôturer le tour ${props.tourOuvert.numero} ?`)) {
        router.post(`/cycles/${props.cycle.id}/tours/${props.tourOuvert.id}/cloturer`)
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
        month: 'short'
    })
}
</script>
