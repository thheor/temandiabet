import { useEffect, useState } from 'react'
import { useAuth } from '../context/AuthContext'
import { api, ApiError } from '../lib/api'
import { DISCLAIMER, STATUS_META, TIMING_LABELS } from '../lib/clinical'
import { daysAgoISO, formatMeasuredAt, todayISO } from '../lib/dates'
import type { GlucoseResponse } from '../types'

export default function GlucoseReportPage() {
  const { user } = useAuth()
  const [from, setFrom] = useState(daysAgoISO(29))
  const [to, setTo] = useState(todayISO())
  const [data, setData] = useState<GlucoseResponse | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!from || !to || from > to) {
      setError('Choose a start date that is not after the end date.')
      setLoading(false)
      return
    }

    let cancelled = false
    setLoading(true)
    api
      .get<GlucoseResponse>(`/api/blood-glucose/index.php?from=${from}&to=${to}`)
      .then((body) => {
        if (!cancelled) {
          setData(body)
          setError('')
        }
      })
      .catch((err) => {
        if (!cancelled) setError(err instanceof ApiError ? err.message : 'Could not load the report')
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [from, to])

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-3 print:hidden">
        <h1 className="text-xl font-semibold">Glucose report</h1>
        <div className="flex flex-wrap items-end gap-3 text-sm">
          <label>
            <span className="font-medium">From</span>
            <input
              type="date"
              value={from}
              max={to}
              onChange={(event) => setFrom(event.target.value)}
              className="mt-1 block rounded border border-slate-300 px-3 py-2"
            />
          </label>
          <label>
            <span className="font-medium">To</span>
            <input
              type="date"
              value={to}
              min={from}
              max={todayISO()}
              onChange={(event) => setTo(event.target.value)}
              className="mt-1 block rounded border border-slate-300 px-3 py-2"
            />
          </label>
          <button
            type="button"
            onClick={() => window.print()}
            disabled={!data || data.records.length === 0}
            className="rounded bg-slate-900 px-4 py-2 font-medium text-white disabled:opacity-60"
          >
            Print
          </button>
        </div>
      </div>

      {error && (
        <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 print:hidden">
          {error}
        </p>
      )}

      <article className="space-y-4 rounded border border-slate-200 bg-white p-6 print:border-0 print:p-0">
        <header className="space-y-1">
          <h2 className="text-lg font-semibold">Teman Diabet: blood glucose report</h2>
          <p className="text-sm">
            {user?.full_name} · {from} to {to}
          </p>
          {user && (
            <p className="text-xs text-slate-500">
              Personal target range {user.target_glucose_min}–{user.target_glucose_max} mg/dL
            </p>
          )}
        </header>

        {data && (
          <p className="text-sm">
            {data.summary.count} readings · average {data.summary.avg ?? '–'} mg/dL · lowest{' '}
            {data.summary.min ?? '–'} · highest {data.summary.max ?? '–'}
          </p>
        )}

        {loading && <p className="text-sm text-slate-500">Loading…</p>}

        {!loading && data?.records.length === 0 && (
          <p className="text-sm text-slate-500">No readings in this period.</p>
        )}

        {data && data.records.length > 0 && (
          <div className="overflow-x-auto print:overflow-visible">
            <table className="w-full min-w-[28rem] border-collapse text-left text-sm">
              <thead>
                <tr className="border-b border-slate-300">
                  <th className="py-2 pr-3 font-medium">Date and time</th>
                  <th className="py-2 pr-3 font-medium">mg/dL</th>
                  <th className="py-2 pr-3 font-medium">Timing</th>
                  <th className="py-2 font-medium">Status</th>
                </tr>
              </thead>
              <tbody>
                {data.records.map((record) => (
                  <tr key={record.id} className="border-b border-slate-100 break-inside-avoid">
                    <td className="py-1.5 pr-3">{formatMeasuredAt(record.measured_at)}</td>
                    <td className="py-1.5 pr-3 font-medium">{record.glucose_mg_dl}</td>
                    <td className="py-1.5 pr-3">{TIMING_LABELS[record.measurement_timing]}</td>
                    <td className="py-1.5">{STATUS_META[record.status_category].label}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <p className="border-t border-slate-200 pt-3 text-xs text-slate-600">{DISCLAIMER}</p>
      </article>
    </div>
  )
}
