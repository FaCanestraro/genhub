<template>
    <div>
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-white">Redes Sociais</h1>
                <p class="text-gray-400 text-sm mt-1">Conecte as Páginas do Facebook e os Instagrams da empresa para publicar os conteúdos gerados.</p>
            </div>
            <button v-if="auth.can('social', 'create')" type="button" @click="connect" :disabled="connecting" class="btn-primary flex items-center gap-2 flex-shrink-0">
                <Loader2 v-if="connecting" class="w-4 h-4 animate-spin" />
                <Plus v-else class="w-4 h-4" />
                Conectar Facebook / Instagram
            </button>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-20">
            <Loader2 class="w-6 h-6 animate-spin text-gray-500" />
        </div>

        <div v-else class="max-w-2xl space-y-4">
            <div v-if="!accounts.length" class="card py-12 text-center">
                <Share2 class="w-10 h-10 text-gray-700 mx-auto mb-3" />
                <p class="text-white font-medium mb-1">Nenhuma conta conectada</p>
                <p class="text-gray-500 text-sm">Clique em "Conectar" e autorize as Páginas que deseja usar.</p>
            </div>

            <div v-else class="card space-y-2">
                <div v-for="account in accounts" :key="account.id" class="flex items-center justify-between gap-3 py-2.5 px-3 rounded-lg bg-gray-800/50">
                    <div class="flex items-center gap-3 min-w-0">
                        <img v-if="account.avatar_url" :src="account.avatar_url" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
                        <div v-else class="w-9 h-9 rounded-full bg-gray-700 flex-shrink-0" />
                        <div class="min-w-0">
                            <p class="text-sm text-white truncate">{{ account.name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ account.username ? '@' + account.username : 'Página' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <PlatformIcon :platform="account.provider" />
                        <button v-if="auth.can('social', 'delete')" type="button" @click="disconnect(account)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-gray-700 transition-colors" title="Desconectar">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="text-xs text-gray-500 space-y-1 px-1">
                <p>• O Instagram precisa ser uma conta <b class="text-gray-400">Profissional</b> (Empresa ou Criador) vinculada a uma Página do Facebook.</p>
                <p>• No Facebook a publicação vai para a <b class="text-gray-400">Página</b>; perfis pessoais não aceitam publicação via API.</p>
                <p>• Adicionou uma Página nova? Clique em "Conectar" de novo para atualizar a lista.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Loader2, Plus, Share2, Trash2 } from 'lucide-vue-next'
import api from '@/services/api'
import PlatformIcon from '@/components/PlatformIcon.vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const auth   = useAuthStore()
const toast  = useToastStore()
const route  = useRoute()
const router = useRouter()

const accounts   = ref([])
const loading    = ref(true)
const connecting = ref(false)

async function loadAccounts() {
    loading.value = true
    try {
        accounts.value = (await api.get('/social-accounts')).data
    } finally {
        loading.value = false
    }
}

async function connect() {
    connecting.value = true
    try {
        window.location.href = (await api.get('/social-accounts/connect-url')).data.url
    } catch {
        connecting.value = false
    }
}

// A Meta devolve para /settings?code=...&state=... — troca o code aqui, autenticado,
// para o backend conferir que foi este usuário/empresa que iniciou a conexão.
async function finishOAuth() {
    const { code, state, error_description } = route.query
    if (!code && !error_description) return

    router.replace({ query: { section: 'sociais' } })
    if (error_description) {
        toast.push(`Conexão cancelada: ${error_description}`, 'error')
        return
    }

    connecting.value = true
    try {
        const { data } = await api.post('/social-accounts/callback', { code, state })
        toast.push(`${data.connected} conta(s) conectada(s).`, 'success')
    } catch (e) {
        if (e.response?.status === 422) toast.push(e.response.data.message, 'error')
    } finally {
        connecting.value = false
    }
}

async function disconnect(account) {
    if (!confirm(`Desconectar "${account.name}"?`)) return
    await api.delete(`/social-accounts/${account.id}`)
    accounts.value = accounts.value.filter(a => a.id !== account.id)
}

onMounted(async () => {
    await finishOAuth()
    await loadAccounts()
})
</script>

<style scoped>
@reference "tailwindcss";
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
