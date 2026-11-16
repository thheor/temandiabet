import type { MeasurementTiming, StatusCategory } from '../types'

// Labels and colours only — classification thresholds stay in
// backend/helpers/glucose.php so the two can never drift apart.
export const TIMING_LABELS: Record<MeasurementTiming, string> = {
  fasting: 'Fasting',
  random: 'Random',
  post_meal: 'Post-meal',
}

export const STATUS_META: Record<StatusCategory, { label: string; badge: string }> = {
  hypoglycemia: { label: 'Hypoglycemia', badge: 'border-red-300 bg-red-100 text-red-800' },
  normal: { label: 'Normal', badge: 'border-emerald-300 bg-emerald-100 text-emerald-800' },
  elevated: { label: 'Elevated', badge: 'border-amber-300 bg-amber-100 text-amber-900' },
  high: { label: 'High', badge: 'border-orange-300 bg-orange-100 text-orange-900' },
}
