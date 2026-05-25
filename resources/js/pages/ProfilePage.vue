<template>
    <div class="p-8 max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-white mb-1">Perfil</h1>
        <p class="text-gray-400 mb-8">Gerencie seus dados e informações da empresa</p>

        <form @submit.prevent="save" class="space-y-6">
            <div class="glass-panel rounded-2xl p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-300 uppercase tracking-wide">Dados pessoais</h2>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nome *</label>
                    <input v-model="form.name" type="text" required class="input" placeholder="Seu nome" />
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">E-mail</label>
                    <input :value="auth.user?.email" type="email" disabled class="input opacity-50 cursor-not-allowed" />
                    <p class="text-xs text-gray-600 mt-1">O e-mail não pode ser alterado por aqui.</p>
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">Telefone</label>
                    <input
                        :value="form.phone"
                        @input="e => onPhoneInput(e, v => form.phone = v)"
                        type="text"
                        inputmode="numeric"
                        class="input"
                        placeholder="(00) 00000-0000"
                        maxlength="15"
                    />
                </div>
            </div>

            <div class="glass-panel rounded-2xl p-6 space-y-4">
                <h2 class="text-sm font-semibold text-gray-300 uppercase tracking-wide">Empresa</h2>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nome da empresa</label>
                    <input v-model="form.company_name" type="text" class="input" placeholder="Razão social ou nome fantasia" />
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">CNPJ</label>
                    <input
                        :value="form.cnpj"
                        @input="e => onCNPJInput(e, v => form.cnpj = v)"
                        type="text"
                        inputmode="numeric"
                        class="input"
                        placeholder="00.000.000/0000-00"
                        maxlength="18"
                    />
                </div>
            </div>

            <div class="flex items-center justify-between">
                <p v-if="saved" class="text-sm text-green-400">Salvo com sucesso!</p>
                <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
                <div class="ml-auto">
                    <button type="submit" :disabled="saving" class="btn-primary">
                        {{ saving ? 'Salvando...' : 'Salvar alterações' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { onCNPJInput, onPhoneInput, maskCNPJ, maskPhone } from '@/utils/mask'

const auth = useAuthStore()
const saving = ref(false)
const saved = ref(false)
const error = ref('')

const form = ref({ name: '', company_name: '', cnpj: '', phone: '' })

onMounted(() => {
    const u = auth.user
    form.value = {
        name: u?.name || '',
        company_name: u?.company_name || '',
        cnpj: u?.cnpj ? maskCNPJ(u.cnpj.replace(/\D/g, '')) : '',
        phone: u?.phone ? maskPhone(u.phone.replace(/\D/g, '')) : '',
    }
})

async function save() {
    saving.value = true
    saved.value = false
    error.value = ''
    try {
        const { data } = await api.put('/auth/profile', form.value)
        auth.user = data
        saved.value = true
        setTimeout(() => (saved.value = false), 3000)
    } catch (e) {
        error.value = 'Erro ao salvar. Tente novamente.'
    } finally {
        saving.value = false
    }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors text-sm; }
.btn-primary { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>

