<template>
    <div class="p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Produtividade</p>
                <h1 class="text-2xl font-bold text-white tracking-tight leading-tight">Tarefas</h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary)">Gerencie suas tarefas e compromissos.</p>
            </div>
            <button @click="openModal()" class="btn-primary flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Nova Tarefa
            </button>
        </div>

        <!-- Tabs + Filtros -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <div class="flex rounded-xl p-1 gap-1" style="background: var(--surface-1); border: 1px solid var(--border-subtle)">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    @click="filters.tab = tab.value; reload()"
                    :class="filters.tab === tab.value ? 'brand-active' : 'text-gray-400 hover:text-white'"
                    class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                <input v-model="filters.search" @input="reload" placeholder="Buscar tarefa..." class="input pl-9 text-sm" />
            </div>
        </div>

        <!-- Tabela -->
        <div class="glass-modal rounded-2xl overflow-hidden">
            <div v-if="loading" class="p-8 text-center">
                <Loader2 class="w-6 h-6 text-violet-400 animate-spin mx-auto" />
            </div>

            <div v-else-if="tasks.length === 0" class="p-16 text-center">
                <CheckSquare class="w-12 h-12 text-gray-700 mx-auto mb-4" />
                <p class="text-gray-400">Nenhuma tarefa encontrada.</p>
                <button @click="openModal()" class="btn-primary mt-4">Criar primeira tarefa</button>
            </div>

            <table v-else class="w-full text-sm">
                <thead class="border-b border-gray-800">
                    <tr>
                        <th class="w-10 px-5 py-3"></th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Tarefa</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Lead</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Prazo</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <tr
                        v-for="task in tasks"
                        :key="task.id"
                        class="hover:bg-gray-800/50 transition-colors group"
                        :class="{ 'opacity-60': task.concluida }"
                    >
                        <td class="px-5 py-3.5">
                            <button @click="toggleTask(task)" class="flex items-center justify-center">
                                <div :class="task.concluida ? 'brand-check' : 'border-gray-600 hover:brand-border'"
                                    class="w-4 h-4 rounded border-2 flex items-center justify-center transition-colors">
                                    <Check v-if="task.concluida" class="w-2.5 h-2.5 text-white" />
                                </div>
                            </button>
                        </td>
                        <td class="px-5 py-3.5">
                            <p :class="task.concluida ? 'line-through text-gray-500' : 'text-white'" class="font-medium">{{ task.titulo }}</p>
                            <p v-if="task.descricao" class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ task.descricao }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <RouterLink
                                v-if="task.lead"
                                :to="`/leads/${task.lead_id}`"
                                class="text-xs text-violet-400 bg-violet-600/10 hover:bg-violet-600/20 px-2 py-0.5 rounded-full transition-colors"
                            >
                                {{ task.lead.nome }}
                            </RouterLink>
                            <span v-else class="text-gray-600">—</span>
                        </td>
                        <td class="px-5 py-3.5 text-gray-400">{{ task.responsavel || '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span v-if="task.prazo" :class="isOverdue(task) ? 'text-red-400' : 'text-gray-400'" class="text-xs">
                                {{ formatDate(task.prazo) }}
                            </span>
                            <span v-else class="text-gray-600">—</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openModal(task)" class="p-1.5 text-gray-500 hover:text-white hover:bg-gray-700 rounded-lg transition-colors">
                                    <Pencil class="w-3.5 h-3.5" />
                                </button>
                                <button @click="deleteTask(task)" class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-gray-700 rounded-lg transition-colors">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Nova/Editar Tarefa -->
        <div v-if="modal.open" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4">
            <div class="glass-modal rounded-2xl w-full max-w-lg">
                <div class="flex items-center justify-between p-6 border-b border-gray-800">
                    <h2 class="font-semibold text-white">{{ modal.task ? 'Editar Tarefa' : 'Nova Tarefa' }}</h2>
                    <button @click="modal.open = false" class="text-gray-500 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="saveTask" class="p-6 space-y-4">
                    <div>
                        <label class="label">Título *</label>
                        <input v-model="form.titulo" required class="input" placeholder="Descreva a tarefa..." />
                    </div>
                    <div>
                        <label class="label">Descrição</label>
                        <textarea v-model="form.descricao" rows="2" class="input resize-none" placeholder="Detalhes adicionais..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label">Lead</label>
                            <select v-model="form.lead_id" class="input">
                                <option :value="null">Sem lead</option>
                                <option v-for="lead in leads" :key="lead.id" :value="lead.id">{{ lead.nome }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Responsável</label>
                            <input v-model="form.responsavel" class="input" placeholder="Nome do responsável" />
                        </div>
                        <div>
                            <label class="label">Prazo</label>
                            <input v-model="form.prazo" type="datetime-local" class="input" />
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="modal.open = false" class="flex-1 py-2.5 border border-gray-700 rounded-xl text-gray-300 hover:bg-gray-800 transition-colors text-sm">Cancelar</button>
                        <button type="submit" :disabled="saving" class="flex-1 btn-primary">{{ saving ? 'Salvando...' : (modal.task ? 'Salvar' : 'Criar Tarefa') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { CheckSquare, Plus, Search, Pencil, Trash2, X, Loader2, Check } from 'lucide-vue-next'
import api from '@/services/api'

const tasks   = ref([])
const leads   = ref([])
const loading = ref(true)
const saving  = ref(false)

const filters = reactive({ tab: 'todas', search: '' })
const modal   = reactive({ open: false, task: null })
const form    = reactive({ titulo: '', descricao: '', lead_id: null, responsavel: '', prazo: '' })

const tabs = [
    { value: 'todas',     label: 'Todas' },
    { value: 'pendentes', label: 'Pendentes' },
    { value: 'atrasadas', label: 'Atrasadas' },
    { value: 'concluidas', label: 'Concluídas' },
]

function formatDate(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function isOverdue(task) {
    return !task.concluida && task.prazo && new Date(task.prazo) < new Date()
}

async function fetchTasks() {
    loading.value = true
    try {
        const params = {}
        if (filters.tab !== 'todas') params.tab = filters.tab
        if (filters.search) params.search = filters.search
        const { data } = await api.get('/tasks', { params })
        tasks.value = data
    } finally {
        loading.value = false
    }
}

async function fetchLeads() {
    const { data } = await api.get('/leads', { params: { page: 1 } })
    leads.value = data.data
}

function reload() { fetchTasks() }

function openModal(task = null) {
    modal.task = task
    Object.assign(form, task
        ? {
            titulo: task.titulo,
            descricao: task.descricao ?? '',
            lead_id: task.lead_id ?? null,
            responsavel: task.responsavel ?? '',
            prazo: task.prazo ? new Date(task.prazo).toISOString().slice(0, 16) : '',
          }
        : { titulo: '', descricao: '', lead_id: null, responsavel: '', prazo: '' }
    )
    modal.open = true
}

async function saveTask() {
    saving.value = true
    try {
        const payload = { ...form }
        if (!payload.prazo) payload.prazo = null
        if (modal.task) {
            await api.put(`/tasks/${modal.task.id}`, payload)
        } else {
            await api.post('/tasks', payload)
        }
        modal.open = false
        fetchTasks()
    } finally {
        saving.value = false
    }
}

async function toggleTask(task) {
    await api.patch(`/tasks/${task.id}/toggle`)
    task.concluida = !task.concluida
}

async function deleteTask(task) {
    if (!confirm(`Excluir a tarefa "${task.titulo}"?`)) return
    await api.delete(`/tasks/${task.id}`)
    fetchTasks()
}

onMounted(() => {
    fetchTasks()
    fetchLeads()
})
</script>

<style scoped>
@reference "tailwindcss";
.input       { @apply w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors text-sm; }
.label       { @apply block text-xs text-gray-400 mb-1 font-medium; }
.btn-primary { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>





