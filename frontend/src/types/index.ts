export type DiabetesType = 'type_1' | 'type_2' | 'prediabetes' | 'caregiver'

export const DIABETES_TYPE_OPTIONS: { value: DiabetesType; label: string }[] = [
  { value: 'type_1', label: 'Type 1 diabetes' },
  { value: 'type_2', label: 'Type 2 diabetes' },
  { value: 'prediabetes', label: 'Prediabetes' },
  { value: 'caregiver', label: 'Family member / caregiver' },
]

export type MeasurementTiming = 'fasting' | 'random' | 'post_meal'
export type StatusCategory = 'hypoglycemia' | 'normal' | 'elevated' | 'high'
export type MealType = 'breakfast' | 'lunch' | 'dinner' | 'snack'
export type InviteStatus = 'pending' | 'accepted'

export interface User {
  id: number
  full_name: string
  email: string
  diabetes_type: DiabetesType
  target_glucose_min: number
  target_glucose_max: number
  avatar_path: string | null
  created_at: string
  updated_at: string
}

export interface BloodGlucoseRecord {
  id: number
  user_id: number
  glucose_mg_dl: number
  measurement_timing: MeasurementTiming
  status_category: StatusCategory
  notes: string | null
  measured_at: string
  created_at: string
}

export interface FoodLog {
  id: number
  user_id: number
  food_name: string
  meal_type: MealType
  estimated_calories: number
  carbohydrates_grams: number
  photo_path: string | null
  eaten_date: string
  created_at: string
}

export interface MedicationSchedule {
  id: number
  user_id: number
  medication_name: string
  dosage: string
  scheduled_time: string
  is_taken: 0 | 1
  scheduled_date: string
  created_at: string
}

export interface InnerCircle {
  id: number
  patient_id: number
  caregiver_id: number | null
  relationship: string
  invite_code: string
  status: InviteStatus
  created_at: string
  accepted_at: string | null
}

// ---- API payloads ----

export interface GlucoseSummary {
  count: number
  avg: number | null
  min: number | null
  max: number | null
}

export interface GlucoseResponse {
  range: '7d' | '30d' | 'custom'
  records: BloodGlucoseRecord[]
  summary: GlucoseSummary
}

export interface FoodResponse {
  eaten_date: string
  meals: FoodLog[]
  totals: { estimated_calories: number; carbohydrates_grams: number }
}

export interface MedicationProgress {
  taken: number
  total: number
}

export interface MedicationResponse {
  scheduled_date: string
  items: MedicationSchedule[]
  progress: MedicationProgress
}

export const RELATIONSHIP_OPTIONS = [
  { value: 'parent', label: 'Parent' },
  { value: 'child', label: 'Child' },
  { value: 'spouse', label: 'Spouse' },
  { value: 'sibling', label: 'Sibling' },
  { value: 'friend', label: 'Friend' },
  { value: 'caregiver', label: 'Caregiver' },
] as const

export interface CircleMember {
  id: number
  user_id: number
  full_name: string
  email: string
  avatar_path: string | null
  relationship: string
  accepted_at: string | null
}

export interface PendingCode {
  id: number
  invite_code: string
  created_at: string
}

export interface InnerCircleResponse {
  caregivers_of_me: CircleMember[]
  patients_i_monitor: CircleMember[]
  pending_codes: PendingCode[]
}

export interface MonitoredPatient {
  id: number
  full_name: string
  diabetes_type: DiabetesType
  target_glucose_min: number
  target_glucose_max: number
  avatar_path: string | null
  relationship: string
}

export interface MonitorResponse {
  patient: MonitoredPatient
  latest: Omit<BloodGlucoseRecord, 'user_id' | 'created_at'> | null
  glucose: {
    records: Omit<BloodGlucoseRecord, 'user_id' | 'created_at'>[]
    summary: GlucoseSummary
  }
  medication: {
    scheduled_date: string
    items: Pick<
      MedicationSchedule,
      'id' | 'medication_name' | 'dosage' | 'scheduled_time' | 'is_taken'
    >[]
    progress: MedicationProgress
  }
}
