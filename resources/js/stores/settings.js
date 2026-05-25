import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useSettingsStore = defineStore('settings', () => {
    const primaryColor = ref('#7c3aed')
    const logoUrl      = ref(null)
    const nomeEmpresa  = ref('')
    const loaded       = ref(false)

    function applyColor(color) {
        if (!color) return
        primaryColor.value = color
        document.documentElement.style.setProperty('--brand', color)
        // Derive a slightly darker shade for hover
        document.documentElement.style.setProperty(
            '--brand-hover',
            `color-mix(in srgb, ${color} 82%, black)`
        )
    }

    async function load() {
        if (loaded.value) return
        try {
            const { data } = await api.get('/settings')
            if (data.cor_primaria) applyColor(data.cor_primaria)
            if (data.logo_url)    logoUrl.value = data.logo_url
            if (data.nome_empresa) nomeEmpresa.value = data.nome_empresa
        } catch {
            // Use defaults silently
        } finally {
            loaded.value = true
        }
    }

    async function save(payload) {
        const { data } = await api.put('/settings', payload)
        if (data.cor_primaria) applyColor(data.cor_primaria)
        if ('logo_url' in data)    logoUrl.value = data.logo_url
        if ('nome_empresa' in data) nomeEmpresa.value = data.nome_empresa
        return data
    }

    async function uploadLogo(file) {
        const form = new FormData()
        form.append('logo', file)
        const { data } = await api.post('/settings/logo', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        logoUrl.value = data.logo_url
        return data.logo_url
    }

    return { primaryColor, logoUrl, nomeEmpresa, loaded, applyColor, load, save, uploadLogo }
})
