<template>
    <div ref="root" class="landing grid-bg min-h-screen">

        <!-- Header -->
        <header class="sticky top-0 z-20 border-b border-line bg-[rgba(7,6,13,.86)] backdrop-blur-md">
            <div class="max-w-[1200px] mx-auto px-6 py-3.5 flex flex-wrap items-center justify-between gap-4">
                <a href="#topo"><img src="/creatiq-logo.png" alt="CREATIQ" class="h-10 w-auto" /></a>
                <nav class="flex flex-wrap items-center gap-2 mono text-[13px] tracking-[.04em]">
                    <a v-for="l in navLinks" :key="l.href" :href="l.href" class="px-3 py-3 text-muted hover:text-white transition-colors">{{ l.label }}</a>
                    <RouterLink to="/login" class="btn-accent ml-2 min-h-11 px-5 text-[15px]">
                        Entrar <ArrowRight class="w-4 h-4" />
                    </RouterLink>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section id="topo" class="max-w-[1200px] mx-auto px-6 pt-24 pb-22 flex flex-wrap items-center gap-14">
            <div class="flex-[1_1_480px] min-w-0 flex flex-col gap-7">
                <div class="self-start inline-flex items-center gap-2.5 px-3.5 py-2 rounded-full border border-line-strong bg-panel mono text-xs tracking-[.12em] text-soft">
                    <span class="w-2 h-2 rounded-full bg-[#C6F432] shadow-[0_0_10px_#C6F432]"></span>
                    AI ENGINE · ONLINE
                </div>
                <h1 class="m-0 text-[clamp(2.6rem,5.4vw,4.4rem)] leading-[1.02] font-bold tracking-[-.035em]">
                    Ofertas, encartes e posts da sua rede, <span class="text-accent-soft">criados com IA.</span>
                </h1>
                <p class="m-0 max-w-[520px] text-[19px] leading-relaxed text-muted">
                    O CREATIQ transforma o catálogo do seu supermercado em criativos prontos: imagens, vídeos, carrosséis e legendas a partir de modelos prontos. Organize por campanha, aprove com o time e mantenha a identidade de cada loja.
                </p>
                <div class="flex flex-wrap gap-3">
                    <RouterLink to="/login" class="btn-accent min-h-13 px-6.5 text-base">
                        Acessar plataforma <ArrowRight class="w-[18px] h-[18px]" />
                    </RouterLink>
                    <a href="#fluxo" class="btn-ghost min-h-13 px-5.5 text-base">Ver como funciona</a>
                </div>
                <div class="flex flex-wrap gap-6 pt-2 mono text-xs tracking-[.1em] text-dim">
                    <span>IMAGEM · VÍDEO · CARROSSEL · LEGENDA</span>
                    <span>→ INSTAGRAM · FACEBOOK · TIKTOK</span>
                </div>
            </div>

            <!-- Console mockup -->
            <div class="tilt flex-[1_1_460px] min-w-0 rounded-2xl border border-line-strong bg-panel shadow-[0_30px_80px_rgba(0,0,0,.55),0_0_0_1px_rgba(138,58,185,.12)]" aria-hidden="true">
                <div class="flex items-center justify-between px-4 py-3 rounded-t-2xl border-b border-line mono text-xs text-dim">
                    <div class="flex gap-1.5">
                        <span v-for="i in 3" :key="i" class="w-2.5 h-2.5 rounded-full bg-[#2E2843]"></span>
                    </div>
                    <span>creatiq://generate</span>
                    <span class="text-[#C6F432]">● live</span>
                </div>
                <div class="p-5 flex flex-col gap-4 preserve">
                    <div class="px-4 py-3.5 rounded-[10px] border border-line-strong bg-base mono text-[13px] leading-relaxed">
                        <span class="text-accent-soft">&gt; prompt:</span>
                        oferta de uva roxa da semana, preço em destaque, clima de feira<span class="caret"></span>
                    </div>
                    <div class="flex flex-wrap gap-2 mono text-[11px] tracking-[.06em]">
                        <span v-for="c in consoleChips" :key="c" class="px-2.5 py-1.5 rounded-md border border-line-strong text-soft">{{ c }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5 mono text-[11px] text-muted preserve">
                        <div class="tile photo" style="--z: 70px"><img src="/showcase/landing-2.jpg" alt="" decoding="async" /></div>
                        <div style="--z: 110px" class="tile photo justify-end !border-[var(--accent)]">
                            <img src="/showcase/landing-1.jpg" alt="" decoding="async" />
                            <span class="relative px-1.5 py-0.5 rounded bg-[var(--accent)] text-white">✓ aprovado</span>
                        </div>
                        <div class="tile photo" style="--z: 40px"><img src="/showcase/landing-3.jpg" alt="" decoding="async" /></div>
                        <div style="--z: 20px" class="tile !flex-col !items-center !justify-center gap-2.5 !border-dashed !border-[#3A3352] !bg-[#0B0914]">
                            <Loader2 class="w-[22px] h-[22px] text-accent-soft animate-spin" />
                            gerando…
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between mono text-[11px] tracking-[.06em] text-muted">
                            <span>RENDERIZANDO 3/4</span><span>75%</span>
                        </div>
                        <div class="h-1 rounded bg-[#221E33] overflow-hidden">
                            <div class="w-3/4 h-full bg-[var(--accent)]"></div>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-between items-center gap-3 pt-1">
                        <span class="mono text-[11px] text-dim">campanha: Ofertas da semana</span>
                        <span class="inline-flex items-center min-h-9 px-3.5 rounded-lg border border-line-strong text-[13px] font-semibold">Vincular à campanha →</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Spec strip -->
        <div class="border-y border-line bg-[#0B0914]">
            <div class="max-w-[1200px] mx-auto px-6 py-5.5 flex flex-wrap justify-between gap-x-8 gap-y-4 mono text-xs tracking-[.14em] text-muted">
                <span v-for="s in specs" :key="s"><span class="text-accent-soft">◆</span> {{ s }}</span>
            </div>
        </div>

        <!-- Pipeline -->
        <section id="fluxo" class="max-w-[1200px] mx-auto px-6 pt-28 pb-10 flex flex-col gap-12">
            <div class="flex flex-wrap items-center justify-between gap-10">
                <div class="flex flex-col gap-3.5 max-w-[640px]">
                    <span class="eyebrow">// PIPELINE</span>
                    <h2 class="section-title">Do prompt ao post em quatro etapas.</h2>
                </div>
                <!-- Embalagem 3D: gira sozinha, cada face é um formato do mesmo produto -->
                <ProductBox class="mx-auto sm:mx-0 sm:mr-10" />
            </div>
            <div class="reveal flex flex-wrap gap-px bg-[#221E33] border border-line rounded-[14px] overflow-hidden">
                <div v-for="(step, i) in steps" :key="step.title" class="flex-[1_1_240px] min-w-0 bg-base px-7 py-8 flex flex-col gap-3.5">
                    <span class="mono text-[13px]" :class="i === steps.length - 1 ? 'text-accent-soft' : 'text-dim'">0{{ i + 1 }} ─────</span>
                    <h3 class="m-0 text-[22px] font-semibold flex items-center gap-2.5">
                        {{ step.title }}
                        <span v-if="step.soon" class="mono text-[10px] tracking-[.1em] px-2 py-0.5 rounded-full border border-line-strong text-soft">EM BREVE</span>
                    </h3>
                    <p class="m-0 text-[15px] leading-relaxed text-muted">{{ step.text }}</p>
                </div>
            </div>
        </section>

        <!-- Modules -->
        <section id="modulos" class="max-w-[1200px] mx-auto px-6 pt-24 pb-10 flex flex-col gap-12">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="flex flex-col gap-3.5 max-w-[640px]">
                    <span class="eyebrow">// MÓDULOS</span>
                    <h2 class="section-title">Uma plataforma. Todo o ciclo criativo.</h2>
                </div>
                <p class="m-0 max-w-[380px] text-base leading-relaxed text-muted">Cada módulo conversa com os outros: o lead que chega vira tarefa, a campanha vira post.</p>
            </div>
            <div class="flex flex-wrap gap-4">
                <div v-for="m in modules" :key="m.title" class="reveal flex-[1_1_340px] min-w-0 rounded-[14px] border border-line bg-panel p-7 flex flex-col gap-3.5">
                    <div class="w-11 h-11 rounded-[10px] border border-line-strong flex items-center justify-center text-accent-soft">
                        <component :is="m.icon" class="w-[22px] h-[22px]" :stroke-width="1.8" />
                    </div>
                    <h3 class="m-0 text-xl font-semibold">{{ m.title }}</h3>
                    <p class="m-0 text-[15px] leading-relaxed text-muted">{{ m.text }}</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="contato" class="max-w-[1200px] mx-auto px-6 pt-24 pb-28">
            <div class="grid-bg rounded-[20px] border border-line-strong bg-panel px-10 py-18 flex flex-col items-center text-center gap-6">
                <span class="eyebrow">$ creatiq --start</span>
                <h2 class="m-0 max-w-[720px] text-[clamp(2.2rem,4.4vw,3.6rem)] leading-[1.05] tracking-[-.035em] font-bold">Seu próximo criativo está a um prompt de distância.</h2>
                <div class="flex flex-wrap justify-center gap-3 pt-2">
                    <RouterLink to="/login" class="btn-accent min-h-13 px-7 text-base">Entrar na plataforma</RouterLink>
                    <!-- ponytail: e-mail de contato fixo; troque pelo endereço real -->
                    <a href="mailto:contato@creatiq.com.br" class="btn-ghost min-h-13 px-5.5 text-base">Falar com a gente</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-line">
            <div class="max-w-[1200px] mx-auto px-6 py-7 flex flex-wrap items-center justify-between gap-4 mono text-xs tracking-[.08em] text-dim">
                <img src="/creatiq-logo.png" alt="CREATIQ" class="h-7 w-auto" />
                <span>© {{ new Date().getFullYear() }} CREATIQ · AI ENGINE</span>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, Loader2, Video, Megaphone, KanbanSquare, LayoutTemplate, Package, Building2 } from 'lucide-vue-next'
