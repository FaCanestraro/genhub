import axios from 'axios'
import { useToastStore } from '@/stores/toast'

const api = axios.create({
    baseURL: '/api',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
})

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`

    const companyId = localStorage.getItem('companyId')
    if (companyId) config.headers['X-Company-Id'] = companyId

    return config
})

api.interceptors.response.use(
    (res) => res,
    (err) => {
        const status = err.response?.status

        if (status === 401) {
            localStorage.removeItem('token')
            window.location.href = '/login'
        } else if (status !== 422) {
            const message = err.response?.data?.message || 'Ocorreu um erro ao processar a solicitação.'
            useToastStore().push(message, 'error')
        }

        return Promise.reject(err)
    }
)

export default api
