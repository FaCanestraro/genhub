<template>
    <!-- Embalagem 3D girando sozinha: cada face é um formato do mesmo produto. -->
    <div class="box-scene" aria-hidden="true">
        <div class="box">
            <div class="box-face front">
                <span class="face-label">01 · PRODUTO</span>
                <ShoppingBasket class="w-14 h-14" :stroke-width="1.4" />
                <span class="caption">foto original</span>
            </div>
            <div class="box-face right">
                <span class="face-label">02 · OFERTA</span>
                <span class="price-tag">-30%</span>
                <span class="text-white text-xl font-bold">R$ 9,90</span>
            </div>
            <div class="box-face back">
                <span class="face-label">03 · ENCARTE</span>
                <div class="grid grid-cols-2 gap-1.5 w-24">
                    <span v-for="i in 4" :key="i" class="flyer-cell" :class="{ hot: i === 1 }"></span>
                </div>
                <span class="caption">página semanal</span>
            </div>
            <div class="box-face left">
                <span class="face-label">04 · POST</span>
                <Heart class="w-12 h-12" :stroke-width="1.4" />
                <span class="caption">instagram · facebook</span>
            </div>
            <div class="box-face top"></div>
            <div class="box-face bottom"></div>
        </div>
    </div>
</template>

<script setup>
import { ShoppingBasket, Heart } from 'lucide-vue-next'
</script>

<style scoped>
.box-scene {
    --accent: #8A3AB9;
    --accent-soft: #B98AF0;
    width: 170px;
    height: 210px;
    perspective: 900px;
    flex-shrink: 0;
}
.box {
    position: relative;
    width: 100%;
    height: 100%;
    transform-style: preserve-3d;
    /* Holds on each face, then turns to the next; 100% = 0% mod 360 so it loops seamlessly */
    animation: turn 10s cubic-bezier(.65, 0, .35, 1) infinite;
}
@keyframes turn {
    0%, 18%   { transform: rotateX(-12deg) rotateY(20deg); }
    25%, 43%  { transform: rotateX(-12deg) rotateY(-70deg); }
    50%, 68%  { transform: rotateX(-12deg) rotateY(-160deg); }
    75%, 93%  { transform: rotateX(-12deg) rotateY(-250deg); }
    100%      { transform: rotateX(-12deg) rotateY(-340deg); }
}
.box-face {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 34px 12px 16px;
    border: 1px solid color-mix(in srgb, var(--accent-soft) 55%, transparent);
    background-color: #120F1F;
    background-image:
        linear-gradient(rgba(185, 138, 240, .08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(185, 138, 240, .08) 1px, transparent 1px);
    background-size: 17px 17px;
    box-shadow: inset 0 0 30px rgba(138, 58, 185, .25);
    color: var(--accent-soft);
    backface-visibility: hidden;
}
.face-label, .caption { font-family: 'JetBrains Mono', ui-monospace, monospace; }
.face-label { position: absolute; top: 12px; left: 12px; font-size: 10px; letter-spacing: .12em; }
.caption { font-size: 11px; color: #9B95AD; }
.price-tag {
    padding: 4px 12px;
    border-radius: 6px;
    background: var(--accent);
    color: #fff;
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -.03em;
}
.flyer-cell {
    aspect-ratio: 1 / 1;
    border-radius: 2px;
    border: 1px solid #2E2843;
    background-image: repeating-linear-gradient(135deg, rgba(255, 255, 255, .05) 0 2px, transparent 2px 12px);
}
.flyer-cell.hot { border-color: var(--accent); }
.front  { transform: translateZ(85px); }
.right  { transform: rotateY(90deg) translateZ(85px); }
.back   { transform: rotateY(180deg) translateZ(85px); }
.left   { transform: rotateY(-90deg) translateZ(85px); }
.top    { height: 170px; transform: rotateX(90deg) translateZ(85px); }
.bottom { height: 170px; transform: rotateX(-90deg) translateZ(125px); }

@media (prefers-reduced-motion: reduce) {
    .box { animation: none; transform: rotateX(-12deg) rotateY(-25deg); }
}
</style>
