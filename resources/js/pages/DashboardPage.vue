<template>
    <div class="p-8 max-w-6xl mx-auto">

        <!-- Page header -->
        <div class="mb-10">
            <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Visão Geral</p>
            <h1 class="page-hero-title text-3xl tracking-tight leading-tight">
                Olá, {{ firstName }} 👋
            </h1>
            <p class="mt-1.5 text-sm" style="color: var(--text-secondary)">Aqui está o resumo da sua conta hoje.</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-10">
            <StatCard label="Campanhas Ativas"       :value="stats.activeCampaigns"  icon="Megaphone" color="violet" />
            <StatCard label="Ações Criadas"           :value="stats.totalActions"     icon="Layers"    color="blue"   />
            <StatCard label="Gerações de Conteúdo"   :value="stats.totalGenerations" icon="Sparkles"  color="green"  />
            <StatCard label="Orçamento Total"        :value="formattedTotalBudget"   icon="Wallet"    color="orange" />
        </div>

        <!-- Sections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Campanhas recentes -->
            <div class="glass-modal rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, #a78bfa 14%, transparent); border: 1px solid color-mix(in srgb, #a78bfa 24%, transparent)">
                            <Megaphone class="w-3.5 h-3.5" style="color: #a78bfa" />
                        </div>
                        <h2 class="text-sm font-semibold text-white">Campanhas Recentes</h2>
                    </div>
                    <RouterLink to="/campaigns" class="text-xs font-medium transition-colors hover:text-white" style="color: var(--text-muted)">
                        Ver todas →
                    </RouterLink>
                </div>

                <div v-if="campaigns.length === 0" class="flex flex-col items-center py-8 gap-2">
                    <Megaphone class="w-8 h-8" style="color: var(--text-muted); opacity: 0.4" />
                    <p class="text-sm" style="color: var(--text-muted)">Nenhuma campanha ainda.</p>
                </div>
                <div v-else class="space-y-2 overflow-y-auto max-h-72 pr-1" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.08) transparent">
                    <RouterLink
                        v-for="c in campaigns"
                        :key="c.id"
                        :to="`/campaigns/${c.id}`"
                        class="flex items-center justify-between px-3.5 py-3 rounded-xl transition-all group list-row"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ c.name }}</p>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted)">{{ c.actions_count || 0 }} ações</p>
                        </div>
                        <StatusBadge :status="c.status" />
                    </RouterLink>
                </div>
            </div>

            <!-- Produtos recentes -->
            <div class="glass-modal rounded-2xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, #60a5fa 14%, transparent); border: 1px solid color-mix(in srgb, #60a5fa 24%, transparent)">
                            <Package class="w-3.5 h-3.5" style="color: #60a5fa" />
                        </div>
                        <h2 class="text-sm font-semibold text-white">Produtos Recentes</h2>
                    </div>
                    <RouterLink to="/products" class="text-xs font-medium transition-colors hover:text-white" style="color: var(--text-muted)">
                        Ver todos →
                    </RouterLink>
                </div>

                <div v-if="products.length === 0" class="flex flex-col items-center py-8 gap-2">
                    <Package class="w-8 h-8" style="color: var(--text-muted); opacity: 0.4" />
                    <p class="text-sm" style="color: var(--text-muted)">Nenhum produto cadastrado ainda.</p>
                </div>
                <div v-else class="space-y-2 overflow-y-auto max-h-72 pr-1" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.08) transparent">
                    <RouterLink
                        v-for="p in products"
                        :key="p.id"
                        to="/products"
                        class="flex items-center gap-3 px-3.5 py-3 rounded-xl transition-all list-row"
                    >
                        <div
                            class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden"
                            style="background: var(--surface-2); border: 1px solid var(--border-subtle)"
                        >
                            <img v-if="p.images?.length" :src="p.images[0]" class="w-full h-full object-cover" />
                            <Package v-else class="w-4 h-4" style="color: var(--text-muted)" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ p.name }}</p>
                            <p class="text-xs mt-0.5 truncate" style="color: var(--text-muted)">{{ p.category || 'Sem categoria' }}</p>
                        </div>
                        <p v-if="p.price" class="ml-auto text-sm font-semibold flex-shrink-0" style="color: #a78bfa">
                            R$ {{ numberToCurrency(p.price) }}
                        </p>
                    </RouterLink>
                </div>
            </div>

        </div>

        <!-- Orçamento -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">

            <!-- Orçamento por campanha -->
            <div class="glass-modal rounded-2xl p-6">
                <div class="flex items-center gap-2.5 mb-5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, #fb923c 14%, transparent); border: 1px solid color-mix(in srgb, #fb923c 24%, transparent)">
                        <Wallet class="w-3.5 h-3.5" style="color: #fb923c" />
                    </div>
                    <h2 class="text-sm font-semibold text-white">Orçamento por Campanha</h2>
                </div>

                <div v-if="budgetByCampaign.length === 0" class="flex flex-col items-center py-8 gap-2">
                    <Wallet class="w-8 h-8" style="color: var(--text-muted); opacity: 0.4" />
                    <p class="text-sm" style="color: var(--text-muted)">Nenhuma campanha com orçamento definido.</p>
                </div>
                <div v-else class="space-y-3.5">
                    <div v-for="row in budgetByCampaign" :key="row.label" class="group">
                        <div class="flex items-center justify-between gap-3 text-xs mb-1.5">
                            <span class="text-gray-300 truncate">{{ row.label }}</span>
                            <span class="text-gray-400 font-medium flex-shrink-0" style="font-variant-numeric: tabular-nums">R$ {{ numberToCurrency(row.value) }}</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.06)">
                            <div
                                class="h-full rounded-full transition-[filter]"
                                :style="{ width: row.pct + '%', background: 'var(--brand)' }"
                                :class="'group-hover:brightness-125'"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orçamento por status -->
            <div class="glass-modal rounded-2xl p-6">
                <div class="flex items-center gap-2.5 mb-5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, #a78bfa 14%, transparent); border: 1px solid color-mix(in srgb, #a78bfa 24%, transparent)">
                        <PieChart class="w-3.5 h-3.5" style="color: #a78bfa" />
                    </div>
                    <h2 class="text-sm font-semibold text-white">Orçamento por Status</h2>
                </div>

                <div v-if="budgetByStatus.length === 0" class="flex flex-col items-center py-8 gap-2">
                    <PieChart class="w-8 h-8" style="color: var(--text-muted); opacity: 0.4" />
                    <p class="text-sm" style="color: var(--text-muted)">Nenhuma campanha com orçamento definido.</p>
                </div>
                <div v-else class="space-y-3.5">
                    <div v-for="row in budgetByStatus" :key="row.status" class="group">
                        <div class="flex items-center justify-between gap-3 text-xs mb-1.5">
                            <span class="flex items-center gap-1.5 text-gray-300 truncate">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ background: row.color }"></span>
                                {{ row.label }}
                            </span>
                            <span class="text-gray-400 font-medium flex-shrink-0" style="font-variant-numeric: tabular-nums">R$ {{ numberToCurrency(row.value) }}</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.06)">
                            <div
                                class="h-full rounded-full transition-[filter] group-hover:brightness-125"
                                :style="{ width: row.pct + '%', background: row.color }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Megaphone, Package, Layers, Sparkles, Wallet, PieChart } from 'lucide-vue-next'
