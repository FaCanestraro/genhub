<template>
    <div class="p-8 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Marketing</p>
                <h1 class="text-2xl font-bold text-white tracking-tight leading-tight">Campanhas</h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary)">Crie e gerencie suas campanhas</p>
            </div>
            <button @click="openModal()" class="btn-primary flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Nova Campanha
            </button>
        </div>

        <!-- Filters -->
        <div class="flex gap-2 mb-6">
            <button
                v-for="s in statuses"
                :key="s.value"
                @click="filterStatus = s.value"
                :class="filterStatus === s.value ? 'brand-active' : 'text-gray-400 hover:text-white'"
                :style="filterStatus !== s.value ? 'background: var(--surface-1); border: 1px solid var(--border-subtle)' : ''"
                class="px-3 py-1.5 rounded-lg text-sm transition-colors"
            >
                {{ s.label }}
            </button>
        </div>

        <div v-if="!loading && campaigns.length === 0" class="text-center py-20">
            <Megaphone class="w-12 h-12 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400">Nenhuma campanha encontrada.</p>
            <button @click="openModal()" class="btn-primary mt-4">Criar campanha</button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <RouterLink
                v-for="c in campaigns"
                :key="c.id"
                :to="`/campaigns/${c.id}`"
                class="glass-modal rounded-2xl p-5 hover:border-gray-700 transition-all hover:shadow-lg block"
            >
                <div class="flex items-start justify-between mb-3">
                    <StatusBadge :status="c.status" />
                    <button
                        @click.prevent="openModal(c)"
                        class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors"
                    >
                        <Pencil class="w-4 h-4" />
                    </button>
                </div>

                <h3 class="font-semibold text-white text-lg">{{ c.name }}</h3>
                <p v-if="c.description" class="text-sm text-gray-400 mt-1 line-clamp-2">{{ c.description }}</p>

                <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-800">
                    <div class="text-center">
                        <p class="text-xl font-bold text-white">{{ c.actions_count || 0 }}</p>
                        <p class="text-xs text-gray-400">Ações</p>
                    </div>
                    <div v-if="c.budget" class="text-center">
                        <p class="text-xl font-bold text-white">R$ {{ numberToCurrency(c.budget) }}</p>
                        <p class="text-xs text-gray-400">Orçamento</p>
                    </div>
                    <div v-if="c.start_date" class="ml-auto text-right">
                        <p class="text-xs text-gray-400">Início</p>
                        <p class="text-sm text-gray-300">{{ formatDate(c.start_date) }}</p>
                    </div>
                </div>
            </RouterLink>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showModal = false">
                <div class="glass-modal rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-lg font-semibold text-white mb-5">{{ editing ? 'Editar' : 'Nova' }} Campanha</h2>

                    <form @submit.prevent="saveCampaign" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Nome *</label>
                            <input v-model="form.name" type="text" required class="input" placeholder="Nome da campanha" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Descrição</label>
                            <textarea v-model="form.description" rows="2" class="input resize-none" placeholder="Objetivo da campanha..."></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Início</label>
                                <input v-model="form.start_date" type="date" class="input" />
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Fim</label>
                                <input v-model="form.end_date" type="date" class="input" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Orçamento (R$)</label>
                            <input
                                :value="numberToCurrency(form.budget)"
                                @input="e => onCurrencyInput(e, v => form.budget = v)"
                                type="text"
                                inputmode="numeric"
                                class="input"
                                placeholder="0,00"
                            />
                        </div>
                        <div v-if="editing">
                            <label class="block text-sm text-gray-400 mb-1">Status</label>
                            <select v-model="form.status" class="input">
                                <option value="draft">Rascunho</option>
                                <option value="active">Ativa</option>
                                <option value="paused">Pausada</option>
                                <option value="finished">Concluída</option>
                                <option value="archived">Arquivada</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn-ghost">Cancelar</button>
                            <button type="submit" :disabled="saving" class="btn-primary">
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
import { Plus, Megaphone, Pencil } from 'lucide-vue-next'
import api from '@/services/api'
import StatusBadge from '@/components/StatusBadge.vue'
import { numberToCurrency, onCurrencyInput } from '@/utils/mask'

const campaigns = ref([])
const loading = ref(false)
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

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('pt-BR') : ''
}

onMounted(fetchCampaigns)
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors text-sm; }
.btn-primary { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
.btn-ghost { @apply text-gray-400 hover:text-white hover:bg-gray-800 font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>





