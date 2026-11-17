import { useCallback, useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { api, ApiError, mediaUrl } from '../lib/api'
import { RELATIONSHIP_OPTIONS } from '../types'
import type { CircleMember, InnerCircleResponse } from '../types'

function Avatar({ member }: { member: CircleMember }) {
  return member.avatar_path ? (
    <img src={mediaUrl(member.avatar_path)} alt="" className="h-10 w-10 shrink-0 rounded-full object-cover" />
  ) : (
    <div
      aria-hidden="true"
      className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-200 text-sm font-medium"
    >
      {member.full_name.charAt(0).toUpperCase()}
    </div>
  )
}

export default function InnerCirclePage() {
  const [data, setData] = useState<InnerCircleResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const [code, setCode] = useState('')
  const [relationship, setRelationship] = useState('')
  const [busy, setBusy] = useState(false)

  const load = useCallback(async () => {
    try {
      setData(await api.get<InnerCircleResponse>('/api/inner-circle/index.php'))
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not load your Inner Circle')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    void load()
  }, [load])

  async function generate() {
    setBusy(true)
    setError('')
    setNotice('')
    try {
      const body = await api.post<{ invite: { invite_code: string } }>('/api/inner-circle/generate-code.php')
      setNotice(`New code ${body.invite.invite_code} created. Share it only with the person you trust.`)
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not create a code')
    } finally {
      setBusy(false)
    }
  }

  async function handleJoin(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError('')
    setNotice('')
    try {
      const body = await api.post<{ link: { patient_name: string } }>('/api/inner-circle/join.php', {
        invite_code: code,
        relationship,
      })
      setCode('')
      setRelationship('')
      setNotice(`You can now view ${body.link.patient_name}'s data in read-only mode.`)
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not join with that code')
    } finally {
      setBusy(false)
    }
  }

  async function remove(id: number, message: string) {
    if (!window.confirm(message)) return

    setError('')
    setNotice('')
    try {
      await api.post('/api/inner-circle/remove.php', { id })
      await load()
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not remove the link')
    }
  }

  const relationshipLabel = (value: string) =>
    RELATIONSHIP_OPTIONS.find((option) => option.value === value)?.label ?? value

  return (
    <div className="space-y-8">
      <h1 className="text-xl font-semibold">Inner Circle</h1>

      {error && (
        <p className="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>
      )}
      {notice && (
        <p className="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
          {notice}
        </p>
      )}
      {loading && <p className="text-sm text-slate-500">Loading…</p>}

      <section className="space-y-3">
        <h2 className="font-semibold">People who can see my data</h2>
        <div className="rounded border border-slate-200 bg-white p-4 text-sm">
          <p className="font-medium">Before you create a code</p>
          <p className="mt-1 text-slate-600">
            Whoever enters your code is able to view, read-only: your name and profile summary, your
            glucose readings from the last 7 days with their status, your target range, and today&apos;s
            medication checklist. They cannot change anything. By creating a code you consent to this
            sharing, and you can remove anyone at any time.
          </p>
          <button
            type="button"
            onClick={() => void generate()}
            disabled={busy}
            className="mt-3 rounded bg-slate-900 px-4 py-2 font-medium text-white disabled:opacity-60"
          >
            Create invitation code
          </button>
        </div>

        {data && data.pending_codes.length > 0 && (
          <div>
            <h3 className="mb-1 text-sm font-medium text-slate-600">Unused codes</h3>
            <ul className="divide-y divide-slate-200 rounded border border-slate-200 bg-white">
              {data.pending_codes.map((pending) => (
                <li key={pending.id} className="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                  <span className="font-mono text-base tracking-wider">{pending.invite_code}</span>
                  <button
                    type="button"
                    onClick={() => void remove(pending.id, `Delete the unused code ${pending.invite_code}?`)}
                    className="rounded border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50"
                  >
                    Delete
                  </button>
                </li>
              ))}
            </ul>
          </div>
        )}

        {data && data.caregivers_of_me.length === 0 && data.pending_codes.length === 0 && (
          <p className="text-sm text-slate-500">Nobody can see your data yet.</p>
        )}

        {data && data.caregivers_of_me.length > 0 && (
          <ul className="divide-y divide-slate-200 rounded border border-slate-200 bg-white">
            {data.caregivers_of_me.map((member) => (
              <li key={member.id} className="flex items-center gap-3 px-4 py-3 text-sm">
                <Avatar member={member} />
                <div className="min-w-0 flex-1">
                  <p className="truncate font-medium">{member.full_name}</p>
                  <p className="truncate text-xs text-slate-500">
                    {relationshipLabel(member.relationship)} · {member.email}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() =>
                    void remove(member.id, `Stop sharing your data with ${member.full_name}?`)
                  }
                  className="rounded border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50"
                >
                  Remove access
                </button>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="space-y-3">
        <h2 className="font-semibold">People I monitor</h2>

        <form onSubmit={handleJoin} className="flex flex-wrap items-end gap-3 rounded border border-slate-200 bg-white p-4 text-sm">
          <label>
            <span className="font-medium">Invitation code</span>
            <input
              value={code}
              onChange={(event) => setCode(event.target.value.toUpperCase())}
              required
              placeholder="GLK-AB234"
              maxLength={9}
              className="mt-1 block w-36 rounded border border-slate-300 px-3 py-2 font-mono uppercase"
            />
          </label>
          <label>
            <span className="font-medium">They are my…</span>
            <select
              value={relationship}
              onChange={(event) => setRelationship(event.target.value)}
              required
              className="mt-1 block rounded border border-slate-300 bg-white px-3 py-2"
            >
              <option value="" disabled>
                Select
              </option>
              {RELATIONSHIP_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
          <button
            type="submit"
            disabled={busy}
            className="rounded bg-slate-900 px-4 py-2 font-medium text-white disabled:opacity-60"
          >
            Join
          </button>
        </form>

        {data && data.patients_i_monitor.length === 0 && (
          <p className="text-sm text-slate-500">You are not monitoring anyone yet.</p>
        )}

        {data && data.patients_i_monitor.length > 0 && (
          <ul className="divide-y divide-slate-200 rounded border border-slate-200 bg-white">
            {data.patients_i_monitor.map((member) => (
              <li key={member.id} className="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                <Avatar member={member} />
                <div className="min-w-0 flex-1">
                  <p className="truncate font-medium">{member.full_name}</p>
                  <p className="truncate text-xs text-slate-500">{relationshipLabel(member.relationship)}</p>
                </div>
                <Link
                  to={`/monitor/${member.user_id}`}
                  className="rounded border border-slate-300 px-3 py-1 text-xs hover:bg-slate-100"
                >
                  View
                </Link>
                <button
                  type="button"
                  onClick={() => void remove(member.id, `Stop monitoring ${member.full_name}?`)}
                  className="rounded border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50"
                >
                  Leave
                </button>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  )
}