import ProductBox from '@/components/ProductBox.vue'

// Scroll position exposed as --scroll; all 3D motion is CSS reading it.
const root = ref(null)
let frame = 0
function onScroll() {
    cancelAnimationFrame(frame)
    frame = requestAnimationFrame(() => root.value?.style.setProperty('--scroll', window.scrollY))
}
onMounted(() => {
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
})
onUnmounted(() => {
    window.removeEventListener('scroll', onScroll)
    cancelAnimationFrame(frame)
})

const navLinks = [
    { href: '#fluxo', label: '/fluxo' },
    { href: '#modulos', label: '/módulos' },
    { href: '#contato', label: '/contato' },
]

const consoleChips = ['1:1', '3 VARIAÇÕES', 'MODELO: OFERTA DA SEMANA', 'PRODUTO: UVA ROXA BANDEJA']

const specs = ['GALERIA DE MODELOS PRONTOS', 'MARCA PRÓPRIA POR LOJA', 'PERMISSÕES POR PERFIL', 'LOG DE AUDITORIA']

const steps = [
    { title: 'Briefing', text: 'Escolha os produtos do catálogo, a campanha e um modelo pronto da galeria.' },
    { title: 'Geração', text: 'Imagens, vídeos, carrosséis e legendas com hashtags, em até 4 variações por vez.' },
    { title: 'Revisão', text: 'Tudo fica no histórico e na campanha: o time compara, baixa e aprova.' },
    { title: 'Publicação', text: 'Baixe em alta e poste nas redes. Publicação direta no Instagram e no Facebook chega em breve.', soon: true },
]

