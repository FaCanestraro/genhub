// Campaign dates come as YYYY-MM-DD; parse as local time so the day doesn't shift.
export const toDate = (d) => d ? new Date(`${d.slice(0, 10)}T00:00:00`) : null

export const formatShort = (d) => d ? toDate(d).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' }).replace('.', '') : ''

// Where today sits in the campaign window: label, text color class and elapsed % (or null).
export function period(c) {
    const start = toDate(c.start_date), end = toDate(c.end_date)
    const today = new Date(); today.setHours(0, 0, 0, 0)
    const day = 86400000
    if (start && today < start) {
        const n = Math.round((start - today) / day)
        return { label: n === 1 ? 'começa amanhã' : `começa em ${n} dias`, tone: 'text-sky-300', pct: null }
    }
    if (end && today > end) return { label: 'encerrada', tone: 'text-gray-500', pct: start ? 100 : null }
    if (end) {
        const n = Math.round((end - today) / day)
        const pct = start ? Math.min(100, ((today - start) / (end - start || day)) * 100) : null
        return { label: n === 0 ? 'termina hoje' : `faltam ${n} dia${n > 1 ? 's' : ''}`, tone: n <= 3 ? 'text-amber-300' : 'text-emerald-300', pct }
    }
    return null
}
