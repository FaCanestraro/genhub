<template>
    <div class="p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <Users class="w-6 h-6 text-violet-400" />
                    Leads
                </h1>
                <p class="text-gray-400 mt-1">Gerencie seus leads e acompanhe o progresso.</p>
            </div>
            <button @click="openModal()" class="btn-primary flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Novo Lead
            </button>
        </div>

        <!-- Filtros -->
        <div class="flex items-center gap-3 mb-6">
            <div class="relative w-64 flex-shrink-0">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                <input v-model="filters.search" @input="reload" placeholder="Buscar nome, email..." class="input pl-9" />
            </div>
            <select v-model="filters.status" @change="reload" class="filter-select w-44">
                <option value="">Todos os status</option>
                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
            <select v-model="filters.fonte" @change="reload" class="filter-select w-40">
                <option value="">Todas as fontes</option>
                <option v-for="f in fontes" :key="f.value" :value="f.value">{{ f.label }}</option>
            </select>
        </div>

        <!-- Tabela -->
        <div class="glass-modal rounded-2xl overflow-hidden">
            <div v-if="loading" class="p-8 text-center">
                <Loader2 class="w-6 h-6 text-violet-400 animate-spin mx-auto" />
            </div>

            <div v-else-if="leads.length === 0" class="p-16 text-center">
                <Users class="w-12 h-12 text-gray-700 mx-auto mb-4" />
                <p class="text-gray-400">Nenhum lead encontrado.</p>
                <button @click="openModal()" class="btn-primary mt-4">Criar primeiro lead</button>
            </div>

            <table v-else class="w-full text-sm">
                <thead class="border-b border-gray-800">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Fonte</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Local</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Criado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <tr v-for="lead in leads" :key="lead.id" class="hover:bg-gray-800/50 transition-colors group cursor-pointer" @click="router.push(`/leads/${lead.id}`)">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-violet-600/20 flex items-center justify-center text-violet-400 font-semibold text-xs flex-shrink-0">
                                    {{ lead.nome.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-medium text-white">{{ lead.nome }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-gray-300">{{ lead.email }}</div>
                            <div class="text-gray-500 text-xs">{{ lead.telefone }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <span :class="statusClass(lead.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ statusLabel(lead.status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-400 capitalize">{{ fonteLabel(lead.fonte) }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ lead.local || '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-400">{{ lead.responsavel || '—' }}</td>
                        <td class="px-5 py-3.5 text-gray-500 text-xs whitespace-nowrap">{{ formatDate(lead.created_at) }}</td>
                        <td class="px-5 py-3.5" @click.stop>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openModal(lead)" class="p-1.5 text-gray-500 hover:text-white hover:bg-gray-700 rounded-lg transition-colors">
                                    <Pencil class="w-3.5 h-3.5" />
                                </button>
                                <button @click="deleteLead(lead)" class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-gray-700 rounded-lg transition-colors">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginação -->
            <div v-if="lastPage > 1" class="flex items-center justify-between px-5 py-3 border-t border-gray-800">
                <p class="text-xs text-gray-500">Página {{ page }} de {{ lastPage }}</p>
                <div class="flex gap-2">
                    <button @click="changePage(page - 1)" :disabled="page <= 1" class="px-3 py-1 text-xs bg-gray-800 rounded-lg disabled:opacity-40 hover:bg-gray-700 transition-colors">Anterior</button>
                    <button @click="changePage(page + 1)" :disabled="page >= lastPage" class="px-3 py-1 text-xs bg-gray-800 rounded-lg disabled:opacity-40 hover:bg-gray-700 transition-colors">Próximo</button>
                </div>
            </div>
        </div>

        <!-- Modal Novo/Editar Lead -->
        <div v-if="modal.open" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4">
            <div class="glass-modal rounded-2xl w-full max-w-lg">
                <div class="flex items-center justify-between p-6 border-b border-gray-800">
                    <h2 class="font-semibold text-white">{{ modal.lead ? 'Editar Lead' : 'Novo Lead' }}</h2>
                    <button @click="modal.open = false" class="text-gray-500 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="saveLead" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="label">Nome *</label>
                            <input v-model="form.nome" required class="input" placeholder="Nome completo" />
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input v-model="form.email" type="email" class="input" placeholder="email@exemplo.com" />
                        </div>
                        <div>
                            <label class="label">Telefone</label>
                            <input v-model="form.telefone" class="input" placeholder="(11) 99999-0000" />
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select v-model="form.status" class="input">
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Fonte</label>
                            <select v-model="form.fonte" class="input">
                                <option v-for="f in fontes" :key="f.value" :value="f.value">{{ f.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Local</label>
                            <input v-model="form.local" class="input" placeholder="Cidade, Estado" />
                        </div>
                        <div>
                            <label class="label">Responsável</label>
                            <input v-model="form.responsavel" class="input" placeholder="Nome do responsável" />
                        </div>
                        <div class="col-span-2">
                            <label class="label">Notas</label>
                            <textarea v-model="form.notas" rows="3" class="input resize-none" placeholder="Observações sobre o lead..."></textarea>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="modal.open = false" class="flex-1 py-2.5 border border-gray-700 rounded-xl text-gray-300 hover:bg-gray-800 transition-colors text-sm">Cancelar</button>
                        <button type="submit" :disabled="saving" class="flex-1 btn-primary">{{ saving ? 'Salvando...' : (modal.lead ? 'Salvar' : 'Criar Lead') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Users, Plus, Search, Pencil, Trash2, X, Loader2 } from 'lucide-vue-next'
import api from '@/services/api'

const router = useRouter()

const leads   = ref([])
const loading = ref(true)
const saving  = ref(false)
const page    = ref(1)
const lastPage = ref(1)

const filters = reactive({ search: '', status: '', fonte: '' })

const modal = reactive({ open: false, lead: null })
const form  = reactive({ nome: '', email: '', telefone: '', status: 'novo_lead', fonte: 'manual', local: '', responsavel: '', notas: '' })

const statuses = [
    { value: 'novo_lead',     label: 'Novo Lead',       class: 'bg-blue-500/20 text-blue-300' },
    { value: 'contato_feito', label: 'Contato Feito',   class: 'bg-cyan-500/20 text-cyan-300' },
    { value: 'qualificacao',  label: 'Qualificação',    class: 'bg-yellow-500/20 text-yellow-300' },
    { value: 'cotacao',       label: 'Cotação',         class: 'bg-orange-500/20 text-orange-300' },
    { value: 'negociacao',    label: 'Negociação',      class: 'bg-purple-500/20 text-purple-300' },
    { value: 'fechado',       label: 'Fechado',         class: 'bg-green-500/20 text-green-300' },
]

const fontes = [
    { value: 'site',      label: 'Site' },
    { value: 'social',    label: 'Social' },
    { value: 'email',     label: 'Email' },
    { value: 'indicacao', label: 'Indicação' },
    { value: 'manual',    label: 'Manual' },
    { value: 'api',       label: 'API' },
]

function statusLabel(v) { return statuses.find(s => s.value === v)?.label ?? v }
function statusClass(v) { return statuses.find(s => s.value === v)?.class ?? '' }
function fonteLabel(v)  { return fontes.find(f => f.value === v)?.label ?? v }

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('pt-BR') : ''
}

async function fetchLeads() {
    loading.value = true
    try {
        const params = { page: page.value }
        if (filters.search) params.search = filters.search
        if (filters.status) params.status = filters.status
        if (filters.fonte)  params.fonte  = filters.fonte
        const { data } = await api.get('/leads', { params })
        leads.value    = data.data
        lastPage.value = data.last_page
    } finally {
        loading.value = false
    }
}

function reload() { page.value = 1; fetchLeads() }
function changePage(p) { page.value = p; fetchLeads() }

function openModal(lead = null) {
    modal.lead = lead
    Object.assign(form, lead
        ? { nome: lead.nome, email: lead.email ?? '', telefone: lead.telefone ?? '', status: lead.status, fonte: lead.fonte, local: lead.local ?? '', responsavel: lead.responsavel ?? '', notas: lead.notas ?? '' }
        : { nome: '', email: '', telefone: '', status: 'novo_lead', fonte: 'manual', local: '', responsavel: '', notas: '' }
    )
    modal.open = true
}

async function saveLead() {
    saving.value = true
    try {
        if (modal.lead) {
            await api.put(`/leads/${modal.lead.id}`, form)
        } else {
            await api.post('/leads', form)
        }
        modal.open = false
        fetchLeads()
    } finally {
        saving.value = false
    }
}

async function deleteLead(lead) {
    if (!confirm(`Excluir o lead "${lead.nome}"?`)) return
    await api.delete(`/leads/${lead.id}`)
    fetchLeads()
}

onMounted(fetchLeads)
</script>

<style scoped>
@reference "tailwindcss";
.filter-select { @apply bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-violet-500 transition-colors text-sm; }
.label         { @apply block text-xs text-gray-400 mb-1 font-medium; }
.btn-primary   { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>





