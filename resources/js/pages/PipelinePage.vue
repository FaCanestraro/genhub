<template>
    <div class="p-8 h-full flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 flex-shrink-0">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <KanbanSquare class="w-6 h-6 text-violet-400" />
                    Pipeline
                </h1>
                <p class="text-gray-400 mt-1">Arraste os cards para mover entre etapas.</p>
            </div>
            <button @click="openModal()" class="btn-primary flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Novo Lead
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex-1 flex items-center justify-center">
            <Loader2 class="w-6 h-6 text-violet-400 animate-spin" />
        </div>

        <!-- Kanban Board -->
        <div v-else class="flex gap-4 flex-1 overflow-x-auto pb-4">
            <div
                v-for="col in columns"
                :key="col.value"
                class="flex-shrink-0 w-72 flex flex-col rounded-2xl transition-colors"
                :class="dragOver === col.value ? 'bg-gray-800/60' : 'bg-gray-900/40'"
                @dragover.prevent="dragOver = col.value"
                @dragleave="onDragLeave(col.value)"
                @drop.prevent="onDrop(col.value)"
            >
                <!-- Column Header -->
                <div class="flex items-center justify-between mb-3 px-3 pt-3">
                    <div class="flex items-center gap-2">
                        <span :class="col.dot" class="w-2 h-2 rounded-full flex-shrink-0"></span>
                        <span class="text-sm font-medium text-gray-300">{{ col.label }}</span>
                    </div>
                    <span class="text-xs text-gray-500 bg-gray-800 px-2 py-0.5 rounded-full">
                        {{ pipeline[col.value]?.length ?? 0 }}
                    </span>
                </div>

                <!-- Drop zone indicator -->
                <div
                    v-if="dragOver === col.value && dragging?.status !== col.value"
                    class="mx-3 mb-2 h-1 rounded-full bg-violet-500/60"
                ></div>

                <!-- Cards -->
                <div class="flex-1 space-y-2 overflow-y-auto px-3 pb-3 max-h-[calc(100vh-220px)]">
                    <div
                        v-for="lead in (pipeline[col.value] ?? [])"
                        :key="lead.id"
                        draggable="true"
                        class="bg-gray-900 border border-gray-800 rounded-xl p-4 cursor-grab active:cursor-grabbing hover:border-gray-700 transition-all group select-none"
                        :class="dragging?.id === lead.id ? 'opacity-40 scale-95' : 'opacity-100'"
                        @dragstart="onDragStart($event, lead)"
                        @dragend="onDragEnd"
                        @click="dragging ? null : router.push(`/leads/${lead.id}`)"
                    >
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-7 h-7 rounded-full bg-violet-600/20 flex items-center justify-center text-violet-400 font-semibold text-xs flex-shrink-0">
                                    {{ lead.nome.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-medium text-white text-sm truncate">{{ lead.nome }}</span>
                            </div>
                            <button
                                @click.stop="deleteLead(lead)"
                                class="p-1 text-gray-600 hover:text-red-400 rounded opacity-0 group-hover:opacity-100 transition-all flex-shrink-0"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <div v-if="lead.email" class="text-xs text-gray-500 mb-0.5 truncate">{{ lead.email }}</div>
                        <div v-if="lead.telefone" class="text-xs text-gray-500 mb-2">{{ lead.telefone }}</div>

                        <div class="flex items-center gap-2 flex-wrap mt-2">
                            <span class="text-xs text-gray-600 bg-gray-800 px-2 py-0.5 rounded-full capitalize">{{ fonteLabel(lead.fonte) }}</span>
                            <span v-if="lead.responsavel" class="text-xs text-gray-600 truncate">· {{ lead.responsavel }}</span>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="!pipeline[col.value]?.length"
                        class="border-2 border-dashed rounded-xl p-6 text-center transition-colors"
                        :class="dragOver === col.value ? 'border-violet-500/40 bg-violet-500/5' : 'border-gray-800'"
                    >
                        <p class="text-xs text-gray-600">
                            {{ dragOver === col.value ? 'Soltar aqui' : 'Nenhum lead' }}
                        </p>
                    </div>
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
                                <option v-for="s in columns" :key="s.value" :value="s.value">{{ s.label }}</option>
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
import { KanbanSquare, Plus, Trash2, X, Loader2 } from 'lucide-vue-next'
import api from '@/services/api'

const router = useRouter()

const pipeline = ref({})
const loading  = ref(true)
const saving   = ref(false)

// Drag state
const dragging = ref(null)   // the lead object being dragged
const dragOver = ref(null)   // column value currently hovered

const modal = reactive({ open: false, lead: null })
const form  = reactive({ nome: '', email: '', telefone: '', status: 'novo_lead', fonte: 'manual', local: '', responsavel: '', notas: '' })

const columns = [
    { value: 'novo_lead',     label: 'Novo Lead',     dot: 'bg-blue-400' },
    { value: 'contato_feito', label: 'Contato Feito', dot: 'bg-cyan-400' },
    { value: 'qualificacao',  label: 'Qualificação',  dot: 'bg-yellow-400' },
    { value: 'cotacao',       label: 'Cotação',       dot: 'bg-orange-400' },
    { value: 'negociacao',    label: 'Negociação',    dot: 'bg-purple-400' },
    { value: 'fechado',       label: 'Fechado',       dot: 'bg-green-400' },
]

const fontes = [
    { value: 'site',      label: 'Site' },
    { value: 'social',    label: 'Social' },
    { value: 'email',     label: 'Email' },
    { value: 'indicacao', label: 'Indicação' },
    { value: 'manual',    label: 'Manual' },
    { value: 'api',       label: 'API' },
]

function fonteLabel(v) { return fontes.find(f => f.value === v)?.label ?? v }

// ─── Drag & Drop ────────────────────────────────────────────────────────────

function onDragStart(event, lead) {
    dragging.value = lead
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', lead.id)
}

function onDragEnd() {
    dragging.value = null
    dragOver.value = null
}

function onDragLeave(colValue) {
    if (dragOver.value === colValue) dragOver.value = null
}

async function onDrop(targetStatus) {
    const lead = dragging.value
    dragging.value = null
    dragOver.value = null

    if (!lead || lead.status === targetStatus) return

    // Optimistic update — move card in local state immediately
    const fromCol = pipeline.value[lead.status] ?? []
    pipeline.value[lead.status] = fromCol.filter(l => l.id !== lead.id)

    const prevStatus = lead.status
    lead.status = targetStatus
    pipeline.value[targetStatus] = [lead, ...(pipeline.value[targetStatus] ?? [])]

    try {
        await api.put(`/leads/${lead.id}`, { status: targetStatus })
    } catch {
        // Rollback on failure
        pipeline.value[targetStatus] = (pipeline.value[targetStatus] ?? []).filter(l => l.id !== lead.id)
        lead.status = prevStatus
        pipeline.value[prevStatus] = [lead, ...(pipeline.value[prevStatus] ?? [])]
    }
}

// ─── Data ────────────────────────────────────────────────────────────────────

async function fetchPipeline() {
    loading.value = true
    try {
        const { data } = await api.get('/leads/pipeline')
        pipeline.value = data
    } finally {
        loading.value = false
    }
}

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
        fetchPipeline()
    } finally {
        saving.value = false
    }
}

async function deleteLead(lead) {
    if (!confirm(`Excluir o lead "${lead.nome}"?`)) return
    await api.delete(`/leads/${lead.id}`)
    fetchPipeline()
}

onMounted(fetchPipeline)
</script>

<style scoped>
@reference "tailwindcss";
.label      { @apply block text-xs text-gray-400 mb-1 font-medium; }
.btn-primary { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>





