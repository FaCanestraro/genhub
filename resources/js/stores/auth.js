import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import { useCompanyStore } from '@/stores/company'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const token = ref(localStorage.getItem('token'))

    const isAuthenticated = computed(() => !!token.value)
    const isPlatformAdmin = computed(() => !!user.value?.is_platform_admin)
    const isClient = computed(() => user.value ? !!user.value.is_client : true)

    // Ownership/permissions are per-company now (a user can own one company and just be a
    // member of another), so they're derived from whichever company is currently active.
    const isOwner = computed(() => !!useCompanyStore().current?.is_owner)

    function can(menu, action) {
        const company = useCompanyStore().current
        if (!company) return false
        if (company.is_owner) return true
        return !!company.role?.permissions?.[menu]?.[action]
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
        useCompanyStore().clear()
    }

    return { user, token, isAuthenticated, isOwner, isPlatformAdmin, isClient, can, login, register, fetchMe, logout }
})
