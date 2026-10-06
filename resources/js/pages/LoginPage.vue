<template>
    <div class="login grid-bg min-h-screen flex">

        <!-- Form -->
        <div class="flex-1 min-w-0 flex flex-col px-6 py-6 sm:px-10">
            <div class="flex items-center justify-between gap-4">
                <RouterLink to="/"><img src="/creatiq-logo.png" alt="CREATIQ" class="h-9 w-auto" /></RouterLink>
                <RouterLink to="/" class="mono inline-flex items-center gap-1.5 min-h-11 px-2 text-xs tracking-[.06em] text-muted hover:text-white transition-colors">
                    <ArrowLeft class="w-3.5 h-3.5" /> voltar ao site
                </RouterLink>
            </div>

            <div class="flex-1 flex items-center justify-center py-12">
                <form @submit.prevent="handleLogin" class="w-full max-w-sm flex flex-col gap-6">
                    <div class="flex flex-col gap-3">
                        <span class="eyebrow">$ creatiq login</span>
                        <h1 class="m-0 text-[2.4rem] leading-[1.05] font-bold tracking-[-.03em] text-white">Bem-vindo de volta.</h1>
                        <p class="m-0 text-muted">Entre para criar e publicar os criativos da sua rede.</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="email" class="mono text-xs tracking-[.08em] text-soft">E-MAIL</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            autofocus
                            required
                            class="field"
                            placeholder="voce@supermercado.com.br"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="password" class="mono text-xs tracking-[.08em] text-soft">SENHA</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                class="field pr-12"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                                class="absolute inset-y-0 right-0 w-12 flex items-center justify-center text-muted hover:text-white transition-colors"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <p v-if="error" role="alert" class="m-0 flex items-start gap-2 px-3.5 py-3 rounded-lg border border-red-500/30 bg-red-500/10 text-sm text-red-300">
                        <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" /> {{ error }}
                    </p>

                    <button type="submit" :disabled="loading" class="btn-accent">
                        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                        {{ loading ? 'Entrando...' : 'Entrar' }}
                        <ArrowRight v-if="!loading" class="w-4 h-4" />
                    </button>
                </form>
            </div>

            <p class="mono m-0 text-[11px] tracking-[.08em] text-dim">© {{ new Date().getFullYear() }} CREATIQ · AI ENGINE</p>
        </div>

        <!-- Brand panel (desktop) -->
        <aside class="hidden lg:flex relative overflow-hidden w-[50%] flex-col items-center justify-center gap-8 px-12 border-l border-[#221E33] bg-[#0B0914]">
            <!-- Mural de criativos em loop: exemplo do que a plataforma produz -->
            <div class="wall" aria-hidden="true">
                <div v-for="(col, c) in wallColumns" :key="c" class="wall-col" :class="`col-${c}`">
                    <!-- Lista duplicada para o loop não ter emenda -->
                    <template v-for="copy in 2" :key="copy">
                        <div v-for="card in col" :key="`${copy}-${card.title}`" class="creative">
                            <div class="flex items-center justify-between mono text-[10px] tracking-[.1em] text-muted">
                                <span>{{ card.kind }}</span>
                                <Play v-if="card.video" class="w-3 h-3" />
                            </div>
                            <div class="art" :class="card.tall ? 'aspect-[9/14]' : 'aspect-square'">
                                <img :src="card.img" alt="" decoding="async" class="w-full h-full object-cover" />
                                <span v-if="card.off" class="off-tag">{{ card.off }}</span>
                            </div>
                            <p class="m-0 text-sm font-semibold text-white leading-tight">{{ card.title }}</p>
                            <p v-if="card.price" class="m-0 price">{{ card.price }}</p>
                        </div>
                    </template>
                </div>
            </div>
            <div class="scrim" aria-hidden="true"></div>

            <span class="relative mono inline-flex items-center gap-2.5 px-3.5 py-2 rounded-full border border-[#2E2843] bg-[#0E0C17] text-xs tracking-[.12em] text-soft">
                <span class="w-2 h-2 rounded-full bg-[#C6F432] shadow-[0_0_10px_#C6F432]"></span>
                AI ENGINE · ONLINE
            </span>
            <div class="relative flex flex-col items-center gap-4 text-center">
                <p class="m-0 max-w-[380px] text-[1.75rem] leading-[1.15] font-bold tracking-[-.03em] text-white">
                    Um produto. <span class="text-accent-soft">Todos os criativos</span> da sua rede.
                </p>
                <p class="mono m-0 text-xs tracking-[.12em] text-dim">PRODUTO → OFERTA → ENCARTE → POST</p>
            </div>
        </aside>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ArrowLeft, ArrowRight, Eye, EyeOff, AlertCircle, Loader2, Play } from 'lucide-vue-next'

