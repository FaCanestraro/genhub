export function numberToCurrency(value) {
    if (value === null || value === undefined || value === '') return ''
    return Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function onCurrencyInput(e, setter) {
    const raw = e.target.value.replace(/\D/g, '')
    const number = raw ? parseInt(raw) / 100 : null
    setter(number)
    e.target.value = number !== null
        ? number.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : ''
}

export function maskCNPJ(value) {
    const d = String(value || '').replace(/\D/g, '').slice(0, 14)
    return d
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
        .replace(/(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d{1,2})$/, '$1.$2.$3/$4-$5')
}

export function onCNPJInput(e, setter) {
    const masked = maskCNPJ(e.target.value)
    setter(masked)
    e.target.value = masked
}

export function maskPhone(value) {
    const d = String(value || '').replace(/\D/g, '').slice(0, 11)
    if (d.length <= 10) {
        return d
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/\((\d{2})\) (\d{4})(\d)/, '($1) $2-$3')
    }
    return d
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/\((\d{2})\) (\d{5})(\d)/, '($1) $2-$3')
}

export function onPhoneInput(e, setter) {
    const masked = maskPhone(e.target.value)
    setter(masked)
    e.target.value = masked
}
