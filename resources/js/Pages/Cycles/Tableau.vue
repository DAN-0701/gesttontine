<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <Link href="/cycles" class="text-sm font-semibold text-[#1B6B4A] hover:underline inline-flex items-center gap-1 mb-2">
                    &larr; Retour aux cycles
                </Link>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ cycle?.libelle || 'Tontine Famille 2026' }}</h1>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                        En cours
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    8 membres · 20 000 FCFA par mois · Tour 3 sur 8 · échéance le 5 nov. 2026
                </p>
            </div>

            <!-- Table Matrix Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 overflow-hidden">
                <div class="overflow-x-auto pb-4">
                    <table class="w-full border-separate border-spacing-2 text-center text-xs">
                        <thead>
                            <tr>
                                <th class="text-left font-semibold text-gray-500 pb-3 pr-4 w-44">
                                    Membre (rang)
                                </th>
                                <th v-for="t in toursData" :key="t.numero" class="pb-3 px-1 min-w-[90px]">
                                    <div class="font-bold text-gray-900 text-sm">T{{ t.numero }}</div>
                                    <div class="text-[11px] text-gray-400 font-normal">{{ t.date_court }}</div>
                                    <div class="text-[11px] text-gray-500 font-medium">{{ t.statut_label }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in matrixMembers" :key="m.id">
                                <!-- Membre Row Label -->
                                <td class="text-left font-medium text-gray-800 pr-4 whitespace-nowrap">
                                    <span class="text-gray-400 font-semibold mr-2">{{ m.rang }}</span>
                                    <span class="font-bold text-gray-900">{{ m.nom }}</span>
                                </td>

                                <!-- Cases pour chaque tour -->
                                <td v-for="t in toursData" :key="t.numero" class="p-0.5">
                                    <!-- Si ce membre est le bénéficiaire de ce tour -->
                                    <div
                                        v-if="m.rang === t.numero"
                                        :class="[
                                            'w-full py-3.5 rounded-lg font-bold text-xs flex items-center justify-center transition-colors',
                                            t.statut === 'ouvert' || t.statut === 'distribue' || t.statut === 'ferme'
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
                                            'w-full py-3.5 px-1 rounded-lg text-xs font-semibold flex flex-col items-center justify-center transition-all',
                                            getCellClass(m, t)
                                        ]"
                                    >
                                        <span>{{ getCellStatusText(m, t) }}</span>
                                        <span
                                            v-if="isCellLate(m, t)"
                                            class="text-[10px] font-normal opacity-90 mt-0.5"
                                        >
                                            20 000 dû
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-400 mt-2">
                    Cliquer une case en retard ouvre la fenêtre d'encaissement. Les cotisations d'un tour à venir ne sont jamais en retard.
                </p>
            </div>

            <!-- Bottom Section: Actions du tour & Légende -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <!-- Colonne Gauche: Tour en cours & actions -->
                <div class="md:col-span-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <h3 class="text-base font-bold text-gray-900">
                        Tour 3 · bénéficiaire : Kouadio N'Guessan
                    </h3>

                    <!-- Warning banner -->
                    <div class="bg-amber-50 border border-amber-200/60 text-amber-900 p-4 rounded-xl text-xs leading-relaxed">
                        6 cotisations sur 8 sont payées. Clôturer maintenant demande un motif, car 2 cotisations ne sont pas soldées.
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-4 pt-1">
                        <button
                            type="button"
                            @click="cloturerTour"
                            class="px-5 py-2.5 border border-gray-800 hover:bg-gray-50 text-gray-900 text-sm font-semibold rounded-lg transition-colors"
                        >
                            Clôturer le tour 3
                        </button>
                        <button
                            type="button"
                            disabled
                            class="px-5 py-2.5 bg-gray-200 text-gray-400 text-sm font-semibold rounded-lg cursor-not-allowed"
                        >
                            Distribuer la cagnotte
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 pt-1">
                        Distribuer n'est possible qu'une fois le tour clôturé et si le bénéficiaire n'est pas bloqué. « Changer de bénéficiaire » n'apparaît que si le bénéficiaire est bloqué.
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
                        <span class="px-3 py-1 rounded-md font-semibold bg-amber-100 text-amber-700">
                            En attente
                        </span>
                        <span class="px-3 py-1 rounded-md font-semibold bg-red-100 text-red-600">
                            En retard
                        </span>
                        <span class="px-3 py-1 rounded-md font-semibold bg-gray-100 text-gray-600">
                            À venir
                        </span>
                        <span class="px-3 py-1 rounded-md font-semibold bg-[#155A3E] text-white">
                            Bénéficiaire
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Paiement (Image 08) -->
        <div
            v-if="showPaymentModal"
            class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50 backdrop-blur-xs"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5 animate-in fade-in duration-150">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Enregistrer un paiement</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ activeModalMember?.nom }} · Tour 3 · échéance le 5 nov. 2026
                    </p>
                </div>

                <!-- Info Badges Attendue & Reste dû -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[#F8F9FA] p-3.5 rounded-xl border border-gray-100">
                        <p class="text-[11px] text-gray-500 font-medium">Attendu</p>
                        <p class="text-base font-bold text-gray-900 mt-1">20 000 FCFA</p>
                    </div>
                    <div class="bg-red-50 p-3.5 rounded-xl border border-red-100">
                        <p class="text-[11px] text-red-600 font-medium">Reste dû</p>
                        <p class="text-base font-bold text-red-700 mt-1">20 000 FCFA</p>
                    </div>
                </div>

                <form @submit.prevent="submitPayment" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1">Montant (FCFA)</label>
                            <input
                                v-model.number="paymentForm.montant"
                                type="number"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1">Date</label>
                            <input
                                v-model="paymentForm.date"
                                type="date"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-500 leading-tight">
                        Un versement supérieur au reste dû est refusé. Le paiement peut se faire en plusieurs fois.
                    </p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1">Mode</label>
                            <select
                                v-model="paymentForm.mode"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:ring-2 focus:ring-[#1B6B4A]"
                            >
                                <option value="Espèces">Espèces</option>
                                <option value="Mobile Money">Mobile Money</option>
                                <option value="Virement">Virement</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-900 mb-1">Référence</label>
                            <input
                                v-model="paymentForm.reference"
                                type="text"
                                placeholder="N° de transaction"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                        </div>
                    </div>

                    <!-- Historique des versements -->
                    <div class="pt-2 border-t border-gray-100">
                        <p class="text-xs font-bold text-gray-900 mb-2">Historique des versements</p>
                        <div class="bg-[#F8F9FA] p-3 rounded-lg flex items-center justify-between text-xs">
                            <span class="line-through text-gray-400">5 nov. · 10 000 FCFA · Espèces</span>
                            <span class="text-red-700 font-semibold">Annulé : erreur de saisie</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-2 leading-tight">
                            Un paiement n'est jamais supprimé : on l'annule avec un motif et la cotisation retrouve le bon statut.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-3">
                        <button
                            type="submit"
                            class="flex-1 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg transition-colors"
                        >
                            Enregistrer le paiement
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
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    cycle: Object,
    membres: Array,
    tours: Array,
    tourOuvert: Object
})

