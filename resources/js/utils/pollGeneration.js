import api from '@/services/api'

// Geração agora roda em fila no backend — o POST inicial retorna quase na hora com
// status "pending"/"processing". Isso consulta /generations/{id} até finalizar
// (completed/failed), pra telas que precisam do resultado (assets/erro) no fim.
export async function pollGeneration(id, { interval = 3000, timeout = 600000 } = {}) {
    const deadline = Date.now() + timeout

    while (Date.now() < deadline) {
        const { data } = await api.get(`/generations/${id}`)
        if (data.status === 'completed' || data.status === 'failed') {
            return data
        }
        await new Promise(resolve => setTimeout(resolve, interval))
    }

    throw new Error('Tempo esgotado aguardando a geração terminar.')
}
