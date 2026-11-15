import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'

// '/temandiabet' in development, '' when served from the domain root.
const basename = import.meta.env.BASE_URL.replace(/\/$/, '')

function Scaffold() {
  return (
    <main className="grid min-h-screen place-items-center px-6">
      <div className="text-center">
        <h1 className="text-2xl font-semibold">Teman Diabet</h1>
        <p className="mt-2 text-sm text-slate-500">
          Scaffold ready. Dashboard screens land in the next tasks.
        </p>
      </div>
    </main>
  )
}

export default function App() {
  return (
    <BrowserRouter basename={basename}>
      <Routes>
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard" element={<Scaffold />} />
      </Routes>
    </BrowserRouter>
  )
}