const modules = [
    { icon: LayoutTemplate, title: 'Galeria de modelos', text: 'Modelos prontos de oferta, família e lançamento: escolha um, combine com o produto e gere.' },
    { icon: Video, title: 'Vídeo e carrossel', text: 'Reels curtos e carrosséis de vários slides a partir do mesmo produto.' },
    { icon: Megaphone, title: 'Campanhas e ações', text: 'Ofertas da semana, datas especiais, inauguração: cada campanha reúne peças, prazos e orçamento.' },
    { icon: Package, title: 'Catálogo de produtos', text: 'Cadastre produtos com foto e preço uma vez e use em qualquer criativo.' },
    { icon: KanbanSquare, title: 'Leads e pipeline', text: 'CRM integrado com funil, atividades e tarefas do time.' },
    { icon: Building2, title: 'Rede com várias lojas', text: 'Cada loja com sua marca, cores e equipe, com permissões por perfil e log de auditoria.' },
]
</script>

<style scoped>
.landing {
    --accent: #8A3AB9;
    --accent-soft: #B98AF0;
    background-color: #07060D;
    color: #EDEAF5;
    font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
}
.mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
.bg-base { background-color: #07060D; }
.bg-panel { background-color: #0E0C17; }
.border-line { border-color: #221E33; }
.border-line-strong { border-color: #2E2843; }
.text-accent-soft { color: var(--accent-soft); }
.text-soft { color: #C9C3DA; }
.text-muted { color: #9B95AD; }
.text-dim { color: #6F6984; }

.grid-bg {
    background-image:
        linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
    background-size: 48px 48px;
}
.stripes { background-image: repeating-linear-gradient(135deg, rgba(255, 255, 255, .05) 0 2px, transparent 2px 12px); }

.eyebrow { font-family: 'JetBrains Mono', monospace; font-size: 13px; letter-spacing: .14em; color: var(--accent-soft); }
.section-title { margin: 0; font-size: clamp(2rem, 3.6vw, 3rem); line-height: 1.08; letter-spacing: -.03em; font-weight: 700; }

.btn-accent, .btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border-radius: 10px;
    transition: background-color .15s, border-color .15s;
}
.btn-accent { background: var(--accent); color: #fff; font-weight: 600; }
.btn-accent:hover { background: color-mix(in srgb, var(--accent) 85%, black); }
.btn-ghost { border: 1px solid #2E2843; color: #EDEAF5; font-weight: 500; }
.btn-ghost:hover { border-color: var(--accent-soft); }

.tile {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    border-radius: 10px;
    border: 1px solid #2E2843;
    background-color: #151223;
    display: flex;
    align-items: flex-end;
    padding: 10px;
}
.tile.photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.caret {
    display: inline-block;
    width: 8px;
    height: 15px;
    margin-left: 2px;
    vertical-align: -2px;
    background: var(--accent-soft);
    animation: blink 1s steps(1) infinite;
}
@keyframes blink { 50% { opacity: 0; } }
/* ── 3D scroll ─────────────────────────────────────────────────── */
.landing { --scroll: 0; }

/* Console: starts tilted, straightens over the first 500px of scroll */
.tilt {
    --t: clamp(0, calc(var(--scroll) / 500), 1);
    --r: calc(1 - var(--t));
    transform: perspective(1400px) rotateX(calc(var(--r) * 24deg)) rotateY(calc(var(--r) * -20deg)) rotateZ(calc(var(--r) * 5deg)) scale(calc(.92 + var(--t) * .08));
    transform-style: preserve-3d;
    will-change: transform;
}
.preserve { transform-style: preserve-3d; }
.tile { transform: translateZ(calc(var(--r, 0) * var(--z, 0px))); }


/* Cards flip in as they enter the viewport (native scroll-driven animation; static where unsupported) */
@supports (animation-timeline: view()) {
    .reveal {
        transform-origin: 50% 100%;
        animation: flip-in linear both;
        animation-timeline: view();
        animation-range: entry 0% entry 90%;
    }
}
@keyframes flip-in {
    from { opacity: 0; transform: perspective(900px) rotateX(40deg) translateY(50px); }
}

@media (prefers-reduced-motion: reduce) {
    .tilt, .tile { transform: none; }
    .reveal, .caret { animation: none; }
}
</style>
