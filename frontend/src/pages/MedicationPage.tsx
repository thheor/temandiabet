import { useCallback, useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { api, ApiError } from '../lib/api'
import { todayISO } from '../lib/dates'
import type { MedicationResponse } from '../types'

export default function MedicationPage() {
  const [date, setDate] = useState(todayISO())
  const [data, setData] = useState<MedicationResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [name, setName] = useState('')
  const [dosage, setDosage] = useState('')
  const [time, setTime] = useState('08:00')
  const [busy, setBusy] = useState(false)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      setData(await api.get<MedicationResponse>(`/api/medication/index.php?scheduled_date=${date}`))
      setError('')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not load the checklist')
    } finally {
      setLoading(false)
    }
  }, [date])

  useEffect(() => {
    void load()
  }, [load])

  async function handleAdd(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError('')

    try {
      await api.post('/api/medication/create.php', {
        medication_name: name,
        dosage,
        scheduled_time: time,
        scheduled_date: date,
      })
      setName('')
      setDosage('')
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not save the entry')
    } finally {
      setBusy(false)
    }
  }

  // Optimistic: the progress bar moves immediately, then the server confirms.
  async function toggle(id: number, taken: boolean) {
    if (!data) return

    const previous = data
    const items = data.items.map((item) =>
      item.id === id ? { ...item, is_taken: (taken ? 1 : 0) as 0 | 1 } : item,
    )
    setData({
      ...data,
      items,
      progress: { taken: items.filter((item) => item.is_taken === 1).length, total: items.length },
    })

    try {
      await api.post('/api/medication/toggle.php', { id, is_taken: taken })
    } catch (err) {
      setData(previous)
      setError(err instanceof ApiError ? err.message : 'Could not update the entry')
    }
  }

  async function remove(id: number, label: string) {
    if (!window.confirm(`Remove "${label}" from the checklist?`)) return

    setError('')
    try {
      await api.post('/api/medication/delete.php', { id })
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not remove the entry')
    }
  }

  const progress = data?.progress
  const percent = progress && progress.total > 0 ? Math.round((progress.taken / progress.total) * 100) : 0

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <h1 className="text-xl font-semibold">Medication checklist</h1>
        <label className="text-sm">
          <span className="font-medium">Date</span>
          <input
            type="date"
            value={date}
            onChange={(event) => event.target.value && setDate(event.target.value)}
            className="mt-1 block rounded border border-slate-300 px-3 py-2"
          />
        </label>
      </div>

      <p className="rounded border border-slate-200 bg-white p-3 text-sm text-slate-600">
        This is a reminder list for the regimen your doctor prescribed. Teman Diabet never suggests or
        changes a dose. Copy the dosage exactly from your prescription.
      </p>

      <form onSubmit={handleAdd} className="space-y-3 rounded border border-slate-200 bg-white p-4">
        <div className="grid gap-3 sm:grid-cols-3">
          <label className="text-sm">
            <span className="font-medium">Medication</span>
            <input
              value={name}
              onChange={(event) => setName(event.target.value)}
              required
              maxLength={120}
              className="mt-1 block w-full rounded border border-slate-300 px-3 py-2"
            />
          </label>
          <label className="text-sm">
            <span className="font-medium">Dosage as prescribed</span>
            <input
              value={dosage}
              onChange={(event) => setDosage(event.target.value)}
              required
              maxLength={60}
              placeholder="e.g. 500 mg, 1 tablet"
              className="mt-1 block w-full rounded border border-slate-300 px-3 py-2"
            />
          </label>
          <label className="text-sm">
            <span className="font-medium">Time</span>
            <input
              type="time"
              value={time}
              onChange={(event) => setTime(event.target.value)}
              required
              className="mt-1 block w-full rounded border border-slate-300 px-3 py-2"
            />
          </label>
        </div>
        <button
          type="submit"
          disabled={busy}
          className="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
        >
          {busy ? 'Saving…' : 'Add to checklist'}
        </button>
      </form>

      {error && (
        <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {error}
        </p>
      )}

      {progress && (
        <section className="space-y-2" aria-label="Progress">
          <div className="flex items-baseline justify-between text-sm">
            <span className="font-medium">
              {progress.taken}/{progress.total} taken
            </span>
            <span className="text-slate-500">{percent}%</span>
          </div>
          <div
            className="h-3 overflow-hidden rounded bg-slate-200"
            role="progressbar"
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={percent}
          >
            <div className="h-full bg-emerald-600 transition-all" style={{ width: `${percent}%` }} />
          </div>
        </section>
      )}

      <section>
        {loading && <p className="text-sm text-slate-500">Loading checklist…</p>}

        {!loading && data?.items.length === 0 && (
          <p className="rounded border border-slate-200 bg-white p-4 text-sm text-slate-500">
            Nothing scheduled for this day.
          </p>
        )}

        {data && data.items.length > 0 && (
          <ul className="divide-y divide-slate-200 rounded border border-slate-200 bg-white">
            {data.items.map((item) => (
              <li key={item.id} className="flex items-center gap-3 px-4 py-3 text-sm">
                <input
                  id={`med-${item.id}`}
                  type="checkbox"
                  checked={item.is_taken === 1}
                  onChange={(event) => void toggle(item.id, event.target.checked)}
                  className="h-5 w-5 shrink-0"
                />
                <label htmlFor={`med-${item.id}`} className="min-w-0 flex-1">
                  <span className={`block font-medium ${item.is_taken ? 'text-slate-400 line-through' : ''}`}>
                    {item.medication_name}
                  </span>
                  <span className="text-xs text-slate-500">
                    {item.scheduled_time} · {item.dosage}
                  </span>
                </label>
                <button
                  type="button"
                  onClick={() => void remove(item.id, item.medication_name)}
                  className="rounded border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50"
                >
                  Remove
                </button>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  )
}
