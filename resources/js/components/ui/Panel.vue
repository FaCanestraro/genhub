<template>
    <!-- Card padrão de seção: título com ícone, link/ações opcionais e conteúdo. -->
    <section class="panel">
        <header class="flex items-center justify-between gap-3 mb-5">
            <div class="flex items-center gap-2.5 min-w-0">
                <span v-if="icon" class="panel-icon">
                    <component :is="icon" class="w-4 h-4" />
                </span>
                <h2 class="panel-title truncate">{{ title }}</h2>
            </div>
            <slot name="actions">
                <RouterLink v-if="to" :to="to" class="panel-link">
                    {{ linkLabel }} <ArrowRight class="w-3.5 h-3.5" />
                </RouterLink>
            </slot>
        </header>
        <slot />
    </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { ArrowRight } from 'lucide-vue-next'

defineProps({
    title: { type: String, required: true },
    icon: [Object, Function],
    to: String,
    linkLabel: { type: String, default: 'Ver tudo' },
})
</script>
