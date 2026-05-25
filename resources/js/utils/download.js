import api from '@/services/api'

/**
 * Downloads a file via the Laravel proxy endpoint, bypassing browser CORS restrictions.
 * The proxy fetches the file server-side and streams it back as an attachment.
 */
export async function downloadAsset(url, filename) {
    try {
        const response = await api.get('/proxy-download', {
            params: { url },
            responseType: 'blob',
        })

        const blob       = response.data
        const contentDisposition = response.headers['content-disposition'] ?? ''
        const match      = contentDisposition.match(/filename="?([^"]+)"?/)
        const name       = filename || match?.[1] || `genhub-${Date.now()}.bin`

        const objectUrl = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href     = objectUrl
        a.download = name
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        setTimeout(() => URL.revokeObjectURL(objectUrl), 10000)
    } catch (e) {
        // Fallback: open in new tab
        window.open(url, '_blank')
    }
}
