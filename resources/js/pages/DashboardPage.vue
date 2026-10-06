<template>
    <div class="page flex flex-col gap-8">

        <!-- Header -->
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <p class="tech-label">{{ today }}</p>
                <h1 class="page-hero-title text-3xl leading-tight">{{ greeting }}, {{ firstName }}</h1>
                <p class="text-sm" style="color: var(--text-secondary)">
                    Aqui está o que está acontecendo<template v-if="companyName"> na <span class="text-white">{{ companyName }}</span></template>.
                </p>
            </div>
            <RouterLink v-if="can('generate')" to="/generate" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white">
                <Sparkles class="w-4 h-4" /> Criar conteúdo
            </RouterLink>
        </header>

        <!-- Quick actions -->
        <nav v-if="quickActions.length" aria-label="Ações rápidas" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <RouterLink v-for="a in quickActions" :key="a.to" :to="a.to" class="action-card">
                <span class="panel-icon !w-10 !h-10 !rounded-xl"><component :is="a.icon" class="w-[18px] h-[18px]" /></span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-white">{{ a.title }}</span>
                    <span class="block text-xs truncate" style="color: var(--text-muted)">{{ a.text }}</span>
                </span>
            </RouterLink>
        </nav>

        <!-- KPIs -->
        <div v-if="kpis.length" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <StatCard v-for="k in kpis" :key="k.label" v-bind="k" :loading="loading" />
        </div>

        <!-- Main grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Criações recentes -->
            <Panel v-if="can('history')" title="Criações recentes" :icon="Images" to="/history" link-label="Ver histórico" class="lg:col-span-2">
                <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-for="i in 8" :key="i" class="shimmer aspect-square rounded-xl"></div>
                </div>
                <EmptyState
                    v-else-if="!creations.length"
                    :icon="Images"
                    title="Nenhuma criação ainda"
                    text="Gere sua primeira imagem ou vídeo e ela aparece aqui."
                    :cta-to="can('generate') ? '/generate' : null"
                    cta-label="Criar conteúdo"
                />
                <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <RouterLink
                        v-for="c in creations"
                        :key="c.id"
                        to="/history"
                        class="group relative aspect-square rounded-xl overflow-hidden"
                        style="background: var(--surface-2); border: 1px solid var(--border-subtle)"
                    >
                        <video v-if="c.isVideo" :src="`${c.url}#t=0.1`" muted playsinline preload="metadata" class="w-full h-full object-cover"></video>
                        <img v-else :src="c.url" :alt="c.label" loading="lazy" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        <span class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 px-2.5 py-2 text-[11px] text-white bg-gradient-to-t from-black/75 to-transparent">
                            <span class="flex items-center gap-1"><Play v-if="c.isVideo" class="w-3 h-3" />{{ c.label }}</span>
                            <span class="text-white/70">{{ c.when }}</span>
                        </span>
                    </RouterLink>
                </div>
            </Panel>

            <!-- Tarefas -->
            <Panel v-if="can('tasks')" title="Tarefas pendentes" :icon="CheckSquare" to="/tasks">
                <div v-if="loading" class="flex flex-col gap-2">
                    <div v-for="i in 4" :key="i" class="shimmer h-12 rounded-xl"></div>
                </div>
                <EmptyState v-else-if="!openTasks.length" :icon="PartyPopper" title="Nada pendente" text="Todas as tarefas estão em dia." />
                <ul v-else class="flex flex-col gap-1.5">
                    <li v-for="t in openTasks.slice(0, 6)" :key="t.id" class="list-row flex items-center gap-3 px-3 py-2.5 rounded-xl">
                        <button
                            v-if="auth.can('tasks', 'edit')"
                            type="button"
                            @click="completeTask(t)"
                            :aria-label="`Concluir tarefa ${t.titulo}`"
                            class="w-5 h-5 flex-shrink-0 rounded-md border flex items-center justify-center transition-colors hover:border-emerald-400 hover:bg-emerald-400/10 group"
                            style="border-color: var(--border-soft)"
                        >
                            <Check class="w-3 h-3 text-emerald-400 opacity-0 group-hover:opacity-100" />
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-white truncate">{{ t.titulo }}</p>
                            <p v-if="t.lead?.nome" class="text-xs truncate" style="color: var(--text-muted)">{{ t.lead.nome }}</p>
                        </div>
                        <span v-if="t.prazo" class="text-[11px] flex-shrink-0 tabular-nums" :class="isOverdue(t) ? 'text-red-400' : ''" :style="isOverdue(t) ? null : 'color: var(--text-muted)'">
                            {{ dueLabel(t.prazo) }}
                        </span>
                    </li>
                </ul>
            </Panel>

            <!-- Campanhas -->
            <Panel v-if="can('campaigns')" title="Campanhas recentes" :icon="Megaphone" to="/campaigns" link-label="Ver todas" class="lg:col-span-2">
                <div v-if="loading" class="flex flex-col gap-2">
                    <div v-for="i in 4" :key="i" class="shimmer h-14 rounded-xl"></div>
                </div>
                <EmptyState
                    v-else-if="!recentCampaigns.length"
                    :icon="Megaphone"
                    title="Nenhuma campanha ainda"
                    text="Organize suas ações e criativos por campanha."
                    :cta-to="auth.can('campaigns', 'create') ? '/campaigns' : null"
                    cta-label="Nova campanha"
                />
                <ul v-else class="flex flex-col gap-1.5">
                    <li v-for="c in recentCampaigns" :key="c.id">
                        <RouterLink :to="`/campaigns/${c.id}`" class="list-row flex items-center gap-4 px-3.5 py-3 rounded-xl">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-white truncate">{{ c.name }}</p>
                                <p class="text-xs mt-0.5" style="color: var(--text-muted)">{{ c.actions_count || 0 }} {{ c.actions_count === 1 ? 'ação' : 'ações' }}</p>
                            </div>
                            <div v-if="Number(c.budget)" class="hidden sm:flex flex-col items-end gap-1.5 w-36 flex-shrink-0">
                                <span class="text-xs tabular-nums" style="color: var(--text-secondary)">R$ {{ numberToCurrency(c.budget) }}</span>
                                <span class="w-full h-1 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.06)">
                                    <span class="block h-full rounded-full" :style="{ width: budgetPct(c) + '%', background: 'var(--brand)' }"></span>
                                </span>
                            </div>
                            <StatusBadge :status="c.status" class="flex-shrink-0" />
                        </RouterLink>
                    </li>
                </ul>
            </Panel>

            <!-- Orçamento -->
            <Panel v-if="can('campaigns')" title="Orçamento por status" :icon="Wallet">
                <div v-if="loading" class="flex flex-col gap-3">
                    <div class="shimmer h-8 w-32 rounded-md"></div>
                    <div class="shimmer h-3 rounded-full"></div>
                    <div v-for="i in 3" :key="i" class="shimmer h-5 rounded-md"></div>
                </div>
                <EmptyState v-else-if="!budgetByStatus.length" :icon="Wallet" title="Sem orçamento definido" text="Defina um orçamento nas campanhas para acompanhar aqui." />
                <div v-else class="flex flex-col gap-5">
                    <div>
                        <p class="text-xs" style="color: var(--text-muted)">Total em campanhas</p>
                        <p class="page-hero-title text-2xl mt-1">R$ {{ numberToCurrency(totalBudget) }}</p>
                    </div>
                    <div class="flex h-2.5 rounded-full overflow-hidden gap-0.5" role="img" :aria-label="budgetAria">
                        <span v-for="row in budgetByStatus" :key="row.status" :style="{ width: row.pct + '%', background: row.color }"></span>
                    </div>
                    <ul class="flex flex-col gap-2.5">
                        <li v-for="row in budgetByStatus" :key="row.status" class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2" style="color: var(--text-secondary)">
                                <span class="w-2 h-2 rounded-full" :style="{ background: row.color }"></span>
                                {{ row.label }}
                            </span>
                            <span class="tabular-nums text-white">
                                R$ {{ numberToCurrency(row.value) }}
                                <span class="text-xs ml-1" style="color: var(--text-muted)">{{ Math.round(row.pct) }}%</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </Panel>

            <!-- Produtos -->
            <Panel v-if="can('products')" title="Produtos recentes" :icon="Package" to="/products" link-label="Ver todos" class="lg:col-span-3">
                <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div v-for="i in 5" :key="i" class="shimmer h-44 rounded-xl"></div>
                </div>
                <EmptyState
                    v-else-if="!products.length"
                    :icon="Package"
                    title="Nenhum produto cadastrado"
                    text="Cadastre produtos para usar como base nas criações."
                    :cta-to="auth.can('products', 'create') ? '/products' : null"
                    cta-label="Cadastrar produto"
                />
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <RouterLink v-for="p in products" :key="p.id" to="/products" class="list-row group flex flex-col gap-2.5 p-2.5 rounded-xl" style="border: 1px solid var(--border-subtle)">
                        <span class="aspect-[4/3] rounded-lg overflow-hidden flex items-center justify-center" style="background: var(--surface-2)">
                            <img v-if="p.images?.length" :src="p.images[0]" :alt="p.name" loading="lazy" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            <Package v-else class="w-6 h-6" style="color: var(--text-muted)" />
                        </span>
                        <span class="min-w-0 px-0.5">
                            <span class="block text-sm font-medium text-white truncate">{{ p.name }}</span>
                            <span class="flex items-center justify-between gap-2 mt-0.5 text-xs">
                                <span class="truncate" style="color: var(--text-muted)">{{ p.category || 'Sem categoria' }}</span>
                                <span v-if="p.price" class="font-semibold tabular-nums flex-shrink-0" style="color: color-mix(in srgb, var(--brand) 55%, white)">R$ {{ numberToCurrency(p.price) }}</span>
                            </span>
                        </span>
                    </RouterLink>
                </div>
            </Panel>

        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCompanyStore } from '@/stores/company'
