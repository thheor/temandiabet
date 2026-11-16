import { useState } from 'react'
import type { FormEvent } from 'react'
import { api, ApiError } from '../lib/api'
import { TIMING_LABELS } from '../lib/clinical'
import type { MeasurementTiming } from '../types'

const TIMINGS = Object.entries(TIMING_LABELS) as [MeasurementTiming, string][]

export default function GlucoseForm({ onSaved }: { onSaved: () => void }) {
  const [value, setValue] = useState('')
  const [timing, setTiming] = useState<'' | MeasurementTiming>('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError('')

    try {
      await api.post('/api/blood-glucose/create.php', {
        glucose_mg_dl: Number(value),
        measurement_timing: timing,
      })
      setValue('')
      setTiming('')
      onSaved()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not save the reading')
    } finally {
      setBusy(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4 rounded border border-slate-200 bg-white p-4">
      <div className="flex flex-wrap items-end gap-3">
        <label className="text-sm">
          <span className="font-medium">Glucose (mg/dL)</span>
          <input
            type="number"
            value={value}
            onChange={(event) => setValue(event.target.value)}
            min={1}
            required
            inputMode="numeric"
            className="mt-1 block w-32 rounded border border-slate-300 px-3 py-2"
          />
        </label>

        <label className="text-sm">
          <span className="font-medium">When</span>
          <select
            value={timing}
            onChange={(event) => setTiming(event.target.value as '' | MeasurementTiming)}
            required
            className="mt-1 block rounded border border-slate-300 bg-white px-3 py-2"
          >
            <option value="" disabled>
              Select timing
            </option>
            {TIMINGS.map(([option, label]) => (
              <option key={option} value={option}>
                {label}
              </option>
            ))}
          </select>
        </label>

        <button
          type="submit"
          disabled={busy}
          className="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
        >
          {busy ? 'Saving…' : 'Save reading'}
        </button>
      </div>

      <p className="text-xs text-slate-500">
        The status is set from the reading and its timing. It is monitoring information, not a
        diagnosis.
      </p>

      {error && (
        <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {error}
        </p>
      )}
    </form>
  )
}
