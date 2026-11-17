import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import Layout from './components/Layout'
import ProtectedRoute from './components/ProtectedRoute'
import { AuthProvider } from './context/AuthContext'
import BloodGlucosePage from './pages/BloodGlucosePage'
import DashboardPage from './pages/DashboardPage'
import FoodLogPage from './pages/FoodLogPage'
import GlucoseReportPage from './pages/GlucoseReportPage'
import InnerCirclePage from './pages/InnerCirclePage'
import LoginPage from './pages/LoginPage'
import MedicationPage from './pages/MedicationPage'
import MonitorPatientPage from './pages/MonitorPatientPage'
import ProfilePage from './pages/ProfilePage'
import RegisterPage from './pages/RegisterPage'

const basename = import.meta.env.BASE_URL.replace(/\/$/, '')

export default function App() {
  return (
    <BrowserRouter basename={basename}>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route element={<ProtectedRoute />}>
            <Route element={<Layout />}>
              <Route path="/" element={<Navigate to="/dashboard" replace />} />
              <Route path="/dashboard" element={<DashboardPage />} />
              <Route path="/glucose" element={<BloodGlucosePage />} />
              <Route path="/report" element={<GlucoseReportPage />} />
              <Route path="/food" element={<FoodLogPage />} />
              <Route path="/medication" element={<MedicationPage />} />
              <Route path="/inner-circle" element={<InnerCirclePage />} />
              <Route path="/monitor/:patientId" element={<MonitorPatientPage />} />
              <Route path="/profile" element={<ProfilePage />} />
            </Route>
          </Route>
          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}
