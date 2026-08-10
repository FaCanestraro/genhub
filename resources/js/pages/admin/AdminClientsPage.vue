<template>
    <div class="p-8 max-w-7xl mx-auto w-full">
        <div class="mb-8">
            <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Painel Admin</p>
            <h1 class="page-hero-title text-2xl tracking-tight leading-tight">Clientes</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary)">Visão geral das empresas usando a plataforma, uso de IA e mensalidade.</p>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-20">
            <Loader2 class="w-6 h-6 animate-spin text-gray-500" />
        </div>

        <div v-else-if="!clients.length" class="text-center py-20">
            <Building2 class="w-12 h-12 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400">Nenhum cliente cadastrado ainda.</p>
        </div>

        <div v-else class="card !p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-800/50 text-gray-400 text-xs uppercase tracking-wide">
                            <th class="text-left font-medium px-4 py-2.5">Empresa</th>
                            <th class="text-left font-medium px-4 py-2.5">Membros</th>
                            <th class="text-left font-medium px-4 py-2.5">Campanhas</th>
                            <th class="text-left font-medium px-4 py-2.5">Gerações (texto / imagem / vídeo)</th>
                            <th class="text-left font-medium px-4 py-2.5">Custo estimado de IA</th>
                            <th class="text-left font-medium px-4 py-2.5">Mensalidade</th>
                            <th class="text-left font-medium px-4 py-2.5">Desde</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in clients" :key="c.id" class="border-t border-gray-800">
                            <td class="px-4 py-3">
                                <p class="text-white font-medium">{{ c.company_name || '(sem nome de empresa cadastrado)' }}</p>
                                <p class="text-xs text-gray-500">{{ c.company_cnpj }} · {{ c.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-300">{{ c.members_count }}</td>
                            <td class="px-4 py-3 text-gray-300">{{ c.campaigns_count }}</td>
                            <td class="px-4 py-3 text-gray-300">{{ c.usage.text }} / {{ c.usage.image }} / {{ c.usage.video }}</td>
                            <td class="px-4 py-3 text-gray-300">{{ formatMoney(c.estimated_ai_cost, 'USD') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="fees[c.id]"
                                        type="number" min="0" step="0.01"
                                        class="input w-28 !py-1.5"
                                        placeholder="0,00"
                                    />
                                    <button
                                        v-if="fees[c.id] != (c.monthly_fee ?? '')"
                                        @click="saveFee(c)"
                                        :disabled="savingId === c.id"
                                        class="text-xs px-2.5 py-1.5 rounded-lg font-medium transition-colors"
                                        :style="{ backgroundColor: 'var(--brand)', color: '#fff' }"
                                    >
                                        {{ savingId === c.id ? '...' : 'Salvar' }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ formatDate(c.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Loader2, Building2 } from 'lucide-vue-next'
import api from '@/services/api'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()

const clients  = ref([])
const loading  = ref(true)
const fees     = reactive({})
const savingId = ref(null)

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('pt-BR') : ''
}

function formatMoney(v, currency) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency }).format(v ?? 0)
}

async function fetchClients() {
    loading.value = true
    try {
        const { data } = await api.get('/admin/clients')
        clients.value = data
        data.forEach(c => { fees[c.id] = c.monthly_fee ?? '' })
    } finally {
        loading.value = false
    }
}

async function saveFee(client) {
    savingId.value = client.id
    try {
        const { data } = await api.patch(`/admin/clients/${client.id}`, { monthly_fee: fees[client.id] || null })
        client.monthly_fee = data.monthly_fee
        toast.push('Mensalidade atualizada.', 'success')
    } catch {
        toast.push('Erro ao atualizar mensalidade.', 'error')
    } finally {
        savingId.value = null
    }
}

onMounted(fetchClients)
</script>

<style scoped>
@reference "tailwindcss";
.card {
    @apply rounded-2xl;
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
.input { @apply bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors; }
</style>
