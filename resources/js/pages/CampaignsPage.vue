<template>
    <div class="page flex flex-col gap-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <p class="tech-label">Marketing</p>
                <h1 class="page-hero-title text-3xl leading-tight">Campanhas</h1>
                <p class="text-sm" style="color: var(--text-secondary)">Organize ações, criativos e orçamento de cada campanha.</p>
            </div>
            <button v-if="auth.can('campaigns', 'create')" @click="openModal()" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white">
                <Plus class="w-4 h-4" /> Nova campanha
            </button>
        </header>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar por status">
            <button
                v-for="s in statuses"
                :key="s.value"
                type="button"
                @click="filterStatus = s.value"
                :aria-pressed="filterStatus === s.value"
                class="filter-chip"
                :class="{ active: filterStatus === s.value }"
            >
                {{ s.label }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="shimmer h-52 rounded-2xl"></div>
        </div>

        <EmptyState
            v-else-if="!campaigns.length"
            class="panel"
            :icon="Megaphone"
            :title="filterStatus ? 'Nenhuma campanha com esse status' : 'Nenhuma campanha ainda'"
            :text="filterStatus ? 'Tente outro filtro.' : 'Crie uma campanha para organizar as ações e criativos de uma oferta ou data especial.'"
        />

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <RouterLink
                v-for="c in campaigns"
                :key="c.id"
                :to="`/campaigns/${c.id}`"
                class="stat-card p-5 flex flex-col gap-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <StatusBadge :status="c.status" />
                    <button
                        v-if="auth.can('campaigns', 'edit')"
                        type="button"
                        @click.prevent="openModal(c)"
                        class="icon-btn -mr-2 -mt-2"
                        :aria-label="`Editar campanha ${c.name}`"
                    >
                        <Pencil class="w-4 h-4" />
                    </button>
                </div>

                <div class="min-w-0">
                    <h3 class="page-hero-title text-lg leading-snug">{{ c.name }}</h3>
                    <p v-if="c.description" class="text-sm mt-1 line-clamp-2" style="color: var(--text-secondary)">{{ c.description }}</p>
                </div>

                <!-- Período -->
                <div v-if="c.start_date || c.end_date" class="flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-3 text-xs" style="color: var(--text-secondary)">
                        <span class="flex items-center gap-1.5">
                            <CalendarRange class="w-3.5 h-3.5" />
                            {{ formatShort(c.start_date) || '—' }} → {{ formatShort(c.end_date) || '—' }}
                        </span>
                        <span v-if="period(c)" :class="period(c).tone">{{ period(c).label }}</span>
                    </div>
                    <span v-if="period(c)?.pct != null" class="h-1 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.06)">
                        <span class="block h-full rounded-full" :style="{ width: period(c).pct + '%', background: 'var(--brand)' }"></span>
                    </span>
                </div>

                <div class="mt-auto flex items-center gap-5 pt-4 text-sm" style="border-top: 1px solid var(--border-subtle)">
                    <span class="flex items-center gap-1.5" style="color: var(--text-secondary)">
                        <Layers class="w-4 h-4" />
                        <span class="text-white font-semibold">{{ c.actions_count || 0 }}</span> {{ c.actions_count === 1 ? 'ação' : 'ações' }}
                    </span>
                    <span v-if="Number(c.budget)" class="flex items-center gap-1.5" style="color: var(--text-secondary)">
                        <Wallet class="w-4 h-4" />
                        <span class="text-white font-semibold">R$ {{ numberToCurrency(c.budget) }}</span>
                    </span>
                </div>
            </RouterLink>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 dialog-backdrop flex items-center justify-center z-50 p-4" @click.self="showModal = false">
                <div class="glass-dialog rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="page-hero-title text-lg mb-5">{{ editing ? 'Editar campanha' : 'Nova campanha' }}</h2>

                    <form @submit.prevent="saveCampaign" class="space-y-4">
                        <div>
                            <label for="campaign-name" class="field-label">Nome *</label>
                            <input id="campaign-name" v-model="form.name" type="text" required class="input" placeholder="Nome da campanha" />
                        </div>
                        <div>
                            <label for="campaign-description" class="field-label">Descrição</label>
                            <textarea id="campaign-description" v-model="form.description" rows="2" class="input resize-none" placeholder="Objetivo da campanha..."></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="campaign-start_date" class="field-label">Início</label>
                                <input id="campaign-start_date" v-model="form.start_date" type="date" class="input" />
                            </div>
                            <div>
                                <label for="campaign-end_date" class="field-label">Fim</label>
                                <input id="campaign-end_date" v-model="form.end_date" type="date" class="input" />
                            </div>
                        </div>
                        <div>
                            <label for="campaign-budget" class="field-label">Orçamento (R$)</label>
                            <input
                                id="campaign-budget"
                                :value="numberToCurrency(form.budget)"
                                @input="e => onCurrencyInput(e, v => form.budget = v)"
                                type="text"
                                inputmode="numeric"
                                class="input"
                                placeholder="0,00"
                            />
                        </div>
                        <div v-if="editing">
                            <label for="campaign-status" class="field-label">Status</label>
                            <select id="campaign-status" v-model="form.status" class="input">
                                <option value="draft">Rascunho</option>
                                <option value="active">Ativa</option>
                                <option value="paused">Pausada</option>
                                <option value="finished">Concluída</option>
                                <option value="archived">Arquivada</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn-secondary">Cancelar</button>
                            <button type="submit" :disabled="saving" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white">
                                {{ saving ? 'Salvando...' : 'Salvar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Plus, Megaphone, Pencil, Layers, Wallet, CalendarRange } from 'lucide-vue-next'
import api from '@/services/api'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { numberToCurrency, onCurrencyInput } from '@/utils/mask'
import { formatShort, period } from '@/utils/campaignPeriod'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const campaigns = ref([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editing = ref(null)
const filterStatus = ref('')
const form = ref({ name: '', description: '', start_date: '', end_date: '', budget: '', status: 'draft' })

const statuses = [
    { value: '', label: 'Todas' },
    { value: 'active', label: 'Ativas' },
    { value: 'draft', label: 'Rascunho' },
    { value: 'paused', label: 'Pausadas' },
    { value: 'finished', label: 'Concluídas' },
]

async function fetchCampaigns() {
    loading.value = true
    const params = filterStatus.value ? `?status=${filterStatus.value}` : ''
    const { data } = await api.get(`/campaigns${params}`)
    campaigns.value = data.data
    loading.value = false
}

watch(filterStatus, fetchCampaigns)

function openModal(campaign = null) {
    editing.value = campaign
    form.value = campaign
        ? { name: campaign.name, description: campaign.description || '', start_date: toDateInput(campaign.start_date), end_date: toDateInput(campaign.end_date), budget: campaign.budget || '', status: campaign.status }
        : { name: '', description: '', start_date: '', end_date: '', budget: '', status: 'draft' }
    showModal.value = true
}

async function saveCampaign() {
    saving.value = true
    try {
        if (editing.value) {
            await api.put(`/campaigns/${editing.value.id}`, form.value)
        } else {
            await api.post('/campaigns', form.value)
        }
        showModal.value = false
        fetchCampaigns()
    } finally {
        saving.value = false
    }
}

function toDateInput(d) {
    return d ? d.slice(0, 10) : ''
}

onMounted(fetchCampaigns)
</script>
