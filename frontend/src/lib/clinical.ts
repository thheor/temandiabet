import type { MeasurementTiming, StatusCategory } from '../types'

export const DISCLAIMER =
  'Disclaimer: Teman Diabet is a personal self-monitoring tool for informational purposes only. It does not provide medical diagnoses, prescribe treatment, or replace professional medical advice. In a medical emergency (severe low or high blood sugar with symptoms), seek immediate medical care.'

export const HYPO_CRITICAL = 54
export const KETONE_WARNING = 250

export const TIMING_LABELS = {
  fasting: 'Fasting',
  random: 'Random',
  post_meal: 'Post-meal',
}

export const TIMING_OPTIONS: { value: MeasurementTiming; label: string }[] = [
  { value: 'fasting', label: 'Fasting' },
  { value: 'random', label: 'Random' },
  { value: 'post_meal', label: 'Post-meal' },
]

export const STATUS_META: {
  [key in StatusCategory]: { label: string; badge: string }
} = {
  hypoglycemia: {
    label: 'Hypoglycemia',
    badge: 'border-red-300 bg-red-100 text-red-800',
  },
  normal: {
    label: 'Normal',
    badge: 'border-emerald-300 bg-emerald-100 text-emerald-800',
  },
  elevated: {
    label: 'Elevated',
    badge: 'border-amber-300 bg-amber-100 text-amber-900',
  },
  high: {
    label: 'High',
    badge: 'border-orange-300 bg-orange-100 text-orange-900',
  },
}
