<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header avec actions et boutons + icônes -->
            <div>
                <Link href="/cycles" class="text-sm font-semibold text-[#1B6B4A] hover:underline inline-flex items-center gap-1 mb-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Retour aux cycles</span>
                </Link>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
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

                    <!-- Action Clôturer le tour courant -->
                    <button
                        v-if="canClotureTour && tourOuvert"
                        type="button"
                        @click="askCloturerTour"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-xs font-semibold rounded-lg shadow-sm transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Clôturer le tour {{ tourOuvert.numero }}</span>
                    </button>
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
                                            {{ formatMoney(getCotisationReste(m, t)) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Paiement & Gestion Pénalité (Demandes 6 & 7) -->
            <div
                v-if="showPaymentModal"
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-in fade-in duration-150"
                @click.self="showPaymentModal = false"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-gray-900">
                                Cotisation Tour {{ activeModalTour?.numero }} · {{ activeModalMember?.nom }}
                            </h3>
                            <p class="text-xs text-gray-500">
                                Statut actuel : <span class="font-semibold capitalize text-gray-800">{{ getCotisationStatusText(activeModalMember, activeModalTour) }}</span>
                            </p>
                        </div>
                        <button
                            @click="showPaymentModal = false"
                            class="text-gray-400 hover:text-gray-600 font-bold p-1 text-sm cursor-pointer"
                        >
                            &times;
                        </button>
                    </div>

                    <!-- Encarts montants -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <p class="text-[11px] text-gray-500 font-medium">Déjà payé</p>
                            <p class="text-base font-bold text-gray-900 mt-1">
                                {{ formatMoney(activeCotisation?.montant_paye ?? 0) }} FCFA
                            </p>
                        </div>
                        <div class="bg-red-50 p-3 rounded-xl border border-red-100">
                            <p class="text-[11px] text-red-600 font-medium">Reste dû</p>
                            <p class="text-base font-bold text-red-700 mt-1">
                                {{ formatMoney(activeCotisation?.reste_du ?? cycle?.montant_cotisation) }} FCFA
                            </p>
                        </div>
                    </div>

                    <!-- Formulaire d'enregistrement d'un paiement -->
                    <form v-if="canRecordPayment" @submit.prevent="submitPayment" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-900 mb-1">Montant à régler (FCFA)</label>
                                <input
                                    v-model.number="paymentForm.montant"
                                    type="number"
                                    required
                                    min="1"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-900 mb-1">Mode de règlement</label>
                                <select
                                    v-model="paymentForm.mode"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                                >
                                    <option value="especes">Espèces</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="virement">Virement</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button
                                type="submit"
                                :disabled="paymentLoading"
                                class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-xs font-semibold rounded-lg transition-colors disabled:opacity-50 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>{{ paymentLoading ? 'Enregistrement...' : 'Enregistrer le paiement' }}</span>
                            </button>
                            <button
                                type="button"
                                @click="showPaymentModal = false"
                                class="px-4 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg cursor-pointer"
                            >
                                Fermer
                            </button>
                        </div>
                    </form>
                    <div v-else class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between text-xs text-amber-800">
                        <span>Vous ne disposez pas de l'autorisation requise pour enregistrer un paiement.</span>
                        <button
                            type="button"
                            @click="showPaymentModal = false"
                            class="px-3 py-1.5 border border-amber-300 hover:bg-amber-100 rounded-lg text-xs font-semibold cursor-pointer"
                        >
                            Fermer
                        </button>
                    </div>

                    <!-- Historique des paiements avec possibilité d'annuler (Demande 7) -->
                    <div v-if="activeCotisation?.paiements && activeCotisation.paiements.length > 0" class="pt-3 border-t border-gray-100 space-y-2">
                        <p class="text-xs font-bold text-gray-800">Historique des versements</p>
                        <div class="space-y-1.5 max-h-36 overflow-y-auto">
                            <div
                                v-for="p in activeCotisation.paiements"
                                :key="p.id"
                                class="flex items-center justify-between p-2.5 rounded-lg border border-gray-100 text-xs bg-gray-50"
                            >
                                <div>
                                    <p class="font-bold text-gray-900">{{ formatMoney(p.montant) }} FCFA · <span class="capitalize text-gray-600">{{ p.mode }}</span></p>
                                    <p class="text-[10px] text-gray-400">{{ formatDate(p.date_paiement) }}</p>
                                    <p v-if="p.annule" class="text-[10px] text-red-600 font-semibold mt-0.5">
                                        Annulé : {{ p.motif_annulation }}
                                    </p>
                                </div>
                                <button
                                    v-if="canCancel && !p.annule"
                                    type="button"
                                    @click="openCancelPaymentModal(p)"
                                    class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-700 font-semibold rounded text-[11px] cursor-pointer"
                                >
                                    Annuler ce paiement
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bloc Pénalité (Demande 7: annuler pénalité avec motif) -->
                    <div v-if="activeCotisation?.penalite" class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-amber-900">
                                Pénalité : {{ formatMoney(activeCotisation.penalite.montant) }} FCFA ({{ activeCotisation.penalite.statut }})
                            </span>
                            <button
                                v-if="canCancel && activeCotisation.penalite.statut !== 'annulee'"
                                type="button"
                                @click="openCancelPenaliteModal(activeCotisation.penalite)"
                                class="px-2 py-1 bg-white hover:bg-amber-100 text-amber-800 border border-amber-300 font-semibold rounded text-[11px] cursor-pointer"
                            >
                                Annuler la pénalité
                            </button>
                        </div>
                        <p v-if="activeCotisation.penalite.motif_annulation" class="text-[11px] text-amber-800">
                            Motif d'annulation : {{ activeCotisation.penalite.motif_annulation }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Motif Annulation (Demande 7) -->
            <div
                v-if="showCancelReasonModal"
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 backdrop-blur-xs animate-in fade-in duration-150"
                @click.self="showCancelReasonModal = false"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <h3 class="text-base font-bold text-gray-900">
                        {{ cancelTargetType === 'paiement' ? 'Annuler le paiement' : 'Annuler la pénalité' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        Veuillez renseigner un justificatif ou motif obligatoire d'annulation.
                    </p>

                    <form @submit.prevent="submitCancelWithReason" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1">Motif d'annulation *</label>
                            <textarea
                                v-model="cancelReason"
                                required
                                rows="3"
                                placeholder="ex: Erreur de saisie / Justificatif médical fourni..."
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs text-gray-900 focus:ring-2 focus:ring-[#1B6B4A]"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="showCancelReasonModal = false"
                                class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg cursor-pointer"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg cursor-pointer"
                            >
                                Confirmer l'annulation
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Confirmation Modal Card (Demande 4) -->
            <ConfirmModal
                :show="confirmData.show"
                :title="confirmData.title"
                :message="confirmData.message"
                :confirmText="confirmData.confirmText"
                :isDanger="confirmData.isDanger"
                @confirm="handleConfirmAction"
                @cancel="confirmData.show = false"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

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

const canClotureTour = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.cloturer_tour
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'gestionnaire'
})

const canCancel = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.annuler_paiement || !!page.props.auth.user.permissions.annuler_penalite
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'gestionnaire'
})

const canRecordPayment = computed(() => {
    if (page.props.auth?.user?.permissions) {
        return !!page.props.auth.user.permissions.enregistrer_paiement
    }
    const role = page.props.auth?.user?.role
    return role === 'administrateur' || role === 'tresorier'
})

function getCotisation(membre, tour) {
    if (!tour.cotisations) return null
    return tour.cotisations.find(c => c.membre_id === membre.id)
}

function getCotisationStatusText(membre, tour) {
    const c = getCotisation(membre, tour)
    if (!c) {
        return (tour.is_retard_applicable && tour.statut === 'ouvert') ? 'En retard' : 'En attente'
    }
    if (c.statut === 'paye' || c.statut === 'payee') return 'Payé'
    if (c.statut === 'partielle' || c.statut === 'partiel') return 'Partiel'
    if (c.reste_du > 0 && tour.statut === 'ouvert' && tour.is_retard_applicable) return 'En retard'
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
    if (tour.statut === 'ouvert' && tour.is_retard_applicable && getCotisationReste(membre, tour) > 0) {
        return 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 shadow-2xs'
    }
    if (tour.statut === 'ouvert') {
        return 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 shadow-2xs'
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

// Clôturer Tour avec Modal Card (Demande 4)
const confirmData = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirmer',
    isDanger: false,
    action: null,
})

function askCloturerTour() {
    if (!props.tourOuvert?.id) return
    confirmData.value = {
        show: true,
        title: `Clôturer le Tour ${props.tourOuvert.numero}`,
        message: `Êtes-vous sûr de vouloir clôturer le tour ${props.tourOuvert.numero} et ouvrir le suivant ?`,
        confirmText: 'Clôturer le tour',
        isDanger: false,
        action: () => {
            router.post(`/cycles/${props.cycle.id}/tours/${props.tourOuvert.id}/cloturer`)
        }
    }
}

function handleConfirmAction() {
    if (confirmData.value.action) {
        confirmData.value.action()
    }
    confirmData.value.show = false
}

// Annulation avec motif obligatoire (Demande 7)
const showCancelReasonModal = ref(false)
const cancelTargetType = ref('paiement')
const cancelTargetItem = ref(null)
const cancelReason = ref('')

function openCancelPaymentModal(paiement) {
    cancelTargetType.value = 'paiement'
    cancelTargetItem.value = paiement
    cancelReason.value = ''
    showCancelReasonModal.value = true
}

function openCancelPenaliteModal(penalite) {
    cancelTargetType.value = 'penalite'
    cancelTargetItem.value = penalite
    cancelReason.value = ''
    showCancelReasonModal.value = true
}

function submitCancelWithReason() {
    if (!cancelReason.value.trim()) return

    if (cancelTargetType.value === 'paiement') {
        router.post(`/paiements/${cancelTargetItem.value.id}/annuler`, {
            motif_annulation: cancelReason.value
        }, {
            onSuccess: () => {
                showCancelReasonModal.value = false
                showPaymentModal.value = false
            }
        })
    } else {
        router.post(`/penalites/${cancelTargetItem.value.id}/annuler`, {
            motif_annulation: cancelReason.value
        }, {
            onSuccess: () => {
                showCancelReasonModal.value = false
                showPaymentModal.value = false
            }
        })
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
