<template>
    <span :style="styles" class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full">
        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ background: cfg.dot }"></span>
        {{ cfg.label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ status: String })

const CONFIG = {
    draft:      { label: 'Rascunho',    dot: '#6b7280', bg: 'rgba(107,114,128,0.12)', color: '#9ca3af' },
    active:     { label: 'Ativa',       dot: '#4ade80', bg: 'rgba(74,222,128,0.12)',  color: '#4ade80' },
    paused:     { label: 'Pausada',     dot: '#facc15', bg: 'rgba(250,204,21,0.12)',  color: '#facc15' },
    finished:   { label: 'Concluída',   dot: '#60a5fa', bg: 'rgba(96,165,250,0.12)',  color: '#60a5fa' },
    archived:   { label: 'Arquivada',   dot: '#6b7280', bg: 'rgba(107,114,128,0.10)', color: '#6b7280' },
    generating: { label: 'Gerando',     dot: '#a78bfa', bg: 'rgba(167,139,250,0.14)', color: '#a78bfa' },
    ready:      { label: 'Pronto',      dot: '#4ade80', bg: 'rgba(74,222,128,0.12)',  color: '#4ade80' },
    scheduled:  { label: 'Agendado',    dot: '#60a5fa', bg: 'rgba(96,165,250,0.12)',  color: '#60a5fa' },
    published:  { label: 'Publicado',   dot: '#34d399', bg: 'rgba(52,211,153,0.12)',  color: '#34d399' },
    failed:     { label: 'Falhou',      dot: '#f87171', bg: 'rgba(248,113,113,0.12)', color: '#f87171' },
    pending:    { label: 'Pendente',    dot: '#6b7280', bg: 'rgba(107,114,128,0.10)', color: '#9ca3af' },
    processing: { label: 'Processando', dot: '#a78bfa', bg: 'rgba(167,139,250,0.14)', color: '#a78bfa' },
    completed:  { label: 'Concluído',   dot: '#4ade80', bg: 'rgba(74,222,128,0.12)',  color: '#4ade80' },
}

const cfg = computed(() => CONFIG[props.status] ?? { label: props.status, dot: '#6b7280', bg: 'rgba(107,114,128,0.10)', color: '#9ca3af' })
const styles = computed(() => ({ background: cfg.value.bg, color: cfg.value.color, border: `1px solid ${cfg.value.dot}22` }))
</script>
