<template>
    <div class="flex h-screen text-gray-100" style="background: var(--bg-base)">

        <!-- Sidebar -->
        <aside class="app-drawer w-60 flex-shrink-0 glass-panel border-r flex flex-col relative z-10" :class="{ open: menuOpen }" style="border-color: var(--border-subtle)" aria-label="Menu principal">

            <!-- Logo -->
            <button type="button" @click="menuOpen = false" class="lg:hidden icon-btn absolute top-3 right-3 z-10" aria-label="Fechar menu"><X class="w-4 h-4" /></button>
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

        <!-- Mobile backdrop -->
        <div v-if="menuOpen" class="lg:hidden fixed inset-0 z-30 bg-black/60 backdrop-blur-sm" @click="menuOpen = false" aria-hidden="true"></div>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0 relative z-10">
            <!-- Mobile top bar -->
            <header class="lg:hidden flex items-center gap-3 h-14 px-3 flex-shrink-0" style="border-bottom: 1px solid var(--border-subtle); background: rgba(7, 6, 13, 0.85); backdrop-filter: blur(12px)">
                <button type="button" @click="menuOpen = true" class="icon-btn" aria-label="Abrir menu" :aria-expanded="menuOpen"><Menu class="w-5 h-5" /></button>
                <img src="/creatiq-logo.png" alt="CREATIQ" class="h-7 w-auto" />
                <span class="tech-label ml-auto">Admin</span>
            </header>
            <main class="flex-1 overflow-y-auto flex flex-col">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute, RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Users2, LayoutTemplate, LogOut, ArrowLeftRight, UserCog, Menu, X } from 'lucide-vue-next'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()

const nav = [
    { path: '/admin/clients',   label: 'Clientes',       icon: Users2 },
    { path: '/admin/templates', label: 'Modelos de Arte', icon: LayoutTemplate },
    { path: '/admin/users',     label: 'Usuários Admin',  icon: UserCog },
]

// Mobile drawer: closes on navigation and on Esc.
const menuOpen = ref(false)
watch(() => route.fullPath, () => { menuOpen.value = false })
const onKey = (e) => { if (e.key === 'Escape') menuOpen.value = false }
onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))

const isActive = (path) => route.path === path || route.path.startsWith(path + '/')

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}
</script>
