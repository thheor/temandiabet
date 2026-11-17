import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import AlertBanner from '../components/AlertBanner'
import { useAuth } from '../context/AuthContext'
import { api } from '../lib/api'
import { STATUS_META, TIMING_LABELS } from '../lib/clinical'
import { formatMeasuredAt, todayISO } from '../lib/dates'
import type { FoodResponse, GlucoseResponse, MedicationResponse } from '../types'

interface Overview {
  glucose: GlucoseResponse | null
  food: FoodResponse | null
  medication: MedicationResponse | null
}

const QUICK_LINKS = [
  { to: '/glucose', label: 'Log glucose' },
  { to: '/food', label: 'Log a meal' },
  { to: '/medication', label: 'Medication checklist' },
  { to: '/inner-circle', label: 'Inner Circle' },
]

export default function DashboardPage() {
  const { user } = useAuth()
  const [overview, setOverview] = useState<Overview | null>(null)

  useEffect(() => {
    const today = todayISO()

    async function loadOverview() {
      const [glucose, food, medication] = await Promise.all([
        api.get<GlucoseResponse>('/api/blood-glucose/index.php?range=7d').catch(() => null),
        api.get<FoodResponse>(`/api/food/index.php?eaten_date=${today}`).catch(() => null),
        api
          .get<MedicationResponse>(`/api/medication/index.php?scheduled_date=${today}`)
          .catch(() => null),
      ])

      setOverview({ glucose, food, medication })
    }

    loadOverview()
  }, [])

  const latest = overview?.glucose?.records[0] ?? null
  const progress = overview?.medication?.progress

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold">Hello, {user?.full_name}</h1>
        <p className="text-sm text-slate-500">
          Target range {user?.target_glucose_min}–{user?.target_glucose_max} mg/dL
        </p>
      </div>

      <AlertBanner reading={latest} />

      {!overview && <p className="text-sm text-slate-500">Loading your overview…</p>}

      {overview && (
        <section className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
          <div className="rounded border border-slate-200 bg-white p-4">
            <p className="text-xs text-slate-500">Latest glucose</p>
            {latest ? (
              <>
                <p className="text-2xl font-semibold">{latest.glucose_mg_dl} mg/dL</p>
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
              <p className="text-sm text-slate-500">No readings in the last 7 days.</p>
            )}
          </div>

          <div className="rounded border border-slate-200 bg-white p-4">
            <p className="text-xs text-slate-500">7-day average</p>
            <p className="text-2xl font-semibold">{overview.glucose?.summary.avg ?? '–'}</p>
            <p className="text-xs text-slate-500">mg/dL</p>
          </div>

          <div className="rounded border border-slate-200 bg-white p-4">
            <p className="text-xs text-slate-500">Calories today</p>
            <p className="text-2xl font-semibold">{overview.food?.totals.estimated_calories ?? '–'}</p>
            <p className="text-xs text-slate-500">
              kcal · {overview.food?.totals.carbohydrates_grams ?? '–'} g carbs
            </p>
          </div>

          <div className="rounded border border-slate-200 bg-white p-4">
            <p className="text-xs text-slate-500">Medication today</p>
            <p className="text-2xl font-semibold">
              {progress ? `${progress.taken}/${progress.total}` : '–'}
            </p>
            <div className="mt-2 h-2 overflow-hidden rounded bg-slate-200">
              <div
                className="h-full bg-emerald-600"
                style={{
                  width: `${progress && progress.total > 0 ? Math.round((progress.taken / progress.total) * 100) : 0}%`,
                }}
              />
            </div>
          </div>
        </section>
      )}

      <section aria-label="Quick links" className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
        {QUICK_LINKS.map((link) => (
          <Link
            key={link.to}
            to={link.to}
            className="rounded border border-slate-200 bg-white px-4 py-3 text-center font-medium hover:bg-slate-100"
          >
            {link.label}
          </Link>
        ))}
      </section>
    </div>
  )
}
