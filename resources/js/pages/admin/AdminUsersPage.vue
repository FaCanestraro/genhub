<template>
    <div class="p-8 max-w-4xl mx-auto w-full">
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Painel Admin</p>
                <h1 class="page-hero-title text-2xl tracking-tight leading-tight">Usuários Admin</h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary)">Quem tem acesso a este painel — independente de qualquer conta de cliente.</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="openGrantModal" class="btn-ghost flex items-center gap-2">
                    <UserPlus class="w-4 h-4" />
                    Conceder a Cliente
                </button>
                <button @click="openModal" class="btn-primary flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    Novo Admin
                </button>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-20">
            <Loader2 class="w-6 h-6 animate-spin text-gray-500" />
        </div>

        <div v-else class="card !p-0 overflow-hidden">
            <div v-for="u in admins" :key="u.id" class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-gray-800 last:border-b-0">
                <div class="min-w-0">
                    <p class="text-sm text-white truncate flex items-center gap-1.5">
                        {{ u.name }}
                        <span v-if="u.id === auth.user?.id" class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-700 text-gray-400">Você</span>
                        <span v-if="u.is_client" class="text-[10px] px-1.5 py-0.5 rounded-full bg-violet-500/15 text-violet-300">Também é cliente</span>
                    </p>
                    <p class="text-xs text-gray-500 truncate">{{ u.email }} · desde {{ formatDate(u.created_at) }}</p>
                </div>
                <button
                    v-if="u.id !== auth.user?.id"
                    @click="revoke(u)"
                    class="text-xs px-2.5 py-1.5 rounded-lg text-red-400 hover:bg-red-500/10 transition-colors flex-shrink-0"
                >
                    Remover acesso
                </button>
            </div>
            <div v-if="!admins.length" class="py-16 text-center text-gray-500 text-sm">Nenhum admin cadastrado.</div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showModal = false">
                <div class="glass-modal rounded-2xl w-full max-w-md p-6">
                    <h2 class="text-lg font-semibold text-white mb-5">Novo Admin</h2>

                    <form @submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Nome *</label>
                            <input v-model="form.name" type="text" required class="input" placeholder="Nome do admin" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">E-mail *</label>
                            <input v-model="form.email" type="email" required class="input" placeholder="email@exemplo.com" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Senha *</label>
                            <input v-model="form.password" type="password" required minlength="8" class="input" placeholder="Mínimo 8 caracteres" />
                        </div>

                        <p v-if="error" class="text-sm text-red-400">{{ error }}</p>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn-ghost">Cancelar</button>
                            <button type="submit" :disabled="saving" class="btn-primary">
                                {{ saving ? 'Salvando...' : 'Criar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal: conceder a cliente existente -->
        <Teleport to="body">
            <div v-if="showGrantModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showGrantModal = false">
                <div class="glass-modal rounded-2xl w-full max-w-md p-6">
                    <h2 class="text-lg font-semibold text-white mb-2">Conceder acesso a cliente existente</h2>
                    <p class="text-sm text-gray-500 mb-5">A conta continua sendo cliente normalmente — só ganha acesso extra ao painel admin.</p>

                    <form @submit.prevent="grant" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">E-mail do cliente *</label>
                            <input v-model="grantEmail" type="email" required class="input" placeholder="email@cliente.com" />
                        </div>

                        <p v-if="grantError" class="text-sm text-red-400">{{ grantError }}</p>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showGrantModal = false" class="btn-ghost">Cancelar</button>
                            <button type="submit" :disabled="granting" class="btn-primary">
                                {{ granting ? 'Concedendo...' : 'Conceder acesso' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Plus, Loader2, UserPlus } from 'lucide-vue-next'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const auth  = useAuthStore()
const toast = useToastStore()

const admins    = ref([])
const loading   = ref(true)
const showModal = ref(false)
const saving    = ref(false)
const error     = ref('')

const showGrantModal = ref(false)
const granting       = ref(false)
const grantError     = ref('')
const grantEmail     = ref('')

const form = reactive({ name: '', email: '', password: '' })

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('pt-BR') : ''
}

async function fetchAdmins() {
    loading.value = true
    try {
        const { data } = await api.get('/admin/users')
        admins.value = data
    } finally {
        loading.value = false
    }
}

function openModal() {
    form.name = ''
    form.email = ''
    form.password = ''
    error.value = ''
    showModal.value = true
}

async function save() {
    saving.value = true
    error.value = ''
    try {
        await api.post('/admin/users', form)
        showModal.value = false
        fetchAdmins()
    } catch (e) {
        error.value = e.response?.data?.message
            ?? e.response?.data?.errors?.email?.[0]
            ?? 'Erro ao criar admin.'
    } finally {
        saving.value = false
    }
}

function openGrantModal() {
    grantEmail.value = ''
    grantError.value = ''
    showGrantModal.value = true
}

async function grant() {
    granting.value = true
    grantError.value = ''
    try {
        await api.post('/admin/users/grant', { email: grantEmail.value })
        showGrantModal.value = false
        fetchAdmins()
        toast.push('Acesso admin concedido.', 'success')
    } catch (e) {
        grantError.value = e.response?.data?.message ?? 'Erro ao conceder acesso.'
    } finally {
        granting.value = false
    }
}

async function revoke(u) {
    if (!confirm(`Remover o acesso admin de "${u.name}"?`)) return
    try {
        await api.delete(`/admin/users/${u.id}`)
        fetchAdmins()
    } catch (e) {
        toast.push(e.response?.data?.message ?? 'Erro ao remover acesso.', 'error')
    }
}

onMounted(fetchAdmins)
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
.input       { @apply w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors; }
.btn-primary { @apply disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; background: var(--brand, #7c3aed); }
.btn-ghost   { @apply text-gray-400 hover:text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; background: transparent; }
.btn-ghost:hover { background: rgba(255, 255, 255, 0.055); }
</style>
