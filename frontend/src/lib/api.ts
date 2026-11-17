const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

export class ApiError extends Error {
  status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const isFormData = init.body instanceof FormData

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...init,
    credentials: 'include',
    headers: isFormData
      ? init.headers
      : { 'Content-Type': 'application/json', ...init.headers },
  })

  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new ApiError(body.message ?? 'Request failed', response.status)
  }

  return body as T
}

export function mediaUrl(path: string): string {
  return `${API_BASE_URL}/${path}`
}

export const api = {
  get: <T>(path: string) => request<T>(path),
  post: <T>(path: string, body?: unknown) =>
    request<T>(path, {
      method: 'POST',
      body: body instanceof FormData ? body : JSON.stringify(body ?? {}),
    }),
}
