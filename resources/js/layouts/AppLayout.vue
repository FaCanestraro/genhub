<template>
    <div class="flex h-screen text-gray-100" style="background: var(--bg-base)">

        <!-- Sidebar -->
        <aside class="w-60 flex-shrink-0 glass-panel border-r flex flex-col relative z-10" style="border-color: var(--border-subtle)">

            <!-- Logo -->
            <div class="px-5 py-5 flex items-center gap-3" style="border-bottom: 1px solid var(--border-subtle)">
                <div
                    class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden shadow-lg"
                    :style="{ backgroundColor: settings.primaryColor || 'var(--brand)', boxShadow: `0 0 16px color-mix(in srgb, ${settings.primaryColor || 'var(--brand)'} 45%, transparent)` }"
                >
                    <img v-if="settings.logoUrl" :src="settings.logoUrl" class="w-full h-full object-contain" alt="Logo" />
                    <Zap v-else class="w-4 h-4 text-white" />
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-white leading-tight tracking-tight truncate">
                        {{ settings.nomeEmpresa || 'GenHub' }}
                    </p>
                    <p class="text-xs truncate" style="color: var(--text-muted)">{{ auth.user?.company_name || auth.user?.name }}</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 overflow-y-auto">

                <p class="nav-label">Workspace</p>
                <RouterLink
                    v-for="item in mainNav"
                    :key="item.path"
                    :to="item.path"
                    class="nav-item"
                    :class="{ active: isActive(item.path) }"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    {{ item.label }}
                </RouterLink>

                <p class="nav-label">Conta</p>
                <RouterLink
                    v-for="item in accountNav"
                    :key="item.path"
                    :to="item.path"
                    class="nav-item"
                    :class="{ active: isActive(item.path) }"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    {{ item.label }}
                </RouterLink>

            </nav>

            <!-- Logout -->
            <div class="px-3 py-4" style="border-top: 1px solid var(--border-subtle)">
                <button
                    @click="handleLogout"
                    class="nav-item w-full text-left"
                >
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
import { onMounted } from 'vue'
import { useRouter, useRoute, RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { LayoutDashboard, Package, Megaphone, LogOut, Zap, UserCircle, Sparkles, History, CheckSquare, Settings } from 'lucide-vue-next'

const router   = useRouter()
const route    = useRoute()
const auth     = useAuthStore()
const settings = useSettingsStore()

const mainNav = [
    { path: '/dashboard', label: 'Dashboard',       icon: LayoutDashboard },
    { path: '/generate',  label: 'Motor de Criação', icon: Sparkles },
    { path: '/history',   label: 'Histórico',        icon: History },
    { path: '/products',  label: 'Produtos',          icon: Package },
    { path: '/campaigns', label: 'Campanhas',         icon: Megaphone },
    { path: '/tasks',     label: 'Tarefas',           icon: CheckSquare },
]

const accountNav = [
    { path: '/settings',  label: 'Configurações',    icon: Settings },
    { path: '/profile',   label: 'Perfil',            icon: UserCircle },
]

const isActive = (path) => route.path.startsWith(path)

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}

onMounted(() => settings.load())
</script>
