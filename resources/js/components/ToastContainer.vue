<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-full max-w-sm pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toastStore.toasts"
                    :key="toast.id"
                    class="glass-modal rounded-xl px-4 py-3 flex items-start gap-2.5 pointer-events-auto"
                >
                    <AlertTriangle v-if="toast.type === 'error'" class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" />
                    <CheckCircle v-else class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-gray-200 flex-1">{{ toast.message }}</p>
                    <button type="button" @click="toastStore.dismiss(toast.id)" class="text-gray-500 hover:text-white transition-colors flex-shrink-0">
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useToastStore } from '@/stores/toast'
import { AlertTriangle, CheckCircle, X } from 'lucide-vue-next'

const toastStore = useToastStore()
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: opacity 0.2s, transform 0.2s; }
.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>
