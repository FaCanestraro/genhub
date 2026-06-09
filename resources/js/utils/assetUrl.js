export function assetUrl(path) {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `/storage/${path}`
}
