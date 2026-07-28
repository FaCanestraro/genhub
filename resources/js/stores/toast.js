import { defineStore } from 'pinia'
import { ref } from 'vue'

let nextId = 1

export const useToastStore = defineStore('toast', () => {
    const toasts = ref([])

    function push(message, type = 'error') {
        const id = nextId++
        toasts.value.push({ id, message, type })
        setTimeout(() => dismiss(id), 5000)
    }

    function dismiss(id) {
        toasts.value = toasts.value.filter(t => t.id !== id)
    }

    return { toasts, push, dismiss }
})
