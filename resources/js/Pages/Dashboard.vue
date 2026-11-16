<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Tableau de bord</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ cycle?.libelle || 'Tontine Famille 2026' }} · Tour {{ cycle?.tour_actuel || 3 }} sur {{ cycle?.total_tours || 8 }} · échéance le {{ formatDate(cycle?.date_echeance) || '5 nov. 2026' }}
                    </p>
                </div>
                <div>
                    <Link
                        v-if="cycle?.id"
                        :href="`/cycles/${cycle.id}/tableau`"
                        class="inline-flex items-center px-4 py-2 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
                    >
                        Ouvrir le tableau du cycle
                    </Link>
                </div>
            </div>

            <!-- Top Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Encaissé sur ce tour -->
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Encaissé sur ce tour</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ formatMoney(cycle?.encaisse ?? 120000) }} FCFA
                    </p>
                    <div class="w-full bg-gray-200 h-1.5 rounded-full mt-3 overflow-hidden">
                        <div
                            class="bg-[#1B6B4A] h-1.5 rounded-full"
                            :style="{ width: getProgressPercentage() + '%' }"
                        ></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        sur {{ formatMoney(cycle?.total_attendu ?? 160000) }} attendus · {{ cycle?.membres_payes ?? 6 }} membres sur {{ cycle?.total_membres ?? 8 }}
                    </p>
                </div>

                <!-- Cotisations en retard -->
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Cotisations en retard</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ cycle?.cotisations_en_retard ?? 2 }}
                    </p>
                    <div class="h-1.5 mt-3"></div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ formatMoney(cycle?.montant_retard ?? 40000) }} FCFA non réglés
                    </p>
                </div>

                <!-- Pénalités dues -->
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Pénalités dues</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ formatMoney(cycle?.montant_penalite ?? 4000) }} FCFA
                    </p>
                    <div class="h-1.5 mt-3"></div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ cycle?.penalites_dues ?? 2 }} pénalités de 2 000 FCFA
                    </p>
                </div>

                <!-- Cagnotte prévue -->
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Cagnotte prévue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ formatMoney(cycle?.cagnotte ?? 155000) }} FCFA
                    </p>
                    <div class="h-1.5 mt-3"></div>
                    <p class="text-xs text-gray-500 mt-2">
                        160 000 moins {{ formatMoney(cycle?.frais_gestion ?? 5000) }} de frais
                    </p>
                </div>
            </div>

            <!-- Lower Section: Bénéficiaire card & Retards à relancer -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <!-- Bénéficiaire du tour -->
                <div class="md:col-span-5 bg-[#1B6B4A] text-white p-6 rounded-2xl flex flex-col justify-between min-h-[220px]">
                    <div>
                        <p class="text-xs font-medium text-emerald-100 uppercase tracking-wider">
                            Bénéficiaire du tour {{ cycle?.tour_actuel || 3 }}
                        </p>
                        <h2 class="text-2xl font-bold mt-2">
                            {{ cycle?.beneficiaire?.nom || "Kouadio N'Guessan" }}
                        </h2>
                        <p class="text-sm text-emerald-100 mt-1">
                            Rang {{ cycle?.beneficiaire?.rang || 3 }} · reçoit la cagnotte après clôture du tour
                        </p>
                    </div>

                    <p class="text-xs text-emerald-200 mt-6 leading-relaxed">
                        Distribution possible quand le tour est clôturé et que le bénéficiaire n'est pas bloqué.
                    </p>
                </div>

                <!-- Retards à relancer -->
                <div class="md:col-span-7 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Retards à relancer</h3>

                    <div class="overflow-x-auto">
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
                                    v-for="(item, index) in (cycle?.retards?.length ? cycle.retards : defaultRetards)"
                                    :key="index"
                                    class="text-gray-800"
                                >
                                    <td class="py-3 font-semibold text-gray-900">{{ item.membre }}</td>
                                    <td class="py-3 text-gray-600">{{ item.telephone }}</td>
                                    <td class="py-3 font-medium">{{ formatMoney(item.reste_du) }} FCFA</td>
                                    <td class="py-3">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                            {{ formatMoney(item.penalite) }} due
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    cycle: Object
})

const defaultRetards = [
    {
        membre: 'Jean-Marc Ehui',
        telephone: '07 00 45 12 08',
        reste_du: 20000,
        penalite: 2000,
    },
    {
        membre: 'Mariam Coulibaly',
        telephone: '05 06 78 90 34',
        reste_du: 20000,
        penalite: 2000,
    }
]

function formatMoney(amount) {
    if (amount === undefined || amount === null) return '0'
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

function formatDate(dateStr) {
    if (!dateStr) return null
    return dateStr
}

function getProgressPercentage() {
    const encaisse = props.cycle?.encaisse ?? 120000
    const total = props.cycle?.total_attendu ?? 160000
    if (!total) return 0
    return Math.min(100, Math.round((encaisse / total) * 100))
}
</script>
