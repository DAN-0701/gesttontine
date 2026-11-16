<template>
    <div class="min-h-screen bg-[#F5F5F0] flex">
        <!-- Sidebar -->
        <aside class="w-60 flex flex-col justify-between border-r border-gray-200 bg-[#F5F5F0] min-h-screen fixed">
            <div>
                <div class="px-6 py-6">
                    <Link href="/dashboard" class="text-xl font-bold text-[#1B6B4A]">Tontine</Link>
                </div>
                <nav class="mt-2">
                    <Link
                        v-for="item in menuItems"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'block px-6 py-2.5 text-sm transition-colors',
                            isActive(item.href)
                                ? 'text-[#1B6B4A] font-semibold bg-[#E8F0EC] border-l-4 border-[#1B6B4A]'
                                : 'text-gray-700 hover:text-[#1B6B4A] hover:bg-gray-100'
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
            <div class="px-6 py-4 border-t border-gray-200" v-if="$page.props.auth?.user">
                <p class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user.name }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ $page.props.auth.user.role }}</p>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 ml-60 p-8">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

const menuItems = [
    { href: '/dashboard', label: 'Tableau de bord' },
    { href: '/membres', label: 'Membres' },
    { href: '/cycles', label: 'Cycles' },
    { href: '/utilisateurs', label: 'Utilisateurs' },
]

function isActive(href) {
    const url = page.url
    if (href === '/dashboard') return url === '/dashboard'
    return url.startsWith(href)
}
</script>
