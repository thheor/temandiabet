import { useAuth } from '../context/AuthContext'

export default function DashboardPage() {
  const { user } = useAuth()

  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">Hello, {user?.full_name}</h1>
      <p className="text-sm text-slate-500">
        Target band {user?.target_glucose_min}–{user?.target_glucose_max} mg/dL
      </p>
      <p className="rounded border border-slate-200 bg-white p-4 text-sm">
        Glucose, meal and medication cards arrive in the next tasks.
      </p>
    </div>
  )
}
