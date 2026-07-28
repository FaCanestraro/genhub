<template>
    <div class="flex flex-col items-center justify-center h-full p-8 text-center">
        <ShieldAlert class="w-12 h-12 text-gray-700 mb-4" />
        <h1 class="text-xl font-bold text-white mb-1">Sem permissão</h1>
        <p class="text-gray-400 text-sm max-w-sm">Você não tem permissão para acessar esta área. Fale com o administrador da sua conta se precisar de acesso.</p>
        <router-link to="/dashboard" class="btn-primary mt-6 inline-block px-4 py-2 rounded-lg text-sm">Voltar ao Dashboard</router-link>
    </div>
</template>

<script setup>
import { ShieldAlert } from 'lucide-vue-next'
</script>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply bg-violet-600 hover:bg-violet-700 text-white font-medium transition-colors; }
</style>
