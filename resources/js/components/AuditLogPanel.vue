<template>
    <div>
        <div class="mb-6">
            <h1 class="text-xl font-bold text-white">Log de Auditoria</h1>
            <p class="text-gray-400 text-sm mt-1">Histórico de ações realizadas na conta: o que foi feito, por quem e o resultado.</p>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap gap-3 mb-4">
            <select v-model="filters.area" @change="reload" class="input filter-field">
                <option value="">Todas as áreas</option>
                <option v-for="opt in areaOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <select v-model="filters.status" @change="reload" class="input filter-field filter-field--sm">
                <option value="">Todos os status</option>
                <option value="success">Sucesso</option>
                <option value="failed">Falhou</option>
            </select>
            <input v-model="filters.date_from" @change="reload" type="date" class="input filter-field filter-field--sm" />
            <input v-model="filters.date_to" @change="reload" type="date" class="input filter-field filter-field--sm" />
        </div>

        <div class="card !p-0 overflow-hidden">
            <div v-if="loading" class="flex items-center justify-center py-20">
                <Loader2 class="w-6 h-6 animate-spin text-gray-500" />
            </div>

            <div v-else-if="!logs.length" class="py-16 text-center">
                <ScrollText class="w-10 h-10 text-gray-700 mx-auto mb-3" />
                <p class="text-gray-400 text-sm">Nenhuma ação registrada com esses filtros.</p>
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-800/50 text-gray-400 text-xs uppercase tracking-wide">
                        <th class="text-left font-medium px-4 py-2.5">Quando</th>
                        <th class="text-left font-medium px-4 py-2.5">Descrição</th>
                        <th class="text-left font-medium px-4 py-2.5">Área</th>
                        <th class="text-left font-medium px-4 py-2.5">Autor</th>
                        <th class="text-left font-medium px-4 py-2.5">Duração</th>
                        <th class="text-left font-medium px-4 py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs" :key="log.id" class="border-t border-gray-800">
                        <td class="px-4 py-2.5 text-gray-500 text-xs whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                        <td class="px-4 py-2.5 text-gray-200">
                            {{ log.description }}
                            <span v-if="log.ai_model" class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full bg-violet-500/15 text-violet-300">{{ log.ai_model }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-400 text-xs">{{ areaLabel(log.area) }}</td>
                        <td class="px-4 py-2.5 text-gray-400 text-xs">{{ log.causer?.name ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ formatDuration(log.duration_ms) }}</td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
                                :style="log.status === 'failed'
                                    ? { background: 'rgba(248,113,113,0.12)', color: '#f87171' }
                                    : { background: 'rgba(74,222,128,0.12)', color: '#4ade80' }">
                                {{ log.status === 'failed' ? 'Falhou' : 'Sucesso' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginação -->
            <div v-if="lastPage > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-800">
                <p class="text-xs text-gray-500">Página {{ page }} de {{ lastPage }}</p>
                <div class="flex gap-2">
                    <button @click="changePage(page - 1)" :disabled="page <= 1" class="px-3 py-1 text-xs bg-gray-800 rounded-lg disabled:opacity-40 hover:bg-gray-700 transition-colors">Anterior</button>
                    <button @click="changePage(page + 1)" :disabled="page >= lastPage" class="px-3 py-1 text-xs bg-gray-800 rounded-lg disabled:opacity-40 hover:bg-gray-700 transition-colors">Próximo</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Loader2, ScrollText } from 'lucide-vue-next'
import api from '@/services/api'

const logs     = ref([])
const loading  = ref(true)
const page     = ref(1)
const lastPage = ref(1)

const filters = reactive({ area: '', status: '', date_from: '', date_to: '' })

const areaOptions = [
    { value: 'auth', label: 'Autenticação' },
    { value: 'settings', label: 'Configurações' },
    { value: 'ai_providers', label: 'Inteligência Artificial' },
    { value: 'generate', label: 'Geração de IA' },
    { value: 'campaigns', label: 'Campanhas' },
    { value: 'products', label: 'Produtos' },
]

function areaLabel(v) { return areaOptions.find(a => a.value === v)?.label ?? v }

function formatDate(d) {
    return d ? new Date(d).toLocaleString('pt-BR') : ''
}

function formatDuration(ms) {
    if (!ms) return '—'
    return ms < 1000 ? `${ms}ms` : `${(ms / 1000).toFixed(1)}s`
}

async function fetchLogs() {
    loading.value = true
    try {
        const params = { page: page.value }
        if (filters.area) params.area = filters.area
        if (filters.status) params.status = filters.status
        if (filters.date_from) params.date_from = filters.date_from
        if (filters.date_to) params.date_to = filters.date_to

        const { data } = await api.get('/audit-logs', { params })
        logs.value     = data.data
        lastPage.value = data.last_page
    } finally {
        loading.value = false
    }
}

function reload() { page.value = 1; fetchLogs() }
function changePage(p) { page.value = p; fetchLogs() }

onMounted(fetchLogs)
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors; }
.filter-field      { width: 200px; flex: 0 0 auto; }
.filter-field--sm  { width: 150px; }
.card {
    @apply rounded-2xl p-6;
    background: rgba(14, 12, 22, 0.68);
    backdrop-filter: blur(28px) saturate(180%);
    -webkit-backdrop-filter: blur(28px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.07);
    box-shadow:
        inset 0 1px 0   rgba(255, 255, 255, 0.10),
        inset 0 -1px 0  rgba(0,   0,   0,   0.25),
        0 0 0 0.5px     rgba(255, 255, 255, 0.04),
        0 12px 48px     rgba(0,   0,   0,   0.55);
}
</style>