import { useToastStore } from '@/stores/toast'
import { Megaphone, Package, Sparkles, Wallet, Images, CheckSquare, Check, Play, Wand2, PartyPopper, AlertTriangle } from 'lucide-vue-next'
import api from '@/services/api'
import StatCard from '@/components/StatCard.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import Panel from '@/components/ui/Panel.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { numberToCurrency } from '@/utils/mask'
import { assetUrl } from '@/utils/assetUrl'

const auth = useAuthStore()
const companyStore = useCompanyStore()
const toast = useToastStore()
const can = (menu) => auth.can(menu, 'view')

const loading = ref(true)
const campaigns = ref([])
const products = ref([])
const generations = ref([])
const generationsTotal = ref(0)
const tasks = ref([])

// ── Header ──────────────────────────────────────────────────────
const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'Usuário')
const companyName = computed(() => companyStore.current?.name)
const today = new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())
const greeting = (() => {
    const h = new Date().getHours()
    return h < 12 ? 'Bom dia' : h < 18 ? 'Boa tarde' : 'Boa noite'
})()

const quickActions = computed(() => [
    { to: '/generate', icon: Sparkles, title: 'Criar conteúdo', text: 'Imagens e vídeos com IA', menu: 'generate' },
    { to: '/generate-prompts', icon: Wand2, title: 'Gerar prompts', text: 'Ideias a partir do produto', menu: 'generate_prompts' },
    { to: '/campaigns', icon: Megaphone, title: 'Campanhas', text: 'Planejar ações e entregas', menu: 'campaigns' },
    { to: '/products', icon: Package, title: 'Produtos', text: 'Catálogo usado nas criações', menu: 'products' },
].filter(a => can(a.menu)))