const toursData = [
    { numero: 1, date_court: '5 sept.', statut: 'distribue', statut_label: 'Distribué' },
    { numero: 2, date_court: '5 oct.', statut: 'distribue', statut_label: 'Distribué' },
    { numero: 3, date_court: '5 nov.', statut: 'ouvert', statut_label: 'Ouvert' },
    { numero: 4, date_court: '5 déc.', statut: 'a_venir', statut_label: 'À venir' },
    { numero: 5, date_court: '5 janv.', statut: 'a_venir', statut_label: 'À venir' },
    { numero: 6, date_court: '5 févr.', statut: 'a_venir', statut_label: 'À venir' },
    { numero: 7, date_court: '5 mars', statut: 'a_venir', statut_label: 'À venir' },
    { numero: 8, date_court: '5 avr.', statut: 'a_venir', statut_label: 'À venir' },
]

const matrixMembers = [
    { id: 1, rang: 1, nom: 'Koffi Yao' },
    { id: 2, rang: 2, nom: 'Aya Traoré' },
    { id: 3, rang: 3, nom: "Kouadio N'Guessan" },
    { id: 4, rang: 4, nom: 'Fatou Diabaté' },
    { id: 5, rang: 5, nom: 'Séraphin Kouamé' },
    { id: 6, rang: 6, nom: 'Mariam Coulibaly' },
    { id: 7, rang: 7, nom: 'Jean-Marc Ehui' },
    { id: 8, rang: 8, nom: 'Adjoua Bamba' },
]

// Identifiants des membres en retard sur le tour 3 d'après la maquette (Mariam Coulibaly rang 6 & Jean-Marc Ehui rang 7)
const lateMembersTour3 = [6, 7]

function isCellLate(membre, tour) {
    return tour.numero === 3 && lateMembersTour3.includes(membre.rang)
}

function getCellStatusText(membre, tour) {
    if (tour.numero < 3) return 'Payé'
    if (tour.numero === 3) {
        if (lateMembersTour3.includes(membre.rang)) return 'En retard'
        return 'Payé'
    }
    return 'À venir'
}

function getCellClass(membre, tour) {
    if (tour.numero < 3) {
        return 'bg-[#E2F2E9] text-[#1B6B4A]'
    }
    if (tour.numero === 3) {
        if (lateMembersTour3.includes(membre.rang)) {
            return 'bg-red-50 text-red-700 border border-red-200 cursor-pointer hover:bg-red-100 shadow-2xs'
        }
        return 'bg-[#E2F2E9] text-[#1B6B4A]'
    }
    return 'bg-[#F0F0EC] text-gray-600'
}

// Payment modal state
const showPaymentModal = ref(false)
const activeModalMember = ref(null)
const paymentForm = ref({
    montant: 10000,
    date: '2026-11-08',
    mode: 'Espèces',
    reference: ''
})

function handleCellClick(membre, tour) {
    if (isCellLate(membre, tour)) {
        activeModalMember.value = membre
        showPaymentModal.value = true
    }
}

function submitPayment() {
    alert(`Paiement de ${paymentForm.value.montant} FCFA enregistré avec succès pour ${activeModalMember.value?.nom} !`)
    showPaymentModal.value = false
}

function cloturerTour() {
    alert('Clôture du tour 3 validée.')
}
</script>
