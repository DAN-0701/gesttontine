<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Utilisateurs</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Les comptes qui peuvent se connecter. Réservé au gestionnaire.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                <!-- Colonne Gauche: Liste des utilisateurs -->
                <div class="md:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-gray-400 text-xs border-b border-gray-100">
                                    <th class="pb-3 font-semibold">Nom</th>
                                    <th class="pb-3 font-semibold">Email</th>
                                    <th class="pb-3 font-semibold">Rôle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="u in displayedUsers"
                                    :key="u.id"
                                    class="hover:bg-gray-50/50 transition-colors"
                                >
                                    <td class="py-3.5 font-semibold text-gray-900">{{ u.name }}</td>
                                    <td class="py-3.5 text-gray-600">{{ u.email }}</td>
                                    <td class="py-3.5">
                                        <span
                                            :class="[
                                                'inline-block px-3 py-0.5 rounded-full text-xs font-semibold',
                                                u.role === 'gestionnaire'
                                                    ? 'bg-[#155A3E] text-white'
                                                    : 'bg-blue-100 text-blue-700'
                                            ]"
                                        >
                                            {{ u.role === 'gestionnaire' ? 'Gestionnaire' : 'Trésorier' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Colonne Droite: Créer un compte -->
                <div class="md:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                    <h2 class="text-base font-bold text-gray-900">Créer un compte</h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1.5">Nom complet</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1.5">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1.5">Mot de passe provisoire</label>
                            <input
                                v-model="form.password"
                                type="password"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1.5">Rôle</label>
                            <select
                                v-model="form.role"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                            >
                                <option value="tresorier">Trésorier</option>
                                <option value="gestionnaire">Gestionnaire</option>
                            </select>
                            <p v-if="form.errors.role" class="mt-1 text-xs text-red-600">{{ form.errors.role }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-800 mb-1.5">Membre lié (facultatif)</label>
                            <select
                                v-model="form.member_id"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#1B6B4A]"
                            >
                                <option :value="null">Aucun</option>
                                <option v-for="m in membres" :key="m.id" :value="m.id">
                                    {{ m.nom }}
                                </option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 bg-[#1B6B4A] hover:bg-[#155A3E] text-white text-sm font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50 mt-2"
                        >
                            Créer le compte
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    users: Array,
    membres: Array,
})

const defaultUsers = [
    { id: 1, name: 'Aya Konan', email: 'aya.konan@mail.ci', role: 'gestionnaire' },
    { id: 2, name: 'Paul Koné', email: 'paul.kone@mail.ci', role: 'tresorier' },
]

const displayedUsers = computed(() => {
    return (props.users && props.users.length > 0) ? props.users : defaultUsers
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'tresorier',
    member_id: null,
})

function submit() {
    form.post('/utilisateurs', {
        onSuccess: () => form.reset()
    })
}
</script>