// ── Tasks ───────────────────────────────────────────────────────
const isOverdue = (t) => t.prazo && new Date(t.prazo) < new Date()
// Overdue first, then by nearest due date; undated last.
const openTasks = computed(() => tasks.value
    .filter(t => !t.concluida)
    .sort((a, b) => (a.prazo ? new Date(a.prazo) : Infinity) - (b.prazo ? new Date(b.prazo) : Infinity)))
const overdueCount = computed(() => openTasks.value.filter(isOverdue).length)

function dueLabel(prazo) {
    const d = new Date(prazo)
    const startOfDay = (x) => new Date(x.getFullYear(), x.getMonth(), x.getDate())
    const days = Math.round((startOfDay(d) - startOfDay(new Date())) / 86400000)
    if (days === 0) return `hoje ${d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`
    if (days === 1) return 'amanhã'
    if (days === -1) return 'ontem'
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

async function completeTask(task) {
    await api.patch(`/tasks/${task.id}/toggle`)
    task.concluida = true
    toast.push('Tarefa concluída.', 'success')
}

// ── Creations ───────────────────────────────────────────────────
const TYPE_LABELS = { image: 'Imagem', video: 'Vídeo', carousel: 'Carrossel', text: 'Texto' }
const creations = computed(() => generations.value
    .filter(g => g.assets?.length)
    .slice(0, 8)
    .map(g => {
        const asset = g.assets[0]
        return {
            id: g.id,
            url: asset.url ?? assetUrl(asset.path),
            isVideo: asset.type === 'video',
            label: TYPE_LABELS[g.type] || g.type,
            when: new Date(g.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }),
        }
    }))

