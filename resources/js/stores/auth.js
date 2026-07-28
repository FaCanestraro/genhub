import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const token = ref(localStorage.getItem('token'))

    const isAuthenticated = computed(() => !!token.value)
    const isOwner = computed(() => !user.value?.owner_id)

    function can(menu, action) {
        if (isOwner.value) return true
        return !!user.value?.role?.permissions?.[menu]?.[action]
    }

    async function login(email, password) {
        const { data } = await api.post('/auth/login', { email, password })
        token.value = data.token
        user.value = data.user
        localStorage.setItem('token', data.token)
    }

    async function register(payload) {
        const { data } = await api.post('/auth/register', payload)
        token.value = data.token
        user.value = data.user
        localStorage.setItem('token', data.token)
    }

    async function fetchMe() {
        const { data } = await api.get('/auth/me')
        user.value = data
    }

    async function logout() {
        await api.post('/auth/logout').catch(() => {})
        token.value = null
        user.value = null
        localStorage.removeItem('token')
    }

    return { user, token, isAuthenticated, isOwner, can, login, register, fetchMe, logout }
})
