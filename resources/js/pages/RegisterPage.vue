<template>
    <div class="min-h-screen bg-gray-950 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-violet-600 rounded-xl mb-4">
                    <Zap class="w-7 h-7 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-white">GenHub</h1>
                <p class="text-gray-400 mt-1">Geração de conteúdo com IA</p>
            </div>

            <div class="glass-modal rounded-2xl p-8">
                <h2 class="text-lg font-semibold text-white mb-6">Criar conta</h2>

                <form @submit.prevent="handleRegister" class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Nome</label>
                        <input v-model="form.name" type="text" required class="input" placeholder="Seu nome" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Empresa (opcional)</label>
                        <input v-model="form.company_name" type="text" class="input" placeholder="Nome da empresa" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">E-mail</label>
                        <input v-model="form.email" type="email" required class="input" placeholder="seu@email.com" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Senha</label>
                        <input v-model="form.password" type="password" required class="input" placeholder="Mínimo 8 caracteres" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Confirmar senha</label>
                        <input v-model="form.password_confirmation" type="password" required class="input" placeholder="Repita a senha" />
                    </div>

                    <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>

                    <button type="submit" :disabled="loading" class="w-full bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium py-2.5 rounded-lg transition-colors">
                        {{ loading ? 'Cadastrando...' : 'Criar conta' }}
                    </button>
                </form>

                <p class="text-center text-sm text-gray-400 mt-6">
                    Já tem conta?
                    <RouterLink to="/login" class="text-violet-400 hover:text-violet-300">Entrar</RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Zap } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)
const error = ref('')
const form = ref({ name: '', email: '', company_name: '', password: '', password_confirmation: '' })

async function handleRegister() {
    loading.value = true
    error.value = ''
    try {
        await auth.register(form.value)
        router.push('/dashboard')
    } catch (e) {
        const errors = e.response?.data?.errors
        error.value = errors ? Object.values(errors).flat()[0] : 'Erro ao cadastrar.'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
@reference "tailwindcss";
</style>

