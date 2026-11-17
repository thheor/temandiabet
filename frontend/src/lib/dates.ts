function pad(value: number): string {
  return String(value).padStart(2, '0')
}

export function toISODate(date: Date): string {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

export function todayISO(): string {
  return toISODate(new Date())
}

export function daysAgoISO(days: number): string {
  const date = new Date()
  date.setDate(date.getDate() - days)
  return toISODate(date)
}

export function parseMeasuredAt(value: string): Date {
  return new Date(value.replace(' ', 'T'))
}

export function formatMeasuredAt(value: string): string {
  return parseMeasuredAt(value).toLocaleString()
}

export function toDateTimeInput(value: string): string {
  return value.replace(' ', 'T').slice(0, 16)
}
