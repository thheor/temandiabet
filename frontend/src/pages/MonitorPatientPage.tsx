import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import AlertBanner from '../components/AlertBanner'
import GlucoseChart from '../components/GlucoseChart'
import { api, ApiError } from '../lib/api'
import { STATUS_META, TIMING_LABELS } from '../lib/clinical'
import { formatMeasuredAt, todayISO } from '../lib/dates'
import type { MonitorResponse } from '../types'

// Strictly read-only: this page renders data and has no form, input or
// mutating button. The server enforces the same rule on monitor.php.
export default function MonitorPatientPage() {
  const { patientId } = useParams()
  const [data, setData] = useState<MonitorResponse | null>(null)
  const [error, setError] = useState('')
  const [forbidden, setForbidden] = useState(false)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setForbidden(false)
    setData(null)

    api
      .get<MonitorResponse>(
        `/api/inner-circle/monitor.php?patient_id=${encodeURIComponent(patientId ?? '')}&scheduled_date=${todayISO()}`,
      )
      .then((body) => {
        if (!cancelled) {
          setData(body)
          setError('')
        }
      })
      .catch((err) => {
        if (cancelled) return
        if (err instanceof ApiError && err.status === 403) {
          setForbidden(true)
        } else {
          setError(err instanceof ApiError ? err.message : 'Could not load this patient')
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [patientId])

  if (loading) {
    return <p className="text-sm text-slate-500">Loading…</p>
  }

  if (forbidden) {
    return (
      <div className="space-y-3">
        <h1 className="text-xl font-semibold">No access</h1>
        <p className="text-sm text-slate-600">
          You are not linked to this person, or they have removed your access.
        </p>
        <Link to="/inner-circle" className="text-sm underline">
          Back to Inner Circle
        </Link>
      </div>
    )
  }

  if (!data) {
    return (
      <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {error || 'Could not load this patient'}
      </p>
    )
  }

  const { patient, latest, glucose, medication } = data
  const percent =
    medication.progress.total > 0
      ? Math.round((medication.progress.taken / medication.progress.total) * 100)
      : 0

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">{patient.full_name}</h1>
          <p className="text-sm text-slate-500">
            Target range {patient.target_glucose_min}–{patient.target_glucose_max} mg/dL
          </p>
        </div>
        <span className="rounded-full border border-slate-400 bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
          Read-only
        </span>
      </div>

      <AlertBanner reading={latest} patientName={patient.full_name} />

      <section className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
        <div className="rounded border border-slate-200 bg-white p-3">
          <p className="text-xs text-slate-500">Latest reading</p>
          {latest ? (
            <>
              <p className="text-lg font-semibold">{latest.glucose_mg_dl} mg/dL</p>
              <span
                className={`mt-1 inline-block rounded-full border px-2 py-0.5 text-xs font-medium ${STATUS_META[latest.status_category].badge}`}
              >
                {STATUS_META[latest.status_category].label}
              </span>
              <p className="mt-1 text-xs text-slate-500">
                {TIMING_LABELS[latest.measurement_timing]} · {formatMeasuredAt(latest.measured_at)}
              </p>
            </>
          ) : (
            <p className="text-lg font-semibold">–</p>
          )}
        </div>
        <div className="rounded border border-slate-200 bg-white p-3">
          <p className="text-xs text-slate-500">7-day average</p>
          <p className="text-lg font-semibold">{glucose.summary.avg ?? '–'} mg/dL</p>
        </div>
        <div className="rounded border border-slate-200 bg-white p-3">
          <p className="text-xs text-slate-500">Lowest / highest</p>
          <p className="text-lg font-semibold">
            {glucose.summary.min ?? '–'} / {glucose.summary.max ?? '–'}
          </p>
        </div>
        <div className="rounded border border-slate-200 bg-white p-3">
          <p className="text-xs text-slate-500">Medication today</p>
          <p className="text-lg font-semibold">
            {medication.progress.taken}/{medication.progress.total}
          </p>
        </div>
      </section>

      <section className="space-y-2">
        <h2 className="font-semibold">Last 7 days</h2>
        <GlucoseChart
          records={glucose.records}
          targetMin={patient.target_glucose_min}
          targetMax={patient.target_glucose_max}
        />
      </section>

      <section className="space-y-2">
        <h2 className="font-semibold">Today&apos;s medication checklist</h2>
        <div
          className="h-3 overflow-hidden rounded bg-slate-200"
          role="progressbar"
          aria-valuemin={0}
          aria-valuemax={100}
          aria-valuenow={percent}
        >
          <div className="h-full bg-emerald-600" style={{ width: `${percent}%` }} />
        </div>
        {medication.items.length === 0 ? (
          <p className="text-sm text-slate-500">Nothing scheduled for today.</p>
        ) : (
          <ul className="divide-y divide-slate-200 rounded border border-slate-200 bg-white">
            {medication.items.map((item) => (
              <li key={item.id} className="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                <div>
                  <p className="font-medium">{item.medication_name}</p>
                  <p className="text-xs text-slate-500">
                    {item.scheduled_time} · {item.dosage}
                  </p>
                </div>
                <span className="text-xs font-medium">{item.is_taken ? 'Taken' : 'Not yet'}</span>
              </li>
            ))}
          </ul>
        )}
      </section>

      <Link to="/inner-circle" className="inline-block text-sm underline">
        Back to Inner Circle
      </Link>
    </div>
  )
}