// Real creatives made with our templates, exported small (~30 KB each) to public/showcase.
const CREATIVES = {
    family: { kind: 'POST', img: '/showcase/login-1.jpg', title: 'Momento em família' },
    snack: { kind: 'STORY · 9:16', img: '/showcase/login-2.jpg', title: 'Lanche da tarde', tall: true },
    lotion: { kind: 'OFERTA', img: '/showcase/login-3.jpg', title: 'Hidratante corporal', price: 'R$ 19,90', off: '-20%' },
    grapes: { kind: 'OFERTA', img: '/showcase/login-4.jpg', title: 'Uva roxa bandeja', price: 'R$ 4,50', off: '-25%' },
    drink: { kind: 'REELS · 0:15', img: '/showcase/login-5.jpg', title: 'Bebida láctea', tall: true, video: true },
    care: { kind: 'POST', img: '/showcase/login-6.jpg', title: 'Cuidado diário' },
}
const wallColumns = [
    [CREATIVES.grapes, CREATIVES.family, CREATIVES.care],
    [CREATIVES.snack, CREATIVES.drink, CREATIVES.lotion],
    [CREATIVES.lotion, CREATIVES.grapes, CREATIVES.snack],
]

const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)
const error = ref('')
const showPassword = ref(false)
const form = ref({ email: '', password: '' })

async function handleLogin() {
    loading.value = true
    error.value = ''
    try {
        await auth.login(form.value.email, form.value.password)
        // The router guard fetches companies and redirects away from /choose-area on its own
        // if this account doesn't actually face a choice (single company, not also an admin).
        router.push('/choose-area')
    } catch (e) {
        error.value = e.response?.data?.message || 'Erro ao entrar. Verifique suas credenciais.'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.login {
    --accent: #8A3AB9;
    --accent-soft: #B98AF0;
    background-color: #07060D;
    color: #EDEAF5;
    font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
}
.grid-bg {
    background-image:
        linear-gradient(rgba(255, 255, 255, .035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .035) 1px, transparent 1px);
    background-size: 48px 48px;
}
.mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
.eyebrow { font-family: 'JetBrains Mono', monospace; font-size: 13px; letter-spacing: .14em; color: var(--accent-soft); }
.text-accent-soft { color: var(--accent-soft); }
.text-soft { color: #C9C3DA; }
.text-muted { color: #9B95AD; }
.text-dim { color: #6F6984; }

.field {
    width: 100%;
    min-height: 48px;
    padding: 0 16px;
    border-radius: 10px;
    border: 1px solid #2E2843;
    background: #0E0C17;
    color: #EDEAF5;
    font-size: 15px;
    transition: border-color .15s, box-shadow .15s;
}
.field::placeholder { color: #6F6984; }
.field:hover { border-color: #3A3352; }
.field:focus { outline: none; border-color: var(--accent-soft); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 30%, transparent); }

.btn-accent {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 52px;
    border-radius: 10px;
    background: var(--accent);
    color: #fff;
    font-weight: 600;
    font-size: 16px;
    transition: background-color .15s;
}
.btn-accent:hover:not(:disabled) { background: color-mix(in srgb, var(--accent) 85%, black); }
.btn-accent:disabled { opacity: .6; cursor: progress; }
.btn-accent:focus-visible { outline: 2px solid var(--accent-soft); outline-offset: 3px; }

/* ── Creative wall ─────────────────────────────────────────────── */
.wall {
    position: absolute;
    inset: -10%;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    opacity: .5;
    transform: rotate(-8deg);
}
.wall-col { display: flex; flex-direction: column; gap: 16px; animation: wall-up linear infinite; }
/* All columns rise; different speeds and start offsets keep them from lining up. */
.col-0 { animation-duration: 60s; }
.col-1 { animation-duration: 74s; animation-delay: -30s; }
.col-2 { animation-duration: 66s; animation-delay: -12s; }
@keyframes wall-up { to { transform: translateY(calc(-50% - 8px)); } }

.creative {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
    border-radius: 12px;
    border: 1px solid #2E2843;
    background: #0E0C17;
}
.art {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    overflow: hidden;
    background: #14121d;
}
.off-tag {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 2px 8px;
    border-radius: 4px;
    background: #FFD23F;
    color: #1A1405;
    font-weight: 700;
    font-size: 14px;
}
.price { font-size: 18px; font-weight: 700; color: #FFD23F; }

/* Darkens the wall toward the center so the headline stays readable */
.scrim {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at center, rgba(7, 6, 13, .92) 0%, rgba(7, 6, 13, .6) 55%, rgba(7, 6, 13, .3) 100%);
}

@media (prefers-reduced-motion: reduce) {
    .wall-col { animation: none; }
}
</style>
