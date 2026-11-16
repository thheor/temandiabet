import { useCallback, useEffect, useState } from 'react'
import GlucoseForm from '../components/GlucoseForm'
import { api, ApiError } from '../lib/api'
import { STATUS_META, TIMING_LABELS } from '../lib/clinical'
import type { BloodGlucoseRecord } from '../types'

interface GlucoseResponse {
  records: BloodGlucoseRecord[]
  summary: { count: number; avg: number | null; min: number | null; max: number | null }
}

const RANGES = [
  { value: '7d', label: 'Last 7 days' },
  { value: '30d', label: 'Last 30 days' },
] as const

function formatMeasuredAt(value: string): string {
  return new Date(value.replace(' ', 'T')).toLocaleString()
}

export default function BloodGlucosePage() {
  const [range, setRange] = useState<'7d' | '30d'>('7d')
  const [data, setData] = useState<GlucoseResponse | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      setData(await api.get<GlucoseResponse>(`/api/blood-glucose/index.php?range=${range}`))
      setError('')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not load the readings')
    } finally {
      setLoading(false)
    }
  }, [range])

  useEffect(() => {
    void load()
  }, [load])

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold">Blood glucose</h1>
        <div className="flex gap-2 text-sm">
          {RANGES.map((option) => (
            <button
              key={option.value}
              type="button"
              aria-pressed={range === option.value}
              onClick={() => setRange(option.value)}
              className={`rounded border px-3 py-1 ${
                range === option.value
                  ? 'border-slate-900 bg-slate-900 text-white'
                  : 'border-slate-300 hover:bg-slate-100'
              }`}
            >
              {option.label}
            </button>
          ))}
        </div>
      </div>

      <GlucoseForm onSaved={load} />

      {error && (
        <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {error}
        </p>
      )}

      {data && (
        <section className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
          <div className="rounded border border-slate-200 bg-white p-3">
            <p className="text-xs text-slate-500">Readings</p>
            <p className="text-lg font-semibold">{data.summary.count}</p>
          </div>
          <div className="rounded border border-slate-200 bg-white p-3">
            <p className="text-xs text-slate-500">Average</p>
            <p className="text-lg font-semibold">{data.summary.avg ?? '–'} mg/dL</p>
          </div>
          <div className="rounded border border-slate-200 bg-white p-3">
            <p className="text-xs text-slate-500">Lowest</p>
            <p className="text-lg font-semibold">{data.summary.min ?? '–'} mg/dL</p>
          </div>
          <div className="rounded border border-slate-200 bg-white p-3">
            <p className="text-xs text-slate-500">Highest</p>
            <p className="text-lg font-semibold">{data.summary.max ?? '–'} mg/dL</p>
          </div>
        </section>
      )}

      <section className="space-y-2">
        {loading && <p className="text-sm text-slate-500">Loading readings…</p>}

        {!loading && data?.records.length === 0 && (
          <p className="rounded border border-slate-200 bg-white p-4 text-sm text-slate-500">
            No readings in this range yet.
          </p>
        )}

        {!loading && data && data.records.length > 0 && (
          <ul className="divide-y divide-slate-200 rounded border border-slate-200 bg-white">
            {data.records.map((record) => {
              const status = STATUS_META[record.status_category]
              return (
                <li
                  key={record.id}
                  className="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm"
                >
                  <div>
                    <p className="font-medium">{record.glucose_mg_dl} mg/dL</p>
                    <p className="text-xs text-slate-500">
                      {TIMING_LABELS[record.measurement_timing]} ·{' '}
                      <time dateTime={record.measured_at.replace(' ', 'T')}>
                        {formatMeasuredAt(record.measured_at)}
                      </time>
                    </p>
                  </div>
                  <span className={`rounded-full border px-3 py-1 text-xs font-medium ${status.badge}`}>
                    {status.label}
                  </span>
                </li>
              )
            })}
          </ul>
        )}
      </section>
    </div>
  )
}
