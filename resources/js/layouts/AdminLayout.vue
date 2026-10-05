<template>
    <div class="flex h-screen text-gray-100" style="background: var(--bg-base)">

        <!-- Sidebar -->
        <aside class="w-60 flex-shrink-0 glass-panel border-r flex flex-col relative z-10" style="border-color: var(--border-subtle)">

            <!-- Logo -->
            <div class="flex items-center justify-center gap-2.5 px-4 py-5" style="border-bottom: 1px solid var(--border-subtle)">
                <img src="/creatiq-logo.png" alt="CREATIQ" class="h-10 w-auto" />
                <p class="tech-label">Admin</p>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 overflow-y-auto">
                <p class="nav-label">Plataforma</p>
                <RouterLink
                    v-for="item in nav"
                    :key="item.path"
                    :to="item.path"
                    class="nav-item"
                    :class="{ active: isActive(item.path) }"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    {{ item.label }}
                </RouterLink>
            </nav>

            <!-- Footer: voltar ao sistema + logout -->
            <div class="px-3 pb-4" style="border-top: 1px solid var(--border-subtle)">
                <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0" style="box-shadow: 0 0 5px #34d399"></span>
                    <span class="tech-label">Admin: {{ auth.user?.name }}</span>
                </div>
                <RouterLink v-if="auth.isClient" to="/dashboard" class="nav-item">
                    <ArrowLeftRight class="w-4 h-4 flex-shrink-0" />
                    Ir para o Sistema
                </RouterLink>
                <button @click="handleLogout" class="nav-item w-full text-left">
                    <LogOut class="w-4 h-4 flex-shrink-0" />
                    Sair
                </button>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-1 overflow-y-auto flex flex-col relative z-10">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { useRouter, useRoute, RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Users2, LayoutTemplate, LogOut, ArrowLeftRight, UserCog } from 'lucide-vue-next'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()

const nav = [
    { path: '/admin/clients',   label: 'Clientes',       icon: Users2 },
    { path: '/admin/templates', label: 'Modelos de Arte', icon: LayoutTemplate },
    { path: '/admin/users',     label: 'Usuários Admin',  icon: UserCog },
]

const isActive = (path) => route.path === path || route.path.startsWith(path + '/')

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}
</script>
