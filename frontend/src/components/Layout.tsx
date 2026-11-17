import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { DISCLAIMER } from '../lib/clinical'

const NAV_ITEMS = [
  { to: '/dashboard', label: 'Dashboard' },
  { to: '/glucose', label: 'Blood glucose' },
  { to: '/food', label: 'Food log' },
  { to: '/medication', label: 'Medication' },
  { to: '/inner-circle', label: 'Inner Circle' },
  { to: '/report', label: 'Report' },
  { to: '/profile', label: 'Profile' },
]

const linkClass = ({ isActive }: { isActive: boolean }) =>
  `block rounded px-3 py-2 text-sm ${
    isActive ? 'bg-slate-900 font-medium text-white' : 'text-slate-600 hover:bg-slate-100'
  }`

export default function Layout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)

  async function handleLogout() {
    await logout()
    navigate('/login', { replace: true })
  }

  const nav = (
    <nav aria-label="Main" className="space-y-1">
      {NAV_ITEMS.map((item) => (
        <NavLink key={item.to} to={item.to} className={linkClass} onClick={() => setMenuOpen(false)}>
          {item.label}
        </NavLink>
      ))}
    </nav>
  )

  return (
    <div className="min-h-screen md:flex">
      <aside className="hidden w-60 shrink-0 flex-col border-r border-slate-200 bg-white p-4 md:flex print:hidden">
        <p className="mb-6 text-lg font-semibold">Teman Diabet</p>
        {nav}
        <div className="mt-auto space-y-2 border-t border-slate-200 pt-4 text-sm">
          <p className="truncate text-slate-500">{user?.full_name}</p>
          <button
            type="button"
            onClick={handleLogout}
            className="w-full rounded border border-slate-300 px-3 py-2 hover:bg-slate-100"
          >
            Log out
          </button>
        </div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="border-b border-slate-200 bg-white md:hidden print:hidden">
          <div className="flex items-center justify-between px-4 py-3">
            <span className="font-semibold">Teman Diabet</span>
            <button
              type="button"
              aria-expanded={menuOpen}
              aria-controls="mobile-menu"
              onClick={() => setMenuOpen((open) => !open)}
              className="rounded border border-slate-300 px-3 py-1 text-sm"
            >
              {menuOpen ? 'Close' : 'Menu'}
            </button>
          </div>
          {menuOpen && (
            <div id="mobile-menu" className="space-y-3 border-t border-slate-200 p-3">
              {nav}
              <button
                type="button"
                onClick={handleLogout}
                className="w-full rounded border border-slate-300 px-3 py-2 text-sm hover:bg-slate-100"
              >
                Log out
              </button>
            </div>
          )}
        </header>

        <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6">
          <Outlet />
        </main>

        <footer className="border-t border-slate-200 bg-white px-4 py-4 text-xs text-slate-500 print:hidden">
          <p className="mx-auto max-w-5xl">{DISCLAIMER}</p>
        </footer>
      </div>
    </div>
  )
}
