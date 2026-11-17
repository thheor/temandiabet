import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useAuth } from '../context/AuthContext'
import { api, ApiError, mediaUrl } from '../lib/api'
import { DIABETES_TYPE_OPTIONS } from '../types'
import type { DiabetesType, User } from '../types'

export default function ProfilePage() {
  const { user, updateUser } = useAuth()

  const [fullName, setFullName] = useState(user?.full_name ?? '')
  const [type, setType] = useState<DiabetesType>(user?.diabetes_type ?? 'type_2')
  const [min, setMin] = useState(String(user?.target_glucose_min ?? 80))
  const [max, setMax] = useState(String(user?.target_glucose_max ?? 130))
  const [file, setFile] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const [error, setError] = useState('')
  const [saved, setSaved] = useState(false)
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (!file) {
      setPreview(null)
      return
    }

    const url = URL.createObjectURL(file)
    setPreview(url)

    return () => URL.revokeObjectURL(url)
  }, [file])

  const avatarSrc = preview ?? (user?.avatar_path ? mediaUrl(user.avatar_path) : null)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError('')
    setSaved(false)

    const form = new FormData()
    form.append('full_name', fullName)
    form.append('diabetes_type', type)
    form.append('target_glucose_min', min)
    form.append('target_glucose_max', max)
    if (file) form.append('avatar', file)

    try {
      const body = await api.post<{ user: User }>('/api/profile/update.php', form)
      updateUser(body.user)
      setFile(null)
      setSaved(true)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not save the profile')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="max-w-lg space-y-6">
      <h1 className="text-xl font-semibold">Profile</h1>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="flex items-center gap-4">
          {avatarSrc ? (
            <img src={avatarSrc} alt="" className="h-16 w-16 rounded-full object-cover" />
          ) : (
            <div className="grid h-16 w-16 place-items-center rounded-full bg-slate-200 text-xs text-slate-500">
              No photo
            </div>
          )}
          <label className="text-sm">
            <span className="font-medium">Avatar</span>
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={(event) => setFile(event.target.files?.[0] ?? null)}
              className="mt-1 block text-xs"
            />
          </label>
        </div>

        <label className="block text-sm">
          <span className="font-medium">Full name</span>
          <input
            value={fullName}
            onChange={(event) => setFullName(event.target.value)}
            required
            minLength={2}
            maxLength={100}
            className="mt-1 w-full rounded border border-slate-300 px-3 py-2"
          />
        </label>

        <label className="block text-sm">
          <span className="font-medium">Diabetes type</span>
          <select
            value={type}
            onChange={(event) => setType(event.target.value as DiabetesType)}
            className="mt-1 w-full rounded border border-slate-300 bg-white px-3 py-2"
          >
            {DIABETES_TYPE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </label>

        <fieldset className="text-sm">
          <legend className="font-medium">Target glucose band (mg/dL)</legend>
          <p className="mt-1 text-xs text-slate-500">
            Use the range your doctor prescribed.
          </p>
          <div className="mt-2 flex items-center gap-2">
            <input
              type="number"
              value={min}
              onChange={(event) => setMin(event.target.value)}
              min={20}
              max={600}
              required
              aria-label="Target minimum"
              className="w-24 rounded border border-slate-300 px-3 py-2"
            />
            <span>to</span>
            <input
              type="number"
              value={max}
              onChange={(event) => setMax(event.target.value)}
              min={20}
              max={600}
              required
              aria-label="Target maximum"
              className="w-24 rounded border border-slate-300 px-3 py-2"
            />
          </div>
        </fieldset>

        {error && (
          <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {error}
          </p>
        )}
        {saved && (
          <p className="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            Profile saved.
          </p>
        )}

        <button
          type="submit"
          disabled={busy}
          className="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
        >
          {busy ? 'Saving…' : 'Save profile'}
        </button>
      </form>
    </div>
  )
}
