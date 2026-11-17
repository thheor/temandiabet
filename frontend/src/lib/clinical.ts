import type { MeasurementTiming, StatusCategory } from '../types'

// One disclaimer string for the layout footer, every alert banner and the
// printed report. Never copy it into a component.
export const DISCLAIMER =
  'Disclaimer: Teman Diabet is a personal self-monitoring tool for informational purposes only. It does not provide medical diagnoses, prescribe treatment, or replace professional medical advice. In a medical emergency (severe low or high blood sugar with symptoms), seek immediate medical care.'

// Display-only refinements of the stored four-value status. Classification
// itself stays exclusively in backend/helpers/glucose.php (classifyGlucose).
export const HYPO_CRITICAL = 54 // mg/dL, level 2 hypoglycemia
export const KETONE_WARNING = 250 // mg/dL, ketone / DKA risk note

export const TIMING_LABELS: Record<MeasurementTiming, string> = {
  fasting: 'Fasting',
  random: 'Random',
  post_meal: 'Post-meal',
}

// `badge` styles the status pill; `color` is the matching chart dot colour.
export const STATUS_META: Record<
  StatusCategory,
  { label: string; badge: string; color: string }
> = {
  hypoglycemia: {
    label: 'Hypoglycemia',
    badge: 'border-red-300 bg-red-100 text-red-800',
    color: '#dc2626',
  },
  normal: {
    label: 'Normal',
    badge: 'border-emerald-300 bg-emerald-100 text-emerald-800',
    color: '#059669',
  },
  elevated: {
    label: 'Elevated',
    badge: 'border-amber-300 bg-amber-100 text-amber-900',
    color: '#d97706',
  },
  high: {
    label: 'High',
    badge: 'border-orange-300 bg-orange-100 text-orange-900',
    color: '#ea580c',
  },
}
