<template>
    <div class="min-h-screen bg-gray-950 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-violet-600 rounded-xl mb-4">
                    <Zap class="w-7 h-7 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-white">Olá, {{ auth.user?.name?.split(' ')[0] }}</h1>
                <p class="text-gray-400 mt-1">{{ subtitle }}</p>
            </div>

            <div class="flex flex-col gap-2 max-h-80 overflow-y-auto pr-1">
                <button
                    v-for="company in companyStore.companies"
                    :key="company.id"
                    @click="enterCompany(company.id)"
                    class="choice-row"
                >
                    <Building2 class="w-5 h-5 text-violet-400 flex-shrink-0" />
                    <span class="min-w-0 text-left">
                        <span class="block text-white font-medium truncate">{{ company.name || '(sem nome cadastrado)' }}</span>
                        <span class="block text-gray-500 text-xs">{{ company.is_owner ? 'Dono da empresa' : (company.role?.name ?? 'Membro da equipe') }}</span>
                    </span>
                </button>
            </div>

            <template v-if="auth.isPlatformAdmin">
                <div class="flex items-center gap-3 my-6">
                    <div class="flex-1 h-px bg-white/10"></div>
                    <span class="text-xs text-gray-600">ou</span>
                    <div class="flex-1 h-px bg-white/10"></div>
                </div>

                <button @click="go('/admin/clients')" class="choice-row">
                    <ShieldCheck class="w-5 h-5 text-violet-400 flex-shrink-0" />
                    <span class="min-w-0 text-left">
                        <span class="block text-white font-medium">Painel Admin</span>
                        <span class="block text-gray-500 text-xs">Visão geral de todos os clientes da plataforma.</span>
                    </span>
                </button>
            </template>

            <button @click="handleLogout" class="w-full text-center text-sm text-gray-500 hover:text-white transition-colors mt-8">
                Sair
            </button>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCompanyStore } from '@/stores/company'
import { Zap, Building2, ShieldCheck } from 'lucide-vue-next'

const router       = useRouter()
const auth         = useAuthStore()
const companyStore = useCompanyStore()

const subtitle = companyStore.hasMultiple
    ? 'Sua conta tem acesso a mais de uma empresa. Onde você quer entrar?'
    : 'Sua conta tem acesso a mais de uma área. Onde você quer entrar?'

function go(path) {
    router.push(path)
}

function enterCompany(companyId) {
    companyStore.select(companyId)
    router.push('/dashboard')
}

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}
</script>

<style scoped>
@reference "tailwindcss";
.choice-row {
    @apply flex items-center gap-3 text-left p-4 rounded-xl transition-colors w-full;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.10);
}
.choice-row:hover {
    background: rgba(124, 58, 237, 0.10);
    border-color: rgba(124, 58, 237, 0.5);
}
</style>
