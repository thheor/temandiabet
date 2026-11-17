import { useEffect, useRef, useState } from 'react'
import type { ChangeEvent, FormEvent } from 'react'
import { FOOD_PRESETS } from '../data/foodPresets'
import { api, ApiError, mediaUrl } from '../lib/api'
import { todayISO } from '../lib/dates'
import type { FoodResponse, MealType } from '../types'

const MEAL_OPTIONS: { value: MealType; label: string }[] = [
  { value: 'breakfast', label: 'Breakfast' },
  { value: 'lunch', label: 'Lunch' },
  { value: 'dinner', label: 'Dinner' },
  { value: 'snack', label: 'Snack' },
]

const MAX_PHOTO_BYTES = 2 * 1024 * 1024
const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp']

export default function FoodLogPage() {
  const [date, setDate] = useState(todayISO())
  const [data, setData] = useState<FoodResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [presetId, setPresetId] = useState('')
  const [name, setName] = useState('')
  const [mealType, setMealType] = useState<MealType>('breakfast')
  const [calories, setCalories] = useState('')
  const [carbs, setCarbs] = useState('')
  const [photo, setPhoto] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const fileInput = useRef<HTMLInputElement>(null)

  async function load() {
    setLoading(true)
    try {
      setData(await api.get<FoodResponse>(`/api/food/index.php?eaten_date=${date}`))
      setError('')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not load the meals')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [date])

  useEffect(() => {
    return () => {
      if (preview) URL.revokeObjectURL(preview)
    }
  }, [preview])

  function handlePreset(event: ChangeEvent<HTMLSelectElement>) {
    const id = event.target.value
    setPresetId(id)

    const preset = FOOD_PRESETS.find((item) => item.id === id)
    if (preset) {
      setName(preset.name)
      setCalories(String(preset.calories))
      setCarbs(String(preset.carbs))
    }
  }

  function handlePhoto(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0] ?? null

    if (file && !PHOTO_TYPES.includes(file.type)) {
      setError('Only JPG, PNG or WebP images are allowed')
      clearPhoto()
      return
    }
    if (file && file.size > MAX_PHOTO_BYTES) {
      setError('Image must be 2 MB or smaller')
      clearPhoto()
      return
    }

    setError('')
    setPhoto(file)
    setPreview(file ? URL.createObjectURL(file) : null)
  }

  function clearPhoto() {
    setPhoto(null)
    setPreview(null)
    if (fileInput.current) fileInput.current.value = ''
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError('')

    const form = new FormData()
    form.append('food_name', name)
    form.append('meal_type', mealType)
    form.append('estimated_calories', calories || '0')
    form.append('carbohydrates_grams', carbs || '0')
    form.append('eaten_date', date)
    if (photo) form.append('photo', photo)

    try {
      await api.post('/api/food/create.php', form)
      setPresetId('')
      setName('')
      setCalories('')
      setCarbs('')
      clearPhoto()
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not save the meal')
    } finally {
      setBusy(false)
    }
  }

  async function remove(id: number, mealName: string) {
    if (!window.confirm(`Delete "${mealName}"?`)) return

    setError('')
    try {
      await api.post('/api/food/delete.php', { id })
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not delete the meal')
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <h1 className="text-xl font-semibold">Food log</h1>
        <label className="text-sm">
          <span className="font-medium">Date</span>
          <input
            type="date"
            value={date}
            max={todayISO()}
            onChange={(event) => event.target.value && setDate(event.target.value)}
            className="mt-1 block rounded border border-slate-300 px-3 py-2"
          />
        </label>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4 rounded border border-slate-200 bg-white p-4">
        <label className="block text-sm">
          <span className="font-medium">Pick a common food (optional)</span>
          <select
            value={presetId}
            onChange={handlePreset}
            className="mt-1 block w-full rounded border border-slate-300 bg-white px-3 py-2"
          >
            <option value="">Enter manually</option>
            {FOOD_PRESETS.map((preset) => (
              <option key={preset.id} value={preset.id}>
                {preset.name} · {preset.portion}
              </option>
            ))}
          </select>
        </label>

        <div className="grid gap-3 sm:grid-cols-2">
          <label className="text-sm sm:col-span-2">
            <span className="font-medium">Food</span>
            <input
              value={name}
              onChange={(event) => setName(event.target.value)}
              required
              maxLength={150}
              className="mt-1 block w-full rounded border border-slate-300 px-3 py-2"
            />
          </label>
          <label className="text-sm">
            <span className="font-medium">Meal</span>
            <select
              value={mealType}
              onChange={(event) => setMealType(event.target.value as MealType)}
              className="mt-1 block w-full rounded border border-slate-300 bg-white px-3 py-2"
            >
              {MEAL_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
          <div className="grid grid-cols-2 gap-3">
            <label className="text-sm">
              <span className="font-medium">Calories (kcal)</span>
              <input
                type="number"
                min={0}
                max={10000}
                value={calories}
                onChange={(event) => setCalories(event.target.value)}
                className="mt-1 block w-full rounded border border-slate-300 px-3 py-2"
              />
            </label>
            <label className="text-sm">
              <span className="font-medium">Carbs (g)</span>
              <input
                type="number"
                min={0}
                max={1000}
                step="0.1"
                value={carbs}
                onChange={(event) => setCarbs(event.target.value)}
                className="mt-1 block w-full rounded border border-slate-300 px-3 py-2"
              />
            </label>
          </div>
        </div>

        <div className="space-y-2 text-sm">
          <label className="block">
            <span className="font-medium">Photo (optional, JPG/PNG/WebP, up to 2 MB)</span>
            <input
              ref={fileInput}
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={handlePhoto}
              className="mt-1 block w-full text-sm"
            />
          </label>
          {preview && (
            <div className="flex items-center gap-3">
              <img src={preview} alt="Meal preview" className="h-20 w-20 rounded object-cover" />
              <button
                type="button"
                onClick={clearPhoto}
                className="rounded border border-slate-300 px-3 py-1 hover:bg-slate-100"
              >
                Remove photo
              </button>
            </div>
          )}
        </div>

        <p className="text-xs text-slate-500">
          Preset values are rough estimates for one typical portion. Adjust them to what you ate.
        </p>

        <button
          type="submit"
          disabled={busy}
          className="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
        >
          {busy ? 'Saving…' : 'Add meal'}
        </button>
      </form>

      {error && (
        <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
          {error}
        </p>
      )}

      {data && (
        <section className="grid grid-cols-2 gap-3 text-sm">
          <div className="rounded border border-slate-200 bg-white p-3">
            <p className="text-xs text-slate-500">Calories for {date}</p>
            <p className="text-lg font-semibold">{data.totals.estimated_calories} kcal</p>
          </div>
          <div className="rounded border border-slate-200 bg-white p-3">
            <p className="text-xs text-slate-500">Carbohydrates</p>
            <p className="text-lg font-semibold">{data.totals.carbohydrates_grams} g</p>
          </div>
        </section>
      )}

      <section>
        {loading && <p className="text-sm text-slate-500">Loading meals…</p>}

        {!loading && data?.meals.length === 0 && (
          <p className="rounded border border-slate-200 bg-white p-4 text-sm text-slate-500">
            No meals logged for this day.
          </p>
        )}

        {!loading && data && data.meals.length > 0 && (
          <ul className="divide-y divide-slate-200 rounded border border-slate-200 bg-white">
            {data.meals.map((meal) => (
              <li key={meal.id} className="flex items-center gap-3 px-4 py-3 text-sm">
                {meal.photo_path ? (
                  <img
                    src={mediaUrl(meal.photo_path)}
                    alt={meal.food_name}
                    className="h-14 w-14 shrink-0 rounded object-cover"
                  />
                ) : (
                  <div className="h-14 w-14 shrink-0 rounded bg-slate-100" aria-hidden="true" />
                )}
                <div className="min-w-0 flex-1">
                  <p className="truncate font-medium">{meal.food_name}</p>
                  <p className="text-xs capitalize text-slate-500">
                    {meal.meal_type} · {meal.estimated_calories} kcal · {meal.carbohydrates_grams} g carbs
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => void remove(meal.id, meal.food_name)}
                  className="rounded border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50"
                >
                  Delete
                </button>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  )
}
