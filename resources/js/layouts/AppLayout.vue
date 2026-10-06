<template>
    <div class="flex h-screen text-gray-100" style="background: var(--bg-base)">

        <!-- Sidebar -->
        <aside class="app-drawer w-60 flex-shrink-0 glass-panel border-r flex flex-col relative z-10" :class="{ open: menuOpen }" style="border-color: var(--border-subtle)" aria-label="Menu principal">

            <!-- Logo -->
            <button type="button" @click="menuOpen = false" class="lg:hidden icon-btn absolute top-3 right-3 z-10" aria-label="Fechar menu"><X class="w-4 h-4" /></button>
            <div class="flex items-center justify-center px-4 py-5" style="border-bottom: 1px solid var(--border-subtle)">
                <img
                    v-if="settings.logoUrl"
                    :src="settings.logoUrl"
                    class="h-14 w-auto max-w-[180px] object-contain"
                    alt="Logo"
                />
                <img v-else src="/creatiq-logo.png" alt="CREATIQ" class="h-12 w-auto" />
            </div>

            <!-- Company switcher -->
            <div v-if="companyStore.companies.length" class="px-3 pt-3">
                <div class="relative">
                    <button @click="showCompanyMenu = !showCompanyMenu" type="button" class="nav-item w-full flex items-center justify-between">
                        <span class="flex items-center gap-2 min-w-0">
                            <Building2 class="w-4 h-4 flex-shrink-0" />
                            <span class="truncate">{{ companyStore.current?.name || '(sem nome)' }}</span>
                        </span>
                        <ChevronsUpDown v-if="companyStore.hasMultiple" class="w-3.5 h-3.5 flex-shrink-0 text-gray-500" />
                    </button>
                    <div
                        v-if="showCompanyMenu && companyStore.hasMultiple"
                        class="absolute left-0 right-0 mt-1 rounded-lg overflow-hidden z-20 py-1"
                        style="background: #14121d; border: 1px solid var(--border-subtle)"
                    >
                        <button
                            v-for="company in companyStore.companies"
                            :key="company.id"
                            @click="switchCompany(company.id)"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-white/5 transition-colors flex items-center justify-between gap-2"
                            :class="company.id === companyStore.currentCompanyId ? 'text-white' : 'text-gray-400'"
                        >
                            <span class="truncate">{{ company.name || '(sem nome)' }}</span>
                            <Check v-if="company.id === companyStore.currentCompanyId" class="w-3.5 h-3.5 flex-shrink-0" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 overflow-y-auto">

                <p class="nav-label">Workspace</p>
                <RouterLink
                    v-for="item in visibleMainNav"
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
                    v-for="item in visibleAccountNav"
                    :key="item.path"
                    :to="item.path"
                    class="nav-item"
                    :class="{ active: isActive(item.path) }"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    {{ item.label }}
                </RouterLink>

            </nav>

            <!-- Footer: status + painel admin + logout -->
            <div class="px-3 pb-4" style="border-top: 1px solid var(--border-subtle)">
                <!-- system status -->
                <div class="flex items-center gap-2 px-3 pt-3 pb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0" style="box-shadow: 0 0 5px #34d399"></span>
                    <span class="tech-label">Sistema operacional</span>
                </div>
                <RouterLink v-if="auth.isPlatformAdmin && auth.isClient" to="/admin/clients" class="nav-item">
                    <ShieldCheck class="w-4 h-4 flex-shrink-0" />
                    Painel Admin
                </RouterLink>
                <button
                    @click="handleLogout"
                    class="nav-item w-full text-left"
                >
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
                <img v-if="settings.logoUrl" :src="settings.logoUrl" alt="Logo" class="h-8 w-auto max-w-[140px] object-contain" />
                <img v-else src="/creatiq-logo.png" alt="CREATIQ" class="h-7 w-auto" />
                <span v-if="companyStore.current?.name" class="ml-auto text-xs truncate max-w-[40%]" style="color: var(--text-muted)">{{ companyStore.current.name }}</span>
            </header>
            <main class="flex-1 overflow-y-auto flex flex-col">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed, watch, onUnmounted } from 'vue'
import { useRouter, useRoute, RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCompanyStore } from '@/stores/company'
import { useSettingsStore } from '@/stores/settings'
import { LayoutDashboard, Package, Megaphone, LogOut, UserCircle, Sparkles, History, CheckSquare, Settings, Wand2, Images, Users, KanbanSquare, ShieldCheck, Building2, ChevronsUpDown, Check, Menu, X } from 'lucide-vue-next'

const router       = useRouter()
const route        = useRoute()
const auth         = useAuthStore()
const companyStore = useCompanyStore()
const settings     = useSettingsStore()

const showCompanyMenu = ref(false)

function switchCompany(companyId) {
    showCompanyMenu.value = false
    if (companyId === companyStore.currentCompanyId) return
    companyStore.select(companyId)
    // Force a full reload so every page re-fetches under the newly selected company.
    window.location.href = '/dashboard'
}

const mainNav = [
    { path: '/dashboard', label: 'Dashboard',       icon: LayoutDashboard, menu: 'dashboard' },
    { path: '/generate',         label: 'Motor de Criação', icon: Sparkles, menu: 'generate' },
    { path: '/generate-prompts', label: 'Gerador de Prompts', icon: Wand2, menu: 'generate_prompts' },
    { path: '/history',   label: 'Histórico',        icon: History, menu: 'history' },
    { path: '/products',  label: 'Produtos',          icon: Package, menu: 'products' },
    { path: '/gallery',   label: 'Galeria',           icon: Images, menu: 'gallery' },
    { path: '/leads',     label: 'Leads',             icon: Users, menu: 'leads' },
    { path: '/pipeline',  label: 'Pipeline',          icon: KanbanSquare, menu: 'pipeline' },
    { path: '/campaigns', label: 'Campanhas',         icon: Megaphone, menu: 'campaigns' },
    { path: '/tasks',     label: 'Tarefas',           icon: CheckSquare, menu: 'tasks' },
]

const accountNav = [
    { path: '/settings',  label: 'Configurações',    icon: Settings, menu: 'settings' },
    { path: '/profile',   label: 'Meu Usuário',      icon: UserCircle, menu: null },
]

const visibleMainNav    = computed(() => mainNav.filter(item => auth.can(item.menu, 'view')))
const visibleAccountNav = computed(() => accountNav.filter(item => !item.menu || auth.can(item.menu, 'view')))

// Mobile drawer: closes on navigation and on Esc.
const menuOpen = ref(false)
watch(() => route.fullPath, () => { menuOpen.value = false })
const onKey = (e) => { if (e.key === 'Escape') menuOpen.value = false }
onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))

const isActive = (path) => route.path === path || (path !== '/' && route.path.startsWith(path + '/'))

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}

onMounted(() => settings.load())
</script>
