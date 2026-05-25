<template>
    <div class="flex flex-col h-full">
        <!-- Loading -->
        <div v-if="loading" class="flex-1 flex items-center justify-center">
            <Loader2 class="w-6 h-6 text-violet-400 animate-spin" />
        </div>

        <template v-else-if="lead">
            <!-- Header -->
            <div class="flex items-start justify-between px-6 py-4 border-b border-gray-800 flex-shrink-0">
                <div class="flex items-start gap-4">
                    <button @click="$router.back()" class="mt-1 p-1.5 text-gray-500 hover:text-white hover:bg-gray-800 rounded-lg transition-colors flex-shrink-0">
                        <ArrowLeft class="w-4 h-4" />
                    </button>
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-violet-600/20 flex items-center justify-center text-violet-400 font-bold text-sm flex-shrink-0">
                                {{ lead.nome.charAt(0).toUpperCase() }}
                            </div>
                            <h1 class="text-xl font-bold text-white uppercase tracking-wide">{{ lead.nome }}</h1>
                        </div>
                        <div class="flex items-center gap-4 mt-1.5 ml-12 flex-wrap">
                            <span v-if="lead.telefone" class="flex items-center gap-1.5 text-sm text-gray-400">
                                <Phone class="w-3.5 h-3.5" />{{ lead.telefone }}
                            </span>
                            <span v-if="lead.email" class="flex items-center gap-1.5 text-sm text-gray-400">
                                <Mail class="w-3.5 h-3.5" />{{ lead.email }}
                            </span>
                            <span v-if="lead.local" class="flex items-center gap-1.5 text-sm text-gray-400">
                                <MapPin class="w-3.5 h-3.5" />{{ lead.local }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="openEdit" class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-700 rounded-lg text-gray-300 hover:bg-gray-800 transition-colors">
                        <Pencil class="w-3.5 h-3.5" />Editar
                    </button>
                    <button @click="confirmDelete" class="flex items-center gap-1.5 px-3 py-1.5 text-sm border border-red-800 rounded-lg text-red-400 hover:bg-red-900/20 transition-colors">
                        <Trash2 class="w-3.5 h-3.5" />Excluir
                    </button>
                </div>
            </div>

            <!-- Body: 3 columns -->
            <div class="flex flex-1 overflow-hidden">

                <!-- LEFT PANEL -->
                <aside class="w-60 flex-shrink-0 border-r border-gray-800 overflow-y-auto p-4 space-y-5">
                    <!-- Status -->
                    <div>
                        <label class="label">Status</label>
                        <select v-model="lead.status" @change="patchField('status', lead.status)" class="input uppercase font-semibold" :class="statusClass(lead.status)">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>

                    <!-- Responsável -->
                    <div>
                        <label class="label">Responsável</label>
                        <input v-model="lead.responsavel"
                            @blur="patchField('responsavel', lead.responsavel)"
                            @keydown.enter="patchField('responsavel', lead.responsavel)"
                            class="input" placeholder="—" />
                    </div>

                    <!-- Fonte -->
                    <div>
                        <label class="label">Fonte</label>
                        <p class="text-sm font-semibold text-white uppercase">{{ fonteLabel(lead.fonte) }}</p>
                    </div>

                    <!-- Notas -->
                    <div>
                        <label class="label">Notas</label>
                        <textarea v-model="lead.notas"
                            @blur="patchField('notas', lead.notas)"
                            rows="3" class="input resize-none text-xs" placeholder="Adicionar notas..."></textarea>
                    </div>

                    <!-- Meta -->
                    <div class="pt-2 border-t border-gray-800 space-y-1">
                        <p class="text-xs text-gray-500">Criado {{ timeAgo(lead.created_at) }}</p>
                        <p v-if="lead.activities?.length" class="text-xs text-gray-600">
                            Última interação {{ timeAgo(lead.activities[0]?.created_at) }}
                        </p>
                    </div>
                </aside>

                <!-- CENTER PANEL — Atividades -->
                <div class="flex-1 overflow-y-auto flex flex-col border-r border-gray-800">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                        <h2 class="font-semibold text-white">Atividades</h2>
                        <button @click="openAddActivity" class="flex items-center gap-1.5 text-sm text-violet-400 hover:text-violet-300 border border-violet-800 hover:border-violet-600 px-3 py-1.5 rounded-lg transition-colors">
                            <Plus class="w-3.5 h-3.5" />Adicionar
                        </button>
                    </div>

                    <div class="flex-1 px-6 py-4 space-y-3">
                        <div v-if="!lead.activities?.length" class="py-12 text-center">
                            <Activity class="w-10 h-10 text-gray-700 mx-auto mb-3" />
                            <p class="text-gray-500 text-sm">Nenhuma atividade registrada.</p>
                        </div>

                        <div
                            v-for="act in sortedActivities"
                            :key="act.id"
                            class="flex gap-3 group"
                        >
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center"
                                    :class="activityIconClass(act.type)">
                                    <component :is="activityIcon(act.type)" class="w-3.5 h-3.5" />
                                </div>
                            </div>
                            <!-- Content -->
                            <div class="flex-1 bg-gray-900 border border-gray-800 rounded-xl px-4 py-3">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-white">{{ act.titulo }}</p>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-xs text-gray-600 whitespace-nowrap">{{ timeAgo(act.created_at) }}</span>
                                        <button @click="deleteActivity(act)" class="p-0.5 text-gray-600 hover:text-red-400 transition-colors">
                                            <X class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>
                                <p v-if="act.descricao" class="text-xs text-gray-400 mt-1">{{ act.descricao }}</p>
                                <p class="text-xs text-gray-600 mt-1.5">
                                    {{ act.user?.name ?? 'Sistema' }} · {{ timeAgo(act.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT PANEL — Tabs -->
                <div class="w-80 flex-shrink-0 flex flex-col overflow-hidden">
                    <!-- Tab headers -->
                    <div class="flex border-b border-gray-800 flex-shrink-0">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="flex-1 py-3 text-xs font-medium transition-colors"
                            :class="activeTab === tab.id ? 'border-b-2 brand-border brand-text' : 'text-gray-500 hover:text-gray-300'"
                        >
                            {{ tab.label }} ({{ tab.count() }})
                        </button>
                    </div>

                    <!-- TAREFAS -->
                    <div v-if="activeTab === 'tarefas'" class="flex-1 overflow-y-auto p-4">
                        <button @click="openAddTask" class="w-full flex items-center gap-2 text-sm text-violet-400 hover:text-violet-300 border border-violet-800 hover:border-violet-600 rounded-lg px-3 py-2 transition-colors mb-3">
                            <Plus class="w-3.5 h-3.5" />Nova Tarefa
                        </button>

                        <div v-if="!leadTasks.length" class="py-10 text-center">
                            <p class="text-gray-600 text-sm">Nenhuma tarefa</p>
                        </div>

                        <div v-for="task in leadTasks" :key="task.id"
                            class="flex items-start gap-2.5 p-3 bg-gray-900 border border-gray-800 rounded-xl mb-2 group">
                            <button @click="toggleTask(task)" class="mt-0.5 flex-shrink-0">
                                <div :class="task.concluida ? 'brand-check' : 'border-gray-600'"
                                    class="w-4 h-4 rounded border-2 flex items-center justify-center transition-colors">
                                    <Check v-if="task.concluida" class="w-2.5 h-2.5 text-white" />
                                </div>
                            </button>
                            <div class="flex-1 min-w-0">
                                <p :class="task.concluida ? 'line-through text-gray-500' : 'text-white'" class="text-sm font-medium">{{ task.titulo }}</p>
                                <p v-if="task.responsavel" class="text-xs text-gray-600 mt-0.5">{{ task.responsavel }}</p>
                                <p v-if="task.prazo" class="text-xs mt-0.5" :class="isOverdue(task) ? 'text-red-400' : 'text-gray-600'">
                                    {{ formatDate(task.prazo) }}
                                </p>
                            </div>
                            <button @click="deleteTask(task)" class="p-1 text-gray-700 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all">
                                <Trash2 class="w-3 h-3" />
                            </button>
                        </div>
                    </div>

                    <!-- COTAÇÕES -->
                    <div v-else-if="activeTab === 'cotacoes'" class="flex-1 overflow-y-auto p-4">
                        <div class="py-10 text-center">
                            <FileText class="w-10 h-10 text-gray-700 mx-auto mb-3" />
                            <p class="text-gray-500 text-sm">Cotações</p>
                            <p class="text-gray-600 text-xs mt-1">Em breve</p>
                        </div>
                    </div>

                    <!-- ARQUIVOS -->
                    <div v-else-if="activeTab === 'arquivos'" class="flex-1 overflow-y-auto p-4">
                        <div class="py-10 text-center">
                            <Paperclip class="w-10 h-10 text-gray-700 mx-auto mb-3" />
                            <p class="text-gray-500 text-sm">Arquivos</p>
                            <p class="text-gray-600 text-xs mt-1">Em breve</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Not found -->
        <div v-else class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <p class="text-gray-400">Lead não encontrado.</p>
                <button @click="$router.back()" class="btn-primary mt-4">Voltar</button>
            </div>
        </div>

        <!-- Modal: Editar Lead -->
        <div v-if="editModal.open" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4">
            <div class="glass-modal rounded-2xl w-full max-w-lg">
                <div class="flex items-center justify-between p-6 border-b border-gray-800">
                    <h2 class="font-semibold text-white">Editar Lead</h2>
                    <button @click="editModal.open = false" class="text-gray-500 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="saveEdit" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="label">Nome *</label>
                            <input v-model="editForm.nome" required class="input" />
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input v-model="editForm.email" type="email" class="input" />
                        </div>
                        <div>
                            <label class="label">Telefone</label>
                            <input v-model="editForm.telefone" class="input" />
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select v-model="editForm.status" class="input">
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Fonte</label>
                            <select v-model="editForm.fonte" class="input">
                                <option v-for="f in fontes" :key="f.value" :value="f.value">{{ f.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Local</label>
                            <input v-model="editForm.local" class="input" placeholder="Cidade, Estado" />
                        </div>
                        <div>
                            <label class="label">Responsável</label>
                            <input v-model="editForm.responsavel" class="input" />
                        </div>
                        <div class="col-span-2">
                            <label class="label">Notas</label>
                            <textarea v-model="editForm.notas" rows="3" class="input resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="editModal.open = false" class="flex-1 py-2.5 border border-gray-700 rounded-xl text-gray-300 hover:bg-gray-800 transition-colors text-sm">Cancelar</button>
                        <button type="submit" :disabled="editModal.saving" class="flex-1 btn-primary">{{ editModal.saving ? 'Salvando...' : 'Salvar' }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Adicionar Atividade -->
        <div v-if="activityModal.open" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4">
            <div class="glass-modal rounded-2xl w-full max-w-md">
                <div class="flex items-center justify-between p-6 border-b border-gray-800">
                    <h2 class="font-semibold text-white">Adicionar Atividade</h2>
                    <button @click="activityModal.open = false" class="text-gray-500 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="saveActivity" class="p-6 space-y-4">
                    <div>
                        <label class="label">Tipo</label>
                        <select v-model="activityForm.type" class="input">
                            <option value="nota">Nota</option>
                            <option value="tarefa">Tarefa</option>
                            <option value="arquivo">Arquivo</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Título *</label>
                        <input v-model="activityForm.titulo" required class="input" placeholder="Descreva a atividade..." />
                    </div>
                    <div>
                        <label class="label">Descrição</label>
                        <textarea v-model="activityForm.descricao" rows="3" class="input resize-none" placeholder="Detalhes adicionais..."></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="activityModal.open = false" class="flex-1 py-2.5 border border-gray-700 rounded-xl text-gray-300 hover:bg-gray-800 transition-colors text-sm">Cancelar</button>
                        <button type="submit" :disabled="activityModal.saving" class="flex-1 btn-primary">{{ activityModal.saving ? 'Salvando...' : 'Adicionar' }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Nova Tarefa -->
        <div v-if="taskModal.open" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4">
            <div class="glass-modal rounded-2xl w-full max-w-md">
                <div class="flex items-center justify-between p-6 border-b border-gray-800">
                    <h2 class="font-semibold text-white">Nova Tarefa</h2>
                    <button @click="taskModal.open = false" class="text-gray-500 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="saveTask" class="p-6 space-y-4">
                    <div>
                        <label class="label">Título *</label>
                        <input v-model="taskForm.titulo" required class="input" placeholder="Descreva a tarefa..." />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Responsável</label>
                            <input v-model="taskForm.responsavel" class="input" :placeholder="lead.responsavel || 'Nome'" />
                        </div>
                        <div>
                            <label class="label">Prazo</label>
                            <input v-model="taskForm.prazo" type="datetime-local" class="input" />
                        </div>
                    </div>
                    <div>
                        <label class="label">Descrição</label>
                        <textarea v-model="taskForm.descricao" rows="2" class="input resize-none"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="taskModal.open = false" class="flex-1 py-2.5 border border-gray-700 rounded-xl text-gray-300 hover:bg-gray-800 transition-colors text-sm">Cancelar</button>
                        <button type="submit" :disabled="taskModal.saving" class="flex-1 btn-primary">{{ taskModal.saving ? 'Criando...' : 'Criar Tarefa' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
    ArrowLeft, Phone, Mail, MapPin, Pencil, Trash2, Plus,
    X, Check, Loader2, Activity, ArrowLeftRight,
    MessageSquare, CheckSquare, Paperclip, FileText
} from 'lucide-vue-next'
import api from '@/services/api'

const route  = useRoute()
const router = useRouter()

const lead    = ref(null)
const loading = ref(true)

// ─── Data ─────────────────────────────────────────────────────────────────────

const statuses = [
    { value: 'novo_lead',     label: 'Novo Lead',     selectClass: 'text-blue-300' },
    { value: 'contato_feito', label: 'Contato Feito', selectClass: 'text-cyan-300' },
    { value: 'qualificacao',  label: 'Qualificação',  selectClass: 'text-yellow-300' },
    { value: 'cotacao',       label: 'Cotação',       selectClass: 'text-orange-300' },
    { value: 'negociacao',    label: 'Negociação',    selectClass: 'text-purple-300' },
    { value: 'fechado',       label: 'Fechado',       selectClass: 'text-green-300' },
]

const fontes = [
    { value: 'site',      label: 'Site' },
    { value: 'social',    label: 'Social' },
    { value: 'email',     label: 'Email' },
    { value: 'indicacao', label: 'Indicação' },
    { value: 'manual',    label: 'Manual' },
    { value: 'api',       label: 'API' },
]

const activeTab = ref('tarefas')

const leadTasks = computed(() => lead.value?.tasks ?? [])

const tabs = [
    { id: 'tarefas',  label: 'Tarefas',  count: () => leadTasks.value.length },
    { id: 'cotacoes', label: 'Cotações', count: () => 0 },
    { id: 'arquivos', label: 'Arquivos', count: () => 0 },
]

const sortedActivities = computed(() =>
    [...(lead.value?.activities ?? [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
)

// ─── Helpers ──────────────────────────────────────────────────────────────────

function fonteLabel(v) { return fontes.find(f => f.value === v)?.label ?? v }

function statusClass(v) {
    const map = {
        novo_lead: 'text-blue-300', contato_feito: 'text-cyan-300',
        qualificacao: 'text-yellow-300', cotacao: 'text-orange-300',
        negociacao: 'text-purple-300', fechado: 'text-green-300',
    }
    return map[v] ?? ''
}

function timeAgo(d) {
    if (!d) return ''
    const diff = (Date.now() - new Date(d).getTime()) / 1000
    if (diff < 60) return 'agora mesmo'
    if (diff < 3600) return `há ${Math.floor(diff / 60)} min`
    if (diff < 86400) return `há ${Math.floor(diff / 3600)} h`
    if (diff < 2592000) return `há ${Math.floor(diff / 86400)} dias`
    return new Date(d).toLocaleDateString('pt-BR')
}

function formatDate(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function isOverdue(task) {
    return !task.concluida && task.prazo && new Date(task.prazo) < new Date()
}

function activityIcon(type) {
    return { status_change: ArrowLeftRight, nota: MessageSquare, tarefa: CheckSquare, arquivo: Paperclip }[type] ?? MessageSquare
}

function activityIconClass(type) {
    return {
        status_change: 'bg-orange-500/20 text-orange-400',
        nota:          'bg-blue-500/20 text-blue-400',
        tarefa:        'bg-violet-500/20 text-violet-400',
        arquivo:       'bg-gray-700 text-gray-400',
    }[type] ?? 'bg-gray-700 text-gray-400'
}

// ─── Fetch ────────────────────────────────────────────────────────────────────

async function fetchLead() {
    loading.value = true
    try {
        const { data } = await api.get(`/leads/${route.params.id}`)
        lead.value = data
    } catch {
        lead.value = null
    } finally {
        loading.value = false
    }
}

// ─── Inline patch ─────────────────────────────────────────────────────────────

async function patchField(field, value) {
    const { data } = await api.put(`/leads/${lead.value.id}`, { [field]: value })
    // Merge activities from response (status change logs)
    if (data.activities) lead.value.activities = data.activities
}

// ─── Edit modal ───────────────────────────────────────────────────────────────

const editModal = reactive({ open: false, saving: false })
const editForm  = reactive({ nome: '', email: '', telefone: '', status: '', fonte: '', local: '', responsavel: '', notas: '' })

function openEdit() {
    Object.assign(editForm, {
        nome: lead.value.nome, email: lead.value.email ?? '',
        telefone: lead.value.telefone ?? '', status: lead.value.status,
        fonte: lead.value.fonte, local: lead.value.local ?? '',
        responsavel: lead.value.responsavel ?? '', notas: lead.value.notas ?? '',
    })
    editModal.open = true
}

async function saveEdit() {
    editModal.saving = true
    try {
        const { data } = await api.put(`/leads/${lead.value.id}`, editForm)
        Object.assign(lead.value, data)
        if (data.activities) lead.value.activities = data.activities
        editModal.open = false
    } finally {
        editModal.saving = false
    }
}

// ─── Delete lead ──────────────────────────────────────────────────────────────

async function confirmDelete() {
    if (!confirm(`Excluir o lead "${lead.value.nome}"? Esta ação não pode ser desfeita.`)) return
    await api.delete(`/leads/${lead.value.id}`)
    router.push('/leads')
}

// ─── Activities ───────────────────────────────────────────────────────────────

const activityModal = reactive({ open: false, saving: false })
const activityForm  = reactive({ type: 'nota', titulo: '', descricao: '' })

function openAddActivity() {
    Object.assign(activityForm, { type: 'nota', titulo: '', descricao: '' })
    activityModal.open = true
}

async function saveActivity() {
    activityModal.saving = true
    try {
        const { data } = await api.post(`/leads/${lead.value.id}/activities`, activityForm)
        lead.value.activities = [data, ...(lead.value.activities ?? [])]
        activityModal.open = false
    } finally {
        activityModal.saving = false
    }
}

async function deleteActivity(act) {
    await api.delete(`/leads/${lead.value.id}/activities/${act.id}`)
    lead.value.activities = lead.value.activities.filter(a => a.id !== act.id)
}

// ─── Tasks ────────────────────────────────────────────────────────────────────

const taskModal = reactive({ open: false, saving: false })
const taskForm  = reactive({ titulo: '', descricao: '', responsavel: '', prazo: '' })

function openAddTask() {
    Object.assign(taskForm, { titulo: '', descricao: '', responsavel: '', prazo: '' })
    taskModal.open = true
}

async function saveTask() {
    taskModal.saving = true
    try {
        const payload = { ...taskForm, lead_id: lead.value.id }
        if (!payload.prazo) payload.prazo = null
        const { data } = await api.post('/tasks', payload)
        lead.value.tasks = [...(lead.value.tasks ?? []), data]
        taskModal.open = false
    } finally {
        taskModal.saving = false
    }
}

async function toggleTask(task) {
    await api.patch(`/tasks/${task.id}/toggle`)
    task.concluida = !task.concluida
}

async function deleteTask(task) {
    await api.delete(`/tasks/${task.id}`)
    lead.value.tasks = lead.value.tasks.filter(t => t.id !== task.id)
}

onMounted(fetchLead)
</script>

<style scoped>
@reference "tailwindcss";
.label        { @apply block text-xs text-gray-400 mb-1 font-medium; }
.btn-primary  { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>