// ── Campaigns & budget ──────────────────────────────────────────
const recentCampaigns = computed(() => campaigns.value.slice(0, 5))
const totalBudget = computed(() => campaigns.value.reduce((s, c) => s + Number(c.budget || 0), 0))
const maxBudget = computed(() => Math.max(0, ...campaigns.value.map(c => Number(c.budget || 0))))
const budgetPct = (c) => maxBudget.value ? (Number(c.budget) / maxBudget.value) * 100 : 0

const STATUS_LABELS = { draft: 'Rascunho', active: 'Ativa', paused: 'Pausada', finished: 'Concluída', archived: 'Arquivada' }
const STATUS_COLORS = { draft: '#6b7280', active: '#4ade80', paused: '#facc15', finished: '#60a5fa', archived: '#4b5563' }

const budgetByStatus = computed(() => {
    const totals = {}
    for (const c of campaigns.value) {
        if (Number(c.budget)) totals[c.status] = (totals[c.status] || 0) + Number(c.budget)
    }
    return Object.entries(totals)
        .sort((a, b) => b[1] - a[1])
        .map(([status, value]) => ({
            status,
            value,
            label: STATUS_LABELS[status] || status,
            color: STATUS_COLORS[status] || '#6b7280',
            pct: totalBudget.value ? (value / totalBudget.value) * 100 : 0,
        }))
})
const budgetAria = computed(() => budgetByStatus.value.map(r => `${r.label}: ${Math.round(r.pct)}%`).join(', '))

// ── KPIs ────────────────────────────────────────────────────────
const kpis = computed(() => {
    const list = []
    if (can('campaigns')) {
        const active = campaigns.value.filter(c => c.status === 'active').length
        list.push({ label: 'Campanhas ativas', value: active, icon: Megaphone, to: '/campaigns', hint: `${campaigns.value.length} no total` })
    }
    if (can('history')) {
        list.push({ label: 'Criações', value: generationsTotal.value, icon: Images, to: '/history', hint: 'imagens, vídeos e textos' })
    }
    if (can('tasks')) {
        list.push({
            label: 'Tarefas abertas',
            value: openTasks.value.length,
            icon: overdueCount.value ? AlertTriangle : CheckSquare,
            to: '/tasks',
            hint: overdueCount.value ? `${overdueCount.value} atrasada${overdueCount.value > 1 ? 's' : ''}` : 'nenhuma atrasada',
            warning: overdueCount.value > 0,
        })
    }
    if (can('campaigns')) {
        list.push({ label: 'Orçamento', value: `R$ ${numberToCurrency(totalBudget.value)}`, icon: Wallet, hint: 'somado das campanhas' })
    }
    return list
})

// ── Load ────────────────────────────────────────────────────────
onMounted(async () => {
    // Only fetch what this user may see; one failing section doesn't blank the others.
    const [c, p, g, t] = await Promise.allSettled([
        can('campaigns') ? api.get('/campaigns?per_page=100') : null,
        can('products') ? api.get('/products?per_page=5') : null,
        can('history') ? api.get('/generations') : null,
        can('tasks') ? api.get('/tasks') : null,
    ])
    if (c.value) campaigns.value = c.value.data.data
    if (p.value) products.value = p.value.data.data.slice(0, 5)
    if (g.value) {
        generations.value = g.value.data.data
        generationsTotal.value = g.value.data.total || 0
    }
    if (t.value) tasks.value = t.value.data
    loading.value = false
})
</script>
