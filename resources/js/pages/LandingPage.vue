<template>
    <div class="min-h-screen text-gray-100" style="background: var(--bg-base)">

        <!-- Header -->
        <header class="sticky top-0 z-20 glass-panel border-b" style="border-color: var(--border-subtle)">
            <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
                <RouterLink to="/"><img src="/creatiq-logo.png" alt="CREATIQ" class="h-10 w-auto" /></RouterLink>
                <RouterLink to="/login" class="btn-primary px-5 py-2 rounded-lg text-sm font-medium">Entrar</RouterLink>
            </div>
        </header>

        <!-- Hero -->
        <section class="max-w-4xl mx-auto px-4 pt-24 pb-20 text-center">
            <p class="tech-label mb-4">AI Engine</p>
            <h1 class="page-hero-title text-4xl md:text-6xl font-bold text-white leading-tight">
                Conteúdo de marketing criado com <span class="text-gradient">inteligência artificial</span>
            </h1>
            <p class="text-gray-400 text-lg mt-6 max-w-2xl mx-auto">
                Gere imagens, vídeos e textos para suas campanhas, organize seus produtos e acompanhe seus leads — tudo em um só lugar.
            </p>
            <RouterLink to="/login" class="btn-primary inline-flex items-center gap-2 mt-10 px-7 py-3 rounded-lg font-medium">
                Acessar plataforma <ArrowRight class="w-4 h-4" />
            </RouterLink>
        </section>

        <!-- Features -->
        <section class="max-w-6xl mx-auto px-4 pb-24 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="f in features" :key="f.title" class="card-glass rounded-xl p-6">
                <component :is="f.icon" class="w-6 h-6 brand-text mb-4" />
                <h3 class="text-white font-semibold mb-1">{{ f.title }}</h3>
                <p class="text-gray-400 text-sm">{{ f.text }}</p>
            </div>
        </section>

        <footer class="border-t py-8 text-center text-sm text-gray-500" style="border-color: var(--border-subtle)">
            © {{ new Date().getFullYear() }} CREATIQ
        </footer>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { ArrowRight, Sparkles, Megaphone, Images, Users } from 'lucide-vue-next'

const features = [
    { icon: Sparkles, title: 'Geração com IA', text: 'Crie imagens e vídeos a partir de prompts e templates.' },
    { icon: Megaphone, title: 'Campanhas', text: 'Planeje ações e publique nas redes sociais.' },
    { icon: Images, title: 'Galeria', text: 'Todos os seus criativos organizados e prontos para usar.' },
    { icon: Users, title: 'Leads e pipeline', text: 'Acompanhe contatos, tarefas e oportunidades.' },
]
</script>