import api from '@/services/api'
import StatCard from '@/components/StatCard.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { numberToCurrency } from '@/utils/mask'

const auth        = useAuthStore()
const allCampaigns = ref([])
const campaigns    = ref([])
const products     = ref([])
const stats        = ref({ activeCampaigns: 0, totalActions: 0, totalGenerations: 0, totalBudget: 0 })

const firstName = computed(() => auth.user?.name?.split(' ')[0] || 'Usuário')
const formattedTotalBudget = computed(() => `R$ ${numberToCurrency(stats.value.totalBudget)}`)

const STATUS_LABELS = { draft: 'Rascunho', active: 'Ativa', paused: 'Pausada', finished: 'Concluída', archived: 'Arquivada' }
const STATUS_COLORS = { draft: '#6b7280', active: '#4ade80', paused: '#facc15', finished: '#60a5fa', archived: '#6b7280' }

const budgetByCampaign = computed(() => {
    const withBudget = allCampaigns.value
        .filter(c => Number(c.budget) > 0)
        .sort((a, b) => Number(b.budget) - Number(a.budget))
        .slice(0, 8)

    const max = withBudget.length ? Number(withBudget[0].budget) : 0

    return withBudget.map(c => ({
        label: c.name,
        value: Number(c.budget),
        pct: max ? (Number(c.budget) / max) * 100 : 0,
    }))
})

const budgetByStatus = computed(() => {
    const totals = {}
    for (const c of allCampaigns.value) {
        if (!Number(c.budget)) continue
        totals[c.status] = (totals[c.status] || 0) + Number(c.budget)
    }

    const entries = Object.entries(totals)
    const max = entries.length ? Math.max(...entries.map(([, v]) => v)) : 0

    return entries
        .sort((a, b) => b[1] - a[1])
        .map(([status, value]) => ({
            status,
            label: STATUS_LABELS[status] || status,
            color: STATUS_COLORS[status] || '#6b7280',
            value,
            pct: max ? (value / max) * 100 : 0,
        }))
})

onMounted(async () => {
    const [campaignsRes, productsRes, genRes] = await Promise.all([
        api.get('/campaigns?per_page=100'),
        api.get('/products?per_page=5'),
        api.get('/generations?per_page=1'),
    ])
    allCampaigns.value = campaignsRes.data.data
    campaigns.value    = allCampaigns.value.slice(0, 5)
    products.value     = productsRes.data.data
    stats.value.activeCampaigns  = allCampaigns.value.filter(c => c.status === 'active').length
    stats.value.totalActions     = allCampaigns.value.reduce((s, c) => s + (c.actions_count || 0), 0)
    stats.value.totalGenerations = genRes.data.total || 0
    stats.value.totalBudget      = allCampaigns.value.reduce((s, c) => s + Number(c.budget || 0), 0)
})
</script>
