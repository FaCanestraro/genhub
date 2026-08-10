import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useCompanyStore = defineStore('company', () => {
    const companies = ref([])
    const currentCompanyId = ref(Number(localStorage.getItem('companyId')) || null)

    const current = computed(() => companies.value.find(c => c.id === currentCompanyId.value) ?? null)
    const hasMultiple = computed(() => companies.value.length > 1)

    async function fetchCompanies() {
        const { data } = await api.get('/auth/companies')
        companies.value = data

        if (!companies.value.some(c => c.id === currentCompanyId.value) && companies.value.length) {
            select(companies.value[0].id)
        }

        return data
    }

    function select(companyId) {
        currentCompanyId.value = companyId
        localStorage.setItem('companyId', companyId)
    }

    function clear() {
        companies.value = []
        currentCompanyId.value = null
        localStorage.removeItem('companyId')
    }

    return { companies, currentCompanyId, current, hasMultiple, fetchCompanies, select, clear }
})
