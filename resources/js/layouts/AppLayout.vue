<template>
    <div class="flex h-screen text-gray-100" style="background: var(--bg-base)">

        <!-- Sidebar -->
        <!-- z-20: o botão de recolher sobressai pra área do conteúdo, que tem fundo próprio
             em algumas páginas e cobriria metade dele. No mobile o .app-drawer força z-40. -->
        <aside
            class="app-drawer w-60 flex-shrink-0 glass-panel border-r flex flex-col relative z-20"
            :class="[{ open: menuOpen }, { 'is-collapsed': collapsed }]"
            style="border-color: var(--border-subtle)"
            aria-label="Menu principal"
        >

            <!-- Fecha o drawer; só existe no mobile. Usa classe própria em vez de lg:hidden
                 porque .icon-btn vem depois do Tailwind no app.css e vencia o utilitário
                 por ordem, deixando o X visível no desktop sem fazer nada. -->
            <button type="button" @click="menuOpen = false" class="drawer-close icon-btn absolute top-3 right-3 z-10" aria-label="Fechar menu"><X class="w-4 h-4" /></button>

            <!-- Recolhe/expande o menu no desktop, em cima da borda direita -->
            <button
                type="button"
                @click="toggleCollapsed"
                class="sidebar-toggle"
                :title="collapsed ? 'Expandir menu' : 'Recolher menu'"
                :aria-label="collapsed ? 'Expandir menu' : 'Recolher menu'"
                :aria-expanded="!collapsed"
            >
                <ChevronLeft class="w-3.5 h-3.5" />
            </button>

            <!-- Logo -->
            <div class="flex items-center justify-center px-4 py-5" style="border-bottom: 1px solid var(--border-subtle)">
                <img
                    v-if="settings.logoUrl"
                    :src="settings.logoUrl"
                    class="brand-logo h-14 w-auto max-w-[180px] object-contain"
                    alt="Logo"
                />
                <img v-else src="/creatiq-logo.png" alt="CREATIQ" class="brand-logo h-12 w-auto" />
            </div>

            <!-- Company switcher -->
            <div v-if="companyStore.companies.length" class="px-3 pt-3">
                <div class="relative">
                    <button @click="showCompanyMenu = !showCompanyMenu" type="button" class="nav-item w-full flex items-center justify-between">
                        <span class="flex items-center gap-2 min-w-0">
                            <Building2 class="w-4 h-4 flex-shrink-0" />
                            <span class="nav-text truncate">{{ companyStore.current?.name || '(sem nome)' }}</span>
                        </span>
                        <ChevronsUpDown v-if="companyStore.hasMultiple" class="nav-text w-3.5 h-3.5 flex-shrink-0 text-gray-500" />
                    </button>
                    <div
                        v-if="showCompanyMenu && companyStore.hasMultiple"
                        class="absolute left-0 right-0 mt-1 rounded-lg overflow-hidden z-20 py-1 min-w-[200px]"
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
                    :title="collapsed ? item.label : null"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    <span class="nav-text">{{ item.label }}</span>
                </RouterLink>

                <p class="nav-label">Conta</p>
                <RouterLink
                    v-for="item in visibleAccountNav"
                    :key="item.path"
                    :to="item.path"
                    class="nav-item"
                    :class="{ active: isActive(item.path) }"
                    :title="collapsed ? item.label : null"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    <span class="nav-text">{{ item.label }}</span>
                </RouterLink>

            </nav>

            <!-- Footer: status + painel admin + logout -->
            <div class="px-3 pb-4" style="border-top: 1px solid var(--border-subtle)">
                <!-- system status -->
                <div class="nav-status flex items-center gap-2 px-3 pt-3 pb-2" :title="collapsed ? 'Sistema operacional' : null">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0" style="box-shadow: 0 0 5px #34d399"></span>
                    <span class="nav-text tech-label">Sistema operacional</span>
                </div>
                <RouterLink v-if="auth.isPlatformAdmin && auth.isClient" to="/admin/clients" class="nav-item">
                    <ShieldCheck class="w-4 h-4 flex-shrink-0" />
                    <span class="nav-text">Painel Admin</span>
                </RouterLink>
                <button
                    @click="handleLogout"
                    class="nav-item w-full text-left"
                >
                    <LogOut class="w-4 h-4 flex-shrink-0" />
                    <span class="nav-text">Sair</span>
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
import { LayoutDashboard, Package, Megaphone, LogOut, UserCircle, Sparkles, History, CheckSquare, Settings, Wand2, Images, Users, KanbanSquare, ShieldCheck, Building2, ChevronsUpDown, Check, Menu, X, ChevronLeft } from 'lucide-vue-next'

const router       = useRouter()
const route        = useRoute()
const auth         = useAuthStore()
const companyStore = useCompanyStore()
const settings     = useSettingsStore()

const showCompanyMenu = ref(false)

// Menu recolhido (desktop): a escolha é lembrada entre páginas e recarregamentos.
const COLLAPSED_KEY = 'creatiq_sidebar_collapsed'

const collapsed = ref(readCollapsed())

function readCollapsed() {
    try { return localStorage.getItem(COLLAPSED_KEY) === '1' } catch { return false }
}

function toggleCollapsed() {
    collapsed.value = !collapsed.value
    showCompanyMenu.value = false
    try { localStorage.setItem(COLLAPSED_KEY, collapsed.value ? '1' : '0') } catch { /* modo privado: segue sem lembrar */ }
}

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

<style scoped>
/*
 * O "X" fecha o drawer e só faz sentido no mobile. Precisa de especificidade maior que
 * .icon-btn (que no app.css vem depois do Tailwind e vencia o lg:hidden por ordem).
 */
@media (min-width: 1024px) {
    .drawer-close { display: none; }
}

/* Recolher é comportamento de desktop: no mobile o drawer abre e fecha inteiro. */
.sidebar-toggle { display: none; }

@media (min-width: 1024px) {
    .sidebar-toggle {
        position: absolute;
        top: 44px;
        right: -12px;
        z-index: 30;
        width: 24px;
        height: 24px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        background: #14121d;
        border: 1px solid var(--border-subtle);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.45);
        transition: color 0.16s, background 0.16s, transform 0.12s;
    }
    .sidebar-toggle:hover {
        color: #fff;
        background: color-mix(in srgb, var(--brand) 22%, #14121d);
        transform: scale(1.08);
    }
    .sidebar-toggle svg { transition: transform 0.2s ease; }
    .is-collapsed .sidebar-toggle svg { transform: rotate(180deg); }

    .app-drawer { transition: width 0.2s ease-out; }

    /* Recolhido: ficam só os ícones. */
    .is-collapsed { width: 68px; }
    .is-collapsed .nav-text,
    .is-collapsed .nav-label { display: none; }
    .is-collapsed .nav-item,
    .is-collapsed .nav-status { justify-content: center; padding-left: 0; padding-right: 0; }
    .is-collapsed .brand-logo { height: 2.25rem; width: auto; }
}

@media (prefers-reduced-motion: reduce) {
    .app-drawer { transition: none; }
    .sidebar-toggle svg { transition: none; }
}
</style>
