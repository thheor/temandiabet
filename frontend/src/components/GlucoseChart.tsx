import {
  CartesianGrid,
  Line,
  LineChart,
  ReferenceArea,
  ReferenceLine,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { STATUS_META, TIMING_LABELS } from '../lib/clinical'
import { formatShortDate, parseMeasuredAt } from '../lib/dates'
import type { MeasurementTiming, StatusCategory } from '../types'

export interface ChartReading {
  id: number
  glucose_mg_dl: number
  measurement_timing: MeasurementTiming
  status_category: StatusCategory
  measured_at: string
}

interface Props {
  records: ChartReading[]
  targetMin: number
  targetMax: number
  // Small variant for the dashboard: no axes labels, shorter, no tooltip text.
  compact?: boolean
}

interface Point extends ChartReading {
  label: string
}

interface DotProps {
  cx?: number
  cy?: number
  payload?: Point
}

// The band and reference lines are the patient's own physician-set target
// (target_glucose_min / max), never the classification thresholds.
export default function GlucoseChart({ records, targetMin, targetMax, compact = false }: Props) {
  // The API returns newest first; a chart reads oldest to newest.
  const data: Point[] = [...records]
    .sort((a, b) => parseMeasuredAt(a.measured_at).getTime() - parseMeasuredAt(b.measured_at).getTime())
    .map((record) => ({ ...record, label: formatShortDate(record.measured_at) }))

  if (data.length === 0) {
    return (
      <p className="rounded border border-slate-200 bg-white p-4 text-sm text-slate-500">
        No readings to chart yet.
      </p>
    )
  }

  const values = data.map((point) => point.glucose_mg_dl)
  const low = Math.max(0, Math.floor((Math.min(...values, targetMin) - 10) / 10) * 10)
  const high = Math.ceil((Math.max(...values, targetMax) + 10) / 10) * 10

  return (
    <div
      className="rounded border border-slate-200 bg-white p-3"
      role="img"
      aria-label={`Glucose trend with ${data.length} readings and target band ${targetMin} to ${targetMax} mg/dL`}
    >
      <div className={compact ? 'h-40' : 'h-72'}>
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={data} margin={{ top: 8, right: 12, bottom: 0, left: compact ? -24 : -8 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
            <ReferenceArea y1={targetMin} y2={targetMax} fill="#10b981" fillOpacity={0.1} />
            <ReferenceLine
              y={targetMin}
              stroke="#059669"
              strokeDasharray="4 4"
              label={compact ? undefined : { value: `Target min ${targetMin}`, position: 'insideBottomLeft', fontSize: 11 }}
            />
            <ReferenceLine
              y={targetMax}
              stroke="#059669"
              strokeDasharray="4 4"
              label={compact ? undefined : { value: `Target max ${targetMax}`, position: 'insideTopLeft', fontSize: 11 }}
            />
            <XAxis dataKey="label" tick={{ fontSize: 11 }} minTickGap={24} hide={compact} />
            <YAxis domain={[low, high]} tick={{ fontSize: 11 }} width={compact ? 40 : 48} />
            <Tooltip
              formatter={(value) => [`${value} mg/dL`, 'Glucose']}
              labelFormatter={(_label, payload) => {
                const point = payload?.[0]?.payload as Point | undefined
                return point
                  ? `${point.label} · ${TIMING_LABELS[point.measurement_timing]} · ${STATUS_META[point.status_category].label}`
                  : ''
              }}
            />
            <Line
              type="monotone"
              dataKey="glucose_mg_dl"
              stroke="#334155"
              strokeWidth={2}
              isAnimationActive={false}
              dot={(props: DotProps) => {
                const { cx, cy, payload } = props
                if (cx === undefined || cy === undefined || !payload) {
                  return <g />
                }
                return (
                  <circle
                    key={payload.id}
                    cx={cx}
                    cy={cy}
                    r={4}
                    fill={STATUS_META[payload.status_category].color}
                    stroke="#fff"
                    strokeWidth={1}
                  />
                )
              }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>
      <p className="mt-2 text-xs text-slate-500">
        Shaded band: target range {targetMin}–{targetMax} mg/dL set in the profile. Dot colour shows
        the status of each reading.
      </p>
    </div>
  )
}
