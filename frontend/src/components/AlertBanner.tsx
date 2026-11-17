import { DISCLAIMER, HYPO_CRITICAL, KETONE_WARNING, STATUS_META } from '../lib/clinical'
import { formatMeasuredAt } from '../lib/dates'
import type { StatusCategory } from '../types'

interface AlertReading {
  glucose_mg_dl: number
  status_category: StatusCategory
  measured_at: string
}

interface Props {
  reading: AlertReading | null
  // Set on the caregiver view so the wording is about the patient, not "you".
  patientName?: string
}

// Shown only for hypoglycemia or high. The 54 and 250 mg/dL refinements are
// derived here from the stored value; the enum stays four values.
export default function AlertBanner({ reading, patientName }: Props) {
  if (!reading || (reading.status_category !== 'hypoglycemia' && reading.status_category !== 'high')) {
    return null
  }

  const value = reading.glucose_mg_dl
  const critical = reading.status_category === 'hypoglycemia' && value < HYPO_CRITICAL
  const ketones = reading.status_category === 'high' && value >= KETONE_WARNING
  const subject = patientName ? `${patientName}'s` : 'Your'
  const urgent = critical || ketones

  let title = `${subject} latest reading is ${STATUS_META[reading.status_category].label.toLowerCase()}`
  let advice: string

  if (critical) {
    title = `Urgent: ${subject} latest reading is very low`
    advice =
      'This is a Level 2 low. Treat it now with fast-acting carbohydrate and get help from someone nearby. If there are symptoms such as confusion, shaking or fainting, call emergency services.'
  } else if (reading.status_category === 'hypoglycemia') {
    advice =
      'Consume fast-acting carbohydrate (for example glucose tablets or sweet juice) and recheck in 15 minutes.'
  } else if (ketones) {
    advice =
      'Drink water, check for ketones if you are able to, and contact a doctor. Seek emergency care if you feel unwell, are vomiting or have trouble breathing.'
  } else {
    advice = 'Re-check your next reading and follow the plan agreed with your doctor.'
  }

  return (
    <div
      role="alert"
      className={`rounded border p-4 text-sm ${
        urgent ? 'border-red-400 bg-red-50 text-red-900' : 'border-amber-300 bg-amber-50 text-amber-900'
      }`}
    >
      <p className="font-semibold">
        {title}: {value} mg/dL
      </p>
      <p className="mt-1">{advice}</p>
      <p className="mt-1 text-xs opacity-80">Measured {formatMeasuredAt(reading.measured_at)}</p>
      <p className="mt-2 border-t border-current/20 pt-2 text-xs opacity-80">{DISCLAIMER}</p>
    </div>
  )
}
