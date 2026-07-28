<template>
    <div class="p-8 max-w-2xl mx-auto">
        <h1 class="page-hero-title text-2xl mb-1">Meu Usuário</h1>
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

                <div class="flex items-center gap-4">
                    <button type="submit" :disabled="saving" class="btn-primary">
                        {{ saving ? 'Salvando...' : 'Salvar alterações' }}
                    </button>
                    <Transition name="fade">
                        <span v-if="saved" class="text-sm text-green-400 flex items-center gap-1.5">
                            <CheckCircle class="w-4 h-4" /> Salvo com sucesso!
                        </span>
                    </Transition>
                    <span v-if="error" class="text-sm text-red-400">{{ error }}</span>
                </div>
            </div>
        </form>

        <div class="glass-panel rounded-2xl p-6 space-y-4 mt-6">
            <h2 class="text-sm font-semibold text-gray-300 uppercase tracking-wide">Alterar Senha</h2>

            <form @submit.prevent="changePassword" class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Senha atual *</label>
                    <div class="relative">
                        <input v-model="pwForm.current_password" :type="showPw.current ? 'text' : 'password'"
                            required class="input pr-10" placeholder="••••••••" />
                        <button type="button" @click="showPw.current = !showPw.current"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <Eye v-if="!showPw.current" class="w-4 h-4" /><EyeOff v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nova senha *</label>
                    <div class="relative">
                        <input v-model="pwForm.password" :type="showPw.password ? 'text' : 'password'"
                            required minlength="8" class="input pr-10" placeholder="Mínimo 8 caracteres" />
                        <button type="button" @click="showPw.password = !showPw.password"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <Eye v-if="!showPw.password" class="w-4 h-4" /><EyeOff v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="flex gap-1 mt-2">
                        <div v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-colors"
                            :class="pwStrength >= i ? strengthColor : 'bg-gray-700'"></div>
                    </div>
                    <p class="text-xs mt-1" :class="pwStrength > 0 ? strengthTextColor : 'text-gray-600'">{{ strengthLabel }}</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Confirmar nova senha *</label>
                    <div class="relative">
                        <input v-model="pwForm.password_confirmation" :type="showPw.confirm ? 'text' : 'password'"
                            required class="input pr-10" placeholder="Repita a nova senha" />
                        <button type="button" @click="showPw.confirm = !showPw.confirm"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <Eye v-if="!showPw.confirm" class="w-4 h-4" /><EyeOff v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="pwForm.password_confirmation && pwForm.password !== pwForm.password_confirmation"
                        class="text-xs text-red-400 mt-1">Senhas não coincidem.</p>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" :disabled="savingPw || pwForm.password !== pwForm.password_confirmation" class="btn-primary">
                        {{ savingPw ? 'Alterando...' : 'Alterar senha' }}
                    </button>
                    <Transition name="fade">
                        <span v-if="savedPw" class="text-sm text-green-400 flex items-center gap-1.5">
                            <CheckCircle class="w-4 h-4" /> Senha alterada!
                        </span>
                    </Transition>
                    <span v-if="errorPw" class="text-sm text-red-400">{{ errorPw }}</span>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { onPhoneInput, maskPhone } from '@/utils/mask'
import { Eye, EyeOff, CheckCircle } from 'lucide-vue-next'

const auth = useAuthStore()
const saving = ref(false)
const saved = ref(false)
const error = ref('')

const form = ref({ name: '', phone: '' })

onMounted(() => {
    const u = auth.user
    form.value = {
        name: u?.name || '',
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

// ─── Alterar Senha ─────────────────────────────────────────────────────────────

const savingPw = ref(false)
const savedPw = ref(false)
const errorPw = ref('')
const pwForm = reactive({ current_password: '', password: '', password_confirmation: '' })
const showPw = reactive({ current: false, password: false, confirm: false })

const pwStrength = computed(() => {
    const p = pwForm.password; if (!p) return 0
    let s = 0
    if (p.length >= 8) s++
    if (/[A-Z]/.test(p)) s++
    if (/[0-9]/.test(p)) s++
    if (/[^A-Za-z0-9]/.test(p)) s++
    return s
})
const strengthColor = computed(() => ['', 'bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'][pwStrength.value])
const strengthTextColor = computed(() => ['', 'text-red-400', 'text-orange-400', 'text-yellow-400', 'text-green-400'][pwStrength.value])
const strengthLabel = computed(() => ['', 'Fraca', 'Razoável', 'Boa', 'Forte'][pwStrength.value])

async function changePassword() {
    savingPw.value = true
    errorPw.value = ''
    try {
        await api.put('/auth/password', pwForm)
        savedPw.value = true
        Object.assign(pwForm, { current_password: '', password: '', password_confirmation: '' })
        setTimeout(() => (savedPw.value = false), 3000)
    } catch (e) {
        errorPw.value = e.response?.data?.message ?? 'Erro ao alterar senha.'
    } finally {
        savingPw.value = false
    }
}
</script>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>

