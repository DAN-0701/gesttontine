<template>
    <AppLayout>
        <div class="max-w-md mx-auto my-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Enregistrer un paiement</h1>
                <p class="text-xs text-gray-500 mt-1">
                    {{ cotisation?.membre || 'Jean-Marc Ehui' }} · Tour {{ cotisation?.tour_numero || 3 }} · échéance le {{ formatDate(cotisation?.date_echeance) || '5 nov. 2026' }}
                </p>
            </div>

            <!-- Attendu & Reste dû -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-[#F8F9FA] p-3.5 rounded-xl border border-gray-100">
                    <p class="text-[11px] text-gray-500 font-medium">Attendu</p>
                    <p class="text-base font-bold text-gray-900 mt-1">
                        {{ formatMoney(cotisation?.montant_attendu || 20000) }} FCFA
                    </p>
                </div>
                <div class="bg-red-50 p-3.5 rounded-xl border border-red-100">
                    <p class="text-[11px] text-red-600 font-medium">Reste dû</p>
                    <p class="text-base font-bold text-red-700 mt-1">
                        {{ formatMoney(cotisation?.reste_du || 20000) }} FCFA
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-900 mb-1">Montant (FCFA)</label>
                        <input
                            v-model.number="form.montant"
                            type="number"
                            placeholder="10000"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        />
                        <p v-if="form.errors.montant" class="text-xs text-red-600 mt-1">{{ form.errors.montant }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-900 mb-1">Date</label>
                        <input
                            v-model="form.date"
                            type="date"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
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
                            v-model="form.mode"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                        >
                            <option value="Espèces">Espèces</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Virement">Virement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-900 mb-1">Référence</label>
                        <input
                            v-model="form.reference"
                            type="text"
                            placeholder="N° de transaction"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
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
                        :disabled="form.processing"
                        class="flex-1 py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                    >
                        Enregistrer le paiement
                    </button>
                    <button
                        type="button"
                        @click="goBack"
                        class="px-5 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    cotisation: Object
})

const form = useForm({
    montant: 10000,
    date: '2026-11-08',
    mode: 'Espèces',
    reference: '',
})

function submit() {
    if (props.cotisation?.id) {
        form.post(`/cotisations/${props.cotisation.id}/paiement`)
    } else {
        alert('Paiement enregistré !')
    }
}

function goBack() {
    window.history.back()
}

function formatMoney(amount) {
    if (amount === undefined || amount === null) return '0'
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function formatDate(dateStr) {
    if (!dateStr) return null
    return dateStr
}
</script>
