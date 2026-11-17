import { useCallback, useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link } from 'react-router-dom'
import GlucoseChart from '../components/GlucoseChart'
import GlucoseForm from '../components/GlucoseForm'
import { useAuth } from '../context/AuthContext'
import { api, ApiError } from '../lib/api'
import { STATUS_META, TIMING_LABELS } from '../lib/clinical'
import { formatMeasuredAt, toDateTimeInput } from '../lib/dates'
import type { BloodGlucoseRecord, GlucoseResponse, MeasurementTiming } from '../types'

const RANGES = [
  { value: '7d', label: 'Last 7 days' },
  { value: '30d', label: 'Last 30 days' },
] as const

const TIMINGS = Object.entries(TIMING_LABELS) as [MeasurementTiming, string][]

interface Draft {
  value: string
  timing: MeasurementTiming
  measuredAt: string
}

export default function BloodGlucosePage() {
  const { user } = useAuth()
  const [range, setRange] = useState<'7d' | '30d'>('7d')
  const [data, setData] = useState<GlucoseResponse | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [editingId, setEditingId] = useState<number | null>(null)
  const [draft, setDraft] = useState<Draft | null>(null)
  const [busy, setBusy] = useState(false)

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

  function startEdit(record: BloodGlucoseRecord) {
    setEditingId(record.id)
    setDraft({
      value: String(record.glucose_mg_dl),
      timing: record.measurement_timing,
      measuredAt: toDateTimeInput(record.measured_at),
    })
    setError('')
  }

  function cancelEdit() {
    setEditingId(null)
    setDraft(null)
  }

  async function saveEdit(event: FormEvent) {
    event.preventDefault()
    if (editingId === null || !draft) return

    setBusy(true)
    setError('')
    try {
      await api.post('/api/blood-glucose/update.php', {
        id: editingId,
        glucose_mg_dl: Number(draft.value),
        measurement_timing: draft.timing,
        measured_at: draft.measuredAt,
      })
      cancelEdit()
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not update the reading')
    } finally {
      setBusy(false)
    }
  }

  async function remove(record: BloodGlucoseRecord) {
    if (!window.confirm(`Delete the ${record.glucose_mg_dl} mg/dL reading?`)) return

    setError('')
    try {
      await api.post('/api/blood-glucose/delete.php', { id: record.id })
      if (editingId === record.id) cancelEdit()
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not delete the reading')
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-xl font-semibold">Blood glucose</h1>
        <div className="flex flex-wrap items-center gap-2 text-sm">
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
          <Link to="/report" className="rounded border border-slate-300 px-3 py-1 hover:bg-slate-100">
            Printable report
          </Link>
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

      {data && user && (
        <GlucoseChart
          records={data.records}
          targetMin={user.target_glucose_min}
          targetMax={user.target_glucose_max}
        />
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
              const editing = editingId === record.id && draft !== null

              if (editing && draft) {
                return (
                  <li key={record.id} className="px-4 py-3">
                    <form onSubmit={saveEdit} className="flex flex-wrap items-end gap-3 text-sm">
                      <label>
                        <span className="font-medium">mg/dL</span>
                        <input
                          type="number"
                          min={1}
                          required
                          value={draft.value}
                          onChange={(event) => setDraft({ ...draft, value: event.target.value })}
                          className="mt-1 block w-24 rounded border border-slate-300 px-3 py-2"
                        />
                      </label>
                      <label>
                        <span className="font-medium">When</span>
                        <select
                          value={draft.timing}
                          onChange={(event) =>
                            setDraft({ ...draft, timing: event.target.value as MeasurementTiming })
                          }
                          className="mt-1 block rounded border border-slate-300 bg-white px-3 py-2"
                        >
                          {TIMINGS.map(([option, label]) => (
                            <option key={option} value={option}>
                              {label}
                            </option>
                          ))}
                        </select>
                      </label>
                      <label>
                        <span className="font-medium">Measured at</span>
                        <input
                          type="datetime-local"
                          required
                          value={draft.measuredAt}
                          onChange={(event) => setDraft({ ...draft, measuredAt: event.target.value })}
                          className="mt-1 block rounded border border-slate-300 px-3 py-2"
                        />
                      </label>
                      <button
                        type="submit"
                        disabled={busy}
                        className="rounded bg-slate-900 px-4 py-2 font-medium text-white disabled:opacity-60"
                      >
                        {busy ? 'Saving…' : 'Save'}
                      </button>
                      <button
                        type="button"
                        onClick={cancelEdit}
                        className="rounded border border-slate-300 px-4 py-2 hover:bg-slate-100"
                      >
                        Cancel
                      </button>
                    </form>
                  </li>
                )
              }

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
                  <div className="flex items-center gap-2">
                    <span className={`rounded-full border px-3 py-1 text-xs font-medium ${status.badge}`}>
                      {status.label}
                    </span>
                    <button
                      type="button"
                      onClick={() => startEdit(record)}
                      className="rounded border border-slate-300 px-3 py-1 text-xs hover:bg-slate-100"
                    >
                      Edit
                    </button>
                    <button
                      type="button"
                      onClick={() => void remove(record)}
                      className="rounded border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50"
                    >
                      Delete
                    </button>
                  </div>
                </li>
              )
            })}
          </ul>
        )}
      </section>
    </div>
  )
}
