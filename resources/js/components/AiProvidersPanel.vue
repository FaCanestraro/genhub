<template>
    <div>
        <div class="mb-6">
            <h1 class="text-xl font-bold text-white">Inteligência Artificial</h1>
            <p class="text-gray-400 text-sm mt-1">Cadastre as chaves de API dos provedores de IA usados para gerar fotos, vídeos, carrosséis e textos.</p>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-20">
            <Loader2 class="w-6 h-6 animate-spin text-gray-500" />
        </div>

        <div v-else class="max-w-2xl space-y-4">
            <div v-for="provider in providers" :key="provider.provider" class="card" :class="{ 'opacity-60': !provider.available }">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                            :style="{ backgroundColor: settingsStore.primaryColor + '22', color: settingsStore.primaryColor }">
                            <Bot class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-white font-medium flex items-center gap-2">
                                {{ provider.label }}
                                <span v-if="!provider.available" class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-700 text-gray-400">Em breve</span>
                                <span v-else-if="provider.has_key" class="text-[10px] px-1.5 py-0.5 rounded-full"
                                    :class="provider.is_active ? 'bg-green-500/15 text-green-400' : 'bg-gray-700 text-gray-400'">
                                    {{ provider.is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ provider.capabilities.map(capabilityLabel).join(' · ') }}</p>
                        </div>
                    </div>

                    <div v-if="provider.available && provider.has_key" class="flex items-center gap-1.5">
                        <button type="button" @click="toggleActive(provider)" class="p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-gray-700 transition-colors" title="Ativar/desativar">
                            <Power class="w-3.5 h-3.5" />
                        </button>
                        <button type="button" @click="removeCredential(provider)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-gray-700 transition-colors" title="Remover chave">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

                <form v-if="provider.available" @submit.prevent="saveKey(provider)" class="mt-4 pt-4 border-t border-gray-800 flex items-end gap-3">
                    <div class="flex-1">
                        <label class="label">{{ fieldLabel(provider) }}</label>
                        <input v-model="forms[provider.provider]" :type="provider.provider === 'comfyui' ? 'text' : 'password'" class="input" :placeholder="fieldPlaceholder(provider)" />
                    </div>
                    <button type="submit" :disabled="!forms[provider.provider] || saving === provider.provider" class="btn-primary px-4 py-2">
                        {{ saving === provider.provider ? 'Salvando...' : 'Salvar' }}
                    </button>
                </form>
                <p v-if="!provider.available" class="mt-4 pt-4 border-t border-gray-800 text-xs text-gray-500">
                    Integração ainda não disponível — em breve você poderá cadastrar uma chave para este provedor.
                </p>

                <p v-if="provider.provider === 'gemini' && !provider.has_key" class="text-xs text-gray-600 mt-2">
                    Sem chave cadastrada, o sistema usa a chave padrão configurada no servidor.
                </p>
                <p v-if="provider.provider === 'comfyui'" class="text-xs text-gray-600 mt-2">
                    Aponte para a instância do ComfyUI rodando na sua máquina (ex: http://127.0.0.1:8188). O servidor precisa conseguir acessar essa URL.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Bot, Loader2, Power, Trash2 } from 'lucide-vue-next'
import api from '@/services/api'
import { useSettingsStore } from '@/stores/settings'
import { useToastStore } from '@/stores/toast'

const settingsStore = useSettingsStore()
const toast = useToastStore()

const providers = ref([])
const loading   = ref(true)
const saving    = ref(null)
const forms     = reactive({})

const CAPABILITY_LABELS = { text: 'Texto', image: 'Imagem', video: 'Vídeo', carousel: 'Carrossel' }
function capabilityLabel(c) { return CAPABILITY_LABELS[c] ?? c }

function fieldLabel(provider) {
    if (provider.provider === 'comfyui') return provider.has_key ? 'Atualizar URL do ComfyUI' : 'URL do ComfyUI'
    return provider.has_key ? 'Rotacionar chave de API' : 'Chave de API'
}

function fieldPlaceholder(provider) {
    if (provider.provider === 'comfyui') return provider.has_key ? '••••••••' : 'http://127.0.0.1:8188'
    return provider.has_key ? '••••••••••••••••' : 'Cole a chave de API aqui'
}

async function loadProviders() {
    loading.value = true
    try {
        const { data } = await api.get('/ai-providers')
        providers.value = data
        data.forEach(p => { forms[p.provider] = '' })
    } finally {
        loading.value = false
    }
}

async function saveKey(provider) {
    saving.value = provider.provider
    try {
        await api.post('/ai-providers', { provider: provider.provider, api_key: forms[provider.provider] })
        forms[provider.provider] = ''
        toast.push('Chave de IA salva com sucesso.', 'success')
        await loadProviders()
    } catch (e) {
        toast.push(e.response?.data?.message ?? 'Erro ao salvar a chave.', 'error')
    } finally {
        saving.value = null
    }
}

async function toggleActive(provider) {
    try {
        await api.patch(`/ai-providers/${provider.id}`, { is_active: !provider.is_active })
        await loadProviders()
    } catch {
        toast.push('Erro ao atualizar o provedor.', 'error')
    }
}

async function removeCredential(provider) {
    if (!confirm(`Remover a chave do provedor "${provider.label}"?`)) return
    try {
        await api.delete(`/ai-providers/${provider.id}`)
        await loadProviders()
    } catch {
        toast.push('Erro ao remover a chave.', 'error')
    }
}

onMounted(loadProviders)
</script>

<style scoped>
@reference "tailwindcss";
.label       { @apply block text-xs text-gray-400 mb-1 font-medium; }
.input       { @apply w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors; }
.btn-primary { @apply disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; background: var(--brand, #7c3aed); }
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
