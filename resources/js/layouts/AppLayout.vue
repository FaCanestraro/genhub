<template>
    <div class="flex h-screen text-gray-100" style="background: var(--bg-base)">

        <!-- Sidebar -->
        <aside class="w-60 flex-shrink-0 glass-panel border-r flex flex-col relative z-10" style="border-color: var(--border-subtle)">

            <!-- Logo -->
            <div class="flex items-center justify-center px-4 py-5" style="border-bottom: 1px solid var(--border-subtle)">
                <img
                    v-if="settings.logoUrl"
                    :src="settings.logoUrl"
                    class="h-14 w-auto max-w-[180px] object-contain"
                    alt="Logo"
                />
                <template v-else>
                    <div
                        class="brand-orb w-8 h-8 flex items-center justify-center flex-shrink-0"
                        :style="{ background: `radial-gradient(circle, color-mix(in srgb, ${settings.primaryColor || 'var(--brand)'} 70%, white) 0%, ${settings.primaryColor || 'var(--brand)'} 60%, transparent 100%)`, boxShadow: `0 0 22px color-mix(in srgb, ${settings.primaryColor || 'var(--brand)'} 55%, transparent)` }"
                    >
                        <Zap class="w-4 h-4 text-white" />
                    </div>
                    <div>
                        <p class="text-white font-semibold text-sm tracking-wide leading-none">GenHub</p>
                        <p class="tech-label mt-0.5">AI Platform</p>
                    </div>
                </template>
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

            <!-- Footer: status + logout -->
            <div class="px-3 pb-4" style="border-top: 1px solid var(--border-subtle)">
                <!-- system status -->
                <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0" style="box-shadow: 0 0 5px #34d399"></span>
                    <span class="tech-label">Sistema operacional</span>
                </div>
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
import { LayoutDashboard, Package, Megaphone, LogOut, Zap, UserCircle, Sparkles, History, CheckSquare, Settings, Wand2, LayoutTemplate, Images } from 'lucide-vue-next'

const router   = useRouter()
const route    = useRoute()
const auth     = useAuthStore()
const settings = useSettingsStore()

const mainNav = [
    { path: '/dashboard', label: 'Dashboard',       icon: LayoutDashboard },
    { path: '/generate',         label: 'Motor de Criação', icon: Sparkles },
    { path: '/generate-prompts', label: 'Gerador de Prompts', icon: Wand2 },
    { path: '/history',   label: 'Histórico',        icon: History },
    { path: '/products',  label: 'Produtos',          icon: Package },
    { path: '/templates', label: 'Modelos de Arte',   icon: LayoutTemplate },
    { path: '/gallery',   label: 'Galeria',           icon: Images },
    { path: '/campaigns', label: 'Campanhas',         icon: Megaphone },
    { path: '/tasks',     label: 'Tarefas',           icon: CheckSquare },
]

const accountNav = [
    { path: '/settings',  label: 'Configurações',    icon: Settings },
    { path: '/profile',   label: 'Perfil',            icon: UserCircle },
]

const isActive = (path) => route.path === path || (path !== '/' && route.path.startsWith(path + '/'))

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}

onMounted(() => settings.load())
</script>
