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
                <h2 class="text-lg font-semibold text-white mb-6">Entrar na conta</h2>

                <form @submit.prevent="handleLogin" class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">E-mail</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors"
                            placeholder="seu@email.com"
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Senha</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors"
                            placeholder="••••••••"
                        />
                    </div>

                    <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium py-2.5 rounded-lg transition-colors"
                    >
                        {{ loading ? 'Entrando...' : 'Entrar' }}
                    </button>
                </form>

                <p class="text-center text-sm text-gray-400 mt-6">
                    Não tem conta?
                    <RouterLink to="/register" class="text-violet-400 hover:text-violet-300">Cadastre-se</RouterLink>
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
const form = ref({ email: '', password: '' })

async function handleLogin() {
    loading.value = true
    error.value = ''
    try {
        await auth.login(form.value.email, form.value.password)
        router.push('/dashboard')
    } catch (e) {
        error.value = e.response?.data?.message || 'Erro ao entrar. Verifique suas credenciais.'
    } finally {
        loading.value = false
    }
}
</script>

